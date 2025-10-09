<?php
/**
 * Service: Notifiche Email
 *
 * Gestisce l'invio di notifiche email agli utenti
 *
 * @package CdV
 * @subpackage Services
 * @since 1.2.0
 */

namespace CdV\Services;

/**
 * Class Notifiche
 */
class Notifiche {
	/**
	 * Initialize hooks
	 */
	public static function init(): void {
		// Notifica risposta amministrazione
		add_action( 'cdv_risposta_pubblicata', array( self::class, 'notifica_risposta_amministrazione' ), 10, 2 );

		// Notifica milestone petizione
		add_action( 'cdv_petizione_milestone', array( self::class, 'notifica_petizione_milestone' ), 10, 2 );

		// Notifica nuovo evento in quartiere
		add_action( 'publish_cdv_evento', array( self::class, 'notifica_nuovo_evento' ), 10, 2 );

		// Notifica proposta approvata
		add_action( 'pending_to_publish', array( self::class, 'notifica_proposta_approvata' ), 10, 1 );

		// Cron per digest settimanale
		add_action( 'cdv_weekly_digest', array( self::class, 'send_weekly_digest' ) );

		// Schedule cron se non esiste
		if ( ! wp_next_scheduled( 'cdv_weekly_digest' ) ) {
			wp_schedule_event( strtotime( 'next monday 9:00' ), 'weekly', 'cdv_weekly_digest' );
		}
	}

	/**
	 * Notifica risposta amministrazione
	 *
	 * @param int $risposta_id ID risposta.
	 * @param int $proposta_id ID proposta.
	 */
	public static function notifica_risposta_amministrazione( int $risposta_id, int $proposta_id ): void {
		if ( ! $proposta_id ) {
			return;
		}

		$proposta = get_post( $proposta_id );
		if ( ! $proposta ) {
			return;
		}

		$risposta = get_post( $risposta_id );
		$status = get_post_meta( $risposta_id, '_cdv_status', true );

		// Notifica all'autore della proposta
		$author = get_userdata( $proposta->post_author );
		if ( $author && $author->user_email ) {
			$subject = sprintf(
				__( 'L\'Amministrazione ha risposto alla tua proposta "%s"', 'cronaca-di-viterbo' ),
				$proposta->post_title
			);

			$message = self::get_template( 'risposta-amministrazione', array(
				'proposta_title' => $proposta->post_title,
				'proposta_link'  => get_permalink( $proposta_id ),
				'risposta_title' => $risposta->post_title,
				'risposta_link'  => get_permalink( $risposta_id ),
				'status'         => $status,
				'status_label'   => \CdV\PostTypes\RispostaAmministrazione::get_status_label( $status ),
			) );

			wp_mail( $author->user_email, $subject, $message, array( 'Content-Type: text/html; charset=UTF-8' ) );
		}

		// Notifica followers (se implementato)
		self::notify_followers( $proposta_id, $subject, $message );
	}

	/**
	 * Notifica milestone petizione
	 *
	 * @param int $petizione_id ID petizione.
	 * @param int $firme_count  Numero firme.
	 */
	public static function notifica_petizione_milestone( int $petizione_id, int $firme_count ): void {
		$petizione = get_post( $petizione_id );
		if ( ! $petizione ) {
			return;
		}

		$author = get_userdata( $petizione->post_author );
		if ( ! $author || ! $author->user_email ) {
			return;
		}

		$soglia = get_post_meta( $petizione_id, '_cdv_soglia_firme', true );

		$subject = sprintf(
			__( 'La tua petizione "%s" ha raggiunto %d firme!', 'cronaca-di-viterbo' ),
			$petizione->post_title,
			$firme_count
		);

		$message = self::get_template( 'petizione-milestone', array(
			'petizione_title' => $petizione->post_title,
			'petizione_link'  => get_permalink( $petizione_id ),
			'firme_count'     => number_format_i18n( $firme_count ),
			'soglia'          => number_format_i18n( $soglia ),
			'percentuale'     => $soglia > 0 ? round( ( $firme_count / $soglia ) * 100, 1 ) : 0,
		) );

		wp_mail( $author->user_email, $subject, $message, array( 'Content-Type: text/html; charset=UTF-8' ) );
	}

