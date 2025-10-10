<?php
/**
 * Custom Post Type: Galleria Foto
 *
 * Gestisce gallerie fotografiche per citizen journalism e reportage
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
 * Class GalleriaFoto
 */
class GalleriaFoto {
	/**
	 * Post type slug
	 */
	const POST_TYPE = 'cdv_galleria';

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
					'name'               => __( 'Gallerie Foto', 'cronaca-di-viterbo' ),
					'singular_name'      => __( 'Galleria Foto', 'cronaca-di-viterbo' ),
					'add_new'            => __( 'Aggiungi Galleria', 'cronaca-di-viterbo' ),
					'add_new_item'       => __( 'Aggiungi Nuova Galleria', 'cronaca-di-viterbo' ),
					'edit_item'          => __( 'Modifica Galleria', 'cronaca-di-viterbo' ),
					'new_item'           => __( 'Nuova Galleria', 'cronaca-di-viterbo' ),
					'view_item'          => __( 'Visualizza Galleria', 'cronaca-di-viterbo' ),
					'search_items'       => __( 'Cerca Gallerie', 'cronaca-di-viterbo' ),
					'not_found'          => __( 'Nessuna galleria trovata', 'cronaca-di-viterbo' ),
					'not_found_in_trash' => __( 'Nessuna galleria nel cestino', 'cronaca-di-viterbo' ),
					'all_items'          => __( 'Tutte le Gallerie', 'cronaca-di-viterbo' ),
				),
				'public'              => true,
				'publicly_queryable'  => true,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_rest'        => true,
				'has_archive'         => true,
				'rewrite'             => array( 'slug' => 'gallerie' ),
				'capability_type'     => 'post',
				'map_meta_cap'        => true,
				'supports'            => array( 'title', 'editor', 'author', 'thumbnail', 'comments', 'excerpt' ),
				'taxonomies'          => array( 'cdv_quartiere', 'cdv_tematica', 'post_tag' ),
				'menu_icon'           => 'dashicons-format-gallery',
				'menu_position'       => 8,
			)
		);
	}

	/**
	 * Add meta boxes
	 */
	public static function add_meta_boxes(): void {
		add_meta_box(
			'cdv_galleria_photos',
			__( 'Foto della Galleria', 'cronaca-di-viterbo' ),
			array( self::class, 'render_meta_box_photos' ),
			self::POST_TYPE,
			'normal',
			'high'
		);

		add_meta_box(
			'cdv_galleria_settings',
			__( 'Impostazioni Galleria', 'cronaca-di-viterbo' ),
			array( self::class, 'render_meta_box_settings' ),
			self::POST_TYPE,
			'side',
			'default'
		);
	}

	/**
	 * Render meta box foto
	 *
	 * @param \WP_Post $post Post object.
	 */
	public static function render_meta_box_photos( $post ): void {
		wp_nonce_field( 'cdv_galleria_meta', 'cdv_galleria_nonce' );

		$photo_ids = get_post_meta( $post->ID, '_cdv_gallery_photos', true );
		$photo_ids = $photo_ids ? explode( ',', $photo_ids ) : array();

		?>
		<div class="cdv-gallery-meta-box">
			<p>
				<button type="button" class="button button-primary cdv-add-gallery-photos">
					<span class="dashicons dashicons-images-alt2"></span> 
					<?php esc_html_e( 'Aggiungi/Modifica Foto', 'cronaca-di-viterbo' ); ?>
				</button>
				<span class="cdv-photo-count">
					<?php 
					/* translators: %d: number of photos */
					printf( esc_html__( 'Foto selezionate: %d', 'cronaca-di-viterbo' ), count( $photo_ids ) ); 
					?>
				</span>
			</p>

			<input type="hidden" name="cdv_gallery_photos" id="cdv_gallery_photos" value="<?php echo esc_attr( implode( ',', $photo_ids ) ); ?>">

			<div class="cdv-gallery-preview">
				<?php if ( ! empty( $photo_ids ) ) : ?>
					<ul class="cdv-gallery-list">
						<?php foreach ( $photo_ids as $photo_id ) : ?>
							<?php 
							$image_url = wp_get_attachment_image_url( $photo_id, 'thumbnail' );
							$image_caption = wp_get_attachment_caption( $photo_id );
							if ( $image_url ) :
							?>
							<li class="cdv-gallery-item" data-id="<?php echo esc_attr( $photo_id ); ?>">
								<img src="<?php echo esc_url( $image_url ); ?>" alt="">
								<div class="cdv-gallery-item-actions">
									<button type="button" class="cdv-remove-photo" title="<?php esc_attr_e( 'Rimuovi', 'cronaca-di-viterbo' ); ?>">
										<span class="dashicons dashicons-no-alt"></span>
									</button>
								</div>
								<?php if ( $image_caption ) : ?>
									<div class="cdv-gallery-item-caption"><?php echo esc_html( $image_caption ); ?></div>
								<?php endif; ?>
							</li>
							<?php endif; ?>
						<?php endforeach; ?>
					</ul>
				<?php else : ?>
					<p class="description">
						<?php esc_html_e( 'Nessuna foto aggiunta. Clicca il pulsante sopra per selezionare le foto.', 'cronaca-di-viterbo' ); ?>
					</p>
				<?php endif; ?>
			</div>
		</div>

		<style>
			.cdv-gallery-list {
				display: grid;
				grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
				gap: 15px;
				margin: 20px 0;
				padding: 0;
				list-style: none;
			}
			.cdv-gallery-item {
				position: relative;
				border: 2px solid #ddd;
				border-radius: 4px;
				overflow: hidden;
				cursor: move;
			}
			.cdv-gallery-item img {
				width: 100%;
				height: 120px;
				object-fit: cover;
				display: block;
			}
			.cdv-gallery-item-actions {
				position: absolute;
				top: 5px;
				right: 5px;
			}
			.cdv-remove-photo {
				background: rgba(255,0,0,0.8);
				color: white;
				border: none;
				border-radius: 3px;
				padding: 2px;
				cursor: pointer;
				line-height: 1;
			}
			.cdv-remove-photo:hover {
				background: rgba(255,0,0,1);
			}
			.cdv-gallery-item-caption {
				padding: 5px;
				font-size: 11px;
				background: #f5f5f5;
				text-align: center;
				overflow: hidden;
				text-overflow: ellipsis;
				white-space: nowrap;
			}
			.cdv-photo-count {
				margin-left: 10px;
				font-weight: bold;
				color: #0073aa;
			}
		</style>
		<?php
	}

	/**
	 * Render meta box impostazioni
	 *
	 * @param \WP_Post $post Post object.
	 */
	public static function render_meta_box_settings( $post ): void {
		$layout = get_post_meta( $post->ID, '_cdv_gallery_layout', true ) ?: 'grid';
		$columns = get_post_meta( $post->ID, '_cdv_gallery_columns', true ) ?: 3;
		$lightbox = get_post_meta( $post->ID, '_cdv_gallery_lightbox', true ) !== '0';
		$photographer = get_post_meta( $post->ID, '_cdv_photographer', true );
		$location = get_post_meta( $post->ID, '_cdv_photo_location', true );
		$date_taken = get_post_meta( $post->ID, '_cdv_date_taken', true );

		?>
		<div class="cdv-gallery-settings">
			<p>
				<label for="cdv_gallery_layout"><strong><?php esc_html_e( 'Layout', 'cronaca-di-viterbo' ); ?></strong></label><br>
				<select name="cdv_gallery_layout" id="cdv_gallery_layout" class="widefat">
					<option value="grid" <?php selected( $layout, 'grid' ); ?>><?php esc_html_e( 'Griglia', 'cronaca-di-viterbo' ); ?></option>
					<option value="masonry" <?php selected( $layout, 'masonry' ); ?>><?php esc_html_e( 'Masonry', 'cronaca-di-viterbo' ); ?></option>
					<option value="slider" <?php selected( $layout, 'slider' ); ?>><?php esc_html_e( 'Slider', 'cronaca-di-viterbo' ); ?></option>
					<option value="justified" <?php selected( $layout, 'justified' ); ?>><?php esc_html_e( 'Justified', 'cronaca-di-viterbo' ); ?></option>
				</select>
			</p>

			<p>
				<label for="cdv_gallery_columns"><strong><?php esc_html_e( 'Colonne', 'cronaca-di-viterbo' ); ?></strong></label><br>
				<input type="number" name="cdv_gallery_columns" id="cdv_gallery_columns" value="<?php echo esc_attr( $columns ); ?>" min="1" max="6" class="small-text">
			</p>

			<p>
				<label>
					<input type="checkbox" name="cdv_gallery_lightbox" value="1" <?php checked( $lightbox ); ?>>
					<?php esc_html_e( 'Abilita Lightbox', 'cronaca-di-viterbo' ); ?>
				</label>
			</p>

			<hr>

			<p>
				<label for="cdv_photographer"><strong><?php esc_html_e( 'Fotografo', 'cronaca-di-viterbo' ); ?></strong></label><br>
				<input type="text" name="cdv_photographer" id="cdv_photographer" value="<?php echo esc_attr( $photographer ); ?>" class="widefat">
			</p>

			<p>
				<label for="cdv_photo_location"><strong><?php esc_html_e( 'Luogo', 'cronaca-di-viterbo' ); ?></strong></label><br>
				<input type="text" name="cdv_photo_location" id="cdv_photo_location" value="<?php echo esc_attr( $location ); ?>" class="widefat">
			</p>

			<p>
				<label for="cdv_date_taken"><strong><?php esc_html_e( 'Data Scatto', 'cronaca-di-viterbo' ); ?></strong></label><br>
				<input type="date" name="cdv_date_taken" id="cdv_date_taken" value="<?php echo esc_attr( $date_taken ); ?>" class="widefat">
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
		if ( ! isset( $_POST['cdv_galleria_nonce'] ) || 
		     ! wp_verify_nonce( $_POST['cdv_galleria_nonce'], 'cdv_galleria_meta' ) ) {
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

		// Salva foto IDs
		if ( isset( $_POST['cdv_gallery_photos'] ) ) {
			$photo_ids = sanitize_text_field( $_POST['cdv_gallery_photos'] );
			update_post_meta( $post_id, '_cdv_gallery_photos', $photo_ids );
		}

		// Salva settings
		$fields = array(
			'cdv_gallery_layout'  => 'sanitize_text_field',
			'cdv_gallery_columns' => 'absint',
			'cdv_photographer'    => 'sanitize_text_field',
			'cdv_photo_location'  => 'sanitize_text_field',
			'cdv_date_taken'      => 'sanitize_text_field',
		);

		foreach ( $fields as $field => $sanitize_callback ) {
			if ( isset( $_POST[ $field ] ) ) {
				$value = call_user_func( $sanitize_callback, $_POST[ $field ] );
				update_post_meta( $post_id, '_' . $field, $value );
			}
		}

		// Checkbox lightbox
		$lightbox = isset( $_POST['cdv_gallery_lightbox'] ) ? '1' : '0';
		update_post_meta( $post_id, '_cdv_gallery_lightbox', $lightbox );
	}
}
