<?php
/**
 * Editorial Dashboard Admin Page
 *
 * @package FPNewspaper\Admin
 */

namespace FPNewspaper\Admin;

use FPNewspaper\Editorial\Dashboard;

defined('ABSPATH') || exit;

/**
 * Dashboard principale redazionale con metriche, grafici e activity feed
 */
class EditorialDashboardPage {
    
    /**
     * @var Dashboard
     */
    private $dashboard;
    
    /**
     * Costruttore
     */
    public function __construct() {
        $this->dashboard = new Dashboard();
        
        add_action('admin_menu', [$this, 'add_menu_page']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        
        // AJAX handlers
        add_action('wp_ajax_fp_refresh_dashboard', [$this, 'ajax_refresh_dashboard']);
    }
    
    /**
     * Aggiunge pagina menu principale
     */
    public function add_menu_page() {
        add_menu_page(
            __('Editorial Dashboard', 'fp-newspaper'),
            __('📊 Editorial', 'fp-newspaper'),
            'edit_posts',
            'fp-editorial-dashboard',
            [$this, 'render_page'],
            'dashicons-analytics',
            3  // Posizione sotto Dashboard
        );
    }
    
    /**
     * Enqueue assets
     */
    public function enqueue_assets($hook) {
        if ('toplevel_page_fp-editorial-dashboard' !== $hook) {
            return;
        }
        
        // Chart.js CDN
        wp_enqueue_script(
            'chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js',
            [],
            '4.4.0',
            true
        );
        
        // Dati per JavaScript
        $chart_data = $this->dashboard->get_chart_data(30);
        
        wp_localize_script('chartjs', 'fpDashboardData', [
            'chartData' => $chart_data,
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('fp_dashboard_nonce'),
        ]);
    }
    
    /**
     * Renderizza pagina dashboard
     */
    public function render_page() {
        if (!current_user_can('edit_posts')) {
            wp_die(__('Permessi insufficienti', 'fp-newspaper'));
        }
        
        $data = $this->dashboard->get_dashboard_data();
        $alerts = $this->dashboard->get_alerts();
        $productivity = $this->dashboard->get_productivity_metrics('month');
        $author_stats = $this->dashboard->get_author_stats(10);
        $upcoming_pubs = $this->dashboard->get_upcoming_publications();
        
        ?>
        <div class="wrap fp-editorial-dashboard">
            <h1><?php _e('📊 Editorial Dashboard', 'fp-newspaper'); ?></h1>
            <p class="description">
                <?php _e('Panoramica completa della redazione - Metriche, performance team e pipeline editoriale', 'fp-newspaper'); ?>
            </p>
            
            <!-- Alerts -->
            <?php if (!empty($alerts)): ?>
                <div class="fp-alerts-container">
                    <?php foreach ($alerts as $alert): ?>
                        <div class="notice notice-<?php echo esc_attr($alert['type']); ?> fp-alert">
                            <p>
                                <span class="dashicons dashicons-<?php echo esc_attr($alert['icon']); ?>"></span>
                                <strong><?php echo esc_html($alert['message']); ?></strong>
                                <?php if (isset($alert['action_link'])): ?>
                                    <a href="<?php echo esc_url($alert['action_link']); ?>" class="button button-small" style="margin-left: 10px;">
                                        <?php echo esc_html($alert['action_text']); ?>
                                    </a>
                                <?php endif; ?>
                            </p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <!-- Overview Stats -->
            <div class="fp-dashboard-row">
                <div class="fp-stats-grid">
                    <div class="fp-stat-card fp-card-primary">
                        <div class="fp-stat-icon">📝</div>
                        <div class="fp-stat-content">
                            <div class="fp-stat-value"><?php echo esc_html($data['overview']['published_today']); ?></div>
                            <div class="fp-stat-label"><?php _e('Pubblicati Oggi', 'fp-newspaper'); ?></div>
                        </div>
                    </div>
                    
                    <div class="fp-stat-card fp-card-success">
                        <div class="fp-stat-icon">📅</div>
                        <div class="fp-stat-content">
                            <div class="fp-stat-value"><?php echo esc_html($data['overview']['published_week']); ?></div>
                            <div class="fp-stat-label"><?php _e('Questa Settimana', 'fp-newspaper'); ?></div>
                        </div>
                    </div>
                    
                    <div class="fp-stat-card fp-card-info">
                        <div class="fp-stat-icon">📊</div>
                        <div class="fp-stat-content">
                            <div class="fp-stat-value"><?php echo esc_html($data['overview']['published_month']); ?></div>
                            <div class="fp-stat-label"><?php _e('Questo Mese', 'fp-newspaper'); ?></div>
                        </div>
                    </div>
                    
                    <div class="fp-stat-card fp-card-warning">
                        <div class="fp-stat-icon">⏱️</div>
                        <div class="fp-stat-content">
                            <div class="fp-stat-value"><?php echo esc_html($data['overview']['avg_per_day']); ?></div>
                            <div class="fp-stat-label"><?php _e('Media Giornaliera', 'fp-newspaper'); ?></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Charts Row -->
            <div class="fp-dashboard-row">
                <div class="fp-chart-container">
                    <div class="fp-chart-card">
                        <h3><?php _e('📈 Trend Pubblicazioni (30 giorni)', 'fp-newspaper'); ?></h3>
                        <canvas id="fp-publications-chart"></canvas>
                    </div>
                </div>
                
                <div class="fp-pipeline-container">
                    <div class="fp-pipeline-card">
                        <h3><?php _e('🔄 Pipeline Editoriale', 'fp-newspaper'); ?></h3>
                        <div class="fp-pipeline-stats">
                            <div class="fp-pipeline-item">
                                <span class="fp-pipeline-count fp-count-draft"><?php echo esc_html($data['pipeline']['drafts']); ?></span>
                                <span class="fp-pipeline-label"><?php _e('Bozze', 'fp-newspaper'); ?></span>
                            </div>
                            <div class="fp-pipeline-arrow">→</div>
                            <div class="fp-pipeline-item">
                                <span class="fp-pipeline-count fp-count-review"><?php echo esc_html($data['pipeline']['in_review']); ?></span>
                                <span class="fp-pipeline-label"><?php _e('In Revisione', 'fp-newspaper'); ?></span>
                            </div>
                            <div class="fp-pipeline-arrow">→</div>
                            <div class="fp-pipeline-item">
                                <span class="fp-pipeline-count fp-count-approved"><?php echo esc_html($data['pipeline']['approved']); ?></span>
                                <span class="fp-pipeline-label"><?php _e('Approvati', 'fp-newspaper'); ?></span>
                            </div>
                            <div class="fp-pipeline-arrow">→</div>
                            <div class="fp-pipeline-item">
                                <span class="fp-pipeline-count fp-count-scheduled"><?php echo esc_html($data['pipeline']['scheduled']); ?></span>
                                <span class="fp-pipeline-label"><?php _e('Programmati', 'fp-newspaper'); ?></span>
                            </div>
                        </div>
                        
                        <div class="fp-productivity-metrics">
                            <div class="fp-metric">
                                <strong><?php echo esc_html($productivity['published']); ?></strong>
                                <span><?php _e('pubblicati (30gg)', 'fp-newspaper'); ?></span>
                            </div>
                            <div class="fp-metric">
                                <strong><?php echo esc_html(round($productivity['avg_time_hours'], 1)); ?>h</strong>
                                <span><?php _e('tempo medio', 'fp-newspaper'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Advanced Charts Row -->
            <div class="fp-dashboard-row">
                <div class="fp-grid-3">
                    <!-- Productivity Donut Chart -->
                    <div class="fp-card">
                        <div class="fp-card-header">
                            <h3 class="fp-card-title">
                                <span class="dashicons dashicons-chart-pie"></span>
                                <?php _e('Produttività', 'fp-newspaper'); ?>
                            </h3>
                        </div>
                        <div class="fp-card-body">
                            <div class="fp-chart-container fp-chart-small">
                                <canvas id="fp-productivity-chart"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Top Authors Bar Chart -->
                    <div class="fp-card">
                        <div class="fp-card-header">
                            <h3 class="fp-card-title">
                                <span class="dashicons dashicons-groups"></span>
                                <?php _e('Top Autori', 'fp-newspaper'); ?>
                            </h3>
                        </div>
                        <div class="fp-card-body">
                            <div class="fp-chart-container fp-chart-small">
                                <canvas id="fp-authors-chart"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Views Trend Chart -->
                    <div class="fp-card">
                        <div class="fp-card-header">
                            <h3 class="fp-card-title">
                                <span class="dashicons dashicons-visibility"></span>
                                <?php _e('Visualizzazioni', 'fp-newspaper'); ?>
                            </h3>
                        </div>
                        <div class="fp-card-body">
                            <div class="fp-chart-container fp-chart-small">
                                <canvas id="fp-views-chart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Content Row -->
            <div class="fp-dashboard-row">
                <!-- Activity Feed -->
                <div class="fp-activity-container">
                    <div class="fp-activity-card">
                        <h3><?php _e('🔔 Attività Recente', 'fp-newspaper'); ?></h3>
                        <div class="fp-activity-feed">
                            <?php if (!empty($data['recent_activity'])): ?>
                                <?php foreach ($data['recent_activity'] as $activity): ?>
                                    <div class="fp-activity-item">
                                        <div class="fp-activity-time"><?php echo esc_html($activity['time_ago']); ?> fa</div>
                                        <div class="fp-activity-content">
                                            <strong><?php echo esc_html($activity['author']); ?></strong>
                                            <?php echo esc_html($activity['action']); ?>:
                                            <a href="<?php echo esc_url($activity['edit_link']); ?>">
                                                "<?php echo esc_html(wp_trim_words($activity['post_title'], 8)); ?>"
                                            </a>
                                            <span class="fp-activity-status fp-status-<?php echo esc_attr($activity['status']); ?>">
                                                <?php echo esc_html($activity['status_label']); ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="fp-empty-state"><?php _e('Nessuna attività recente', 'fp-newspaper'); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Trending Articles -->
                <div class="fp-trending-container">
                    <div class="fp-trending-card">
                        <h3><?php _e('🔥 Trending (48h)', 'fp-newspaper'); ?></h3>
                        <?php if (!empty($data['trending'])): ?>
                            <div class="fp-trending-list">
                                <?php foreach ($data['trending'] as $index => $article): ?>
                                    <div class="fp-trending-item">
                                        <div class="fp-trending-rank">#<?php echo esc_html($index + 1); ?></div>
                                        <div class="fp-trending-content">
                                            <a href="<?php echo esc_url(get_edit_post_link($article->ID)); ?>">
                                                <?php echo esc_html(wp_trim_words($article->post_title, 10)); ?>
                                            </a>
                                            <div class="fp-trending-stats">
                                                <span>👁️ <?php echo esc_html(number_format_i18n($article->views)); ?></span>
                                                <span>🚀 <?php echo esc_html(round($article->velocity, 1)); ?> views/h</span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="fp-empty-state"><?php _e('Nessun articolo trending', 'fp-newspaper'); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Team Performance -->
            <div class="fp-dashboard-row">
                <div class="fp-team-container">
                    <div class="fp-team-card">
                        <h3><?php _e('👥 Performance Team (30 giorni)', 'fp-newspaper'); ?></h3>
                        <?php if (!empty($author_stats)): ?>
                            <table class="wp-list-table widefat fixed striped">
                                <thead>
                                    <tr>
                                        <th><?php _e('Autore', 'fp-newspaper'); ?></th>
                                        <th><?php _e('Pubblicati', 'fp-newspaper'); ?></th>
                                        <th><?php _e('In Revisione', 'fp-newspaper'); ?></th>
                                        <th><?php _e('Bozze', 'fp-newspaper'); ?></th>
                                        <th><?php _e('Totale', 'fp-newspaper'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($author_stats as $author): ?>
                                        <tr>
                                            <td>
                                                <?php echo get_avatar($author->ID, 32); ?>
                                                <strong><?php echo esc_html($author->display_name); ?></strong>
                                            </td>
                                            <td><strong style="color: #27ae60;"><?php echo esc_html($author->published); ?></strong></td>
                                            <td><?php echo esc_html($author->in_review); ?></td>
                                            <td><?php echo esc_html($author->drafts); ?></td>
                                            <td><?php echo esc_html($author->total_articles); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p class="fp-empty-state"><?php _e('Nessun dato disponibile', 'fp-newspaper'); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Upcoming Publications -->
            <?php if (!empty($upcoming_pubs)): ?>
                <div class="fp-dashboard-row">
                    <div class="fp-upcoming-container">
                        <div class="fp-upcoming-card">
                            <h3><?php _e('📆 Prossime Pubblicazioni (7 giorni)', 'fp-newspaper'); ?></h3>
                            <div class="fp-upcoming-list">
                                <?php foreach ($upcoming_pubs as $pub): ?>
                                    <div class="fp-upcoming-item">
                                        <div class="fp-upcoming-date">
                                            <?php echo esc_html(date_i18n('d M', strtotime($pub->post_date))); ?>
                                            <div class="fp-upcoming-time"><?php echo esc_html(date_i18n('H:i', strtotime($pub->post_date))); ?></div>
                                        </div>
                                        <div class="fp-upcoming-content">
                                            <a href="<?php echo esc_url(get_edit_post_link($pub->ID)); ?>">
                                                <?php echo esc_html($pub->post_title); ?>
                                            </a>
                                            <div class="fp-upcoming-author">
                                                <?php echo esc_html(get_the_author_meta('display_name', $pub->post_author)); ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Quick Actions -->
            <div class="fp-dashboard-row">
                <div class="fp-quick-actions-card">
                    <h3><?php _e('⚡ Azioni Rapide', 'fp-newspaper'); ?></h3>
                    <div class="fp-actions-grid">
                        <a href="<?php echo admin_url('post-new.php'); ?>" class="fp-action-btn fp-action-primary">
                            <span class="dashicons dashicons-plus-alt"></span>
                            <?php _e('Nuovo Articolo', 'fp-newspaper'); ?>
                        </a>
                        <a href="<?php echo admin_url('edit.php?page=fp-newspaper-workflow'); ?>" class="fp-action-btn fp-action-workflow">
                            <span class="dashicons dashicons-clipboard"></span>
                            <?php _e('Workflow', 'fp-newspaper'); ?>
                        </a>
                        <a href="<?php echo admin_url('edit.php?page=fp-newspaper-calendar'); ?>" class="fp-action-btn fp-action-calendar">
                            <span class="dashicons dashicons-calendar-alt"></span>
                            <?php _e('Calendario', 'fp-newspaper'); ?>
                        </a>
                        <a href="<?php echo admin_url('edit.php'); ?>" class="fp-action-btn">
                            <span class="dashicons dashicons-list-view"></span>
                            <?php _e('Tutti gli Articoli', 'fp-newspaper'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
            .fp-editorial-dashboard {
                max-width: 1400px;
            }
            
            /* Alerts */
            .fp-alerts-container {
                margin: 20px 0;
            }
            .fp-alert .dashicons {
                vertical-align: middle;
                margin-right: 5px;
            }
            
            /* Dashboard Rows */
            .fp-dashboard-row {
                display: grid;
                gap: 20px;
                margin: 20px 0;
            }
            
            /* Stats Grid */
            .fp-stats-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
                gap: 20px;
            }
            .fp-stat-card {
                background: white;
                padding: 25px;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                display: flex;
                align-items: center;
                gap: 20px;
                border-left: 4px solid;
            }
            .fp-card-primary { border-color: #2271b1; }
            .fp-card-success { border-color: #27ae60; }
            .fp-card-info { border-color: #3498db; }
            .fp-card-warning { border-color: #f39c12; }
            .fp-stat-icon {
                font-size: 42px;
                line-height: 1;
            }
            .fp-stat-value {
                font-size: 36px;
                font-weight: bold;
                color: #2c3e50;
                line-height: 1;
            }
            .fp-stat-label {
                color: #7f8c8d;
                font-size: 13px;
                margin-top: 5px;
            }
            
            /* Charts */
            .fp-dashboard-row:has(.fp-chart-container) {
                grid-template-columns: 2fr 1fr;
            }
            .fp-chart-card,
            .fp-pipeline-card,
            .fp-activity-card,
            .fp-trending-card,
            .fp-team-card,
            .fp-upcoming-card,
            .fp-quick-actions-card {
                background: white;
                padding: 25px;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            
            /* Fix altezza grafico */
            .fp-chart-card {
                min-height: 350px;
                max-height: 450px;
            }
            .fp-chart-card canvas {
                max-height: 350px !important;
                height: 350px !important;
            }
            .fp-chart-card h3,
            .fp-pipeline-card h3,
            .fp-activity-card h3,
            .fp-trending-card h3,
            .fp-team-card h3,
            .fp-upcoming-card h3,
            .fp-quick-actions-card h3 {
                margin-top: 0;
                padding-bottom: 15px;
                border-bottom: 2px solid #f0f0f0;
            }
            
            /* Pipeline */
            .fp-pipeline-stats {
                display: flex;
                align-items: center;
                justify-content: space-around;
                margin: 20px 0;
            }
            .fp-pipeline-item {
                text-align: center;
            }
            .fp-pipeline-count {
                display: block;
                font-size: 32px;
                font-weight: bold;
                margin-bottom: 5px;
            }
            .fp-count-draft { color: #95a5a6; }
            .fp-count-review { color: #f39c12; }
            .fp-count-approved { color: #27ae60; }
            .fp-count-scheduled { color: #3498db; }
            .fp-pipeline-label {
                display: block;
                font-size: 12px;
                color: #666;
            }
            .fp-pipeline-arrow {
                font-size: 24px;
                color: #bdc3c7;
            }
            .fp-productivity-metrics {
                display: flex;
                gap: 30px;
                justify-content: center;
                margin-top: 20px;
                padding-top: 20px;
                border-top: 1px solid #f0f0f0;
            }
            .fp-metric {
                text-align: center;
            }
            .fp-metric strong {
                display: block;
                font-size: 24px;
                color: #2271b1;
            }
            .fp-metric span {
                font-size: 11px;
                color: #666;
            }
            
            /* Activity Feed & Trending */
            .fp-dashboard-row:has(.fp-activity-container) {
                grid-template-columns: 2fr 1fr;
            }
            .fp-activity-feed {
                max-height: 400px;
                overflow-y: auto;
            }
            .fp-activity-item {
                padding: 12px 0;
                border-bottom: 1px solid #f0f0f0;
            }
            .fp-activity-item:last-child {
                border-bottom: none;
            }
            .fp-activity-time {
                font-size: 11px;
                color: #999;
                margin-bottom: 4px;
            }
            .fp-activity-content {
                font-size: 13px;
                line-height: 1.6;
            }
            .fp-activity-status {
                display: inline-block;
                padding: 2px 8px;
                border-radius: 10px;
                font-size: 11px;
                margin-left: 5px;
            }
            .fp-status-draft { background: #ecf0f1; color: #7f8c8d; }
            .fp-status-fp_in_review { background: #fff3cd; color: #856404; }
            .fp-status-fp_approved { background: #d4edda; color: #155724; }
            .fp-status-publish { background: #d1ecf1; color: #0c5460; }
            
            /* Trending */
            .fp-trending-list {
                display: flex;
                flex-direction: column;
                gap: 12px;
            }
            .fp-trending-item {
                display: flex;
                align-items: flex-start;
                gap: 12px;
                padding: 10px;
                background: #f9f9f9;
                border-radius: 6px;
            }
            .fp-trending-rank {
                font-size: 20px;
                font-weight: bold;
                color: #2271b1;
                min-width: 30px;
            }
            .fp-trending-content a {
                display: block;
                font-weight: 600;
                margin-bottom: 4px;
            }
            .fp-trending-stats {
                font-size: 11px;
                color: #666;
            }
            .fp-trending-stats span {
                margin-right: 10px;
            }
            
            /* Team Performance */
            .fp-team-card table {
                margin-top: 15px;
            }
            .fp-team-card td {
                vertical-align: middle;
            }
            .fp-team-card img {
                vertical-align: middle;
                margin-right: 8px;
                border-radius: 50%;
            }
            
            /* Upcoming Publications */
            .fp-upcoming-list {
                display: flex;
                flex-direction: column;
                gap: 12px;
            }
            .fp-upcoming-item {
                display: flex;
                align-items: center;
                gap: 15px;
                padding: 12px;
                background: #f9f9f9;
                border-radius: 6px;
            }
            .fp-upcoming-date {
                background: #2271b1;
                color: white;
                padding: 8px 12px;
                border-radius: 6px;
                text-align: center;
                min-width: 60px;
                font-weight: bold;
            }
            .fp-upcoming-time {
                font-size: 11px;
                margin-top: 3px;
            }
            .fp-upcoming-content a {
                display: block;
                font-weight: 600;
                margin-bottom: 3px;
            }
            .fp-upcoming-author {
                font-size: 12px;
                color: #666;
            }
            
            /* Quick Actions */
            .fp-actions-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
                gap: 15px;
                margin-top: 15px;
            }
            .fp-action-btn {
                display: flex;
                align-items: center;
                gap: 10px;
                padding: 15px 20px;
                text-decoration: none;
                border-radius: 6px;
                font-weight: 600;
                transition: all 0.2s;
            }
            .fp-action-btn .dashicons {
                font-size: 20px;
                width: 20px;
                height: 20px;
            }
            .fp-action-primary {
                background: #2271b1;
                color: white;
            }
            .fp-action-primary:hover {
                background: #135e96;
                color: white;
            }
            .fp-action-workflow {
                background: #f39c12;
                color: white;
            }
            .fp-action-workflow:hover {
                background: #e67e22;
                color: white;
            }
            .fp-action-calendar {
                background: #27ae60;
                color: white;
            }
            .fp-action-calendar:hover {
                background: #229954;
                color: white;
            }
            .fp-action-btn:not([class*="fp-action-"]) {
                background: #ecf0f1;
                color: #2c3e50;
            }
            .fp-action-btn:not([class*="fp-action-"]):hover {
                background: #d5dbdb;
            }
            
            .fp-empty-state {
                text-align: center;
                color: #999;
                padding: 30px;
                font-style: italic;
            }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Inizializza Chart.js
            var ctx = document.getElementById('fp-publications-chart');
            
            if (ctx && typeof Chart !== 'undefined' && fpDashboardData.chartData) {
                new Chart(ctx, {
                    type: 'line',
                    data: fpDashboardData.chartData,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                mode: 'index',
                                intersect: false,
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    precision: 0
                                }
                            }
                        }
                    }
                });
            }
            
            // Auto-refresh ogni 5 minuti
            setInterval(function() {
                location.reload();
            }, 300000);
        });
        </script>
        <?php
    }
    
    /**
     * AJAX handler per refresh dashboard
     */
    public function ajax_refresh_dashboard() {
        // Security check
        check_ajax_referer('fp_dashboard_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('Permessi insufficienti', 'fp-newspaper')]);
        }
        
        // Get parameters
        $date_range = isset($_POST['date_range']) ? absint($_POST['date_range']) : 30;
        $author = isset($_POST['author']) ? sanitize_text_field($_POST['author']) : 'all';
        
        // Validate date range
        $date_range = min(max($date_range, 7), 365); // Between 7 and 365 days
        
        try {
            // Get fresh data
            $data = $this->dashboard->get_dashboard_data();
            $chart_data = $this->dashboard->get_chart_data($date_range);
            $productivity = $this->dashboard->get_productivity_metrics('month');
            $author_stats = $this->dashboard->get_author_stats(10, $author);
            $recent_activity = $this->dashboard->get_recent_activity(10);
            
            // Prepare response
            $response = [
                'stats' => [
                    'published' => $data['overview']['published_today'] ?? 0,
                    'drafts' => $data['overview']['drafts'] ?? 0,
                    'views' => $data['overview']['views_today'] ?? 0,
                    'week' => $data['overview']['published_week'] ?? 0,
                    'month' => $data['overview']['published_month'] ?? 0,
                ],
                'chartData' => $chart_data,
                'productivity' => [
                    'published' => $productivity['published'] ?? 0,
                    'review' => $productivity['in_review'] ?? 0,
                    'drafts' => $productivity['drafts'] ?? 0,
                ],
                'authors' => [
                    'labels' => array_column($author_stats, 'name'),
                    'counts' => array_column($author_stats, 'count'),
                ],
                'activity' => $this->format_activity_feed($recent_activity),
                'timestamp' => current_time('timestamp'),
            ];
            
            wp_send_json_success($response);
            
        } catch (\Exception $e) {
            error_log('FP Newspaper Dashboard Refresh Error: ' . $e->getMessage());
            wp_send_json_error(['message' => __('Errore durante l\'aggiornamento dei dati', 'fp-newspaper')]);
        }
    }
    
    /**
     * Formatta activity feed per JSON response
     * 
     * @param array $activities
     * @return array
     */
    private function format_activity_feed($activities) {
        $formatted = [];
        
        foreach ($activities as $activity) {
            $formatted[] = [
                'title' => $activity['title'] ?? '',
                'time' => $activity['time'] ?? '',
                'icon' => $activity['icon'] ?? 'dashicons-admin-post',
                'type' => $activity['type'] ?? 'post',
            ];
        }
        
        return $formatted;
    }
}

