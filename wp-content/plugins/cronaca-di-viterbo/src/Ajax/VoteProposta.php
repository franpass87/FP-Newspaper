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

		// Incrementa voti (atomico)
		$current_votes = (int) get_post_meta( $proposta_id, '_cdv_votes', true );
		$new_votes = $current_votes + 1;
		update_post_meta( $proposta_id, '_cdv_votes', $new_votes );

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
