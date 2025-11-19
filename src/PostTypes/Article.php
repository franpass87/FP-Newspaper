<?php
/**
 * Estensioni per Post Type Nativo di WordPress
 *
 * @package FPNewspaper
 */

namespace FPNewspaper\PostTypes;

defined('ABSPATH') || exit;

/**
 * Estende il post type nativo 'post' con funzionalità FP Newspaper
 * 
 * NOTA: Non crea un CPT separato, usa il 'post' nativo di WordPress
 * per massima compatibilità con plugin (Yoast SEO, Rank Math, etc.)
 */
class Article {
    
    /**
     * Slug del post type (usa post nativo)
     */
    const POST_TYPE = 'post';
    
    /**
     * Meta key per identificare articoli gestiti da FP Newspaper (opzionale)
     */
    const META_KEY_MANAGED = '_fp_newspaper_managed';
    
    /**
     * Registra estensioni al post type nativo
     */
    public static function register() {
        add_action('init', [__CLASS__, 'add_post_type_support']);
        
        // Tassonomie extra (opzionale - vedi sotto)
        // add_action('init', [__CLASS__, 'register_extra_taxonomies']);
    }
    
    /**
     * Aggiungi supporto features ai post nativi
     */
    public static function add_post_type_support() {
        // Post type 'post' ha già supporto per:
        // - title, editor, author, thumbnail, excerpt, comments, revisions, custom-fields
        
        // Aggiungiamo solo se mancano
        add_post_type_support('post', 'excerpt');
        add_post_type_support('post', 'custom-fields');
        
        // Abilita REST API se non già attivo
        add_post_type_support('post', 'rest-api');
    }
    
    /**
     * Registra tassonomie EXTRA (opzionale)
     * 
     * IMPORTANTE: WordPress ha già 'category' e 'post_tag' nativi.
     * Usa quelli! Registra tassonomie custom SOLO se hai bisogno
     * di qualcosa in PIÙ (es: "Sezioni Giornale" oltre categorie).
     * 
     * Per la maggior parte dei casi, NON serve questo metodo.
     */
    public static function register_extra_taxonomies() {
        // ESEMPIO: Tassonomia "Sezioni Giornale" (oltre alle categorie normali)
        // Decommentare se necessario
        
        /*
        register_taxonomy('fp_sezione', ['post'], [
            'hierarchical'      => true,
            'labels'            => [
                'name'          => __('Sezioni Giornale', 'fp-newspaper'),
                'singular_name' => __('Sezione', 'fp-newspaper'),
                'menu_name'     => __('Sezioni', 'fp-newspaper'),
            ],
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => ['slug' => 'sezione'],
            'show_in_rest'      => true,
        ]);
        */
    }
    
    /**
     * Verifica se un post è gestito da FP Newspaper
     * 
     * @param int $post_id
     * @return bool
     */
    public static function is_fp_newspaper_post($post_id) {
        // OPZIONE A: Tutti i post sono gestiti (default)
        return get_post_type($post_id) === 'post';
        
        // OPZIONE B: Solo post con meta key (decommentare se serve)
        // return (bool) get_post_meta($post_id, self::META_KEY_MANAGED, true);
    }
    
    /**
     * Marca un post come gestito da FP Newspaper
     * 
     * @param int $post_id
     */
    public static function mark_as_managed($post_id) {
        update_post_meta($post_id, self::META_KEY_MANAGED, '1');
    }
    
    /**
     * Helper: Ottieni tutti i post FP Newspaper
     * 
     * @param array $args Additional WP_Query args
     * @return WP_Query
     */
    public static function get_articles($args = []) {
        $defaults = [
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'posts_per_page' => 10,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ];
        
        // Se vuoi solo post "gestiti", aggiungi meta_query
        // $defaults['meta_query'] = [
        //     [
        //         'key'   => self::META_KEY_MANAGED,
        //         'value' => '1',
        //     ]
        // ];
        
        $args = wp_parse_args($args, $defaults);
        
        return new \WP_Query($args);
    }
}

