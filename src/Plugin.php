<?php
/**
 * Classe principale del plugin FP Newspaper
 *
 * @package FPNewspaper
 */

namespace FPNewspaper;

defined('ABSPATH') || exit;

/**
 * Classe singleton per gestire l'inizializzazione del plugin
 */
class Plugin {
    
    /**
     * Istanza singleton
     *
     * @var Plugin|null
     */
    private static $instance = null;
    
    /**
     * Ottiene l'istanza singleton
     *
     * @return Plugin
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Costruttore privato (singleton)
     */
    private function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Previene clonazione dell'istanza
     */
    private function __clone() {
        _doing_it_wrong(
            __FUNCTION__,
            __('Clonazione non permessa.', 'fp-newspaper'),
            FP_NEWSPAPER_VERSION
        );
    }
    
    /**
     * Previeni deserializzazione dell'istanza
     */
    public function __wakeup() {
        _doing_it_wrong(
            __FUNCTION__,
            __('Deserializzazione non permessa.', 'fp-newspaper'),
            FP_NEWSPAPER_VERSION
        );
    }
    
    /**
     * Inizializza gli hook WordPress
     */
    private function init_hooks() {
        // Azioni admin
        add_action('admin_menu', [$this, 'register_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        
        // Azioni frontend
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        
        // Cache invalidation quando articoli cambiano
        add_action('save_post_fp_article', [$this, 'invalidate_caches'], 10, 3);
        add_action('delete_post', [$this, 'invalidate_caches_on_delete']);
        add_action('update_post_meta', [$this, 'invalidate_caches_on_meta_update'], 10, 4);
        
        // Inizializza componenti
        $this->init_components();
    }
    
    /**
     * Inizializza i componenti del plugin
     */
    private function init_components() {
        // Registra post types e tassonomie
        if (class_exists('FPNewspaper\PostTypes\Article')) {
            PostTypes\Article::register();
        }
        
        // Inizializza REST API
        if (class_exists('FPNewspaper\REST\Controller')) {
            new REST\Controller();
        }
        
        // Inizializza meta boxes
        if (class_exists('FPNewspaper\Admin\MetaBoxes')) {
            new Admin\MetaBoxes();
        }
        
        // Inizializza colonne admin
        if (class_exists('FPNewspaper\Admin\Columns')) {
            new Admin\Columns();
        }
        
        // Inizializza bulk actions
        if (class_exists('FPNewspaper\Admin\BulkActions')) {
            new Admin\BulkActions();
        }
        
        // Inizializza settings page
        if (class_exists('FPNewspaper\Admin\Settings')) {
            new Admin\Settings();
        }
        
        // Inizializza WP-CLI commands
        if (class_exists('FPNewspaper\CLI\Commands')) {
            CLI\Commands::register();
        }
        
        // Inizializza shortcodes
        if (class_exists('FPNewspaper\Shortcodes\Articles')) {
            Shortcodes\Articles::register();
        }
        
        // Inizializza widgets
        add_action('widgets_init', function() {
            if (class_exists('FPNewspaper\Widgets\LatestArticles')) {
                register_widget('FPNewspaper\Widgets\LatestArticles');
            }
        });
        
        // Inizializza cron jobs
        if (class_exists('FPNewspaper\Cron\Jobs')) {
            Cron\Jobs::register();
        }
    }
    
    /**
     * Registra menu admin
     */
    public function register_admin_menu() {
        add_menu_page(
            __('FP Newspaper', 'fp-newspaper'),
            __('FP Newspaper', 'fp-newspaper'),
            'manage_options',
            'fp-newspaper',
            [$this, 'render_admin_page'],
            'dashicons-media-text',
            25
        );
    }
    
    /**
     * Renderizza la pagina admin principale
     */
    public function render_admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Non hai i permessi per accedere a questa pagina.', 'fp-newspaper'));
        }
        
        global $wpdb;
        
        // Statistiche
        $total_articles = wp_count_posts('fp_article');
        if (!$total_articles) {
            $total_articles = (object) ['publish' => 0, 'draft' => 0];
        }
        
        $table_name = $wpdb->prefix . 'fp_newspaper_stats';
        
        // Verifica che la tabella esista
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        
        if ($table_exists) {
            $stats = $wpdb->get_row(
                "SELECT 
                    COALESCE(SUM(views), 0) as total_views, 
                    COALESCE(SUM(shares), 0) as total_shares,
                    COUNT(*) as tracked_articles
                FROM $table_name"
            );
        }
        
        // Fallback se query fallisce o tabella non esiste
        if (!isset($stats) || !$stats) {
            $stats = (object) [
                'total_views' => 0,
                'total_shares' => 0,
                'tracked_articles' => 0
            ];
        }
        
        // Articoli recenti
        $recent_articles = get_posts([
            'post_type' => 'fp_article',
            'posts_per_page' => 5,
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC'
        ]);
        
        // Articoli più visti
        $most_viewed = [];
        if ($table_exists) {
            $most_viewed_results = $wpdb->get_results(
                "SELECT p.ID, p.post_title, s.views 
                FROM {$wpdb->posts} p
                INNER JOIN $table_name s ON p.ID = s.post_id
                WHERE p.post_type = 'fp_article' AND p.post_status = 'publish'
                ORDER BY s.views DESC
                LIMIT 5"
            );
            if ($most_viewed_results && !is_wp_error($most_viewed_results)) {
                $most_viewed = $most_viewed_results;
            }
        }
        
        // Breaking News
        $breaking_news = get_posts([
            'post_type' => 'fp_article',
            'posts_per_page' => 5,
            'meta_key' => '_fp_breaking_news',
            'meta_value' => '1',
            'post_status' => 'publish'
        ]);
        
        // Articoli in evidenza
        $featured_articles = get_posts([
            'post_type' => 'fp_article',
            'posts_per_page' => 5,
            'meta_key' => '_fp_featured',
            'meta_value' => '1',
            'post_status' => 'publish'
        ]);
        
        ?>
        <div class="wrap fp-newspaper-dashboard">
            <h1><?php echo esc_html__('FP Newspaper - Dashboard', 'fp-newspaper'); ?></h1>
            
            <!-- Statistiche principali -->
            <div class="fp-stats-grid">
                <div class="fp-stat-box fp-stat-articles">
                    <div class="fp-stat-icon">📰</div>
                    <div class="fp-stat-content">
                        <div class="fp-stat-number"><?php echo number_format_i18n($total_articles->publish); ?></div>
                        <div class="fp-stat-label"><?php _e('Articoli Pubblicati', 'fp-newspaper'); ?></div>
                    </div>
                </div>
                
                <div class="fp-stat-box fp-stat-views">
                    <div class="fp-stat-icon">👁️</div>
                    <div class="fp-stat-content">
                        <div class="fp-stat-number"><?php echo number_format_i18n($stats->total_views); ?></div>
                        <div class="fp-stat-label"><?php _e('Visualizzazioni Totali', 'fp-newspaper'); ?></div>
                    </div>
                </div>
                
                <div class="fp-stat-box fp-stat-shares">
                    <div class="fp-stat-icon">🔗</div>
                    <div class="fp-stat-content">
                        <div class="fp-stat-number"><?php echo number_format_i18n($stats->total_shares); ?></div>
                        <div class="fp-stat-label"><?php _e('Condivisioni', 'fp-newspaper'); ?></div>
                    </div>
                </div>
                
                <div class="fp-stat-box fp-stat-drafts">
                    <div class="fp-stat-icon">📝</div>
                    <div class="fp-stat-content">
                        <div class="fp-stat-number"><?php echo number_format_i18n($total_articles->draft); ?></div>
                        <div class="fp-stat-label"><?php _e('Bozze', 'fp-newspaper'); ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Azioni rapide -->
            <div class="fp-quick-actions">
                <h2><?php _e('Azioni Rapide', 'fp-newspaper'); ?></h2>
                <div class="fp-actions-grid">
                    <a href="<?php echo admin_url('post-new.php?post_type=fp_article'); ?>" class="fp-action-btn fp-action-primary">
                        <span class="dashicons dashicons-plus-alt"></span>
                        <?php _e('Nuovo Articolo', 'fp-newspaper'); ?>
                    </a>
                    <a href="<?php echo admin_url('edit.php?post_type=fp_article'); ?>" class="fp-action-btn">
                        <span class="dashicons dashicons-list-view"></span>
                        <?php _e('Tutti gli Articoli', 'fp-newspaper'); ?>
                    </a>
                    <a href="<?php echo admin_url('edit-tags.php?taxonomy=fp_article_category&post_type=fp_article'); ?>" class="fp-action-btn">
                        <span class="dashicons dashicons-category"></span>
                        <?php _e('Categorie', 'fp-newspaper'); ?>
                    </a>
                    <a href="<?php echo admin_url('edit-tags.php?taxonomy=fp_article_tag&post_type=fp_article'); ?>" class="fp-action-btn">
                        <span class="dashicons dashicons-tag"></span>
                        <?php _e('Tag', 'fp-newspaper'); ?>
                    </a>
                </div>
            </div>
            
            <div class="fp-dashboard-columns">
                <!-- Colonna sinistra -->
                <div class="fp-dashboard-left">
                    
                    <!-- Articoli recenti -->
                    <div class="fp-dashboard-widget">
                        <h2><?php _e('Articoli Recenti', 'fp-newspaper'); ?></h2>
                        <?php if (!empty($recent_articles)): ?>
                            <ul class="fp-article-list">
                                <?php foreach ($recent_articles as $article): ?>
                                    <li>
                                        <a href="<?php echo get_edit_post_link($article->ID); ?>">
                                            <strong><?php echo esc_html($article->post_title); ?></strong>
                                        </a>
                                        <span class="fp-meta">
                                            <?php echo get_the_date('', $article->ID); ?> - 
                                            <?php echo get_the_author_meta('display_name', $article->post_author); ?>
                                        </span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            <p class="fp-view-all">
                                <a href="<?php echo admin_url('edit.php?post_type=fp_article'); ?>">
                                    <?php _e('Vedi tutti gli articoli →', 'fp-newspaper'); ?>
                                </a>
                            </p>
                        <?php else: ?>
                            <p class="fp-empty-state">
                                <?php _e('Nessun articolo pubblicato.', 'fp-newspaper'); ?>
                                <a href="<?php echo admin_url('post-new.php?post_type=fp_article'); ?>">
                                    <?php _e('Crea il primo articolo', 'fp-newspaper'); ?>
                                </a>
                            </p>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Articoli più visti -->
                    <div class="fp-dashboard-widget">
                        <h2><?php _e('Articoli Più Visti', 'fp-newspaper'); ?></h2>
                        <?php if (!empty($most_viewed)): ?>
                            <ul class="fp-article-list fp-trending">
                                <?php foreach ($most_viewed as $idx => $article): ?>
                                    <li>
                                        <span class="fp-rank">#<?php echo $idx + 1; ?></span>
                                        <a href="<?php echo get_edit_post_link($article->ID); ?>">
                                            <strong><?php echo esc_html($article->post_title); ?></strong>
                                        </a>
                                        <span class="fp-views">
                                            👁️ <?php echo number_format_i18n($article->views); ?> views
                                        </span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p class="fp-empty-state">
                                <?php _e('Nessuna statistica disponibile ancora.', 'fp-newspaper'); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                    
                </div>
                
                <!-- Colonna destra -->
                <div class="fp-dashboard-right">
                    
                    <!-- Breaking News -->
                    <?php if (!empty($breaking_news)): ?>
                    <div class="fp-dashboard-widget fp-breaking-widget">
                        <h2>🔥 <?php _e('Breaking News', 'fp-newspaper'); ?></h2>
                        <ul class="fp-article-list">
                            <?php foreach ($breaking_news as $article): ?>
                                <li>
                                    <a href="<?php echo get_edit_post_link($article->ID); ?>">
                                        <strong><?php echo esc_html($article->post_title); ?></strong>
                                    </a>
                                    <span class="fp-meta">
                                        <?php echo human_time_diff(get_the_time('U', $article->ID), current_time('timestamp')); ?> fa
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Articoli in evidenza -->
                    <?php if (!empty($featured_articles)): ?>
                    <div class="fp-dashboard-widget fp-featured-widget">
                        <h2>⭐ <?php _e('In Evidenza', 'fp-newspaper'); ?></h2>
                        <ul class="fp-article-list">
                            <?php foreach ($featured_articles as $article): ?>
                                <li>
                                    <a href="<?php echo get_edit_post_link($article->ID); ?>">
                                        <strong><?php echo esc_html($article->post_title); ?></strong>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Informazioni sistema -->
                    <div class="fp-dashboard-widget fp-system-info">
                        <h2><?php _e('Informazioni Sistema', 'fp-newspaper'); ?></h2>
                        <table class="fp-info-table">
                            <tr>
                                <td><strong><?php _e('Versione Plugin:', 'fp-newspaper'); ?></strong></td>
                                <td><?php echo FP_NEWSPAPER_VERSION; ?></td>
                            </tr>
                            <tr>
                                <td><strong><?php _e('Categorie:', 'fp-newspaper'); ?></strong></td>
                                <td>
                                    <?php 
                                    $cat_count = wp_count_terms(['taxonomy' => 'fp_article_category']);
                                    echo is_wp_error($cat_count) ? 0 : $cat_count;
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <td><strong><?php _e('Tag:', 'fp-newspaper'); ?></strong></td>
                                <td>
                                    <?php 
                                    $tag_count = wp_count_terms(['taxonomy' => 'fp_article_tag']);
                                    echo is_wp_error($tag_count) ? 0 : $tag_count;
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <td><strong><?php _e('REST API:', 'fp-newspaper'); ?></strong></td>
                                <td><span class="fp-status-active">✅ Attiva</span></td>
                            </tr>
                        </table>
                        
                        <div class="fp-system-links">
                            <a href="<?php echo rest_url('fp-newspaper/v1/stats'); ?>" target="_blank" class="button">
                                <?php _e('Testa REST API', 'fp-newspaper'); ?>
                            </a>
                        </div>
                    </div>
                    
                    <!-- Tips & Tricks -->
                    <div class="fp-dashboard-widget fp-tips-widget">
                        <h2>💡 <?php _e('Suggerimenti', 'fp-newspaper'); ?></h2>
                        <ul class="fp-tips-list">
                            <li><?php _e('Usa i meta box per contrassegnare articoli come "In evidenza" o "Breaking News"', 'fp-newspaper'); ?></li>
                            <li><?php _e('Le statistiche di visualizzazione vengono tracciate automaticamente via REST API', 'fp-newspaper'); ?></li>
                            <li><?php _e('Gli articoli supportano Gutenberg e tutti i blocchi moderni', 'fp-newspaper'); ?></li>
                            <li><?php _e('Puoi gestire categorie e tag come i normali post di WordPress', 'fp-newspaper'); ?></li>
                        </ul>
                    </div>
                    
                </div>
            </div>
            
            <!-- Footer dashboard -->
            <div class="fp-dashboard-footer">
                <p>
                    <?php 
                    printf(
                        __('FP Newspaper v%s | Creato da %s', 'fp-newspaper'),
                        FP_NEWSPAPER_VERSION,
                        '<a href="https://francescopasseri.com" target="_blank">Francesco Passeri</a>'
                    ); 
                    ?>
                </p>
            </div>
            
        </div>
        
        <style>
            .fp-newspaper-dashboard {
                max-width: 1400px;
            }
            
            /* Statistiche Grid */
            .fp-stats-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 20px;
                margin: 30px 0;
            }
            
