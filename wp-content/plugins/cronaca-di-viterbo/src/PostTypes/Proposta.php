<?php
/**
 * CPT Proposta (Idee dei cittadini).
 *
 * @package CdV\PostTypes
 */

namespace CdV\PostTypes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Classe per il Custom Post Type Proposta.
 */
class Proposta {

	/**
	 * Costruttore.
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'register' ] );
		add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ] );
		add_action( 'save_post_cdv_proposta', [ $this, 'save_meta' ], 10, 2 );
	}

	/**
	 * Registra il CPT Proposta.
	 */
	public function register() {
		$labels = [
			'name'                  => __( 'Proposte', 'cronaca-di-viterbo' ),
			'singular_name'         => __( 'Proposta', 'cronaca-di-viterbo' ),
			'menu_name'             => __( 'Proposte', 'cronaca-di-viterbo' ),
			'add_new'               => __( 'Aggiungi nuova', 'cronaca-di-viterbo' ),
			'add_new_item'          => __( 'Aggiungi nuova Proposta', 'cronaca-di-viterbo' ),
			'edit_item'             => __( 'Modifica Proposta', 'cronaca-di-viterbo' ),
			'new_item'              => __( 'Nuova Proposta', 'cronaca-di-viterbo' ),
			'view_item'             => __( 'Visualizza Proposta', 'cronaca-di-viterbo' ),
			'search_items'          => __( 'Cerca Proposte', 'cronaca-di-viterbo' ),
			'not_found'             => __( 'Nessuna proposta trovata', 'cronaca-di-viterbo' ),
			'not_found_in_trash'    => __( 'Nessuna proposta nel cestino', 'cronaca-di-viterbo' ),
			'all_items'             => __( 'Tutte le Proposte', 'cronaca-di-viterbo' ),
		];

		$args = [
			'labels'              => $labels,
			'public'              => true,
			'show_in_rest'        => false, // No Gutenberg
			'publicly_queryable'  => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'query_var'           => true,
			'rewrite'             => [ 'slug' => 'proposte' ],
			'capability_type'     => 'post',
			'has_archive'         => true,
			'hierarchical'        => false,
			'menu_position'       => 6,
			'menu_icon'           => 'dashicons-megaphone',
			'supports'            => [ 'title', 'editor', 'author', 'comments' ],
			'taxonomies'          => [ 'cdv_quartiere', 'cdv_tematica' ],
		];

		register_post_type( 'cdv_proposta', $args );
	}

	/**
	 * Aggiunge meta boxes.
	 */
	public function add_meta_boxes() {
		add_meta_box(
			'cdv_proposta_meta',
			__( 'Informazioni Proposta', 'cronaca-di-viterbo' ),
			[ $this, 'render_meta_box' ],
			'cdv_proposta',
			'side',
			'high'
		);
	}

	/**
	 * Render meta box.
	 *
	 * @param \WP_Post $post Post object.
	 */
	public function render_meta_box( $post ) {
		$votes = get_post_meta( $post->ID, '_cdv_votes', true ) ?: 0;
		wp_nonce_field( 'cdv_proposta_meta', 'cdv_proposta_meta_nonce' );
		?>
		<p>
			<strong><?php esc_html_e( 'Voti:', 'cronaca-di-viterbo' ); ?></strong><br>
			<input type="number" name="cdv_votes" value="<?php echo esc_attr( $votes ); ?>" min="0" style="width: 100%;">
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
		if ( ! isset( $_POST['cdv_proposta_meta_nonce'] ) || ! wp_verify_nonce( $_POST['cdv_proposta_meta_nonce'], 'cdv_proposta_meta' ) ) {
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

		// Salva voti
		if ( isset( $_POST['cdv_votes'] ) ) {
			$votes = absint( $_POST['cdv_votes'] );
			update_post_meta( $post_id, '_cdv_votes', $votes );
		}
	}
}
