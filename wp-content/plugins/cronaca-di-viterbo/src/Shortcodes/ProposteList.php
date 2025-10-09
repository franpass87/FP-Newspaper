<?php
/**
 * Shortcode Lista Proposte.
 *
 * @package CdV\Shortcodes
 */

namespace CdV\Shortcodes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Classe per lo shortcode [cdv_proposte].
 */
class ProposteList {

	/**
	 * Costruttore.
	 */
	public function __construct() {
		add_shortcode( 'cdv_proposte', [ $this, 'render' ] );
	}

	/**
	 * Render dello shortcode.
	 *
	 * @param array $atts Attributi shortcode.
	 * @return string HTML della lista.
	 */
	public function render( $atts ) {
		$atts = shortcode_atts(
			[
				'limit'     => 10,
				'quartiere' => '',
				'tematica'  => '',
				'orderby'   => 'date',
				'order'     => 'DESC',
			],
			$atts,
			'cdv_proposte'
		);

		$args = [
			'post_type'      => 'cdv_proposta',
			'posts_per_page' => (int) $atts['limit'],
			'orderby'        => $atts['orderby'],
			'order'          => $atts['order'],
			'post_status'    => 'publish',
		];

		// Filtro per quartiere
		if ( ! empty( $atts['quartiere'] ) ) {
			$args['tax_query'][] = [
				'taxonomy' => 'cdv_quartiere',
				'field'    => 'slug',
				'terms'    => $atts['quartiere'],
			];
		}

		// Filtro per tematica
		if ( ! empty( $atts['tematica'] ) ) {
			$args['tax_query'][] = [
				'taxonomy' => 'cdv_tematica',
				'field'    => 'slug',
				'terms'    => $atts['tematica'],
			];
		}

		$query = new \WP_Query( $args );

		ob_start();
		?>
		<div class="cdv-proposte-list">
			<?php if ( $query->have_posts() ) : ?>
				<?php while ( $query->have_posts() ) : $query->the_post(); ?>
					<?php
					$votes = get_post_meta( get_the_ID(), '_cdv_votes', true ) ?: 0;
					$quartiere_terms = get_the_terms( get_the_ID(), 'cdv_quartiere' );
					$tematica_terms = get_the_terms( get_the_ID(), 'cdv_tematica' );
					?>
					<div class="cdv-proposta-card" data-id="<?php echo esc_attr( get_the_ID() ); ?>">
						<h4><?php the_title(); ?></h4>
						<div class="cdv-proposta-meta">
							<?php if ( $quartiere_terms ) : ?>
								<span class="cdv-quartiere">📍 <?php echo esc_html( $quartiere_terms[0]->name ); ?></span>
							<?php endif; ?>
							<?php if ( $tematica_terms ) : ?>
								<span class="cdv-tematica">🏷️ <?php echo esc_html( $tematica_terms[0]->name ); ?></span>
							<?php endif; ?>
						</div>
						<div class="cdv-proposta-excerpt">
							<?php the_excerpt(); ?>
						</div>
						<div class="cdv-proposta-actions">
							<button class="cdv-vote-btn" data-id="<?php echo esc_attr( get_the_ID() ); ?>">
								👍 <span class="cdv-vote-count"><?php echo esc_html( $votes ); ?></span>
							</button>
							<a href="<?php the_permalink(); ?>" class="cdv-read-more">
								<?php esc_html_e( 'Leggi tutto', 'cronaca-di-viterbo' ); ?>
							</a>
						</div>
					</div>
				<?php endwhile; ?>
				<?php wp_reset_postdata(); ?>
			<?php else : ?>
				<p><?php esc_html_e( 'Nessuna proposta trovata.', 'cronaca-di-viterbo' ); ?></p>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}
}
