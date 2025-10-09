<?php
/**
 * AJAX Handler: Vota Sondaggio
 *
 * Gestisce il voto nei sondaggi
 *
 * @package CdV
 * @subpackage Ajax
 * @since 1.4.0
 */

namespace CdV\Ajax;

use CdV\Services\Security;

/**
 * Class VotaSondaggio
 */
class VotaSondaggio {
	/**
	 * Handle AJAX request
	 */
	public static function handle(): void {
		// Verify nonce
		if ( ! check_ajax_referer( 'cdv_ajax_nonce', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Sessione scaduta. Ricarica la pagina.', 'cronaca-di-viterbo' ) ), 403 );
		}

		// Get data
		$sondaggio_id = isset( $_POST['sondaggio_id'] ) ? intval( $_POST['sondaggio_id'] ) : 0;
		$options = isset( $_POST['options'] ) && is_array( $_POST['options'] ) ? array_map( 'intval', $_POST['options'] ) : array();

		// Validate
		if ( ! $sondaggio_id || get_post_type( $sondaggio_id ) !== 'cdv_sondaggio' ) {
			wp_send_json_error( array( 'message' => __( 'Sondaggio non valido.', 'cronaca-di-viterbo' ) ), 400 );
		}

		if ( empty( $options ) ) {
			wp_send_json_error( array( 'message' => __( 'Seleziona almeno un\'opzione.', 'cronaca-di-viterbo' ) ), 400 );
		}

		// Check if open
		$aperto = get_post_meta( $sondaggio_id, '_cdv_aperto', true );
		if ( $aperto === '0' ) {
			wp_send_json_error( array( 'message' => __( 'Questo sondaggio è chiuso.', 'cronaca-di-viterbo' ) ), 400 );
		}

		// Check deadline
		$scadenza = get_post_meta( $sondaggio_id, '_cdv_scadenza', true );
		if ( $scadenza && strtotime( $scadenza ) < time() ) {
			wp_send_json_error( array( 'message' => __( 'Questo sondaggio è scaduto.', 'cronaca-di-viterbo' ) ), 400 );
		}

		// Check multiplo
		$multiplo = get_post_meta( $sondaggio_id, '_cdv_multiplo', true );
		if ( ! $multiplo && count( $options ) > 1 ) {
			wp_send_json_error( array( 'message' => __( 'Puoi selezionare solo un\'opzione.', 'cronaca-di-viterbo' ) ), 400 );
		}

		// Get user identifier
		$user_id = get_current_user_id();
		$ip = Security::get_client_ip();
		$user_identifier = $user_id > 0 ? 'user_' . $user_id : 'ip_' . $ip;

		// Check if already voted
		global $wpdb;
		$table = $wpdb->prefix . 'cdv_sondaggi_voti';
		$existing = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM $table WHERE sondaggio_id = %d AND user_identifier = %s",
			$sondaggio_id,
			$user_identifier
		) );

		if ( $existing > 0 ) {
			wp_send_json_error( array( 'message' => __( 'Hai già votato in questo sondaggio.', 'cronaca-di-viterbo' ) ), 400 );
		}

		// Validate options
		$available_options = get_post_meta( $sondaggio_id, '_cdv_options', true ) ?: array();
		foreach ( $options as $option_index ) {
			if ( ! isset( $available_options[ $option_index ] ) ) {
				wp_send_json_error( array( 'message' => __( 'Opzione non valida.', 'cronaca-di-viterbo' ) ), 400 );
			}
		}

		// Insert votes
		foreach ( $options as $option_index ) {
			$wpdb->insert(
				$table,
				array(
					'sondaggio_id'    => $sondaggio_id,
					'option_index'    => $option_index,
					'user_id'         => $user_id,
					'user_identifier' => $user_identifier,
					'ip_address'      => $ip,
				),
				array( '%d', '%d', '%d', '%s', '%s' )
			);
		}

		// Get results if enabled
		$mostra_risultati = get_post_meta( $sondaggio_id, '_cdv_mostra_risultati', true );
		$results = array();

		if ( $mostra_risultati !== '0' ) {
			$raw_results = $wpdb->get_results( $wpdb->prepare(
				"SELECT option_index, COUNT(*) as votes FROM $table WHERE sondaggio_id = %d GROUP BY option_index",
				$sondaggio_id
			) );

			$total = $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(DISTINCT user_identifier) FROM $table WHERE sondaggio_id = %d",
				$sondaggio_id
			) );

			foreach ( $raw_results as $result ) {
				if ( isset( $available_options[ $result->option_index ] ) ) {
					$results[] = array(
						'option'      => $available_options[ $result->option_index ],
						'votes'       => intval( $result->votes ),
						'percentage'  => $total > 0 ? round( ( $result->votes / $total ) * 100, 1 ) : 0,
					);
				}
			}
		}

		// Hook
		do_action( 'cdv_sondaggio_votato', $sondaggio_id, $options, $user_id );

		// Response
		$response = array(
			'success' => true,
			'message' => __( 'Grazie per aver votato!', 'cronaca-di-viterbo' ),
			'ga4_event' => array(
				'event'        => 'sondaggio_votato',
				'sondaggio_id' => $sondaggio_id,
			),
		);

		if ( ! empty( $results ) ) {
			$response['results'] = $results;
		}

		wp_send_json_success( $response );
	}
}
