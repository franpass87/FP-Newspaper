<?php
/**
 * AJAX Handler: Firma Petizione
 *
 * Gestisce la firma di petizioni digitali
 *
 * @package CdV
 * @subpackage Ajax
 * @since 1.3.0
 */

namespace CdV\Ajax;

use CdV\Services\Security;
use CdV\Services\Sanitization;

/**
 * Class FirmaPetizione
 */
class FirmaPetizione {
	/**
	 * Handle AJAX request
	 */
	public static function handle(): void {
		// Verify nonce
		if ( ! check_ajax_referer( 'cdv_ajax_nonce', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Sessione scaduta. Ricarica la pagina.', 'cronaca-di-viterbo' ) ), 403 );
		}

		// Get data
		$petizione_id = isset( $_POST['petizione_id'] ) ? intval( $_POST['petizione_id'] ) : 0;
		$nome = isset( $_POST['nome'] ) ? Sanitization::sanitize_title( $_POST['nome'] ) : '';
		$cognome = isset( $_POST['cognome'] ) ? Sanitization::sanitize_title( $_POST['cognome'] ) : '';
		$email = isset( $_POST['email'] ) ? sanitize_email( $_POST['email'] ) : '';
		$comune = isset( $_POST['comune'] ) ? Sanitization::sanitize_title( $_POST['comune'] ) : '';
		$motivazione = isset( $_POST['motivazione'] ) ? Sanitization::sanitize_content( $_POST['motivazione'] ) : '';
		$privacy = isset( $_POST['privacy'] ) ? sanitize_text_field( $_POST['privacy'] ) : '';

		// Validate
		if ( ! $petizione_id || get_post_type( $petizione_id ) !== 'cdv_petizione' ) {
			wp_send_json_error( array( 'message' => __( 'Petizione non valida.', 'cronaca-di-viterbo' ) ), 400 );
		}

		if ( empty( $nome ) || empty( $cognome ) || empty( $email ) ) {
			wp_send_json_error( array( 'message' => __( 'Nome, cognome ed email sono obbligatori.', 'cronaca-di-viterbo' ) ), 400 );
		}

		if ( ! is_email( $email ) ) {
			wp_send_json_error( array( 'message' => __( 'Email non valida.', 'cronaca-di-viterbo' ) ), 400 );
		}

		if ( $privacy !== 'on' ) {
			wp_send_json_error( array( 'message' => __( 'Devi accettare la privacy policy.', 'cronaca-di-viterbo' ) ), 400 );
		}

		// Check if petition is open
		$aperta = get_post_meta( $petizione_id, '_cdv_aperta', true );
		if ( $aperta === '0' ) {
			wp_send_json_error( array( 'message' => __( 'Questa petizione è chiusa.', 'cronaca-di-viterbo' ) ), 400 );
		}

		// Check deadline
		$deadline = get_post_meta( $petizione_id, '_cdv_deadline', true );
		if ( $deadline && strtotime( $deadline ) < time() ) {
			wp_send_json_error( array( 'message' => __( 'Questa petizione è scaduta.', 'cronaca-di-viterbo' ) ), 400 );
		}

		// Check if already signed
		global $wpdb;
		$table = $wpdb->prefix . 'cdv_petizioni_firme';
		$existing = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM $table WHERE petizione_id = %d AND email = %s",
			$petizione_id,
			$email
		) );

		if ( $existing ) {
			wp_send_json_error( array( 'message' => __( 'Hai già firmato questa petizione.', 'cronaca-di-viterbo' ) ), 400 );
		}

		// Rate limiting
		$ip = Security::get_client_ip();
		if ( ! Security::check_rate_limit( 'firma_petizione_' . $ip, 60 ) ) {
			wp_send_json_error( array( 'message' => __( 'Attendi almeno 1 minuto prima di firmare un\'altra petizione.', 'cronaca-di-viterbo' ) ), 429 );
		}

		// Insert firma
		$user_id = get_current_user_id();
		$result = $wpdb->insert(
			$table,
			array(
				'petizione_id'     => $petizione_id,
				'user_id'          => $user_id,
				'nome'             => $nome,
				'cognome'          => $cognome,
				'email'            => $email,
				'comune'           => $comune,
				'motivazione'      => $motivazione,
				'privacy_accepted' => 1,
				'verified'         => $user_id > 0 ? 1 : 0,
				'ip_address'       => $ip,
				'user_agent'       => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( substr( $_SERVER['HTTP_USER_AGENT'], 0, 255 ) ) : '',
			),
			array( '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s' )
		);

		if ( ! $result ) {
			wp_send_json_error( array( 'message' => __( 'Errore nel salvare la firma. Riprova.', 'cronaca-di-viterbo' ) ), 500 );
		}

		// Update count
		$count = intval( get_post_meta( $petizione_id, '_cdv_firme_count', true ) );
		update_post_meta( $petizione_id, '_cdv_firme_count', $count + 1 );

		$new_count = $count + 1;
		$soglia = intval( get_post_meta( $petizione_id, '_cdv_soglia_firme', true ) );
		$percentuale = $soglia > 0 ? round( ( $new_count / $soglia ) * 100, 1 ) : 0;

		// Check milestones for notifications
		$milestones = array( 50, 100, 250, 500, 1000, 5000 );
		if ( in_array( $new_count, $milestones, true ) ) {
			do_action( 'cdv_petizione_milestone', $petizione_id, $new_count );
		}

		// Hook for custom actions
		do_action( 'cdv_petizione_firmata', $petizione_id, $email, $user_id );

		// GA4 tracking
		$response_data = array(
			'success'     => true,
			'message'     => __( 'Grazie per aver firmato! La tua firma è stata registrata.', 'cronaca-di-viterbo' ),
			'firme_count' => $new_count,
			'percentuale' => $percentuale,
			'ga4_event'   => array(
				'event'        => 'petizione_firmata',
				'petizione_id' => $petizione_id,
				'firme_totali' => $new_count,
			),
		);

		wp_send_json_success( $response_data );
	}
}
