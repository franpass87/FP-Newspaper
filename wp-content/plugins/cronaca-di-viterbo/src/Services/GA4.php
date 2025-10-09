<?php
/**
 * Service per tracking GA4.
 *
 * @package CdV\Services
 */

namespace CdV\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Classe per il tracking Google Analytics 4.
 */
class GA4 {

	/**
	 * Costruttore.
	 */
	public function __construct() {
		// Hook per eventi personalizzati
		add_action( 'cdv_proposta_submitted', [ $this, 'track_proposta_submitted' ], 10, 3 );
		add_action( 'cdv_proposta_voted', [ $this, 'track_proposta_voted' ], 10, 2 );
		add_action( 'wp_footer', [ $this, 'track_dossier_read' ] );
	}

	/**
	 * Track proposta submitted.
	 *
	 * @param int $post_id Post ID.
	 * @param int $quartiere_id Quartiere ID.
	 * @param int $tematica_id Tematica ID.
	 */
	public function track_proposta_submitted( $post_id, $quartiere_id, $tematica_id ) {
		$quartiere = get_term( $quartiere_id, 'cdv_quartiere' );
		$tematica = get_term( $tematica_id, 'cdv_tematica' );

		?>
		<script>
		if (typeof dataLayer !== 'undefined') {
			dataLayer.push({
				'event': 'proposta_submitted',
				'proposta_id': <?php echo absint( $post_id ); ?>,
				'quartiere': '<?php echo $quartiere ? esc_js( $quartiere->name ) : ''; ?>',
				'tematica': '<?php echo $tematica ? esc_js( $tematica->name ) : ''; ?>'
			});
		}
		</script>
		<?php
	}

	/**
	 * Track proposta voted.
	 *
	 * @param int $post_id Post ID.
	 * @param int $votes Numero voti.
	 */
	public function track_proposta_voted( $post_id, $votes ) {
		// Questo viene triggerato via AJAX, gestiamo lato JS
	}

	/**
	 * Track dossier lettura (60s).
	 */
	public function track_dossier_read() {
		if ( ! is_singular( 'cdv_dossier' ) ) {
			return;
		}

		?>
		<script>
		(function() {
			var timer = setTimeout(function() {
				if (typeof dataLayer !== 'undefined') {
					dataLayer.push({
						'event': 'dossier_read_60s',
						'dossier_id': <?php echo get_the_ID(); ?>,
						'dossier_title': '<?php echo esc_js( get_the_title() ); ?>'
					});
				}
			}, 60000); // 60 secondi

			// Cancella timer se l'utente lascia la pagina
			window.addEventListener('beforeunload', function() {
				clearTimeout(timer);
			});
		})();
		</script>
		<?php
	}
}
