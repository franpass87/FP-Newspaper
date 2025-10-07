<?php
/**
 * Content Filter
 *
 * Gestisce i filtri automatici del contenuto (the_content).
 *
 * @package CV_Dossier_Context
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Classe per filtri contenuto.
 */
class CV_Content_Filter {
    
    /**
     * Renderer context card.
     *
     * @var CV_Context_Card
     */
    private $context_renderer;
    
    /**
     * Renderer timeline.
     *
     * @var CV_Timeline
     */
    private $timeline_renderer;
    
    /**
     * Renderer mappa.
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
     * Cache marker.
     *
     * @var array
     */
    private $marker_cache = [];
    
    /**
     * Costruttore.
     *
     * @param CV_Context_Card   $context_renderer  Renderer context.
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
     * Inizializza il filtro.
     */
    public function init() {
        add_filter( 'the_content', [ $this, 'filter_content' ] );
    }
    
    /**
     * Filtra il contenuto aggiungendo elementi automatici.
     *
     * @param string $content Contenuto originale.
     * @return string Contenuto filtrato.
     */
    public function filter_content( $content ) {
        if ( ! in_the_loop() || ! is_main_query() ) {
            return $content;
        }
        
        if ( is_singular( 'post' ) ) {
            return $this->filter_post_content( $content );
        }
        
        if ( is_singular( 'cv_dossier' ) ) {
            return $this->filter_dossier_content( $content );
        }
        
        return $content;
    }
    
    /**
     * Filtra il contenuto di un post standard.
     *
     * @param string $content Contenuto originale.
     * @return string Contenuto filtrato.
     */
    private function filter_post_content( $content ) {
        $prepend = '';
        $dossier_id = intval( get_post_meta( get_the_ID(), '_cv_dossier_id', true ) );
        
        if ( $dossier_id && $this->is_feature_enabled( $dossier_id, '_cv_show_context', true ) ) {
            $card = $this->context_renderer->render( $dossier_id, true );
            if ( $card ) {
                $this->assets->ensure_frontend_assets();
                $prepend .= $card;
            }
        }
        
        $map = $this->map_renderer->render_post_map( get_the_ID(), $this->marker_cache );
        if ( $map ) {
            $this->assets->ensure_frontend_assets( true );
            $prepend .= $map;
        }
        
        if ( $prepend ) {
            $content = $prepend . $content;
        }
        
        return $content;
    }
    
    /**
     * Filtra il contenuto di un dossier.
     *
     * @param string $content Contenuto originale.
     * @return string Contenuto filtrato.
     */
    private function filter_dossier_content( $content ) {
        $post_id = get_the_ID();
        $append  = '';
        
        if ( ! has_shortcode( $content, 'cv_dossier_context' ) && $this->is_feature_enabled( $post_id, '_cv_show_context', true ) ) {
            $card = $this->context_renderer->render( $post_id, false );
            if ( $card ) {
                $this->assets->ensure_frontend_assets();
                $append .= $card;
            }
        }
        
        if ( ! has_shortcode( $content, 'cv_dossier_timeline' ) && $this->is_feature_enabled( $post_id, '_cv_show_timeline', true ) ) {
            $timeline = $this->timeline_renderer->render( $post_id );
            if ( $timeline ) {
                $this->assets->ensure_frontend_assets();
                $append .= $timeline;
            }
        }
        
        $has_markers = CV_Marker_Helper::dossier_has_markers( $post_id, $this->marker_cache );
        
        if ( ! has_shortcode( $content, 'cv_dossier_map' ) && $this->is_feature_enabled( $post_id, '_cv_show_map', $has_markers ) ) {
            $map_height_meta = get_post_meta( $post_id, '_cv_dossier_map_height', true );
            $map_markup = $this->map_renderer->render_dossier_map( $post_id, CV_Sanitizer::sanitize_map_height( $map_height_meta, 380 ), $this->marker_cache );
            if ( $map_markup ) {
                $this->assets->ensure_frontend_assets( true );
                $append .= $map_markup;
            }
        }
        
        if ( $append ) {
            $content .= $append;
        }
        
        return $content;
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