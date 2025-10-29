<?php
/**
 * Controller REST API
 *
 * @package FPNewspaper
 */

namespace FPNewspaper\REST;

defined('ABSPATH') || exit;

/**
 * Gestisce gli endpoint REST API del plugin
 */
class Controller {
    
    /**
     * Namespace REST API
     */
    const NAMESPACE = 'fp-newspaper/v1';
    
    /**
     * Costruttore
     */
    public function __construct() {
        add_action('rest_api_init', [$this, 'register_routes']);
    }
    
    /**
     * Registra gli endpoint REST
     */
    public function register_routes() {
        // Endpoint per ottenere statistiche
        register_rest_route(self::NAMESPACE, '/stats', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [$this, 'get_stats'],
            'permission_callback' => [$this, 'check_permission'],
        ]);
        
        // Endpoint per aggiornare visualizzazioni
        register_rest_route(self::NAMESPACE, '/articles/(?P<id>\d+)/view', [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'increment_views'],
            'permission_callback' => '__return_true',
            'args'                => [
                'id' => [
                    'required'          => true,
                    'type'              => 'integer',
                    'minimum'           => 1,
                    'validate_callback' => function($param, $request, $key) {
                        return is_numeric($param) && $param > 0;
                    },
                    'sanitize_callback' => function($param) {
                        return absint($param);
                    },
                ],
            ],
        ]);
        
        // Endpoint per articoli in evidenza
        register_rest_route(self::NAMESPACE, '/articles/featured', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [$this, 'get_featured_articles'],
            'permission_callback' => '__return_true',
        ]);
        
        // Endpoint health check (monitoring)
        register_rest_route(self::NAMESPACE, '/health', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [$this, 'health_check'],
            'permission_callback' => [$this, 'check_permission'],
        ]);
    }
    
    /**
     * Health check per monitoring
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function health_check($request) {
        $health = [
            'status' => 'healthy',
            'timestamp' => current_time('c'),
            'version' => FP_NEWSPAPER_VERSION,
            'checks' => []
        ];
        
        // Check 1: Database table exists
        global $wpdb;
        $table_name = $wpdb->prefix . 'fp_newspaper_stats';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        
        $health['checks']['database'] = [
            'status' => $table_exists ? 'ok' : 'error',
            'message' => $table_exists ? 'Table exists' : 'Table missing'
        ];
        
        if (!$table_exists) {
            $health['status'] = 'unhealthy';
        }
        
        // Check 2: Post type registered
        $pt_exists = post_type_exists('fp_article');
        $health['checks']['post_type'] = [
            'status' => $pt_exists ? 'ok' : 'error',
            'message' => $pt_exists ? 'Post type registered' : 'Post type missing'
        ];
        
        if (!$pt_exists) {
            $health['status'] = 'degraded';
        }
        
        // Check 3: Cache working
        $test_key = 'fp_health_check_' . time();
        set_transient($test_key, 'test', 10);
        $cache_works = get_transient($test_key) === 'test';
        delete_transient($test_key);
        
        $health['checks']['cache'] = [
            'status' => $cache_works ? 'ok' : 'warning',
            'message' => $cache_works ? 'Cache working' : 'Cache not working'
        ];
        
        // Check 4: Database performance
        if ($table_exists && class_exists('FPNewspaper\DatabaseOptimizer')) {
            $perf = \FPNewspaper\DatabaseOptimizer::analyze_performance();
            $health['checks']['performance'] = [
                'status' => count($perf['suggestions']) === 0 ? 'ok' : 'warning',
                'row_count' => $perf['row_count'],
                'suggestions' => $perf['suggestions']
            ];
        }
        
        $status_code = $health['status'] === 'healthy' ? 200 : 503;
        return new \WP_REST_Response($health, $status_code);
    }
    
    /**
     * Verifica permessi
     *
     * @return bool
     */
    public function check_permission() {
        return current_user_can('manage_options');
    }
    
    /**
     * Ottiene statistiche generali
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function get_stats($request) {
        global $wpdb;
        
        // Usa transient per cache (5 minuti)
        $cache_key = 'fp_newspaper_stats_cache';
        $cached_stats = get_transient($cache_key);
        
        if (false !== $cached_stats) {
            return new \WP_REST_Response($cached_stats, 200);
        }
        
        $stats = [
            'total_articles' => wp_count_posts('fp_article')->publish,
            'total_views'    => 0,
            'total_shares'   => 0,
        ];
        
        $table_name = $wpdb->prefix . 'fp_newspaper_stats';
        
        // Verifica esistenza tabella con prepared statement
        $table_check = $wpdb->get_var($wpdb->prepare(
            "SELECT TABLE_NAME FROM information_schema.TABLES 
             WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s",
            DB_NAME,
            $table_name
        ));
        
        if ($table_check === $table_name) {
            // Query preparata con sicurezza extra
            $totals = $wpdb->get_row($wpdb->prepare(
                "SELECT COALESCE(SUM(views), 0) as total_views, 
                        COALESCE(SUM(shares), 0) as total_shares 
                 FROM `{$wpdb->prefix}fp_newspaper_stats`"
            ));
            
            if ($totals && !is_wp_error($totals)) {
                $stats['total_views'] = (int) $totals->total_views;
                $stats['total_shares'] = (int) $totals->total_shares;
            }
        }
        
        // Cache per 5 minuti
        set_transient($cache_key, $stats, 5 * MINUTE_IN_SECONDS);
        
        return new \WP_REST_Response($stats, 200);
    }
    
    /**
     * Incrementa visualizzazioni articolo
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function increment_views($request) {
        global $wpdb;
        
        $post_id = absint($request['id']); // Sanitizzato doppiamente
        
        // Verifica che il post esista E sia del tipo corretto
        $post = get_post($post_id);
        if (!$post || 'fp_article' !== $post->post_type || 'publish' !== $post->post_status) {
            return new \WP_REST_Response([
                'error' => __('Articolo non trovato', 'fp-newspaper')
            ], 404);
        }
        
        // Rate limiting semplice: max 1 view ogni 30 secondi per IP+post_id
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
        $rate_limit_key = 'fp_view_' . md5($ip_address . $post_id);
        
        if (false !== get_transient($rate_limit_key)) {
            // View già registrata recentemente, ignora silenziosamente
            return new \WP_REST_Response([
                'success' => true,
                'message' => __('Visualizzazione già registrata', 'fp-newspaper'),
                'cached' => true
            ], 200);
        }
        
        $table_name = $wpdb->prefix . 'fp_newspaper_stats';
        
        // LOCK per prevenire race condition
        $lock_name = 'fp_view_lock_' . $post_id;
        $lock = $wpdb->get_var($wpdb->prepare(
            "SELECT GET_LOCK(%s, 2)",
            $lock_name
        ));
        
        if ($lock != 1) {
            // Non riuscito ad ottenere il lock, prova più tardi
            return new \WP_REST_Response([
                'success' => false,
                'error' => __('Servizio momentaneamente non disponibile', 'fp-newspaper')
            ], 503);
        }
        
        // Inserisce o aggiorna il contatore con prepared statement sicuro
        $result = $wpdb->query($wpdb->prepare(
            "INSERT INTO `{$wpdb->prefix}fp_newspaper_stats` (post_id, views) 
             VALUES (%d, 1) 
             ON DUPLICATE KEY UPDATE views = views + 1, last_updated = CURRENT_TIMESTAMP",
            $post_id
        ));
        
        // Rilascia il lock
        $wpdb->query($wpdb->prepare("SELECT RELEASE_LOCK(%s)", $lock_name));
        
        // Invalida cache statistiche
        delete_transient('fp_newspaper_stats_cache');
        
        // Verifica se la query è andata a buon fine
        if ($result === false) {
            // NON esporre db_error in produzione - solo log
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('FP Newspaper: Errore increment_views - ' . $wpdb->last_error);
            }
            
            return new \WP_REST_Response([
                'success' => false,
                'error' => __('Errore nel salvataggio della visualizzazione', 'fp-newspaper')
            ], 500);
        }
        
        // Imposta transient per rate limiting
        set_transient($rate_limit_key, true, 30);
        
        return new \WP_REST_Response([
            'success' => true,
            'message' => __('Visualizzazione registrata', 'fp-newspaper')
        ], 200);
    }
    
    /**
     * Ottiene articoli in evidenza
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function get_featured_articles($request) {
        // Cache per 10 minuti
        $cache_key = 'fp_featured_articles_cache';
        $cached_articles = get_transient($cache_key);
        
        if (false !== $cached_articles) {
            return new \WP_REST_Response($cached_articles, 200);
        }
        
        // Limita numero articoli (max 20)
        $per_page = isset($request['per_page']) ? min(absint($request['per_page']), 20) : 5;
        
        $args = [
            'post_type'              => 'fp_article',
            'posts_per_page'         => $per_page,
            'post_status'            => 'publish',
            'no_found_rows'          => true,  // Performance: non conta righe totali
            'update_post_meta_cache' => true,  // Carichiamo meta in batch
            'update_post_term_cache' => true,  // Carichiamo tassonomie in batch
            'meta_query'             => [
                [
                    'key'     => '_fp_featured',
                    'value'   => '1',
                    'compare' => '='
                ]
            ],
            'orderby'                => 'date',
            'order'                  => 'DESC',
        ];
        
        $query = new \WP_Query($args);
        $articles = [];
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                
                $article_id = get_the_ID();
                
                // Sanitizza tutti gli output
                $articles[] = [
                    'id'        => $article_id,
                    'title'     => wp_kses_post(get_the_title()),
                    'excerpt'   => wp_kses_post(get_the_excerpt()),
                    'permalink' => esc_url_raw(get_permalink()),
                    'thumbnail' => get_the_post_thumbnail_url($article_id, 'medium') 
                                   ? esc_url_raw(get_the_post_thumbnail_url($article_id, 'medium'))
                                   : null,
                    'date'      => get_the_date('c'),
                    'author'    => [
                        'id'   => get_the_author_meta('ID'),
                        'name' => sanitize_text_field(get_the_author())
                    ],
                ];
            }
            wp_reset_postdata();
        }
        
        // Cache per 10 minuti
        set_transient($cache_key, $articles, 10 * MINUTE_IN_SECONDS);
        
        return new \WP_REST_Response($articles, 200);
    }
}

