<?php
/**
 * Main Plugin Class
 *
 * Orchestratore principale del plugin CV Dossier & Context.
 *
 * @package CV_Dossier_Context
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Classe principale del plugin.
 */
class CV_Plugin {
    
    const VERSION = '1.0.2';
    const NONCE   = 'cv_dossier_nonce';
    const TABLE   = 'cv_dossier_followers';
    
    /**
     * Istanza singleton.
     *
     * @var CV_Plugin|null
     */
    private static $instance = null;
    
    /**
     * File principale del plugin.
     *
     * @var string
     */
    private $plugin_file;
    
    /**
     * Manager CPT.
     *
     * @var CV_CPT_Manager
     */
    private $cpt_manager;
    
    /**
     * Manager meta boxes.
     *
     * @var CV_Meta_Boxes
     */
    private $meta_boxes;
    
    /**
     * Manager assets.
     *
     * @var CV_Assets_Manager
     */
    private $assets;
    
    /**
     * Handler AJAX.
     *
     * @var CV_AJAX_Handler
     */
    private $ajax_handler;
    
    /**
     * Manager shortcodes.
     *
     * @var CV_Shortcodes
     */
    private $shortcodes;
    
    /**
     * Filtro contenuti.
     *
     * @var CV_Content_Filter
     */
    private $content_filter;
    
    /**
     * Inizializza il plugin (singleton).
     *
     * @param string $plugin_file Path al file principale.
     * @return CV_Plugin Istanza del plugin.
     */
    public static function init( $plugin_file ) {
        if ( null === self::$instance ) {
            self::$instance = new self( $plugin_file );
        }
        return self::$instance;
    }
    
    /**
     * Costruttore privato (singleton).
     *
     * @param string $plugin_file Path al file principale.
     */
    private function __construct( $plugin_file ) {
        $this->plugin_file = $plugin_file;
        
        $this->load_dependencies();
        $this->init_components();
        $this->register_hooks();
    }
    
    /**
     * Carica le dipendenze del plugin.
     */
    private function load_dependencies() {
        // Helper
        require_once dirname( $this->plugin_file ) . '/includes/helpers/class-cv-validator.php';
        require_once dirname( $this->plugin_file ) . '/includes/helpers/class-cv-sanitizer.php';
        require_once dirname( $this->plugin_file ) . '/includes/helpers/class-cv-marker-helper.php';
        
        // Admin
        require_once dirname( $this->plugin_file ) . '/includes/admin/class-cv-cpt-manager.php';
        require_once dirname( $this->plugin_file ) . '/includes/admin/class-cv-meta-boxes.php';
        require_once dirname( $this->plugin_file ) . '/includes/admin/class-cv-assets-manager.php';
        
        // Frontend
        require_once dirname( $this->plugin_file ) . '/includes/frontend/class-cv-context-card.php';
        require_once dirname( $this->plugin_file ) . '/includes/frontend/class-cv-timeline.php';
        require_once dirname( $this->plugin_file ) . '/includes/frontend/class-cv-map-renderer.php';
        require_once dirname( $this->plugin_file ) . '/includes/frontend/class-cv-shortcodes.php';
        
        // Core
        require_once dirname( $this->plugin_file ) . '/includes/class-cv-ajax-handler.php';
        require_once dirname( $this->plugin_file ) . '/includes/class-cv-content-filter.php';
    }
    
    /**
     * Inizializza i componenti del plugin.
     */
    private function init_components() {
        // Admin components
        $this->cpt_manager  = new CV_CPT_Manager();
        $this->meta_boxes   = new CV_Meta_Boxes( self::NONCE );
        $this->assets       = new CV_Assets_Manager( self::VERSION, $this->plugin_file, self::NONCE );
        
        // Frontend renderers
        $context_renderer   = new CV_Context_Card();
        $timeline_renderer  = new CV_Timeline();
        $map_renderer       = new CV_Map_Renderer();
        
        // Frontend components
        $this->shortcodes     = new CV_Shortcodes( $context_renderer, $timeline_renderer, $map_renderer, $this->assets );
        $this->content_filter = new CV_Content_Filter( $context_renderer, $timeline_renderer, $map_renderer, $this->assets );
        
        // AJAX
        $this->ajax_handler = new CV_AJAX_Handler( self::NONCE, self::TABLE );
    }
    
    /**
     * Registra gli hooks WordPress.
     */
    private function register_hooks() {
        // Lifecycle
        register_activation_hook( $this->plugin_file, [ $this, 'activate' ] );
        register_uninstall_hook( $this->plugin_file, [ __CLASS__, 'uninstall' ] );
        
        // Textdomain
        add_action( 'plugins_loaded', [ $this, 'load_textdomain' ] );
        
        // Init components
        $this->cpt_manager->init();
        $this->meta_boxes->init();
        $this->assets->init();
        $this->shortcodes->init();
        $this->content_filter->init();
        $this->ajax_handler->init();
        
        // Localizzazione script admin quando necessario
        add_action( 'admin_enqueue_scripts', [ $this, 'localize_admin_scripts' ], 20 );
    }
    
    /**
     * Attivazione del plugin.
     */
    public function activate() {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE;
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            dossier_id BIGINT UNSIGNED NOT NULL,
            email VARCHAR(190) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY dossier_email (dossier_id, email),
            KEY dossier_id (dossier_id)
        ) {$charset_collate};";
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }
    
    /**
     * Disinstallazione del plugin.
     */
    public static function uninstall() {
        if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
            return;
        }
        
        global $wpdb;
        
        // Rimuovi opzioni
        delete_option( 'cv_dossier_version' );
        
        // Rimuovi meta
        $wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_cv_%'" );
        
        // Flush cache
        wp_cache_flush();
    }
    
    /**
     * Carica il text domain per le traduzioni.
     */
    public function load_textdomain() {
        load_plugin_textdomain( 'cv-dossier', false, dirname( plugin_basename( $this->plugin_file ) ) . '/languages/' );
    }
    
    /**
     * Localizza gli script admin.
     *
     * @param string $hook Hook corrente.
     */
    public function localize_admin_scripts( $hook ) {
        if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) {
            return;
        }
        
        $screen = get_current_screen();
        if ( ! $screen || 'post' !== $screen->post_type ) {
            return;
        }
        
        if ( ! wp_script_is( 'cv-dossier-admin', 'enqueued' ) ) {
            return;
        }
        
        $marker_template = $this->meta_boxes->get_marker_template( '__INDEX__' );
        $this->assets->localize_admin_script( $marker_template );
    }
}