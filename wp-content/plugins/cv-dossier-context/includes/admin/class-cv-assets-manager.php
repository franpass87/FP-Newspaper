<?php
/**
 * Assets Manager
 *
 * Gestisce l'enqueuing di CSS e JavaScript.
 *
 * @package CV_Dossier_Context
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Classe per gestione assets (CSS/JS).
 */
class CV_Assets_Manager {
    
    /**
     * Versione del plugin per cache busting.
     *
     * @var string
     */
    private $version;
    
    /**
     * Path base del plugin.
     *
     * @var string
     */
    private $plugin_file;
    
    /**
     * Nonce name.
     *
     * @var string
     */
    private $nonce;
    
    /**
     * Costruttore.
     *
     * @param string $version     Versione plugin.
     * @param string $plugin_file Path file principale.
     * @param string $nonce       Nome nonce.
     */
    public function __construct( $version, $plugin_file, $nonce ) {
        $this->version     = $version;
        $this->plugin_file = $plugin_file;
        $this->nonce       = $nonce;
    }
    
    /**
     * Inizializza il manager registrando gli hooks.
     */
    public function init() {
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_frontend' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'localize_frontend_script' ], 20 );
    }
    
    /**
     * Enqueue assets frontend.
     */
    public function enqueue_frontend() {
        $this->register_frontend_assets();
        
        $needs_assets = false;
        $needs_leaflet = false;
        
        $post_id = get_queried_object_id();
        if ( $post_id && is_singular( 'post' ) ) {
            $linked_dossier = intval( get_post_meta( $post_id, '_cv_dossier_id', true ) );
            if ( $linked_dossier && $this->is_feature_enabled( $linked_dossier, '_cv_show_context', true ) ) {
                $needs_assets = true;
            }
            
            $markers = get_post_meta( $post_id, '_cv_map_markers', true );
            $map_enabled = $this->is_post_map_enabled( $post_id );
            
            if ( $map_enabled && is_array( $markers ) && ! empty( $markers ) ) {
                $needs_assets  = true;
                $needs_leaflet = true;
            }
        }
        
        global $post;
        if ( $post instanceof WP_Post ) {
            if ( has_shortcode( $post->post_content, 'cv_dossier_context' ) || has_shortcode( $post->post_content, 'cv_dossier_timeline' ) ) {
                $needs_assets = true;
            }
            
            if ( has_shortcode( $post->post_content, 'cv_dossier_map' ) ) {
                $needs_assets  = true;
                $needs_leaflet = true;
            }
        }
        
        if ( $needs_assets ) {
            $this->ensure_frontend_assets( $needs_leaflet );
        }
    }
    
    /**
     * Registra gli assets frontend.
     */
    private function register_frontend_assets() {
        wp_register_style( 'cv-dossier', plugins_url( 'css/cv-dossier.css', $this->plugin_file ), [], $this->version );
        
        if ( ! wp_style_is( 'leaflet', 'registered' ) ) {
            wp_register_style( 'leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', [], '1.9.4' );
        }
        
        if ( ! wp_script_is( 'leaflet', 'registered' ) ) {
            wp_register_script( 'leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', [], '1.9.4', true );
        }
        
        wp_register_script( 'cv-dossier', plugins_url( 'js/cv-dossier.js', $this->plugin_file ), [ 'jquery' ], $this->version, true );
    }
    
    /**
     * Assicura che gli assets frontend siano enqueued.
     *
     * @param bool $include_leaflet Se includere Leaflet.
     */
    public function ensure_frontend_assets( $include_leaflet = false ) {
        if ( wp_style_is( 'cv-dossier', 'registered' ) && ! wp_style_is( 'cv-dossier', 'enqueued' ) ) {
            wp_enqueue_style( 'cv-dossier' );
        }
        
        if ( wp_script_is( 'cv-dossier', 'registered' ) && ! wp_script_is( 'cv-dossier', 'enqueued' ) ) {
            wp_enqueue_script( 'cv-dossier' );
        }
        
        if ( $include_leaflet ) {
            if ( wp_style_is( 'leaflet', 'registered' ) && ! wp_style_is( 'leaflet', 'enqueued' ) ) {
                wp_enqueue_style( 'leaflet' );
            }
            if ( wp_script_is( 'leaflet', 'registered' ) && ! wp_script_is( 'leaflet', 'enqueued' ) ) {
                wp_enqueue_script( 'leaflet' );
            }
        }
    }
    
