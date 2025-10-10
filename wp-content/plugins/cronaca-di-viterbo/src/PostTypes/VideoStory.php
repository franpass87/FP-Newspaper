<?php
/**
 * Custom Post Type: Video Story
 *
 * Gestisce video brevi in stile Stories/Shorts per citizen journalism
 *
 * @package CdV
 * @subpackage PostTypes
 * @since 2.0.0
 */

namespace CdV\PostTypes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class VideoStory
 */
class VideoStory {
	/**
	 * Post type slug
	 */
	const POST_TYPE = 'cdv_video';

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'register' ] );
	}

	/**
	 * Register post type
	 */
	public function register(): void {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'              => array(
					'name'               => __( 'Video Stories', 'cronaca-di-viterbo' ),
					'singular_name'      => __( 'Video Story', 'cronaca-di-viterbo' ),
					'add_new'            => __( 'Aggiungi Video', 'cronaca-di-viterbo' ),
					'add_new_item'       => __( 'Aggiungi Nuovo Video', 'cronaca-di-viterbo' ),
					'edit_item'          => __( 'Modifica Video', 'cronaca-di-viterbo' ),
					'new_item'           => __( 'Nuovo Video', 'cronaca-di-viterbo' ),
					'view_item'          => __( 'Visualizza Video', 'cronaca-di-viterbo' ),
					'search_items'       => __( 'Cerca Video', 'cronaca-di-viterbo' ),
					'not_found'          => __( 'Nessun video trovato', 'cronaca-di-viterbo' ),
					'not_found_in_trash' => __( 'Nessun video nel cestino', 'cronaca-di-viterbo' ),
					'all_items'          => __( 'Tutti i Video', 'cronaca-di-viterbo' ),
				),
				'public'              => true,
				'publicly_queryable'  => true,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_rest'        => true,
				'has_archive'         => true,
				'rewrite'             => array( 'slug' => 'video-stories' ),
				'capability_type'     => 'post',
				'map_meta_cap'        => true,
				'supports'            => array( 'title', 'editor', 'author', 'thumbnail', 'comments', 'excerpt' ),
				'taxonomies'          => array( 'cdv_quartiere', 'cdv_tematica', 'post_tag' ),
				'menu_icon'           => 'dashicons-video-alt3',
				'menu_position'       => 7,
			)
		);
	}

	/**
	 * Add meta boxes
	 */
	public static function add_meta_boxes(): void {
		add_meta_box(
			'cdv_video_details',
			__( 'Dettagli Video', 'cronaca-di-viterbo' ),
			array( self::class, 'render_meta_box_details' ),
			self::POST_TYPE,
			'normal',
			'high'
		);

		add_meta_box(
			'cdv_video_stats',
			__( 'Statistiche', 'cronaca-di-viterbo' ),
			array( self::class, 'render_meta_box_stats' ),
			self::POST_TYPE,
			'side',
			'default'
		);
	}

	/**
	 * Render meta box dettagli
	 *
	 * @param \WP_Post $post Post object.
	 */
	public static function render_meta_box_details( $post ): void {
		wp_nonce_field( 'cdv_video_meta', 'cdv_video_nonce' );

		$video_url = get_post_meta( $post->ID, '_cdv_video_url', true );
		$video_type = get_post_meta( $post->ID, '_cdv_video_type', true ) ?: 'embed';
		$duration = get_post_meta( $post->ID, '_cdv_video_duration', true ) ?: 0;
		$format = get_post_meta( $post->ID, '_cdv_video_format', true ) ?: 'vertical';
		$platform = get_post_meta( $post->ID, '_cdv_video_platform', true ) ?: '';

		?>
		<div class="cdv-video-meta-box">
			<div class="cdv-embed-section">
				<h3 style="margin-top:0;">🎬 <?php esc_html_e( 'Embed Video da Social', 'cronaca-di-viterbo' ); ?></h3>
				<p class="description">
					<?php esc_html_e( 'Incolla l\'URL del video da Instagram, YouTube, TikTok o altre piattaforme social. Il video verrà automaticamente incorporato.', 'cronaca-di-viterbo' ); ?>
				</p>

				<p>
					<label for="cdv_video_url"><strong><?php esc_html_e( 'URL Video', 'cronaca-di-viterbo' ); ?></strong></label><br>
					<input type="url" name="cdv_video_url" id="cdv_video_url" value="<?php echo esc_url( $video_url ); ?>" class="widefat cdv-url-input" placeholder="https://www.instagram.com/reel/...">
				</p>

				<div class="cdv-platform-examples">
					<p><strong><?php esc_html_e( 'Piattaforme supportate:', 'cronaca-di-viterbo' ); ?></strong></p>
					<ul style="list-style: none; padding-left: 0;">
						<li>📷 <strong>Instagram</strong>: Reels, IGTV, Post video</li>
						<li>▶️ <strong>YouTube</strong>: Video, Shorts</li>
						<li>🎵 <strong>TikTok</strong>: Video pubblici</li>
						<li>🎬 <strong>Vimeo</strong>: Video embed</li>
						<li>👥 <strong>Facebook</strong>: Video pubblici</li>
						<li>🐦 <strong>Twitter/X</strong>: Video tweet</li>
					</ul>
				</div>

				<div class="cdv-url-examples">
					<details>
						<summary style="cursor: pointer; color: #667eea;"><?php esc_html_e( 'Mostra esempi URL', 'cronaca-di-viterbo' ); ?></summary>
						<div style="margin-top: 10px; padding: 10px; background: #f5f5f5; border-radius: 4px;">
							<p><strong>Instagram Reel:</strong><br>
							<code>https://www.instagram.com/reel/ABC123/</code></p>
							
							<p><strong>YouTube Short:</strong><br>
							<code>https://www.youtube.com/shorts/ABC123</code></p>
							
							<p><strong>YouTube Video:</strong><br>
							<code>https://www.youtube.com/watch?v=ABC123</code><br>
							<code>https://youtu.be/ABC123</code></p>
							
							<p><strong>TikTok:</strong><br>
							<code>https://www.tiktok.com/@user/video/123456</code></p>
							
							<p><strong>Vimeo:</strong><br>
							<code>https://vimeo.com/123456</code></p>
						</div>
					</details>
				</div>

				<?php if ( $video_url ) : ?>
				<div class="cdv-auto-detect" style="margin-top: 15px; padding: 10px; background: #e8f5e9; border-left: 3px solid #4caf50; border-radius: 4px;">
					<p style="margin: 0;">
						<strong>✅ <?php esc_html_e( 'Piattaforma rilevata:', 'cronaca-di-viterbo' ); ?></strong>
						<span id="cdv-detected-platform">
							<?php 
							$detected = self::detect_video_platform( $video_url );
							echo esc_html( $detected ? ucfirst( $detected ) : __( 'Sconosciuta', 'cronaca-di-viterbo' ) ); 
							?>
						</span>
					</p>
				</div>
				<?php endif; ?>
			</div>

			<hr style="margin: 20px 0;">

			<div class="cdv-upload-section">
				<h4><?php esc_html_e( 'Oppure carica file video', 'cronaca-di-viterbo' ); ?></h4>
				<p>
					<button type="button" class="button cdv-upload-video-btn">
						<span class="dashicons dashicons-upload"></span> <?php esc_html_e( 'Carica Video dalla Libreria', 'cronaca-di-viterbo' ); ?>
					</button>
					<span class="description" style="display: block; margin-top: 5px;">
						<?php esc_html_e( 'Supportato: MP4, WebM, OGG (max upload_max_filesize)', 'cronaca-di-viterbo' ); ?>
					</span>
				</p>
			</div>

			<input type="hidden" name="cdv_video_type" id="cdv_video_type" value="<?php echo esc_attr( $video_type ); ?>">
			<input type="hidden" name="cdv_video_platform" id="cdv_video_platform" value="<?php echo esc_attr( $platform ); ?>">

			<p>
				<label for="cdv_video_duration"><strong><?php esc_html_e( 'Durata (secondi)', 'cronaca-di-viterbo' ); ?></strong></label><br>
				<input type="number" name="cdv_video_duration" id="cdv_video_duration" value="<?php echo esc_attr( $duration ); ?>" min="0" max="180" class="small-text">
				<small><?php esc_html_e( 'Max 180 secondi (3 minuti)', 'cronaca-di-viterbo' ); ?></small>
			</p>

			<p>
				<label for="cdv_video_format"><strong><?php esc_html_e( 'Formato', 'cronaca-di-viterbo' ); ?></strong></label><br>
				<select name="cdv_video_format" id="cdv_video_format">
					<option value="vertical" <?php selected( $format, 'vertical' ); ?>>📱 <?php esc_html_e( 'Verticale (9:16 - Stories)', 'cronaca-di-viterbo' ); ?></option>
					<option value="square" <?php selected( $format, 'square' ); ?>>⬜ <?php esc_html_e( 'Quadrato (1:1)', 'cronaca-di-viterbo' ); ?></option>
					<option value="horizontal" <?php selected( $format, 'horizontal' ); ?>>🎬 <?php esc_html_e( 'Orizzontale (16:9)', 'cronaca-di-viterbo' ); ?></option>
				</select>
			</p>

			<?php if ( $video_url ) : ?>
			<div class="cdv-video-preview">
				<p><strong><?php esc_html_e( 'Anteprima:', 'cronaca-di-viterbo' ); ?></strong></p>
				<div class="cdv-video-container cdv-format-<?php echo esc_attr( $format ); ?>">
					<?php echo wp_video_shortcode( array( 'src' => $video_url ) ); ?>
				</div>
			</div>
			<?php endif; ?>
		</div>

		<style>
			.cdv-video-preview {
				margin-top: 20px;
				padding: 15px;
				background: #f5f5f5;
				border-radius: 4px;
			}
			.cdv-video-container {
				max-width: 100%;
				margin-top: 10px;
			}
			.cdv-format-vertical { max-width: 300px; }
			.cdv-format-square { max-width: 400px; }
			.cdv-format-horizontal { max-width: 100%; }
		</style>
		<?php
	}

	/**
	 * Render meta box statistiche
	 *
	 * @param \WP_Post $post Post object.
	 */
	public static function render_meta_box_stats( $post ): void {
		$views = get_post_meta( $post->ID, '_cdv_video_views', true ) ?: 0;
		$likes = get_post_meta( $post->ID, '_cdv_video_likes', true ) ?: 0;
		$shares = get_post_meta( $post->ID, '_cdv_video_shares', true ) ?: 0;

		?>
		<div class="cdv-video-stats">
			<p>
				<strong>👁️ <?php esc_html_e( 'Visualizzazioni', 'cronaca-di-viterbo' ); ?>:</strong><br>
				<span style="font-size: 24px; font-weight: bold;"><?php echo esc_html( number_format_i18n( $views ) ); ?></span>
			</p>
			<p>
				<strong>❤️ <?php esc_html_e( 'Mi piace', 'cronaca-di-viterbo' ); ?>:</strong><br>
				<span style="font-size: 24px; font-weight: bold;"><?php echo esc_html( number_format_i18n( $likes ) ); ?></span>
			</p>
			<p>
				<strong>🔗 <?php esc_html_e( 'Condivisioni', 'cronaca-di-viterbo' ); ?>:</strong><br>
				<span style="font-size: 24px; font-weight: bold;"><?php echo esc_html( number_format_i18n( $shares ) ); ?></span>
			</p>
			<hr>
			<p>
				<small>📅 <strong><?php esc_html_e( 'Pubblicato', 'cronaca-di-viterbo' ); ?>:</strong><br>
				<?php echo esc_html( get_the_date( 'j F Y - H:i', $post ) ); ?></small>
			</p>
		</div>
		<?php
	}

	/**
	 * Save meta box data
	 *
	 * @param int $post_id Post ID.
	 */
	public static function save_meta_box( $post_id ): void {
		// Verifica nonce
		if ( ! isset( $_POST['cdv_video_nonce'] ) || 
		     ! wp_verify_nonce( $_POST['cdv_video_nonce'], 'cdv_video_meta' ) ) {
			return;
		}

		// Verifica autosave
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Verifica permessi
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Salva URL e auto-detect platform
		if ( isset( $_POST['cdv_video_url'] ) ) {
			$video_url = esc_url_raw( $_POST['cdv_video_url'] );
			update_post_meta( $post_id, '_cdv_video_url', $video_url );

			// Auto-detect platform
			$platform = self::detect_video_platform( $video_url );
			if ( $platform ) {
				update_post_meta( $post_id, '_cdv_video_platform', $platform );
				update_post_meta( $post_id, '_cdv_video_type', 'embed' );
			} else {
				update_post_meta( $post_id, '_cdv_video_type', 'upload' );
			}
		}

		// Salva altri campi
		$fields = array(
			'cdv_video_duration' => 'absint',
			'cdv_video_format'   => 'sanitize_text_field',
		);

		foreach ( $fields as $field => $sanitize_callback ) {
			if ( isset( $_POST[ $field ] ) ) {
				$value = call_user_func( $sanitize_callback, $_POST[ $field ] );
				update_post_meta( $post_id, '_' . $field, $value );
			}
		}
	}

	/**
	 * Detect video platform from URL
	 *
	 * @param string $url Video URL.
	 * @return string|false Platform name or false.
	 */
	public static function detect_video_platform( string $url ) {
		if ( empty( $url ) ) {
			return false;
		}

		$url_lower = strtolower( $url );

		// Instagram
		if ( strpos( $url_lower, 'instagram.com' ) !== false || strpos( $url_lower, 'instagr.am' ) !== false ) {
			return 'instagram';
		}

		// YouTube
		if ( strpos( $url_lower, 'youtube.com' ) !== false || strpos( $url_lower, 'youtu.be' ) !== false ) {
			return 'youtube';
		}

		// TikTok
		if ( strpos( $url_lower, 'tiktok.com' ) !== false ) {
			return 'tiktok';
		}

		// Vimeo
		if ( strpos( $url_lower, 'vimeo.com' ) !== false ) {
			return 'vimeo';
		}

		// Facebook
		if ( strpos( $url_lower, 'facebook.com' ) !== false || strpos( $url_lower, 'fb.watch' ) !== false ) {
			return 'facebook';
		}

		// Twitter/X
		if ( strpos( $url_lower, 'twitter.com' ) !== false || strpos( $url_lower, 'x.com' ) !== false ) {
			return 'twitter';
		}

		return false;
	}

	/**
	 * Get embed HTML for video
	 *
	 * @param int $post_id Post ID.
	 * @return string Embed HTML.
	 */
	public static function get_embed_html( int $post_id ): string {
		$video_url = get_post_meta( $post_id, '_cdv_video_url', true );
		$platform = get_post_meta( $post_id, '_cdv_video_platform', true );
		$format = get_post_meta( $post_id, '_cdv_video_format', true ) ?: 'vertical';

		if ( empty( $video_url ) ) {
			return '<div class="cdv-video-error">' . esc_html__( 'URL video mancante.', 'cronaca-di-viterbo' ) . '</div>';
		}

		// Usa oEmbed WordPress per piattaforme supportate
		if ( in_array( $platform, array( 'youtube', 'vimeo', 'facebook', 'twitter' ), true ) ) {
			$embed_html = wp_oembed_get( $video_url );
			if ( $embed_html ) {
				return '<div class="cdv-embed-responsive cdv-format-' . esc_attr( $format ) . '">' . $embed_html . '</div>';
			}
		}

		// Fallback per piattaforme specifiche
		switch ( $platform ) {
			case 'instagram':
				return self::get_instagram_embed( $video_url, $format );
			
			case 'tiktok':
				return self::get_tiktok_embed( $video_url, $format );
			
			default:
				// Try WordPress oEmbed as last resort
				$embed_html = wp_oembed_get( $video_url );
				if ( $embed_html ) {
					return '<div class="cdv-embed-responsive cdv-format-' . esc_attr( $format ) . '">' . $embed_html . '</div>';
				}

				// Direct video URL
				return '<div class="cdv-video-player-wrapper cdv-format-' . esc_attr( $format ) . '">
					<video class="cdv-video-player" controls preload="metadata">
						<source src="' . esc_url( $video_url ) . '" type="video/mp4">
						' . esc_html__( 'Il tuo browser non supporta il tag video.', 'cronaca-di-viterbo' ) . '
					</video>
				</div>';
		}
	}

	/**
	 * Get Instagram embed HTML
	 *
	 * @param string $url Instagram URL.
	 * @param string $format Video format.
	 * @return string Embed HTML.
	 */
	private static function get_instagram_embed( string $url, string $format ): string {
		// Instagram oEmbed
		$oembed_url = 'https://api.instagram.com/oembed/?url=' . urlencode( $url );
		$response = wp_remote_get( $oembed_url, array( 'timeout' => 10 ) );

		if ( ! is_wp_error( $response ) ) {
			$body = wp_remote_retrieve_body( $response );
			$data = json_decode( $body, true );
			
			if ( isset( $data['html'] ) ) {
				return '<div class="cdv-embed-responsive cdv-embed-instagram cdv-format-' . esc_attr( $format ) . '">' . $data['html'] . '</div>';
			}
		}

		// Fallback: Blockquote embed
		return '<div class="cdv-embed-responsive cdv-embed-instagram cdv-format-' . esc_attr( $format ) . '">
			<blockquote class="instagram-media" data-instgrm-permalink="' . esc_url( $url ) . '" data-instgrm-version="14">
				<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener">' . esc_html__( 'Visualizza su Instagram', 'cronaca-di-viterbo' ) . '</a>
			</blockquote>
			<script async src="//www.instagram.com/embed.js"></script>
		</div>';
	}

	/**
	 * Get TikTok embed HTML
	 *
	 * @param string $url TikTok URL.
	 * @param string $format Video format.
	 * @return string Embed HTML.
	 */
	private static function get_tiktok_embed( string $url, string $format ): string {
		// TikTok oEmbed
		$oembed_url = 'https://www.tiktok.com/oembed?url=' . urlencode( $url );
		$response = wp_remote_get( $oembed_url, array( 'timeout' => 10 ) );

		if ( ! is_wp_error( $response ) ) {
			$body = wp_remote_retrieve_body( $response );
			$data = json_decode( $body, true );
			
			if ( isset( $data['html'] ) ) {
				return '<div class="cdv-embed-responsive cdv-embed-tiktok cdv-format-' . esc_attr( $format ) . '">' . $data['html'] . '</div>';
			}
		}

		// Fallback: Blockquote embed
		return '<div class="cdv-embed-responsive cdv-embed-tiktok cdv-format-' . esc_attr( $format ) . '">
			<blockquote class="tiktok-embed" cite="' . esc_url( $url ) . '" data-video-id="">
				<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener">' . esc_html__( 'Visualizza su TikTok', 'cronaca-di-viterbo' ) . '</a>
			</blockquote>
			<script async src="https://www.tiktok.com/embed.js"></script>
		</div>';
	}

	/**
	 * Increment video views
	 *
	 * @param int $post_id Post ID.
	 */
	public static function increment_views( $post_id ): void {
		$views = (int) get_post_meta( $post_id, '_cdv_video_views', true );
		update_post_meta( $post_id, '_cdv_video_views', $views + 1 );
	}

	/**
	 * Increment video likes
	 *
	 * @param int $post_id Post ID.
	 */
	public static function increment_likes( $post_id ): void {
		$likes = (int) get_post_meta( $post_id, '_cdv_video_likes', true );
		update_post_meta( $post_id, '_cdv_video_likes', $likes + 1 );
	}
}
