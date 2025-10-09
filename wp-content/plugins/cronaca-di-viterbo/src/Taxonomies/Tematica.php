<?php
/**
 * Tassonomia Tematica (flat).
 *
 * @package CdV\Taxonomies
 */

namespace CdV\Taxonomies;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Classe per la tassonomia Tematica.
 */
class Tematica {

	/**
	 * Costruttore.
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'register' ] );
	}

	/**
	 * Registra la tassonomia Tematica.
	 */
	public function register() {
		$labels = [
			'name'                       => __( 'Tematiche', 'cronaca-di-viterbo' ),
			'singular_name'              => __( 'Tematica', 'cronaca-di-viterbo' ),
			'menu_name'                  => __( 'Tematiche', 'cronaca-di-viterbo' ),
			'all_items'                  => __( 'Tutte le Tematiche', 'cronaca-di-viterbo' ),
			'new_item_name'              => __( 'Nuova Tematica', 'cronaca-di-viterbo' ),
			'add_new_item'               => __( 'Aggiungi Nuova Tematica', 'cronaca-di-viterbo' ),
			'edit_item'                  => __( 'Modifica Tematica', 'cronaca-di-viterbo' ),
			'update_item'                => __( 'Aggiorna Tematica', 'cronaca-di-viterbo' ),
			'view_item'                  => __( 'Visualizza Tematica', 'cronaca-di-viterbo' ),
			'separate_items_with_commas' => __( 'Separa le tematiche con virgole', 'cronaca-di-viterbo' ),
			'add_or_remove_items'        => __( 'Aggiungi o rimuovi tematiche', 'cronaca-di-viterbo' ),
			'choose_from_most_used'      => __( 'Scegli dalle più usate', 'cronaca-di-viterbo' ),
			'popular_items'              => __( 'Tematiche Popolari', 'cronaca-di-viterbo' ),
			'search_items'               => __( 'Cerca Tematiche', 'cronaca-di-viterbo' ),
			'not_found'                  => __( 'Nessuna tematica trovata', 'cronaca-di-viterbo' ),
		];

		$args = [
			'labels'            => $labels,
			'hierarchical'      => false, // Flat come tags
			'public'            => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_nav_menus' => true,
			'show_tagcloud'     => true,
			'show_in_rest'      => false,
			'rewrite'           => [ 'slug' => 'tematica' ],
		];

		register_taxonomy(
			'cdv_tematica',
			[ 'cdv_dossier', 'cdv_proposta', 'cdv_evento' ],
			$args
		);
	}
}
