<?php
/**
 * Shortcode: Galleria Foto
 *
 * Visualizza gallerie fotografiche
 *
 * @package CdV
 * @subpackage Shortcodes
 * @since 2.0.0
 */

namespace CdV\Shortcodes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class GalleriaFoto
 */
class GalleriaFoto {
	/**
	 * Render shortcode
	 *
	 * Usage: [cdv_galleria id="123" layout="grid" columns="3"]
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public static function render( $atts ): string {
		$atts = shortcode_atts(
			array(
				'id'       => 0,
				'layout'   => '', // se vuoto, usa quello del post
				'columns'  => '', // se vuoto, usa quello del post
				'lightbox' => 'yes',
			),
			$atts,
			'cdv_galleria'
		);

		$gallery_id = intval( $atts['id'] );

		if ( ! $gallery_id ) {
			return '<p class="cdv-error">' . esc_html__( 'ID galleria non specificato.', 'cronaca-di-viterbo' ) . '</p>';
		}

		$gallery_post = get_post( $gallery_id );

		if ( ! $gallery_post || 'cdv_galleria' !== $gallery_post->post_type ) {
			return '<p class="cdv-error">' . esc_html__( 'Galleria non trovata.', 'cronaca-di-viterbo' ) . '</p>';
		}

		// Get photo IDs
		$photo_ids = get_post_meta( $gallery_id, '_cdv_gallery_photos', true );
		if ( empty( $photo_ids ) ) {
			return '<p class="cdv-no-results">' . esc_html__( 'Nessuna foto in questa galleria.', 'cronaca-di-viterbo' ) . '</p>';
		}

		$photo_ids = explode( ',', $photo_ids );

		// Settings
		$layout = ! empty( $atts['layout'] ) ? $atts['layout'] : get_post_meta( $gallery_id, '_cdv_gallery_layout', true ) ?: 'grid';
		$columns = ! empty( $atts['columns'] ) ? intval( $atts['columns'] ) : intval( get_post_meta( $gallery_id, '_cdv_gallery_columns', true ) ) ?: 3;
		$lightbox_enabled = 'yes' === $atts['lightbox'];

		// Gallery info
		$photographer = get_post_meta( $gallery_id, '_cdv_photographer', true );
		$location = get_post_meta( $gallery_id, '_cdv_photo_location', true );

		ob_start();
		?>
		<div class="cdv-galleria-container" data-gallery-id="<?php echo esc_attr( $gallery_id ); ?>">
			
			<div class="cdv-galleria-header">
				<h2 class="cdv-galleria-title"><?php echo esc_html( get_the_title( $gallery_id ) ); ?></h2>
				
				<?php if ( $gallery_post->post_excerpt ) : ?>
					<div class="cdv-galleria-excerpt">
						<?php echo wp_kses_post( wpautop( $gallery_post->post_excerpt ) ); ?>
					</div>
				<?php endif; ?>

				<?php if ( $photographer || $location ) : ?>
					<div class="cdv-galleria-meta">
						<?php if ( $photographer ) : ?>
							<span class="cdv-photographer">
								<span class="dashicons dashicons-camera"></span>
								<strong><?php esc_html_e( 'Fotografo:', 'cronaca-di-viterbo' ); ?></strong>
								<?php echo esc_html( $photographer ); ?>
							</span>
						<?php endif; ?>
						<?php if ( $location ) : ?>
							<span class="cdv-location">
								<span class="dashicons dashicons-location-alt"></span>
								<strong><?php esc_html_e( 'Luogo:', 'cronaca-di-viterbo' ); ?></strong>
								<?php echo esc_html( $location ); ?>
							</span>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</div>

			<div class="cdv-galleria-photos cdv-layout-<?php echo esc_attr( $layout ); ?> cdv-columns-<?php echo esc_attr( $columns ); ?>" 
			     data-layout="<?php echo esc_attr( $layout ); ?>"
			     data-lightbox="<?php echo $lightbox_enabled ? 'true' : 'false'; ?>">
				
				<?php foreach ( $photo_ids as $index => $photo_id ) : ?>
					<?php
					$image_full = wp_get_attachment_image_url( $photo_id, 'full' );
					$image_large = wp_get_attachment_image_url( $photo_id, 'large' );
					$image_thumb = wp_get_attachment_image_url( $photo_id, 'medium_large' );
					$caption = wp_get_attachment_caption( $photo_id );
					$alt = get_post_meta( $photo_id, '_wp_attachment_image_alt', true );

					if ( ! $image_thumb ) {
						continue;
					}
					?>
					<div class="cdv-photo-item" data-index="<?php echo esc_attr( $index ); ?>">
						<?php if ( $lightbox_enabled ) : ?>
							<a href="<?php echo esc_url( $image_full ); ?>" 
							   class="cdv-photo-link" 
							   data-lightbox="cdv-gallery-<?php echo esc_attr( $gallery_id ); ?>"
							   data-title="<?php echo esc_attr( $caption ); ?>">
								<img src="<?php echo esc_url( $image_thumb ); ?>" 
								     alt="<?php echo esc_attr( $alt ?: $caption ); ?>"
								     loading="lazy">
								<div class="cdv-photo-overlay">
									<span class="dashicons dashicons-search"></span>
								</div>
							</a>
						<?php else : ?>
							<div class="cdv-photo-static">
								<img src="<?php echo esc_url( $image_thumb ); ?>" 
								     alt="<?php echo esc_attr( $alt ?: $caption ); ?>"
								     loading="lazy">
							</div>
						<?php endif; ?>

						<?php if ( $caption ) : ?>
							<div class="cdv-photo-caption"><?php echo esc_html( $caption ); ?></div>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>

			</div>

			<div class="cdv-galleria-footer">
				<p class="cdv-photo-count">
					<?php 
					/* translators: %d: number of photos */
					printf( esc_html__( '%d foto', 'cronaca-di-viterbo' ), count( $photo_ids ) ); 
					?>
				</p>
			</div>