    /**
     * Localizza lo script frontend.
     */
    public function localize_frontend_script() {
        if ( ! wp_script_is( 'cv-dossier', 'registered' ) ) {
            return;
        }
        
        $data = [
            'ajax'                 => admin_url( 'admin-ajax.php' ),
            'nonce'                => wp_create_nonce( $this->nonce ),
            'lightboxCloseLabel'   => __( 'Chiudi immagine', 'cv-dossier' ),
            'lightboxDialogLabel'  => __( 'Immagine ingrandita', 'cv-dossier' ),
            'submitLoadingText'    => __( 'Invio…', 'cv-dossier' ),
            'followSuccessHtml'    => wp_kses_post( sprintf( '<span class="cv-ok" role="status" aria-live="polite" tabindex="-1">%s</span>', esc_html__( 'Iscritto ✔', 'cv-dossier' ) ) ),
            'followGenericError'   => __( 'Errore durante l\'iscrizione. Riprova.', 'cv-dossier' ),
            'followNetworkError'   => __( 'Errore di connessione. Riprova.', 'cv-dossier' ),
            'mapLoadingLabel'      => __( 'Caricamento mappa...', 'cv-dossier' ),
            'mapErrorGeneric'      => __( 'Impossibile caricare la mappa.', 'cv-dossier' ),
            'mapErrorLeaflet'      => __( 'Impossibile caricare la mappa: il servizio cartografico non ha risposto.', 'cv-dossier' ),
            'mapErrorTimeout'      => __( 'Impossibile inizializzare la mappa entro il tempo previsto.', 'cv-dossier' ),
            'mapErrorRefresh'      => __( 'Ricarica la pagina e riprova', 'cv-dossier' ),
            'cardAriaLabel'        => __( 'Scheda dossier', 'cv-dossier' ),
            'followFormAriaLabel'  => __( 'Modulo per seguire il dossier', 'cv-dossier' ),
            'followEmailAriaLabel' => __( 'Inserisci la tua email per ricevere aggiornamenti', 'cv-dossier' ),
        ];
        
        wp_localize_script( 'cv-dossier', 'CVDossier', $data );
    }
    
    /**
     * Enqueue assets admin.
     *
     * @param string $hook Hook corrente.
     */
    public function enqueue_admin( $hook ) {
        if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) {
            return;
        }
        
        $screen = get_current_screen();
        if ( ! $screen || 'post' !== $screen->post_type ) {
            return;
        }
        
        wp_enqueue_media();
        wp_enqueue_style( 'cv-dossier-admin', plugins_url( 'css/cv-dossier-admin.css', $this->plugin_file ), [], $this->version );
        wp_enqueue_script( 'cv-dossier-admin', plugins_url( 'js/cv-dossier-admin.js', $this->plugin_file ), [ 'jquery' ], $this->version, true );
    }
    
    /**
     * Localizza lo script admin.
     *
     * @param string $marker_template Template HTML per marker.
     */
    public function localize_admin_script( $marker_template ) {
        wp_localize_script( 'cv-dossier-admin', 'CVDossierAdmin', [
            'markerTemplate' => $marker_template,
            'chooseImage'    => __( 'Scegli immagine', 'cv-dossier' ),
            'removeImage'    => __( 'Rimuovi immagine', 'cv-dossier' ),
            'removePoint'    => __( 'Rimuovi punto', 'cv-dossier' ),
            'noImage'        => __( 'Nessuna immagine selezionata', 'cv-dossier' ),
        ] );
    }
    
    /**
     * Verifica se una feature del dossier è abilitata.
     *
     * @param int    $post_id ID post.
     * @param string $meta_key Meta key.
     * @param bool   $default Valore default.
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
     * Interpreta un meta value come booleano.
     *
     * @param mixed $value Valore da interpretare.
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
    
    /**
     * Verifica se la mappa di un post è abilitata.
     *
     * @param int $post_id ID del post.
     * @return bool True se abilitata.
     */
    private function is_post_map_enabled( $post_id ) {
        $raw_value = get_post_meta( $post_id, '_cv_map_enabled', true );
        $has_meta  = metadata_exists( 'post', $post_id, '_cv_map_enabled' );
        
        if ( $has_meta ) {
            if ( is_array( $raw_value ) ) {
                $has_meta = false;
            } elseif ( is_string( $raw_value ) ) {
                $trimmed = trim( $raw_value );
                if ( '' === $trimmed ) {
                    $has_meta = false;
                }
            }
        }
        
        if ( ! $has_meta ) {
            return true; // Default enabled.
        }
        
        return $this->interpret_boolean_meta( $raw_value );
    }
}