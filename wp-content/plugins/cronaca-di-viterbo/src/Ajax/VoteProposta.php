<?php
/**
 * AJAX Handler per votazione proposta.
 *
 * @package CdV\Ajax
 */

namespace CdV\Ajax;

use CdV\Services\Security;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Classe per gestire la votazione AJAX delle proposte.
 */
class VoteProposta {

	/**
	 * Costruttore.
	 */
	public function __construct() {
		add_action( 'wp_ajax_cdv_vote_proposta', [ $this, 'handle' ] );
		add_action( 'wp_ajax_nopriv_cdv_vote_proposta', [ $this, 'handle' ] );
	}

	/**
	 * Gestisce la richiesta AJAX.
	 */
	public function handle() {
		// Verifica nonce
		if ( ! check_ajax_referer( 'cdv_ajax_nonce', 'nonce', false ) ) {
			wp_send_json_error(
				[
					'message' => __( 'Errore di sicurezza.', 'cronaca-di-viterbo' ),
				],
				403
			);
		}

		// Verifica ID proposta
		$proposta_id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

		if ( ! $proposta_id ) {
			wp_send_json_error(
				[
					'message' => __( 'ID proposta non valido.', 'cronaca-di-viterbo' ),
				],
				400
			);
		}

		// Verifica che esista e sia una proposta
		$proposta = get_post( $proposta_id );
		if ( ! $proposta || 'cdv_proposta' !== $proposta->post_type ) {
			wp_send_json_error(
				[
					'message' => __( 'Proposta non trovata.', 'cronaca-di-viterbo' ),
				],
				404
			);
		}

		// Cooldown: 1 ora per utente loggato o IP
		$user_id = get_current_user_id();
		$ip = Security::get_client_ip();

		if ( $user_id ) {
			$cooldown_key = 'cdv_vote_' . $proposta_id . '_user_' . $user_id;
		} else {
			$cooldown_key = 'cdv_vote_' . $proposta_id . '_ip_' . md5( $ip );
		}

		if ( get_transient( $cooldown_key ) ) {
			wp_send_json_error(
				[
					'message' => __( 'Hai già votato questa proposta. Potrai votare di nuovo tra 1 ora.', 'cronaca-di-viterbo' ),
				],
				429
			);
		}

		// Incrementa voti in modo atomico usando una query SQL diretta
		global $wpdb;
		
		// Prima assicuriamoci che il meta esista
		$meta_exists = $wpdb->get_var( $wpdb->prepare(
			"SELECT meta_id FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = '_cdv_votes'",
			$proposta_id
		) );
		
		if ( ! $meta_exists ) {
			// Se non esiste, crealo con valore 1
			add_post_meta( $proposta_id, '_cdv_votes', 1, true );
			$new_votes = 1;
		} else {
			// UPDATE atomico: incrementa direttamente nel database
			$wpdb->query( $wpdb->prepare(
				"UPDATE {$wpdb->postmeta} SET meta_value = meta_value + 1 WHERE post_id = %d AND meta_key = '_cdv_votes'",
				$proposta_id
			) );
			
			// Recupera il nuovo valore
			$new_votes = (int) get_post_meta( $proposta_id, '_cdv_votes', true );
		}

		// Imposta cooldown di 1 ora
		set_transient( $cooldown_key, true, HOUR_IN_SECONDS );

		// Tracking GA4
		do_action( 'cdv_proposta_voted', $proposta_id, $new_votes );

		wp_send_json_success(
			[
				'message' => __( 'Grazie per il tuo voto!', 'cronaca-di-viterbo' ),
				'votes'   => $new_votes,
			]
		);
	}
}
