<?php
/**
 * Custom Post Type: Articolo
 *
 * @package FPNewspaper
 */

namespace FPNewspaper\PostTypes;

defined('ABSPATH') || exit;

/**
 * Gestisce il custom post type per gli articoli
 */
class Article {
    
    /**
     * Slug del post type
     */
    const POST_TYPE = 'fp_article';
    
    /**
     * Registra il post type
     */
    public static function register() {
        add_action('init', [__CLASS__, 'register_post_type']);
        add_action('init', [__CLASS__, 'register_taxonomies']);
    }
    
    /**
     * Registra il custom post type
     */
    public static function register_post_type() {
        $labels = [
            'name'                  => _x('Articoli', 'Post type general name', 'fp-newspaper'),
            'singular_name'         => _x('Articolo', 'Post type singular name', 'fp-newspaper'),
            'menu_name'             => _x('Articoli', 'Admin Menu text', 'fp-newspaper'),
            'name_admin_bar'        => _x('Articolo', 'Add New on Toolbar', 'fp-newspaper'),
            'add_new'               => __('Aggiungi Nuovo', 'fp-newspaper'),
            'add_new_item'          => __('Aggiungi Nuovo Articolo', 'fp-newspaper'),
            'new_item'              => __('Nuovo Articolo', 'fp-newspaper'),
            'edit_item'             => __('Modifica Articolo', 'fp-newspaper'),
            'view_item'             => __('Visualizza Articolo', 'fp-newspaper'),
            'all_items'             => __('Tutti gli Articoli', 'fp-newspaper'),
            'search_items'          => __('Cerca Articoli', 'fp-newspaper'),
            'parent_item_colon'     => __('Articoli Padre:', 'fp-newspaper'),
            'not_found'             => __('Nessun articolo trovato.', 'fp-newspaper'),
            'not_found_in_trash'    => __('Nessun articolo nel cestino.', 'fp-newspaper'),
            'featured_image'        => _x('Immagine in evidenza', 'Overrides the "Featured Image" phrase', 'fp-newspaper'),
            'set_featured_image'    => _x('Imposta immagine in evidenza', 'Overrides the "Set featured image" phrase', 'fp-newspaper'),
            'remove_featured_image' => _x('Rimuovi immagine in evidenza', 'Overrides the "Remove featured image" phrase', 'fp-newspaper'),
            'use_featured_image'    => _x('Usa come immagine in evidenza', 'Overrides the "Use as featured image" phrase', 'fp-newspaper'),
            'archives'              => _x('Archivio Articoli', 'The post type archive label', 'fp-newspaper'),
            'insert_into_item'      => _x('Inserisci nell\'articolo', 'Overrides the "Insert into post"/"Insert into page" phrase', 'fp-newspaper'),
            'uploaded_to_this_item' => _x('Caricato in questo articolo', 'Overrides the "Uploaded to this post"/"Uploaded to this page" phrase', 'fp-newspaper'),
            'filter_items_list'     => _x('Filtra lista articoli', 'Screen reader text', 'fp-newspaper'),
            'items_list_navigation' => _x('Navigazione lista articoli', 'Screen reader text', 'fp-newspaper'),
            'items_list'            => _x('Lista articoli', 'Screen reader text', 'fp-newspaper'),
        ];
        
        $args = [
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => ['slug' => 'articoli'],
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => 5,
            'menu_icon'          => 'dashicons-media-document',
            'supports'           => ['title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments', 'revisions', 'custom-fields'],
            'show_in_rest'       => true,
            'rest_base'          => 'articles',
            'rest_controller_class' => 'WP_REST_Posts_Controller',
        ];
        
        register_post_type(self::POST_TYPE, $args);
    }
    
    /**
     * Registra le tassonomie
     */
    public static function register_taxonomies() {
        // Categoria Articolo
        $category_labels = [
            'name'              => _x('Categorie', 'taxonomy general name', 'fp-newspaper'),
            'singular_name'     => _x('Categoria', 'taxonomy singular name', 'fp-newspaper'),
            'search_items'      => __('Cerca Categorie', 'fp-newspaper'),
            'all_items'         => __('Tutte le Categorie', 'fp-newspaper'),
            'parent_item'       => __('Categoria Padre', 'fp-newspaper'),
            'parent_item_colon' => __('Categoria Padre:', 'fp-newspaper'),
            'edit_item'         => __('Modifica Categoria', 'fp-newspaper'),
            'update_item'       => __('Aggiorna Categoria', 'fp-newspaper'),
            'add_new_item'      => __('Aggiungi Nuova Categoria', 'fp-newspaper'),
            'new_item_name'     => __('Nome Nuova Categoria', 'fp-newspaper'),
            'menu_name'         => __('Categorie', 'fp-newspaper'),
        ];
        
        register_taxonomy('fp_article_category', [self::POST_TYPE], [
            'hierarchical'      => true,
            'labels'            => $category_labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => ['slug' => 'categoria-articoli'],
            'show_in_rest'      => true,
        ]);
        
        // Tag Articolo
        $tag_labels = [
            'name'                       => _x('Tag', 'taxonomy general name', 'fp-newspaper'),
            'singular_name'              => _x('Tag', 'taxonomy singular name', 'fp-newspaper'),
            'search_items'               => __('Cerca Tag', 'fp-newspaper'),
            'popular_items'              => __('Tag Popolari', 'fp-newspaper'),
            'all_items'                  => __('Tutti i Tag', 'fp-newspaper'),
            'edit_item'                  => __('Modifica Tag', 'fp-newspaper'),
            'update_item'                => __('Aggiorna Tag', 'fp-newspaper'),
            'add_new_item'               => __('Aggiungi Nuovo Tag', 'fp-newspaper'),
            'new_item_name'              => __('Nome Nuovo Tag', 'fp-newspaper'),
            'separate_items_with_commas' => __('Separa i tag con virgole', 'fp-newspaper'),
            'add_or_remove_items'        => __('Aggiungi o rimuovi tag', 'fp-newspaper'),
            'choose_from_most_used'      => __('Scegli dai tag più usati', 'fp-newspaper'),
            'not_found'                  => __('Nessun tag trovato.', 'fp-newspaper'),
            'menu_name'                  => __('Tag', 'fp-newspaper'),
        ];
        
        register_taxonomy('fp_article_tag', [self::POST_TYPE], [
            'hierarchical'      => false,
            'labels'            => $tag_labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => ['slug' => 'tag-articoli'],
            'show_in_rest'      => true,
        ]);
    }
}

