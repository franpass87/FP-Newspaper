<?php
/**
 * CPT Evento (Micro-eventi/riunioni/serate).
 *
 * @package CdV\PostTypes
 */

namespace CdV\PostTypes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Classe per il Custom Post Type Evento.
 */
class Evento {

	/**
	 * Costruttore.
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'register' ] );
		add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ] );
		add_action( 'save_post_cdv_evento', [ $this, 'save_meta' ], 10, 2 );
	}

	/**
	 * Registra il CPT Evento.
	 */
	public function register() {
		$labels = [
			'name'                  => __( 'Eventi', 'cronaca-di-viterbo' ),
			'singular_name'         => __( 'Evento', 'cronaca-di-viterbo' ),
			'menu_name'             => __( 'Eventi', 'cronaca-di-viterbo' ),
			'add_new'               => __( 'Aggiungi nuovo', 'cronaca-di-viterbo' ),
			'add_new_item'          => __( 'Aggiungi nuovo Evento', 'cronaca-di-viterbo' ),
			'edit_item'             => __( 'Modifica Evento', 'cronaca-di-viterbo' ),
			'new_item'              => __( 'Nuovo Evento', 'cronaca-di-viterbo' ),
			'view_item'             => __( 'Visualizza Evento', 'cronaca-di-viterbo' ),
			'search_items'          => __( 'Cerca Eventi', 'cronaca-di-viterbo' ),
			'not_found'             => __( 'Nessun evento trovato', 'cronaca-di-viterbo' ),
			'not_found_in_trash'    => __( 'Nessun evento nel cestino', 'cronaca-di-viterbo' ),
			'all_items'             => __( 'Tutti gli Eventi', 'cronaca-di-viterbo' ),
		];

		$args = [
			'labels'              => $labels,
			'public'              => true,
			'show_in_rest'        => false, // No Gutenberg
			'publicly_queryable'  => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'query_var'           => true,
			'rewrite'             => [ 'slug' => 'eventi' ],
			'capability_type'     => 'post',
			'has_archive'         => true,
			'hierarchical'        => false,
			'menu_position'       => 7,
			'menu_icon'           => 'dashicons-calendar-alt',
			'supports'            => [ 'title', 'editor', 'thumbnail', 'excerpt', 'author', 'comments' ],
			'taxonomies'          => [ 'cdv_quartiere', 'cdv_tematica' ],
		];

		register_post_type( 'cdv_evento', $args );
	}

	/**
	 * Aggiunge meta boxes.
	 */
	public function add_meta_boxes() {
		add_meta_box(
			'cdv_evento_meta',
			__( 'Dettagli Evento', 'cronaca-di-viterbo' ),
			[ $this, 'render_meta_box' ],
			'cdv_evento',
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
		$data      = get_post_meta( $post->ID, '_cdv_evento_data', true );
		$ora       = get_post_meta( $post->ID, '_cdv_evento_ora', true );
		$luogo     = get_post_meta( $post->ID, '_cdv_evento_luogo', true );
		$indirizzo = get_post_meta( $post->ID, '_cdv_evento_indirizzo', true );

		wp_nonce_field( 'cdv_evento_meta', 'cdv_evento_meta_nonce' );
		?>
		<p>
			<label><strong><?php esc_html_e( 'Data:', 'cronaca-di-viterbo' ); ?></strong></label><br>
			<input type="date" name="cdv_evento_data" value="<?php echo esc_attr( $data ); ?>" style="width: 100%;">
		</p>
		<p>
			<label><strong><?php esc_html_e( 'Ora:', 'cronaca-di-viterbo' ); ?></strong></label><br>
			<input type="time" name="cdv_evento_ora" value="<?php echo esc_attr( $ora ); ?>" style="width: 100%;">
		</p>
		<p>
			<label><strong><?php esc_html_e( 'Luogo:', 'cronaca-di-viterbo' ); ?></strong></label><br>
			<input type="text" name="cdv_evento_luogo" value="<?php echo esc_attr( $luogo ); ?>" style="width: 100%;">
		</p>
		<p>
			<label><strong><?php esc_html_e( 'Indirizzo:', 'cronaca-di-viterbo' ); ?></strong></label><br>
			<input type="text" name="cdv_evento_indirizzo" value="<?php echo esc_attr( $indirizzo ); ?>" style="width: 100%;">
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
		if ( ! isset( $_POST['cdv_evento_meta_nonce'] ) || ! wp_verify_nonce( $_POST['cdv_evento_meta_nonce'], 'cdv_evento_meta' ) ) {
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
		$fields = [ 'cdv_evento_data', 'cdv_evento_ora', 'cdv_evento_luogo', 'cdv_evento_indirizzo' ];
		foreach ( $fields as $field ) {
			if ( isset( $_POST[ $field ] ) ) {
				update_post_meta( $post_id, '_' . $field, sanitize_text_field( $_POST[ $field ] ) );
			}
		}
	}
}
