<?php
/**
 * Gutenberg Blocks Integration
 *
 * @package CdV
 * @subpackage Gutenberg
 * @since 1.6.0
 */

namespace CdV\Gutenberg;

/**
 * Class Blocks
 */
class Blocks {
	/**
	 * Initialize
	 */
	public static function init(): void {
		add_action( 'init', array( self::class, 'register_blocks' ) );
		add_action( 'enqueue_block_editor_assets', array( self::class, 'enqueue_block_assets' ) );
	}

	/**
	 * Register blocks
	 */
	public static function register_blocks(): void {
		// Block: Proposte List
		register_block_type(
			'cdv/proposte-list',
			array(
				'render_callback' => array( self::class, 'render_proposte_block' ),
				'attributes'      => array(
					'limit'     => array(
						'type'    => 'number',
						'default' => 5,
					),
					'quartiere' => array(
						'type'    => 'string',
						'default' => '',
					),
					'orderby'   => array(
						'type'    => 'string',
						'default' => 'date',
					),
				),
			)
		);

		// Block: Petizioni List
		register_block_type(
			'cdv/petizioni-list',
			array(
				'render_callback' => array( self::class, 'render_petizioni_block' ),
				'attributes'      => array(
					'limit'  => array(
						'type'    => 'number',
						'default' => 5,
					),
					'status' => array(
						'type'    => 'string',
						'default' => 'aperte',
					),
				),
			)
		);

		// Block: Dashboard Stats
		register_block_type(
			'cdv/dashboard',
			array(
				'render_callback' => array( self::class, 'render_dashboard_block' ),
				'attributes'      => array(
					'periodo' => array(
						'type'    => 'number',
						'default' => 30,
					),
				),
			)
		);

		// Block: User Profile
		register_block_type(
			'cdv/user-profile',
			array(
				'render_callback' => array( self::class, 'render_profile_block' ),
				'attributes'      => array(
					'userId' => array(
						'type'    => 'number',
						'default' => 0,
					),
				),
			)
		);

		// Block: Mappa
		register_block_type(
			'cdv/mappa',
			array(
				'render_callback' => array( self::class, 'render_mappa_block' ),
				'attributes'      => array(
					'tipo'   => array(
						'type'    => 'string',
						'default' => 'proposte',
					),
					'height' => array(
						'type'    => 'string',
						'default' => '500px',
					),
				),
			)
		);
	}

	/**
	 * Enqueue block assets
	 */
	public static function enqueue_block_assets(): void {
		wp_enqueue_script(
			'cdv-blocks',
			CDV_PLUGIN_URL . 'assets/js/blocks.js',
			array( 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components' ),
			CDV_VERSION,
			true
		);

		wp_localize_script(
			'cdv-blocks',
			'cdvBlocks',
			array(
				'quartieri' => self::get_quartieri_options(),
				'tematiche' => self::get_tematiche_options(),
			)
		);
	}

	/**
	 * Render proposte block
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public static function render_proposte_block( $attributes ): string {
		$atts = array(
			'limit'     => absint( $attributes['limit'] ?? 5 ),
			'quartiere' => sanitize_text_field( $attributes['quartiere'] ?? '' ),
			'orderby'   => sanitize_text_field( $attributes['orderby'] ?? 'date' ),
		);

		return do_shortcode( sprintf(
			'[cdv_proposte limit="%d" quartiere="%s" orderby="%s"]',
			$atts['limit'],
			esc_attr( $atts['quartiere'] ),
			esc_attr( $atts['orderby'] )
		) );
	}

	/**
	 * Render petizioni block
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public static function render_petizioni_block( $attributes ): string {
		$atts = array(
			'limit'  => absint( $attributes['limit'] ?? 5 ),
			'status' => sanitize_text_field( $attributes['status'] ?? 'aperte' ),
		);

		return do_shortcode( sprintf(
			'[cdv_petizioni limit="%d" status="%s"]',
			$atts['limit'],
			esc_attr( $atts['status'] )
		) );
	}

	/**
	 * Render dashboard block
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public static function render_dashboard_block( $attributes ): string {
		$periodo = absint( $attributes['periodo'] ?? 30 );
		return do_shortcode( sprintf( '[cdv_dashboard periodo="%d"]', $periodo ) );
	}

	/**
	 * Render profile block
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public static function render_profile_block( $attributes ): string {
		$user_id = absint( $attributes['userId'] ?? 0 );
		return do_shortcode( sprintf( '[cdv_user_profile user_id="%d"]', $user_id ) );
	}

	/**
	 * Render mappa block
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public static function render_mappa_block( $attributes ): string {
		$atts = array(
			'tipo'   => sanitize_text_field( $attributes['tipo'] ?? 'proposte' ),
			'height' => sanitize_text_field( $attributes['height'] ?? '500px' ),
		);

		return do_shortcode( sprintf(
			'[cdv_mappa tipo="%s" height="%s"]',
			esc_attr( $atts['tipo'] ),
			esc_attr( $atts['height'] )
		) );
	}

	/**
	 * Get quartieri options
	 *
	 * @return array
	 */
	private static function get_quartieri_options(): array {
		$terms = get_terms( array( 'taxonomy' => 'cdv_quartiere', 'hide_empty' => false ) );
		$options = array();

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return $options;
		}

		foreach ( $terms as $term ) {
			$options[] = array(
				'label' => $term->name,
				'value' => $term->slug,
			);
		}

		return $options;
	}

	/**
	 * Get tematiche options
	 *
	 * @return array
	 */
	private static function get_tematiche_options(): array {
		$terms = get_terms( array( 'taxonomy' => 'cdv_tematica', 'hide_empty' => false ) );
		$options = array();

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return $options;
		}

		foreach ( $terms as $term ) {
			$options[] = array(
				'label' => $term->name,
				'value' => $term->slug,
			);
		}

		return $options;
	}
}
