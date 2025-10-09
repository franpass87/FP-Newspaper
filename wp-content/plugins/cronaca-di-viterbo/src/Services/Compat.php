<?php
/**
 * Service per compatibilità retroattiva.
 *
 * @package CdV\Services
 */

namespace CdV\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Classe per garantire compatibilità con versioni precedenti.
 */
class Compat {

	/**
	 * Costruttore.
	 */
	public function __construct() {
		// Shim per shortcodes vecchi
		add_shortcode( 'cv_proposta_form', [ $this, 'legacy_proposta_form' ] );
		add_shortcode( 'cv_dossier_map', [ $this, 'legacy_dossier_map' ] );

		// Hook per notice di deprecazione
		add_action( 'admin_notices', [ $this, 'deprecation_notices' ] );
	}

	/**
	 * Shim per vecchio shortcode proposta form.
	 *
	 * @param array $atts Attributi.
	 * @return string Output.
	 */
	public function legacy_proposta_form( $atts ) {
		_doing_it_wrong(
			'[cv_proposta_form]',
			__( 'Questo shortcode è deprecato. Usa [cdv_proposta_form] invece.', 'cronaca-di-viterbo' ),
			'1.0.0'
		);

		return do_shortcode( '[cdv_proposta_form]' );
	}

	/**
	 * Shim per vecchio shortcode mappa dossier.
	 *
	 * @param array $atts Attributi.
	 * @return string Output.
	 */
	public function legacy_dossier_map( $atts ) {
		_doing_it_wrong(
			'[cv_dossier_map]',
			__( 'Questo shortcode è deprecato e non più supportato. La funzionalità mappe è stata spostata in un modulo opzionale.', 'cronaca-di-viterbo' ),
			'1.0.0'
		);

		return '<div class="notice notice-warning"><p>' .
			__( 'La funzionalità mappe è stata deprecata. Contatta l\'amministratore per maggiori informazioni.', 'cronaca-di-viterbo' ) .
			'</p></div>';
	}

	/**
	 * Notice admin per funzionalità deprecate.
	 */
	public function deprecation_notices() {
		$screen = get_current_screen();
		if ( ! $screen || 'plugins' !== $screen->id ) {
			return;
		}

		// Controlla se ci sono shortcodes vecchi nelle pagine
		global $wpdb;
		$old_shortcodes = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->posts} 
			WHERE post_content LIKE '%[cv_%' 
			AND post_status = 'publish'"
		);

		if ( $old_shortcodes > 0 ) {
			?>
			<div class="notice notice-warning">
				<p>
					<strong><?php esc_html_e( 'Cronaca di Viterbo:', 'cronaca-di-viterbo' ); ?></strong>
					<?php
					printf(
						/* translators: %d: numero di pagine con shortcode vecchi */
						esc_html__( 'Attenzione: %d pagine/post utilizzano shortcodes deprecati [cv_*]. Aggiornali a [cdv_*].', 'cronaca-di-viterbo' ),
						absint( $old_shortcodes )
					);
					?>
				</p>
			</div>
			<?php
		}
	}
}
