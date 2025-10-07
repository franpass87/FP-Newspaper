<?php
/**
 * Shortcodes Manager
 *
 * Gestisce gli shortcodes del plugin.
 *
 * @package CV_Dossier_Context
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Classe per gestione shortcodes.
 */
class CV_Shortcodes {
    
    /**
     * Renderer per context card.
     *
     * @var CV_Context_Card
     */
    private $context_renderer;
    
    /**
     * Renderer per timeline.
     *
     * @var CV_Timeline
     */
    private $timeline_renderer;
    
    /**
     * Renderer per mappe.
     *
     * @var CV_Map_Renderer
     */
    private $map_renderer;
    
    /**
     * Assets manager.
     *
     * @var CV_Assets_Manager
     */
    private $assets;
    
    /**
     * Cache per marker.
     *
     * @var array
     */
    private $marker_cache = [];
    
    /**
     * Costruttore.
     *
     * @param CV_Context_Card   $context_renderer  Renderer context card.
     * @param CV_Timeline       $timeline_renderer Renderer timeline.
     * @param CV_Map_Renderer   $map_renderer      Renderer mappa.
     * @param CV_Assets_Manager $assets            Assets manager.
     */
    public function __construct( $context_renderer, $timeline_renderer, $map_renderer, $assets ) {
        $this->context_renderer  = $context_renderer;
        $this->timeline_renderer = $timeline_renderer;
        $this->map_renderer      = $map_renderer;
        $this->assets            = $assets;
    }
    
    /**
     * Inizializza il manager registrando gli shortcodes.
     */
    public function init() {
        add_shortcode( 'cv_dossier_context', [ $this, 'context_shortcode' ] );
        add_shortcode( 'cv_dossier_timeline', [ $this, 'timeline_shortcode' ] );
        add_shortcode( 'cv_dossier_map', [ $this, 'map_shortcode' ] );
    }
    
    /**
     * Shortcode per la scheda riassuntiva.
     *
     * @param array $atts Attributi shortcode.
     * @return string HTML renderizzato.
     */
    public function context_shortcode( $atts ) {
        $id = $this->resolve_dossier_id( $atts );
        if ( ! $id ) {
            return '';
        }
        
        if ( ! $this->is_feature_enabled( $id, '_cv_show_context', true ) ) {
            return '';
        }
        
        $card = $this->context_renderer->render( $id, false );
        if ( $card ) {
            $this->assets->ensure_frontend_assets();
        }
        return $card;
    }
    
    /**
     * Shortcode per la timeline.
     *
     * @param array $atts Attributi shortcode.
     * @return string HTML renderizzato.
     */
    public function timeline_shortcode( $atts ) {
        $id = $this->resolve_dossier_id( $atts );
        if ( ! $id ) {
            return '';
        }
        
        if ( ! $this->is_feature_enabled( $id, '_cv_show_timeline', true ) ) {
            return '';
        }
        
        $this->assets->ensure_frontend_assets();
        
        return $this->timeline_renderer->render( $id );
    }
    
    /**
     * Shortcode per la mappa.
     *
     * @param array $atts Attributi shortcode.
     * @return string HTML renderizzato.
     */
    public function map_shortcode( $atts ) {
        $id = $this->resolve_dossier_id( $atts );
        if ( ! $id ) {
            return '';
        }
        
        $height_raw = $atts['height'] ?? '';
        $height     = CV_Sanitizer::sanitize_map_height( $height_raw, 380 );
        
        $default_enabled = CV_Marker_Helper::dossier_has_markers( $id, $this->marker_cache );
        if ( ! $this->is_feature_enabled( $id, '_cv_show_map', $default_enabled ) ) {
            return '';
        }
        
        $markers = CV_Marker_Helper::get_dossier_markers( $id, $this->marker_cache );
        if ( empty( $markers ) ) {
            return '';
        }
        
        $this->assets->ensure_frontend_assets( true );
        
        return $this->map_renderer->render_dossier_map( $id, $height, $this->marker_cache );
    }
    
    /**
     * Risolve l'ID del dossier dagli attributi dello shortcode.
     *
     * @param array $atts Attributi shortcode.
     * @return int ID del dossier o 0.
     */
    private function resolve_dossier_id( $atts ) {
        $id = 0;
        
        if ( isset( $atts['id'] ) ) {
            $id = intval( $atts['id'] );
        }
        
        if ( $id > 0 ) {
            return $id;
        }
        
        global $post;
        
        if ( $post instanceof WP_Post && 'cv_dossier' === $post->post_type ) {
            return (int) $post->ID;
        }
        
        return 0;
    }
    
    /**
     * Verifica se una feature è abilitata.
     *
     * @param int    $post_id  ID post.
     * @param string $meta_key Meta key.
     * @param bool   $default  Valore default.
     * @return bool True se abilitata.
     */
    private function is_feature_enabled( $post_id, $meta_key, $default = false ) {
        $post_id = intval( $post_id );
        if ( $post_id <= 0 ) {
            return (bool) $default;
        }
        
        if ( ! metadata_exists( 'post', $post_id, $meta_key ) ) {
            return (bool) $default;
        }
        
        $value = get_post_meta( $post_id, $meta_key, true );
        
        if ( '' === $value ) {
            return (bool) $default;
        }
        
        return $this->interpret_boolean_meta( $value );
    }
    
    /**
     * Interpreta valore meta come booleano.
     *
     * @param mixed $value Valore.
     * @return bool Valore booleano.
     */
    private function interpret_boolean_meta( $value ) {
        if ( is_array( $value ) ) {
            return false;
        }
        
        if ( is_bool( $value ) ) {
            return $value;
        }
        
        if ( is_int( $value ) ) {
            return ( 0 !== $value );
        }
        
        if ( is_string( $value ) ) {
            $normalized = strtolower( trim( $value ) );
            
            if ( '' === $normalized ) {
                return false;
            }
            
            if ( is_numeric( $normalized ) ) {
                return intval( $normalized ) !== 0;
            }
            
            if ( in_array( $normalized, [ 'true', 'yes', 'on', 'enabled' ], true ) ) {
                return true;
            }
            
            if ( in_array( $normalized, [ 'false', 'no', 'off', 'disabled' ], true ) ) {
                return false;
            }
        }
        
        if ( is_numeric( $value ) ) {
            return intval( $value ) !== 0;
        }
        
        return false;
    }
}