		</div>
		<?php

		return ob_get_clean();
	}

	/**
	 * Render lista gallerie
	 *
	 * Usage: [cdv_gallerie limit="6" quartiere="centro"]
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public static function render_list( $atts ): string {
		$atts = shortcode_atts(
			array(
				'limit'     => 12,
				'quartiere' => '',
				'tematica'  => '',
				'orderby'   => 'date',
				'order'     => 'DESC',
			),
			$atts,
			'cdv_gallerie'
		);

		$args = array(
			'post_type'      => 'cdv_galleria',
			'posts_per_page' => intval( $atts['limit'] ),
			'post_status'    => 'publish',
			'orderby'        => sanitize_text_field( $atts['orderby'] ),
			'order'          => sanitize_text_field( $atts['order'] ),
		);

		// Filtri tassonomie
		if ( ! empty( $atts['quartiere'] ) || ! empty( $atts['tematica'] ) ) {
			$args['tax_query'] = array( 'relation' => 'AND' );

			if ( ! empty( $atts['quartiere'] ) ) {
				$args['tax_query'][] = array(
					'taxonomy' => 'cdv_quartiere',
					'field'    => 'slug',
					'terms'    => sanitize_text_field( $atts['quartiere'] ),
				);
			}

			if ( ! empty( $atts['tematica'] ) ) {
				$args['tax_query'][] = array(
					'taxonomy' => 'cdv_tematica',
					'field'    => 'slug',
					'terms'    => sanitize_text_field( $atts['tematica'] ),
				);
			}
		}

		$query = new \WP_Query( $args );

		if ( ! $query->have_posts() ) {
			return '<p class="cdv-no-results">' . esc_html__( 'Nessuna galleria trovata.', 'cronaca-di-viterbo' ) . '</p>';
		}

		ob_start();
		?>
		<div class="cdv-gallerie-list">
			<?php
			while ( $query->have_posts() ) :
				$query->the_post();
				$gallery_id = get_the_ID();
				$photo_ids = get_post_meta( $gallery_id, '_cdv_gallery_photos', true );
				$photo_count = $photo_ids ? count( explode( ',', $photo_ids ) ) : 0;
				$photographer = get_post_meta( $gallery_id, '_cdv_photographer', true );
				?>
				<article class="cdv-galleria-card">
					<div class="cdv-galleria-thumb">
						<a href="<?php the_permalink(); ?>">
							<?php 
							if ( has_post_thumbnail() ) {
								the_post_thumbnail( 'large' );
							} else {
								echo '<div class="cdv-placeholder-thumb"><span class="dashicons dashicons-format-gallery"></span></div>';
							}
							?>
							<div class="cdv-thumb-overlay">
								<span class="cdv-photo-count-badge">
									<span class="dashicons dashicons-images-alt2"></span>
									<?php echo esc_html( $photo_count ); ?>
								</span>
							</div>
						</a>
					</div>
					<div class="cdv-galleria-content">
						<h3 class="cdv-galleria-title-card">
							<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
						</h3>
						<?php if ( has_excerpt() ) : ?>
							<div class="cdv-galleria-excerpt-card">
								<?php the_excerpt(); ?>
							</div>
						<?php endif; ?>
						<div class="cdv-galleria-meta-card">
							<?php if ( $photographer ) : ?>
								<span class="cdv-meta-photographer">
									<span class="dashicons dashicons-camera"></span>
									<?php echo esc_html( $photographer ); ?>
								</span>
							<?php endif; ?>
							<span class="cdv-meta-date">
								<span class="dashicons dashicons-calendar-alt"></span>
								<?php echo esc_html( get_the_date() ); ?>
							</span>
						</div>
					</div>
				</article>
				<?php
			endwhile;
			?>
		</div>
		<?php

		wp_reset_postdata();

		return ob_get_clean();
	}
}