	/**
	 * Notifica nuovo evento
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 */
	public static function notifica_nuovo_evento( int $post_id, $post ): void {
		$quartieri = wp_get_post_terms( $post_id, 'cdv_quartiere', array( 'fields' => 'ids' ) );
		$tematiche = wp_get_post_terms( $post_id, 'cdv_tematica', array( 'fields' => 'ids' ) );

		// Get subscribers per quartiere/tematica
		$subscribers = self::get_subscribers( array(
			'quartieri' => $quartieri,
			'tematiche' => $tematiche,
		) );

		if ( empty( $subscribers ) ) {
			return;
		}

		$data_evento = get_post_meta( $post_id, '_cdv_data_inizio', true );
		$luogo = get_post_meta( $post_id, '_cdv_luogo', true );

		$subject = sprintf(
			__( 'Nuovo evento: %s', 'cronaca-di-viterbo' ),
			$post->post_title
		);

		$message = self::get_template( 'nuovo-evento', array(
			'evento_title' => $post->post_title,
			'evento_link'  => get_permalink( $post_id ),
			'data_evento'  => $data_evento ? date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $data_evento ) ) : '',
			'luogo'        => $luogo,
			'excerpt'      => get_the_excerpt( $post ),
		) );

		foreach ( $subscribers as $email ) {
			wp_mail( $email, $subject, $message, array( 'Content-Type: text/html; charset=UTF-8' ) );
		}
	}

	/**
	 * Notifica proposta approvata
	 *
	 * @param \WP_Post $post Post object.
	 */
	public static function notifica_proposta_approvata( $post ): void {
		if ( $post->post_type !== 'cdv_proposta' ) {
			return;
		}

		$author = get_userdata( $post->post_author );
		if ( ! $author || ! $author->user_email ) {
			return;
		}

		$subject = sprintf(
			__( 'La tua proposta "%s" è stata pubblicata!', 'cronaca-di-viterbo' ),
			$post->post_title
		);

		$message = self::get_template( 'proposta-approvata', array(
			'proposta_title' => $post->post_title,
			'proposta_link'  => get_permalink( $post->ID ),
		) );

		wp_mail( $author->user_email, $subject, $message, array( 'Content-Type: text/html; charset=UTF-8' ) );
	}

	/**
	 * Send weekly digest
	 */
	public static function send_weekly_digest(): void {
		$subscribers = self::get_all_subscribers();

		if ( empty( $subscribers ) ) {
			return;
		}

		// Get content ultima settimana
		$date_query = array(
			'after' => '1 week ago',
		);

		$proposte = get_posts( array(
			'post_type'   => 'cdv_proposta',
			'post_status' => 'publish',
			'date_query'  => $date_query,
			'numberposts' => 5,
		) );

		$eventi = get_posts( array(
			'post_type'   => 'cdv_evento',
			'post_status' => 'publish',
			'date_query'  => $date_query,
			'numberposts' => 5,
		) );

		$dossier = get_posts( array(
			'post_type'   => 'cdv_dossier',
			'post_status' => 'publish',
			'date_query'  => $date_query,
			'numberposts' => 3,
		) );

		if ( empty( $proposte ) && empty( $eventi ) && empty( $dossier ) ) {
			return; // Niente di nuovo
		}

		$subject = sprintf(
			__( 'Cronaca di Viterbo - Riepilogo settimanale del %s', 'cronaca-di-viterbo' ),
			date_i18n( get_option( 'date_format' ) )
		);

		foreach ( $subscribers as $subscriber ) {
			$message = self::get_template( 'weekly-digest', array(
				'proposte' => $proposte,
				'eventi'   => $eventi,
				'dossier'  => $dossier,
				'email'    => $subscriber['email'],
			) );

			wp_mail( $subscriber['email'], $subject, $message, array( 'Content-Type: text/html; charset=UTF-8' ) );
		}
	}

	/**
	 * Get email template
	 *
	 * @param string $template Template name.
	 * @param array  $vars     Variables.
	 * @return string
	 */
	private static function get_template( string $template, array $vars = array() ): string {
		ob_start();
		extract( $vars );

		$template_file = plugin_dir_path( dirname( __DIR__ ) ) . 'templates/email/' . $template . '.php';

		if ( file_exists( $template_file ) ) {
			include $template_file;
		} else {
			// Fallback generico
			echo self::get_default_template( $vars );
		}

		return ob_get_clean();
	}

	/**
	 * Get default template
	 *
	 * @param array $vars Variables.
	 * @return string
	 */
	private static function get_default_template( array $vars ): string {
		$html = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">';
		$html .= '<div style="max-width: 600px; margin: 0 auto; padding: 20px;">';
		$html .= '<h2 style="color: #1e73be;">Cronaca di Viterbo</h2>';
		$html .= '<div style="background: #f5f5f5; padding: 15px; border-left: 4px solid #1e73be;">';
		
		foreach ( $vars as $key => $value ) {
			if ( is_string( $value ) && ! empty( $value ) ) {
				$html .= '<p><strong>' . esc_html( ucfirst( str_replace( '_', ' ', $key ) ) ) . ':</strong> ' . wp_kses_post( $value ) . '</p>';
			}
		}

		$html .= '</div>';
		$html .= '<p style="margin-top: 20px; font-size: 12px; color: #999;">Questa è una email automatica di Cronaca di Viterbo.</p>';
		$html .= '</div></body></html>';

		return $html;
	}

	/**
	 * Notify followers
	 *
	 * @param int    $post_id Post ID.
	 * @param string $subject Subject.
	 * @param string $message Message.
	 */
	private static function notify_followers( int $post_id, string $subject, string $message ): void {
		// TODO: Implementare sistema followers
		// Placeholder per future implementazione
	}

	/**
	 * Get subscribers
	 *
	 * @param array $filters Filters.
	 * @return array
	 */
	private static function get_subscribers( array $filters = array() ): array {
		global $wpdb;
		$table = $wpdb->prefix . 'cdv_subscribers';

		// Create table if not exists
		$wpdb->query( "CREATE TABLE IF NOT EXISTS $table (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			email varchar(200) NOT NULL,
			quartieri text,
			tematiche text,
			active tinyint(1) DEFAULT 1,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY email (email)
		)" );

		// TODO: Implementare filtri
		return array(); // Placeholder
	}

	/**
	 * Get all subscribers
	 *
	 * @return array
	 */
	private static function get_all_subscribers(): array {
		global $wpdb;
		$table = $wpdb->prefix . 'cdv_subscribers';

		return $wpdb->get_results( "SELECT * FROM $table WHERE active = 1", ARRAY_A );
	}
}
