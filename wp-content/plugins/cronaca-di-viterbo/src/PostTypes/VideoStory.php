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
		$video_type = get_post_meta( $post->ID, '_cdv_video_type', true ) ?: 'upload';
		$duration = get_post_meta( $post->ID, '_cdv_video_duration', true ) ?: 0;
		$format = get_post_meta( $post->ID, '_cdv_video_format', true ) ?: 'vertical';

		?>
		<div class="cdv-video-meta-box">
			<p>
				<label for="cdv_video_type"><strong><?php esc_html_e( 'Tipo Video', 'cronaca-di-viterbo' ); ?></strong></label><br>
				<select name="cdv_video_type" id="cdv_video_type" class="widefat">
					<option value="upload" <?php selected( $video_type, 'upload' ); ?>><?php esc_html_e( 'Upload File', 'cronaca-di-viterbo' ); ?></option>
					<option value="youtube" <?php selected( $video_type, 'youtube' ); ?>><?php esc_html_e( 'YouTube', 'cronaca-di-viterbo' ); ?></option>
					<option value="vimeo" <?php selected( $video_type, 'vimeo' ); ?>><?php esc_html_e( 'Vimeo', 'cronaca-di-viterbo' ); ?></option>
					<option value="url" <?php selected( $video_type, 'url' ); ?>><?php esc_html_e( 'URL Esterno', 'cronaca-di-viterbo' ); ?></option>
				</select>
			</p>

			<p>
				<label for="cdv_video_url"><strong><?php esc_html_e( 'URL Video', 'cronaca-di-viterbo' ); ?></strong></label><br>
				<input type="text" name="cdv_video_url" id="cdv_video_url" value="<?php echo esc_url( $video_url ); ?>" class="widefat" placeholder="https://...">
				<small><?php esc_html_e( 'Incolla URL YouTube/Vimeo o URL diretto al file video', 'cronaca-di-viterbo' ); ?></small>
			</p>

			<p>
				<button type="button" class="button button-primary cdv-upload-video-btn">
					<span class="dashicons dashicons-upload"></span> <?php esc_html_e( 'Carica Video dalla Libreria', 'cronaca-di-viterbo' ); ?>
				</button>
			</p>

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

		// Salva meta
		$fields = array(
			'cdv_video_url'      => 'esc_url_raw',
			'cdv_video_type'     => 'sanitize_text_field',
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
