<?php
/**
 * Shortcode: Video Stories
 *
 * Visualizza una lista di video stories in formato feed
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
 * Class VideoStories
 */
class VideoStories {
	/**
	 * Render shortcode
	 *
	 * Usage: [cdv_video_stories limit="6" format="vertical" autoplay="yes"]
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public static function render( $atts ): string {
		$atts = shortcode_atts(
			array(
				'limit'      => 10,
				'format'     => 'all', // vertical, horizontal, square, all
				'quartiere'  => '',
				'tematica'   => '',
				'layout'     => 'grid', // grid, slider, stories
				'autoplay'   => 'no',
				'orderby'    => 'date',
				'order'      => 'DESC',
			),
			$atts,
			'cdv_video_stories'
		);

		// Query args
		$args = array(
			'post_type'      => 'cdv_video',
			'posts_per_page' => intval( $atts['limit'] ),
			'post_status'    => 'publish',
			'orderby'        => sanitize_text_field( $atts['orderby'] ),
			'order'          => sanitize_text_field( $atts['order'] ),
		);

		// Filtro formato
		if ( 'all' !== $atts['format'] ) {
			$args['meta_query'] = array(
				array(
					'key'     => '_cdv_video_format',
					'value'   => sanitize_text_field( $atts['format'] ),
					'compare' => '=',
				),
			);
		}

		// Filtro quartiere
		if ( ! empty( $atts['quartiere'] ) ) {
			$args['tax_query'][] = array(
				'taxonomy' => 'cdv_quartiere',
				'field'    => 'slug',
				'terms'    => sanitize_text_field( $atts['quartiere'] ),
			);
		}

		// Filtro tematica
		if ( ! empty( $atts['tematica'] ) ) {
			$args['tax_query'][] = array(
				'taxonomy' => 'cdv_tematica',
				'field'    => 'slug',
				'terms'    => sanitize_text_field( $atts['tematica'] ),
			);
		}

		$query = new \WP_Query( $args );

		if ( ! $query->have_posts() ) {
			return '<p class="cdv-no-results">' . esc_html__( 'Nessun video trovato.', 'cronaca-di-viterbo' ) . '</p>';
		}

		// Output
		ob_start();

		$layout_class = 'cdv-video-layout-' . esc_attr( $atts['layout'] );
		$autoplay_attr = 'yes' === $atts['autoplay'] ? 'autoplay muted loop playsinline' : '';

		?>
		<div class="cdv-video-stories-container <?php echo esc_attr( $layout_class ); ?>" data-layout="<?php echo esc_attr( $atts['layout'] ); ?>">
			
			<?php if ( 'stories' === $atts['layout'] ) : ?>
				<!-- Layout Stories (stile Instagram/TikTok) -->
				<div class="cdv-stories-viewer">
					<div class="cdv-stories-nav cdv-stories-prev">
						<button type="button" class="cdv-nav-btn">←</button>
					</div>
					<div class="cdv-stories-content">
						<?php
						while ( $query->have_posts() ) :
							$query->the_post();
							self::render_video_story_card( get_the_ID(), $autoplay_attr );
						endwhile;
						?>
					</div>
					<div class="cdv-stories-nav cdv-stories-next">
						<button type="button" class="cdv-nav-btn">→</button>
					</div>
				</div>

			<?php elseif ( 'slider' === $atts['layout'] ) : ?>
				<!-- Layout Slider -->
				<div class="cdv-video-slider swiper">
					<div class="swiper-wrapper">
						<?php
						while ( $query->have_posts() ) :
							$query->the_post();
							echo '<div class="swiper-slide">';
							self::render_video_card( get_the_ID(), $autoplay_attr );
							echo '</div>';
						endwhile;
						?>
					</div>
					<div class="swiper-pagination"></div>
					<div class="swiper-button-prev"></div>
					<div class="swiper-button-next"></div>
				</div>

			<?php else : ?>
				<!-- Layout Grid (default) -->
				<div class="cdv-video-grid">
					<?php
					while ( $query->have_posts() ) :
						$query->the_post();
						self::render_video_card( get_the_ID(), $autoplay_attr );
					endwhile;
					?>
				</div>
			<?php endif; ?>

		</div>
		<?php

		wp_reset_postdata();

		return ob_get_clean();
	}

	/**
	 * Render single video card
	 *
	 * @param int    $post_id Post ID.
	 * @param string $autoplay_attr Autoplay attributes.
	 */
	private static function render_video_card( $post_id, $autoplay_attr = '' ): void {
		$video_url = get_post_meta( $post_id, '_cdv_video_url', true );
		$format = get_post_meta( $post_id, '_cdv_video_format', true );
		$duration = get_post_meta( $post_id, '_cdv_video_duration', true );
		$views = get_post_meta( $post_id, '_cdv_video_views', true ) ?: 0;
		$likes = get_post_meta( $post_id, '_cdv_video_likes', true ) ?: 0;

		$quartieri = wp_get_post_terms( $post_id, 'cdv_quartiere', array( 'fields' => 'names' ) );
		$tematiche = wp_get_post_terms( $post_id, 'cdv_tematica', array( 'fields' => 'names' ) );

		?>
		<article class="cdv-video-card cdv-format-<?php echo esc_attr( $format ); ?>" data-video-id="<?php echo esc_attr( $post_id ); ?>">
			<div class="cdv-video-player-wrapper">
				<?php if ( $video_url ) : ?>
					<video 
						class="cdv-video-player" 
						<?php echo $autoplay_attr; ?>
						controls
						preload="metadata"
						poster="<?php echo esc_url( get_the_post_thumbnail_url( $post_id, 'large' ) ); ?>"
					>
						<source src="<?php echo esc_url( $video_url ); ?>" type="video/mp4">
						<?php esc_html_e( 'Il tuo browser non supporta il tag video.', 'cronaca-di-viterbo' ); ?>
					</video>
				<?php else : ?>
					<div class="cdv-video-placeholder">
						<span class="dashicons dashicons-video-alt3"></span>
						<p><?php esc_html_e( 'Video non disponibile', 'cronaca-di-viterbo' ); ?></p>
					</div>
				<?php endif; ?>

				<?php if ( $duration ) : ?>
					<div class="cdv-video-duration">
						<?php echo esc_html( gmdate( 'i:s', $duration ) ); ?>
					</div>
				<?php endif; ?>

				<div class="cdv-video-overlay">
					<button type="button" class="cdv-play-btn" aria-label="<?php esc_attr_e( 'Play video', 'cronaca-di-viterbo' ); ?>">
						<span class="dashicons dashicons-controls-play"></span>
					</button>
				</div>
			</div>

			<div class="cdv-video-info">
				<h3 class="cdv-video-title">
					<a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>">
						<?php echo esc_html( get_the_title( $post_id ) ); ?>
					</a>
				</h3>

				<?php if ( ! empty( $quartieri ) || ! empty( $tematiche ) ) : ?>
					<div class="cdv-video-meta">
						<?php if ( ! empty( $quartieri ) ) : ?>
							<span class="cdv-video-quartiere">
								<span class="dashicons dashicons-location"></span>
								<?php echo esc_html( implode( ', ', $quartieri ) ); ?>
							</span>
						<?php endif; ?>
						<?php if ( ! empty( $tematiche ) ) : ?>
							<span class="cdv-video-tematica">
								<span class="dashicons dashicons-tag"></span>
								<?php echo esc_html( implode( ', ', $tematiche ) ); ?>
							</span>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<div class="cdv-video-stats">
					<span class="cdv-stat-views" title="<?php esc_attr_e( 'Visualizzazioni', 'cronaca-di-viterbo' ); ?>">
						<span class="dashicons dashicons-visibility"></span>
						<?php echo esc_html( number_format_i18n( $views ) ); ?>
					</span>
					<span class="cdv-stat-likes" title="<?php esc_attr_e( 'Mi piace', 'cronaca-di-viterbo' ); ?>">
						<button type="button" class="cdv-like-btn" data-video-id="<?php echo esc_attr( $post_id ); ?>">
							<span class="dashicons dashicons-heart"></span>
							<span class="cdv-likes-count"><?php echo esc_html( number_format_i18n( $likes ) ); ?></span>
						</button>
					</span>
				</div>
			</div>
		</article>
		<?php
	}

