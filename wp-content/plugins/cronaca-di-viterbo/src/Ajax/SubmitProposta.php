<?php
/**
 * AJAX Handler per invio proposta.
 *
 * @package CdV\Ajax
 */

namespace CdV\Ajax;

use CdV\Services\Security;
use CdV\Services\Sanitization;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Classe per gestire l'invio AJAX delle proposte.
 */
class SubmitProposta {

	/**
	 * Costruttore.
	 */
	public function __construct() {
		add_action( 'wp_ajax_cdv_submit_proposta', [ $this, 'handle' ] );
		add_action( 'wp_ajax_nopriv_cdv_submit_proposta', [ $this, 'handle' ] );
	}

	/**
	 * Gestisce la richiesta AJAX.
	 */
	public function handle() {
		// Verifica nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'cdv_nonce' ) ) {
			wp_send_json_error(
				[
					'message' => __( 'Errore di sicurezza. Ricarica la pagina e riprova.', 'cronaca-di-viterbo' ),
				],
				403
			);
		}

		// Rate limiting per IP
		$ip = Security::get_client_ip();
		$rate_limit_key = 'cdv_submit_proposta_' . md5( $ip );

		if ( get_transient( $rate_limit_key ) ) {
			wp_send_json_error(
				[
					'message' => __( 'Hai già inviato una proposta di recente. Attendi 60 secondi.', 'cronaca-di-viterbo' ),
				],
				429
			);
		}

		// Validazione campi
		$title = isset( $_POST['title'] ) ? Sanitization::sanitize_title( $_POST['title'] ) : '';
		$content = isset( $_POST['content'] ) ? Sanitization::sanitize_content( $_POST['content'] ) : '';
		$quartiere_id = isset( $_POST['quartiere'] ) ? absint( $_POST['quartiere'] ) : 0;
		$tematica_id = isset( $_POST['tematica'] ) ? absint( $_POST['tematica'] ) : 0;
		$privacy = isset( $_POST['privacy'] ) && 'on' === $_POST['privacy'];

		// Validazione titolo (max 140 caratteri)
		if ( empty( $title ) || mb_strlen( $title ) > 140 ) {
			wp_send_json_error(
				[
					'message' => __( 'Il titolo è obbligatorio e deve essere massimo 140 caratteri.', 'cronaca-di-viterbo' ),
				],
				400
			);
		}

		// Validazione contenuto
		if ( empty( $content ) ) {
			wp_send_json_error(
				[
					'message' => __( 'La descrizione è obbligatoria.', 'cronaca-di-viterbo' ),
				],
				400
			);
		}

		// Validazione quartiere
		if ( ! $quartiere_id || ! term_exists( $quartiere_id, 'cdv_quartiere' ) ) {
			wp_send_json_error(
				[
					'message' => __( 'Seleziona un quartiere valido.', 'cronaca-di-viterbo' ),
				],
				400
			);
		}

		// Validazione tematica
		if ( ! $tematica_id || ! term_exists( $tematica_id, 'cdv_tematica' ) ) {
			wp_send_json_error(
				[
					'message' => __( 'Seleziona una tematica valida.', 'cronaca-di-viterbo' ),
				],
				400
			);
		}

		// Validazione privacy
		if ( ! $privacy ) {
			wp_send_json_error(
				[
					'message' => __( 'Devi accettare l\'informativa sulla privacy.', 'cronaca-di-viterbo' ),
				],
				400
			);
		}

		// Crea la proposta
		$post_id = wp_insert_post(
			[
				'post_title'   => $title,
				'post_content' => $content,
				'post_type'    => 'cdv_proposta',
				'post_status'  => 'pending', // In moderazione
				'post_author'  => get_current_user_id(),
			]
		);

		if ( is_wp_error( $post_id ) ) {
			wp_send_json_error(
				[
					'message' => __( 'Errore durante la creazione della proposta.', 'cronaca-di-viterbo' ),
				],
				500
			);
		}

		// Assegna tassonomie
		wp_set_object_terms( $post_id, $quartiere_id, 'cdv_quartiere' );
		wp_set_object_terms( $post_id, $tematica_id, 'cdv_tematica' );

		// Inizializza voti a 0
		update_post_meta( $post_id, '_cdv_votes', 0 );

		// Salva IP per tracking (opzionale)
		update_post_meta( $post_id, '_cdv_submit_ip', $ip );

		// Imposta rate limit
		set_transient( $rate_limit_key, true, 60 ); // 60 secondi

		// Tracking GA4
		do_action( 'cdv_proposta_submitted', $post_id, $quartiere_id, $tematica_id );

		wp_send_json_success(
			[
				'message' => __( 'Proposta inviata con successo! Sarà pubblicata dopo la moderazione.', 'cronaca-di-viterbo' ),
				'id'      => $post_id,
				'status'  => 'pending',
			]
		);
	}
}
