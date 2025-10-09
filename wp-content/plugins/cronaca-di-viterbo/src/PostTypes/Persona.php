<?php
/**
 * CPT Persona (Ambasciatori civici/redazione).
 *
 * @package CdV\PostTypes
 */

namespace CdV\PostTypes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Classe per il Custom Post Type Persona.
 */
class Persona {

	/**
	 * Costruttore.
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'register' ] );
		add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ] );
		add_action( 'save_post_cdv_persona', [ $this, 'save_meta' ], 10, 2 );
	}

	/**
	 * Registra il CPT Persona.
	 */
	public function register() {
		$labels = [
			'name'                  => __( 'Persone', 'cronaca-di-viterbo' ),
			'singular_name'         => __( 'Persona', 'cronaca-di-viterbo' ),
			'menu_name'             => __( 'Persone', 'cronaca-di-viterbo' ),
			'add_new'               => __( 'Aggiungi nuova', 'cronaca-di-viterbo' ),
			'add_new_item'          => __( 'Aggiungi nuova Persona', 'cronaca-di-viterbo' ),
			'edit_item'             => __( 'Modifica Persona', 'cronaca-di-viterbo' ),
			'new_item'              => __( 'Nuova Persona', 'cronaca-di-viterbo' ),
			'view_item'             => __( 'Visualizza Persona', 'cronaca-di-viterbo' ),
			'search_items'          => __( 'Cerca Persone', 'cronaca-di-viterbo' ),
			'not_found'             => __( 'Nessuna persona trovata', 'cronaca-di-viterbo' ),
			'not_found_in_trash'    => __( 'Nessuna persona nel cestino', 'cronaca-di-viterbo' ),
			'all_items'             => __( 'Tutte le Persone', 'cronaca-di-viterbo' ),
		];

		$args = [
			'labels'              => $labels,
			'public'              => true,
			'show_in_rest'        => false, // No Gutenberg
			'publicly_queryable'  => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'query_var'           => true,
			'rewrite'             => [ 'slug' => 'persone' ],
			'capability_type'     => 'post',
			'has_archive'         => true,
			'hierarchical'        => false,
			'menu_position'       => 8,
			'menu_icon'           => 'dashicons-id',
			'supports'            => [ 'title', 'editor', 'thumbnail', 'excerpt' ],
		];

		register_post_type( 'cdv_persona', $args );
	}

	/**
	 * Aggiunge meta boxes.
	 */
	public function add_meta_boxes() {
		add_meta_box(
			'cdv_persona_meta',
			__( 'Informazioni Persona', 'cronaca-di-viterbo' ),
			[ $this, 'render_meta_box' ],
			'cdv_persona',
			'normal',
			'high'
		);
	}

	/**
	 * Render meta box.
	 *
	 * @param \WP_Post $post Post object.
	 */
	public function render_meta_box( $post ) {
		$ruolo     = get_post_meta( $post->ID, '_cdv_persona_ruolo', true );
		$email     = get_post_meta( $post->ID, '_cdv_persona_email', true );
		$telefono  = get_post_meta( $post->ID, '_cdv_persona_telefono', true );
		$social    = get_post_meta( $post->ID, '_cdv_persona_social', true );

		wp_nonce_field( 'cdv_persona_meta', 'cdv_persona_meta_nonce' );
		?>
		<p>
			<label><strong><?php esc_html_e( 'Ruolo:', 'cronaca-di-viterbo' ); ?></strong></label><br>
			<input type="text" name="cdv_persona_ruolo" value="<?php echo esc_attr( $ruolo ); ?>" style="width: 100%;">
		</p>
		<p>
			<label><strong><?php esc_html_e( 'Email:', 'cronaca-di-viterbo' ); ?></strong></label><br>
			<input type="email" name="cdv_persona_email" value="<?php echo esc_attr( $email ); ?>" style="width: 100%;">
		</p>
		<p>
			<label><strong><?php esc_html_e( 'Telefono:', 'cronaca-di-viterbo' ); ?></strong></label><br>
			<input type="tel" name="cdv_persona_telefono" value="<?php echo esc_attr( $telefono ); ?>" style="width: 100%;">
		</p>
		<p>
			<label><strong><?php esc_html_e( 'Social (URL):', 'cronaca-di-viterbo' ); ?></strong></label><br>
			<input type="url" name="cdv_persona_social" value="<?php echo esc_attr( $social ); ?>" style="width: 100%;">
		</p>
		<?php
	}

	/**
	 * Salva i meta.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 */
	public function save_meta( $post_id, $post ) {
		// Verifica nonce
		if ( ! isset( $_POST['cdv_persona_meta_nonce'] ) || ! wp_verify_nonce( $_POST['cdv_persona_meta_nonce'], 'cdv_persona_meta' ) ) {
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
		if ( isset( $_POST['cdv_persona_ruolo'] ) ) {
			update_post_meta( $post_id, '_cdv_persona_ruolo', sanitize_text_field( $_POST['cdv_persona_ruolo'] ) );
		}
		if ( isset( $_POST['cdv_persona_email'] ) ) {
			update_post_meta( $post_id, '_cdv_persona_email', sanitize_email( $_POST['cdv_persona_email'] ) );
		}
		if ( isset( $_POST['cdv_persona_telefono'] ) ) {
			update_post_meta( $post_id, '_cdv_persona_telefono', sanitize_text_field( $_POST['cdv_persona_telefono'] ) );
		}
		if ( isset( $_POST['cdv_persona_social'] ) ) {
			update_post_meta( $post_id, '_cdv_persona_social', esc_url_raw( $_POST['cdv_persona_social'] ) );
		}
	}
}
