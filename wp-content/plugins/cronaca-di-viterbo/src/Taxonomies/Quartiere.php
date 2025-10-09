<?php
/**
 * Tassonomia Quartiere (gerarchica).
 *
 * @package CdV\Taxonomies
 */

namespace CdV\Taxonomies;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Classe per la tassonomia Quartiere.
 */
class Quartiere {

	/**
	 * Costruttore.
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'register' ] );
	}

	/**
	 * Registra la tassonomia Quartiere.
	 */
	public function register() {
		$labels = [
			'name'                       => __( 'Quartieri', 'cronaca-di-viterbo' ),
			'singular_name'              => __( 'Quartiere', 'cronaca-di-viterbo' ),
			'menu_name'                  => __( 'Quartieri', 'cronaca-di-viterbo' ),
			'all_items'                  => __( 'Tutti i Quartieri', 'cronaca-di-viterbo' ),
			'parent_item'                => __( 'Quartiere Genitore', 'cronaca-di-viterbo' ),
			'parent_item_colon'          => __( 'Quartiere Genitore:', 'cronaca-di-viterbo' ),
			'new_item_name'              => __( 'Nuovo Quartiere', 'cronaca-di-viterbo' ),
			'add_new_item'               => __( 'Aggiungi Nuovo Quartiere', 'cronaca-di-viterbo' ),
			'edit_item'                  => __( 'Modifica Quartiere', 'cronaca-di-viterbo' ),
			'update_item'                => __( 'Aggiorna Quartiere', 'cronaca-di-viterbo' ),
			'view_item'                  => __( 'Visualizza Quartiere', 'cronaca-di-viterbo' ),
			'separate_items_with_commas' => __( 'Separa i quartieri con virgole', 'cronaca-di-viterbo' ),
			'add_or_remove_items'        => __( 'Aggiungi o rimuovi quartieri', 'cronaca-di-viterbo' ),
			'choose_from_most_used'      => __( 'Scegli dai più usati', 'cronaca-di-viterbo' ),
			'popular_items'              => __( 'Quartieri Popolari', 'cronaca-di-viterbo' ),
			'search_items'               => __( 'Cerca Quartieri', 'cronaca-di-viterbo' ),
			'not_found'                  => __( 'Nessun quartiere trovato', 'cronaca-di-viterbo' ),
		];

		$args = [
			'labels'            => $labels,
			'hierarchical'      => true, // Gerarchica come categories
			'public'            => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_nav_menus' => true,
			'show_tagcloud'     => false,
			'show_in_rest'      => false,
			'rewrite'           => [ 'slug' => 'quartiere' ],
		];

		register_taxonomy(
			'cdv_quartiere',
			[ 'cdv_dossier', 'cdv_proposta', 'cdv_evento' ],
			$args
		);
	}
}
