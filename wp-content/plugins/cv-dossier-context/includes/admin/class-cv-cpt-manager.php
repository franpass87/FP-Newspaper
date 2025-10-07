<?php
/**
 * Custom Post Types Manager
 *
 * Gestisce la registrazione dei Custom Post Types del plugin.
 *
 * @package CV_Dossier_Context
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Classe per gestione Custom Post Types.
 */
class CV_CPT_Manager {
    
    /**
     * Inizializza il manager registrando gli hooks.
     */
    public function init() {
        add_action( 'init', [ $this, 'register_post_types' ] );
    }
    
    /**
     * Registra i custom post types.
     */
    public function register_post_types() {
        $this->register_dossier_cpt();
        $this->register_event_cpt();
    }
    
    /**
     * Registra il CPT Dossier.
     */
    private function register_dossier_cpt() {
        register_post_type( 'cv_dossier', [
            'label'         => __( 'Dossier', 'cv-dossier' ),
            'public'        => true,
            'show_in_rest'  => true,
            'menu_icon'     => 'dashicons-portfolio',
            'supports'      => [ 'title', 'editor', 'thumbnail', 'excerpt', 'author' ],
            'has_archive'   => true,
            'rewrite'       => [ 'slug' => 'dossier' ],
            'labels'        => $this->get_dossier_labels(),
        ]);
    }
    
    /**
     * Registra il CPT Eventi.
     */
    private function register_event_cpt() {
        register_post_type( 'cv_dossier_event', [
            'label'        => __( 'Eventi Dossier', 'cv-dossier' ),
            'public'       => false,
            'show_ui'      => true,
            'show_in_rest' => false,
            'menu_icon'    => 'dashicons-clock',
            'supports'     => [ 'title', 'editor' ],
            'labels'       => $this->get_event_labels(),
        ]);
    }
    
    /**
     * Ottiene le label per il CPT Dossier.
     *
     * @return array Label localizzate.
     */
    private function get_dossier_labels() {
        return [
            'name'                  => __( 'Dossier', 'cv-dossier' ),
            'singular_name'         => __( 'Dossier', 'cv-dossier' ),
            'menu_name'             => __( 'Dossier', 'cv-dossier' ),
            'add_new'               => __( 'Aggiungi nuovo', 'cv-dossier' ),
            'add_new_item'          => __( 'Aggiungi nuovo Dossier', 'cv-dossier' ),
            'edit_item'             => __( 'Modifica Dossier', 'cv-dossier' ),
            'new_item'              => __( 'Nuovo Dossier', 'cv-dossier' ),
            'view_item'             => __( 'Visualizza Dossier', 'cv-dossier' ),
            'search_items'          => __( 'Cerca Dossier', 'cv-dossier' ),
            'not_found'             => __( 'Nessun dossier trovato', 'cv-dossier' ),
            'not_found_in_trash'    => __( 'Nessun dossier nel cestino', 'cv-dossier' ),
            'all_items'             => __( 'Tutti i Dossier', 'cv-dossier' ),
        ];
    }
    
    /**
     * Ottiene le label per il CPT Eventi.
     *
     * @return array Label localizzate.
     */
    private function get_event_labels() {
        return [
            'name'                  => __( 'Eventi', 'cv-dossier' ),
            'singular_name'         => __( 'Evento', 'cv-dossier' ),
            'menu_name'             => __( 'Eventi Dossier', 'cv-dossier' ),
            'add_new'               => __( 'Aggiungi nuovo', 'cv-dossier' ),
            'add_new_item'          => __( 'Aggiungi nuovo Evento', 'cv-dossier' ),
            'edit_item'             => __( 'Modifica Evento', 'cv-dossier' ),
            'new_item'              => __( 'Nuovo Evento', 'cv-dossier' ),
            'view_item'             => __( 'Visualizza Evento', 'cv-dossier' ),
            'search_items'          => __( 'Cerca Eventi', 'cv-dossier' ),
            'not_found'             => __( 'Nessun evento trovato', 'cv-dossier' ),
            'not_found_in_trash'    => __( 'Nessun evento nel cestino', 'cv-dossier' ),
            'all_items'             => __( 'Tutti gli Eventi', 'cv-dossier' ),
        ];
    }
}