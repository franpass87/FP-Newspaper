<?php
/**
 * CPT Dossier (Inchieste).
 *
 * @package CdV\PostTypes
 */

namespace CdV\PostTypes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Classe per il Custom Post Type Dossier.
 */
class Dossier {

	/**
	 * Costruttore.
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'register' ] );
	}

	/**
	 * Registra il CPT Dossier.
	 */
	public function register() {
		$labels = [
			'name'                  => __( 'Dossier', 'cronaca-di-viterbo' ),
			'singular_name'         => __( 'Dossier', 'cronaca-di-viterbo' ),
			'menu_name'             => __( 'Dossier', 'cronaca-di-viterbo' ),
			'add_new'               => __( 'Aggiungi nuovo', 'cronaca-di-viterbo' ),
			'add_new_item'          => __( 'Aggiungi nuovo Dossier', 'cronaca-di-viterbo' ),
			'edit_item'             => __( 'Modifica Dossier', 'cronaca-di-viterbo' ),
			'new_item'              => __( 'Nuovo Dossier', 'cronaca-di-viterbo' ),
			'view_item'             => __( 'Visualizza Dossier', 'cronaca-di-viterbo' ),
			'search_items'          => __( 'Cerca Dossier', 'cronaca-di-viterbo' ),
			'not_found'             => __( 'Nessun dossier trovato', 'cronaca-di-viterbo' ),
			'not_found_in_trash'    => __( 'Nessun dossier nel cestino', 'cronaca-di-viterbo' ),
			'all_items'             => __( 'Tutti i Dossier', 'cronaca-di-viterbo' ),
		];

		$args = [
			'labels'              => $labels,
			'public'              => true,
			'show_in_rest'        => false, // No Gutenberg
			'publicly_queryable'  => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'query_var'           => true,
			'rewrite'             => [ 'slug' => 'dossier' ],
			'capability_type'     => 'post',
			'has_archive'         => true,
			'hierarchical'        => false,
			'menu_position'       => 5,
			'menu_icon'           => 'dashicons-portfolio',
			'supports'            => [ 'title', 'editor', 'thumbnail', 'excerpt', 'author', 'comments' ],
			'taxonomies'          => [ 'cdv_quartiere', 'cdv_tematica' ],
		];

		register_post_type( 'cdv_dossier', $args );
	}
}
