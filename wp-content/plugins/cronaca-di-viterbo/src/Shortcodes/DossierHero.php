<?php
/**
 * Shortcode Hero Dossier.
 *
 * @package CdV\Shortcodes
 */

namespace CdV\Shortcodes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Classe per lo shortcode [cdv_dossier_hero].
 */
class DossierHero {

	/**
	 * Costruttore.
	 */
	public function __construct() {
		add_shortcode( 'cdv_dossier_hero', [ $this, 'render' ] );
	}

	/**
	 * Render dello shortcode.
	 *
	 * @param array $atts Attributi shortcode.
	 * @return string HTML hero section.
	 */
	public function render( $atts ) {
		global $post;

		if ( ! is_singular( 'cdv_dossier' ) && empty( $atts['id'] ) ) {
			return '';
		}

		$atts = shortcode_atts(
			[
				'id'     => get_the_ID(),
				'cta'    => __( 'Approfondisci', 'cronaca-di-viterbo' ),
			],
			$atts,
			'cdv_dossier_hero'
		);

		$post_id = (int) $atts['id'];
		$dossier = get_post( $post_id );

		if ( ! $dossier || 'cdv_dossier' !== $dossier->post_type ) {
			return '';
		}

		$thumbnail = get_the_post_thumbnail_url( $post_id, 'large' );
		$quartiere_terms = get_the_terms( $post_id, 'cdv_quartiere' );
		$tematica_terms = get_the_terms( $post_id, 'cdv_tematica' );

		ob_start();
		?>
		<div class="cdv-dossier-hero" style="<?php echo $thumbnail ? 'background-image: url(' . esc_url( $thumbnail ) . ');' : ''; ?>">
			<div class="cdv-dossier-hero-overlay">
				<div class="cdv-dossier-hero-content">
					<h1><?php echo esc_html( $dossier->post_title ); ?></h1>
					
					<div class="cdv-dossier-meta">
						<?php if ( $quartiere_terms ) : ?>
							<span class="cdv-quartiere">📍 <?php echo esc_html( $quartiere_terms[0]->name ); ?></span>
						<?php endif; ?>
						<?php if ( $tematica_terms ) : ?>
							<span class="cdv-tematica">🏷️ <?php echo esc_html( $tematica_terms[0]->name ); ?></span>
						<?php endif; ?>
					</div>

					<?php if ( $dossier->post_excerpt ) : ?>
						<p class="cdv-dossier-excerpt"><?php echo esc_html( $dossier->post_excerpt ); ?></p>
					<?php endif; ?>

					<?php if ( ! is_singular( 'cdv_dossier' ) ) : ?>
						<a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>" class="cdv-btn cdv-btn-primary">
							<?php echo esc_html( $atts['cta'] ); ?>
						</a>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}