            .fp-stat-box {
                background: white;
                border: 1px solid #c3c4c7;
                border-radius: 8px;
                padding: 25px;
                display: flex;
                align-items: center;
                gap: 20px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.05);
                transition: all 0.3s ease;
            }
            
            .fp-stat-box:hover {
                box-shadow: 0 4px 12px rgba(0,0,0,0.1);
                transform: translateY(-2px);
            }
            
            .fp-stat-icon {
                font-size: 40px;
                opacity: 0.8;
            }
            
            .fp-stat-number {
                font-size: 32px;
                font-weight: bold;
                color: #1d2327;
                line-height: 1;
            }
            
            .fp-stat-label {
                font-size: 13px;
                color: #646970;
                margin-top: 5px;
            }
            
            .fp-stat-articles { border-left: 4px solid #2271b1; }
            .fp-stat-views { border-left: 4px solid #00a32a; }
            .fp-stat-shares { border-left: 4px solid #dba617; }
            .fp-stat-drafts { border-left: 4px solid #646970; }
            
            /* Azioni rapide */
            .fp-quick-actions {
                background: white;
                border: 1px solid #c3c4c7;
                border-radius: 8px;
                padding: 25px;
                margin-bottom: 30px;
            }
            
            .fp-quick-actions h2 {
                margin-top: 0;
                font-size: 18px;
            }
            
            .fp-actions-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 15px;
                margin-top: 20px;
            }
            
            .fp-action-btn {
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                padding: 15px 20px;
                background: #f0f0f1;
                border: 1px solid #c3c4c7;
                border-radius: 6px;
                text-decoration: none;
                color: #1d2327;
                font-weight: 500;
                transition: all 0.2s ease;
            }
            
            .fp-action-btn:hover {
                background: #e5e5e5;
                border-color: #2271b1;
                color: #2271b1;
                transform: translateY(-1px);
            }
            
            .fp-action-primary {
                background: #2271b1;
                color: white;
                border-color: #2271b1;
            }
            
            .fp-action-primary:hover {
                background: #135e96;
                color: white;
            }
            
            /* Dashboard columns */
            .fp-dashboard-columns {
                display: grid;
                grid-template-columns: 2fr 1fr;
                gap: 20px;
                margin-top: 30px;
            }
            
            .fp-dashboard-widget {
                background: white;
                border: 1px solid #c3c4c7;
                border-radius: 8px;
                padding: 20px;
                margin-bottom: 20px;
            }
            
            .fp-dashboard-widget h2 {
                margin-top: 0;
                font-size: 16px;
                border-bottom: 1px solid #e5e5e5;
                padding-bottom: 10px;
            }
            
            .fp-article-list {
                list-style: none;
                margin: 0;
                padding: 0;
            }
            
            .fp-article-list li {
                padding: 12px 0;
                border-bottom: 1px solid #f0f0f1;
            }
            
            .fp-article-list li:last-child {
                border-bottom: none;
            }
            
            .fp-article-list a {
                text-decoration: none;
                color: #2271b1;
                font-size: 14px;
            }
            
            .fp-article-list a:hover {
                color: #135e96;
            }
            
            .fp-meta {
                display: block;
                font-size: 12px;
                color: #646970;
                margin-top: 4px;
            }
            
            .fp-trending li {
                display: flex;
                align-items: center;
                gap: 10px;
            }
            
            .fp-rank {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 30px;
                height: 30px;
                background: #f0f0f1;
                border-radius: 50%;
                font-weight: bold;
                font-size: 14px;
                color: #646970;
                flex-shrink: 0;
            }
            
            .fp-views {
                margin-left: auto;
                font-size: 12px;
                color: #646970;
                white-space: nowrap;
            }
            
            .fp-empty-state {
                text-align: center;
                padding: 40px 20px;
                color: #646970;
            }
            
            .fp-empty-state a {
                color: #2271b1;
                text-decoration: none;
            }
            
            .fp-view-all {
                text-align: right;
                margin: 15px 0 0;
            }
            
            .fp-view-all a {
                font-size: 13px;
                text-decoration: none;
                color: #2271b1;
            }
            
            .fp-breaking-widget {
                border-left: 4px solid #d63638;
            }
            
            .fp-featured-widget {
                border-left: 4px solid #dba617;
            }
            
            .fp-info-table {
                width: 100%;
                font-size: 13px;
            }
            
            .fp-info-table td {
                padding: 8px 0;
                border-bottom: 1px solid #f0f0f1;
            }
            
            .fp-status-active {
                color: #00a32a;
                font-weight: 500;
            }
            
            .fp-system-links {
                margin-top: 15px;
                text-align: center;
            }
            
            .fp-tips-list {
                list-style: none;
                padding: 0;
                margin: 0;
            }
            
            .fp-tips-list li {
                padding: 10px 0 10px 25px;
                position: relative;
                font-size: 13px;
                line-height: 1.6;
                color: #646970;
            }
            
            .fp-tips-list li:before {
                content: "→";
                position: absolute;
                left: 0;
                color: #2271b1;
                font-weight: bold;
            }
            
            .fp-dashboard-footer {
                background: #f0f0f1;
                padding: 20px;
                text-align: center;
                border-radius: 8px;
                margin-top: 30px;
                font-size: 13px;
                color: #646970;
            }
            
            .fp-dashboard-footer a {
                color: #2271b1;
                text-decoration: none;
            }
            
            @media (max-width: 1200px) {
                .fp-dashboard-columns {
                    grid-template-columns: 1fr;
                }
            }
            
            @media (max-width: 782px) {
                .fp-stats-grid {
                    grid-template-columns: 1fr;
                }
                
                .fp-actions-grid {
                    grid-template-columns: 1fr;
                }
            }
        </style>
        <?php
    }
    
    /**
     * Carica asset admin
     */
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'fp-newspaper') === false) {
            return;
        }
        
        wp_enqueue_style(
            'fp-newspaper-admin',
            FP_NEWSPAPER_URL . 'assets/css/admin.css',
            [],
            FP_NEWSPAPER_VERSION
        );
        
        wp_enqueue_script(
            'fp-newspaper-admin',
            FP_NEWSPAPER_URL . 'assets/js/admin.js',
            ['jquery'],
            FP_NEWSPAPER_VERSION,
            true
        );
    }
    
    /**
     * Carica asset frontend
     */
    public function enqueue_frontend_assets() {
        wp_enqueue_style(
            'fp-newspaper-frontend',
            FP_NEWSPAPER_URL . 'assets/css/frontend.css',
            [],
            FP_NEWSPAPER_VERSION
        );
        
        wp_enqueue_script(
            'fp-newspaper-frontend',
            FP_NEWSPAPER_URL . 'assets/js/frontend.js',
            ['jquery'],
            FP_NEWSPAPER_VERSION,
            true
        );
    }
    
    /**
     * Invalida tutte le cache REST API quando un articolo cambia
     *
     * @param int $post_id
     * @param \WP_Post $post
     * @param bool $update
     */
    public function invalidate_caches($post_id, $post, $update) {
        // Solo per articoli pubblicati
        if ('publish' !== $post->post_status) {
            return;
        }
        
        delete_transient('fp_newspaper_stats_cache');
        delete_transient('fp_featured_articles_cache');
        
        // Log in debug mode
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("FP Newspaper: Cache invalidata per post ID $post_id");
        }
    }
    
    /**
     * Invalida cache quando un post viene eliminato
     *
     * @param int $post_id
     */
    public function invalidate_caches_on_delete($post_id) {
        if ('fp_article' === get_post_type($post_id)) {
            delete_transient('fp_newspaper_stats_cache');
            delete_transient('fp_featured_articles_cache');
        }
    }
    
    /**
     * Invalida cache quando meta _fp_featured cambia
     *
     * @param int $meta_id
     * @param int $post_id
     * @param string $meta_key
     * @param mixed $meta_value
     */
    public function invalidate_caches_on_meta_update($meta_id, $post_id, $meta_key, $meta_value) {
        if ('_fp_featured' === $meta_key && 'fp_article' === get_post_type($post_id)) {
            delete_transient('fp_featured_articles_cache');
        }
        
        if ('_fp_breaking_news' === $meta_key && 'fp_article' === get_post_type($post_id)) {
            delete_transient('fp_featured_articles_cache');
        }
    }
}

