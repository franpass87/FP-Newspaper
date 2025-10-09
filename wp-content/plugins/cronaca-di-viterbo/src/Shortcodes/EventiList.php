<?php
/**
 * Shortcode Lista Eventi.
 *
 * @package CdV\Shortcodes
 */

namespace CdV\Shortcodes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Classe per lo shortcode [cdv_eventi].
 */
class EventiList {

	/**
	 * Costruttore.
	 */
	public function __construct() {
		add_shortcode( 'cdv_eventi', [ $this, 'render' ] );
	}

	/**
	 * Render dello shortcode.
	 *
	 * @param array $atts Attributi shortcode.
	 * @return string HTML della lista eventi.
	 */
	public function render( $atts ) {
		$atts = shortcode_atts(
			[
				'limit'     => 6,
				'quartiere' => '',
				'tematica'  => '',
				'upcoming'  => 'yes', // Solo eventi futuri
			],
			$atts,
			'cdv_eventi'
		);

		$args = [
			'post_type'      => 'cdv_evento',
			'posts_per_page' => (int) $atts['limit'],
			'post_status'    => 'publish',
		];

		// Eventi futuri
		if ( 'yes' === $atts['upcoming'] ) {
			$args['meta_query'][] = [
				'key'     => '_cdv_evento_data',
				'value'   => date( 'Y-m-d' ),
				'compare' => '>=',
				'type'    => 'DATE',
			];
			$args['orderby']  = 'meta_value';
			$args['order']    = 'ASC';
			$args['meta_key'] = '_cdv_evento_data';
		} else {
			$args['orderby'] = 'date';
			$args['order']   = 'DESC';
		}

		// Filtro quartiere
		if ( ! empty( $atts['quartiere'] ) ) {
			$args['tax_query'][] = [
				'taxonomy' => 'cdv_quartiere',
				'field'    => 'slug',
				'terms'    => $atts['quartiere'],
			];
		}

		// Filtro tematica
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
		<div class="cdv-eventi-list">
			<?php if ( $query->have_posts() ) : ?>
				<?php while ( $query->have_posts() ) : $query->the_post(); ?>
					<?php
					$data = get_post_meta( get_the_ID(), '_cdv_evento_data', true );
					$ora = get_post_meta( get_the_ID(), '_cdv_evento_ora', true );
					$luogo = get_post_meta( get_the_ID(), '_cdv_evento_luogo', true );
					$quartiere_terms = get_the_terms( get_the_ID(), 'cdv_quartiere' );
					?>
					<div class="cdv-evento-card">
						<?php if ( has_post_thumbnail() ) : ?>
							<div class="cdv-evento-thumbnail">
								<?php the_post_thumbnail( 'medium' ); ?>
							</div>
						<?php endif; ?>
						<div class="cdv-evento-content">
							<h4><?php the_title(); ?></h4>
							<div class="cdv-evento-meta">
								<?php if ( $data ) : ?>
									<span class="cdv-data">📅 <?php echo esc_html( date_i18n( 'd/m/Y', strtotime( $data ) ) ); ?></span>
								<?php endif; ?>
								<?php if ( $ora ) : ?>
									<span class="cdv-ora">🕐 <?php echo esc_html( $ora ); ?></span>
								<?php endif; ?>
								<?php if ( $luogo ) : ?>
									<span class="cdv-luogo">📍 <?php echo esc_html( $luogo ); ?></span>
								<?php endif; ?>
								<?php if ( $quartiere_terms ) : ?>
									<span class="cdv-quartiere">🏘️ <?php echo esc_html( $quartiere_terms[0]->name ); ?></span>
								<?php endif; ?>
							</div>
							<div class="cdv-evento-excerpt">
								<?php the_excerpt(); ?>
							</div>
							<a href="<?php the_permalink(); ?>" class="cdv-btn cdv-btn-secondary">
								<?php esc_html_e( 'Scopri di più', 'cronaca-di-viterbo' ); ?>
							</a>
						</div>
					</div>
				<?php endwhile; ?>
				<?php wp_reset_postdata(); ?>
			<?php else : ?>
				<p><?php esc_html_e( 'Nessun evento trovato.', 'cronaca-di-viterbo' ); ?></p>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}
}
