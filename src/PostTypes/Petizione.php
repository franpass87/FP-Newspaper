<?php
/**
 * Custom Post Type: Petizione
 *
 * Gestisce le petizioni digitali con raccolta firme
 *
 * @package CdV
 * @subpackage PostTypes
 * @since 1.3.0
 */

namespace CdV\PostTypes;

/**
 * Class Petizione
 */
class Petizione {
	/**
	 * Post type slug
	 */
	const POST_TYPE = 'cdv_petizione';

	/**
	 * Register post type
	 */
	public static function register(): void {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'              => array(
					'name'               => __( 'Petizioni', 'cronaca-di-viterbo' ),
					'singular_name'      => __( 'Petizione', 'cronaca-di-viterbo' ),
					'add_new'            => __( 'Aggiungi Petizione', 'cronaca-di-viterbo' ),
					'add_new_item'       => __( 'Aggiungi Nuova Petizione', 'cronaca-di-viterbo' ),
					'edit_item'          => __( 'Modifica Petizione', 'cronaca-di-viterbo' ),
					'new_item'           => __( 'Nuova Petizione', 'cronaca-di-viterbo' ),
					'view_item'          => __( 'Visualizza Petizione', 'cronaca-di-viterbo' ),
					'search_items'       => __( 'Cerca Petizioni', 'cronaca-di-viterbo' ),
					'not_found'          => __( 'Nessuna petizione trovata', 'cronaca-di-viterbo' ),
					'not_found_in_trash' => __( 'Nessuna petizione nel cestino', 'cronaca-di-viterbo' ),
				),
				'public'              => true,
				'publicly_queryable'  => true,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_rest'        => true,
				'has_archive'         => true,
				'rewrite'             => array( 'slug' => 'petizioni' ),
				'capability_type'     => array( 'cdv_petizione', 'cdv_petizioni' ),
				'map_meta_cap'        => true,
				'supports'            => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments' ),
				'taxonomies'          => array( 'cdv_quartiere', 'cdv_tematica' ),
				'menu_icon'           => 'dashicons-edit-page',
			)
		);
	}

	/**
	 * Add meta boxes
	 */
	public static function add_meta_boxes(): void {
		add_meta_box(
			'cdv_petizione_details',
			__( 'Dettagli Petizione', 'cronaca-di-viterbo' ),
			array( self::class, 'render_meta_box_details' ),
			self::POST_TYPE,
			'side',
			'high'
		);
	}

	/**
	 * Render meta box dettagli
	 *
	 * @param \WP_Post $post Post object.
	 */
	public static function render_meta_box_details( $post ): void {
		wp_nonce_field( 'cdv_petizione_meta', 'cdv_petizione_nonce' );

		$soglia = get_post_meta( $post->ID, '_cdv_soglia_firme', true ) ?: 100;
		$deadline = get_post_meta( $post->ID, '_cdv_deadline', true );
		$firme = get_post_meta( $post->ID, '_cdv_firme_count', true ) ?: 0;
		$aperta = get_post_meta( $post->ID, '_cdv_aperta', true ) !== '0';

		?>
		<p>
			<label for="cdv_soglia_firme"><strong><?php esc_html_e( 'Soglia Firme Obiettivo', 'cronaca-di-viterbo' ); ?></strong></label><br>
			<input type="number" name="cdv_soglia_firme" id="cdv_soglia_firme" value="<?php echo esc_attr( $soglia ); ?>" class="widefat" min="1">
		</p>

		<p>
			<label for="cdv_deadline"><strong><?php esc_html_e( 'Scadenza', 'cronaca-di-viterbo' ); ?></strong></label><br>
			<input type="date" name="cdv_deadline" id="cdv_deadline" value="<?php echo esc_attr( $deadline ); ?>" class="widefat">
		</p>

		<p>
			<strong><?php esc_html_e( 'Firme Attuali', 'cronaca-di-viterbo' ); ?>:</strong> 
			<span class="cdv-firme-count"><?php echo esc_html( number_format_i18n( $firme ) ); ?></span>
		</p>

		<p>
			<strong><?php esc_html_e( 'Progressione', 'cronaca-di-viterbo' ); ?>:</strong><br>
			<progress value="<?php echo esc_attr( $firme ); ?>" max="<?php echo esc_attr( $soglia ); ?>" style="width: 100%;"></progress><br>
			<small><?php echo esc_html( $soglia > 0 ? round( ( $firme / $soglia ) * 100, 1 ) : 0 ); ?>%</small>
		</p>

		<p>
			<label>
				<input type="checkbox" name="cdv_aperta" value="1" <?php checked( $aperta ); ?>>
				<?php esc_html_e( 'Petizione aperta alle firme', 'cronaca-di-viterbo' ); ?>
			</label>
		</p>

		<?php if ( $firme > 0 ) : ?>
			<p>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=cdv-firme&petizione_id=' . $post->ID ) ); ?>" class="button">
					<?php esc_html_e( 'Visualizza Firmatari', 'cronaca-di-viterbo' ); ?>
				</a>
			</p>
		<?php endif; ?>
		<?php
	}

	/**
	 * Save meta box data
	 *
	 * @param int $post_id Post ID.
	 */
	public static function save_meta_box( int $post_id ): void {
		if ( ! isset( $_POST['cdv_petizione_nonce'] ) || ! wp_verify_nonce( $_POST['cdv_petizione_nonce'], 'cdv_petizione_meta' ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( isset( $_POST['cdv_soglia_firme'] ) ) {
			update_post_meta( $post_id, '_cdv_soglia_firme', intval( $_POST['cdv_soglia_firme'] ) );
		}

		if ( isset( $_POST['cdv_deadline'] ) ) {
			update_post_meta( $post_id, '_cdv_deadline', sanitize_text_field( $_POST['cdv_deadline'] ) );
		}

		$aperta = isset( $_POST['cdv_aperta'] ) ? '1' : '0';
		update_post_meta( $post_id, '_cdv_aperta', $aperta );
	}

	/**
	 * Create firme table
	 */
	public static function create_firme_table(): void {
		global $wpdb;
		$table_name = $wpdb->prefix . 'cdv_petizioni_firme';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			petizione_id bigint(20) UNSIGNED NOT NULL,
			user_id bigint(20) UNSIGNED DEFAULT 0,
			nome varchar(200) NOT NULL,
			cognome varchar(200) NOT NULL,
			email varchar(200) NOT NULL,
			comune varchar(200) DEFAULT '',
			motivazione text,
			privacy_accepted tinyint(1) NOT NULL DEFAULT 0,
			verified tinyint(1) NOT NULL DEFAULT 0,
			ip_address varchar(100) NOT NULL,
			user_agent text,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY petizione_id (petizione_id),
			KEY email (email),
			KEY user_id (user_id),
			UNIQUE KEY unique_signature (petizione_id, email)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}
}
