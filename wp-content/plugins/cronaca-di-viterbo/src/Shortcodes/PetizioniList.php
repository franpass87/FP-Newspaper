<?php
/**
 * Shortcode: Lista Petizioni
 *
 * @package CdV
 * @subpackage Shortcodes
 * @since 1.3.0
 */

namespace CdV\Shortcodes;

/**
 * Class PetizioniList
 */
class PetizioniList {
	/**
	 * Render shortcode
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public static function render( $atts ): string {
		$atts = shortcode_atts(
			array(
				'limit'     => 10,
				'quartiere' => '',
				'tematica'  => '',
				'status'    => 'aperte', // aperte, chiuse, tutte
				'orderby'   => 'firme',  // firme, date, title
				'order'     => 'DESC',
			),
			$atts,
			'cdv_petizioni'
		);

		$args = array(
			'post_type'      => 'cdv_petizione',
			'post_status'    => 'publish',
			'posts_per_page' => intval( $atts['limit'] ),
			'orderby'        => 'date',
			'order'          => strtoupper( $atts['order'] ),
		);

		// Filter by quartiere
		if ( ! empty( $atts['quartiere'] ) ) {
			$args['tax_query'][] = array(
				'taxonomy' => 'cdv_quartiere',
				'field'    => 'slug',
				'terms'    => sanitize_title( $atts['quartiere'] ),
			);
		}

		// Filter by tematica
		if ( ! empty( $atts['tematica'] ) ) {
			$args['tax_query'][] = array(
				'taxonomy' => 'cdv_tematica',
				'field'    => 'slug',
				'terms'    => sanitize_title( $atts['tematica'] ),
			);
		}

		// Filter by status
		if ( $atts['status'] === 'aperte' ) {
			$args['meta_query'][] = array(
				'key'   => '_cdv_aperta',
				'value' => '0',
				'compare' => '!=',
			);
		} elseif ( $atts['status'] === 'chiuse' ) {
			$args['meta_query'][] = array(
				'key'   => '_cdv_aperta',
				'value' => '0',
			);
		}

		// Order by firme
		if ( $atts['orderby'] === 'firme' ) {
			$args['meta_key'] = '_cdv_firme_count';
			$args['orderby'] = 'meta_value_num';
		}

		$petizioni = new \WP_Query( $args );

		if ( ! $petizioni->have_posts() ) {
			return '<p class="cdv-no-results">' . __( 'Nessuna petizione trovata.', 'cronaca-di-viterbo' ) . '</p>';
		}

		ob_start();
		?>
		<div class="cdv-petizioni-list">
			<?php while ( $petizioni->have_posts() ) : $petizioni->the_post(); ?>
				<?php
				$firme = intval( get_post_meta( get_the_ID(), '_cdv_firme_count', true ) );
				$soglia = intval( get_post_meta( get_the_ID(), '_cdv_soglia_firme', true ) );
				$percentuale = $soglia > 0 ? round( ( $firme / $soglia ) * 100, 1 ) : 0;
				$aperta = get_post_meta( get_the_ID(), '_cdv_aperta', true ) !== '0';
				$deadline = get_post_meta( get_the_ID(), '_cdv_deadline', true );
				?>
				<article class="cdv-petizione-card <?php echo $aperta ? 'cdv-petizione-aperta' : 'cdv-petizione-chiusa'; ?>">
					<?php if ( has_post_thumbnail() ) : ?>
						<div class="cdv-petizione-thumbnail">
							<a href="<?php the_permalink(); ?>">
								<?php the_post_thumbnail( 'medium' ); ?>
							</a>
						</div>
					<?php endif; ?>

					<div class="cdv-petizione-content">
						<h3 class="cdv-petizione-title">
							<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
						</h3>

						<div class="cdv-petizione-meta">
							<?php
							$quartieri = get_the_terms( get_the_ID(), 'cdv_quartiere' );
							$tematiche = get_the_terms( get_the_ID(), 'cdv_tematica' );
							?>
							<?php if ( $quartieri ) : ?>
								<span class="cdv-meta-quartiere">📍 <?php echo esc_html( $quartieri[0]->name ); ?></span>
							<?php endif; ?>
							<?php if ( $tematiche ) : ?>
								<span class="cdv-meta-tematica">🏷️ <?php echo esc_html( $tematiche[0]->name ); ?></span>
							<?php endif; ?>
						</div>

						<div class="cdv-petizione-excerpt">
							<?php the_excerpt(); ?>
						</div>

						<div class="cdv-petizione-progress">
							<div class="cdv-progress-bar">
								<div class="cdv-progress-fill" style="width: <?php echo esc_attr( min( $percentuale, 100 ) ); ?>%"></div>
							</div>
							<div class="cdv-progress-stats">
								<strong><?php echo esc_html( number_format_i18n( $firme ) ); ?></strong> <?php esc_html_e( 'firme', 'cronaca-di-viterbo' ); ?>
								<span class="cdv-obiettivo"><?php echo esc_html( number_format_i18n( $soglia ) ); ?></span>
							</div>
						</div>

						<div class="cdv-petizione-footer">
							<?php if ( $deadline ) : ?>
								<span class="cdv-deadline">
									⏰ <?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $deadline ) ) ); ?>
								</span>
							<?php endif; ?>
							<a href="<?php the_permalink(); ?>" class="cdv-btn cdv-btn-small">
								<?php $aperta ? esc_html_e( 'Firma ora', 'cronaca-di-viterbo' ) : esc_html_e( 'Dettagli', 'cronaca-di-viterbo' ); ?>
							</a>
						</div>
					</div>
				</article>
			<?php endwhile; ?>
			<?php wp_reset_postdata(); ?>
		</div>
		<?php
		return ob_get_clean();
	}
}