	/**
	 * Render video story card (fullscreen format)
	 *
	 * @param int    $post_id Post ID.
	 * @param string $autoplay_attr Autoplay attributes.
	 */
	private static function render_video_story_card( $post_id, $autoplay_attr = '' ): void {
		$video_url = get_post_meta( $post_id, '_cdv_video_url', true );
		
		?>
		<div class="cdv-story-item" data-story-id="<?php echo esc_attr( $post_id ); ?>">
			<?php if ( $video_url ) : ?>
				<video 
					class="cdv-story-video" 
					<?php echo $autoplay_attr; ?>
					preload="auto"
				>
					<source src="<?php echo esc_url( $video_url ); ?>" type="video/mp4">
				</video>
			<?php endif; ?>

			<div class="cdv-story-info">
				<div class="cdv-story-header">
					<?php echo get_avatar( get_post_field( 'post_author', $post_id ), 40 ); ?>
					<div class="cdv-story-author">
						<span class="cdv-author-name"><?php echo esc_html( get_the_author_meta( 'display_name', get_post_field( 'post_author', $post_id ) ) ); ?></span>
						<span class="cdv-story-time"><?php echo esc_html( human_time_diff( get_post_time( 'U', false, $post_id ), current_time( 'U' ) ) ); ?> fa</span>
					</div>
				</div>
				<h3 class="cdv-story-title"><?php echo esc_html( get_the_title( $post_id ) ); ?></h3>
			</div>

			<div class="cdv-story-progress">
				<div class="cdv-progress-bar"></div>
			</div>
		</div>
		<?php
	}
}
