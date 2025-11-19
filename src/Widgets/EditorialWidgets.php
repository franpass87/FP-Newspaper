<?php
/**
 * Widget Dashboard WordPress per Metriche Editoriali
 *
 * @package FPNewspaper\Widgets
 */

namespace FPNewspaper\Widgets;

use FPNewspaper\Editorial\Dashboard;

defined('ABSPATH') || exit;

/**
 * Widget per WordPress Dashboard nativa
 */
class EditorialWidgets {
    
    /**
     * Costruttore
     */
    public function __construct() {
        add_action('wp_dashboard_setup', [$this, 'add_dashboard_widgets']);
    }
    
    /**
     * Registra widget dashboard
     */
    public function add_dashboard_widgets() {
        // Widget principale statistiche
        wp_add_dashboard_widget(
            'fp_editorial_stats',
            __('📊 Statistiche Editoriali', 'fp-newspaper'),
            [$this, 'render_stats_widget']
        );
        
        // Widget assegnazioni personali
        wp_add_dashboard_widget(
            'fp_my_assignments',
            __('🎯 I Miei Articoli', 'fp-newspaper'),
            [$this, 'render_assignments_widget']
        );
        
        // Widget attività recente
        wp_add_dashboard_widget(
            'fp_recent_activity',
            __('🔔 Attività Recente', 'fp-newspaper'),
            [$this, 'render_activity_widget']
        );
    }
    
    /**
     * Widget statistiche
     */
    public function render_stats_widget() {
        $dashboard = new Dashboard();
        $stats = $dashboard->get_quick_stats();
        $pipeline = $dashboard->get_pipeline_stats();
        
        ?>
        <div class="fp-widget-stats">
            <div class="fp-widget-row">
                <div class="fp-widget-stat">
                    <div class="fp-widget-number" style="color: #27ae60;"><?php echo esc_html($stats['published_today']); ?></div>
                    <div class="fp-widget-label"><?php _e('Pubblicati Oggi', 'fp-newspaper'); ?></div>
                </div>
                <div class="fp-widget-stat">
                    <div class="fp-widget-number" style="color: #3498db;"><?php echo esc_html($stats['published_week']); ?></div>
                    <div class="fp-widget-label"><?php _e('Questa Settimana', 'fp-newspaper'); ?></div>
                </div>
            </div>
            
            <div class="fp-widget-divider"></div>
            
            <div class="fp-widget-pipeline">
                <div class="fp-pipeline-item-small">
                    <span class="fp-pipeline-dot" style="background: #95a5a6;"></span>
                    <span><?php echo esc_html($pipeline['drafts']); ?> <?php _e('bozze', 'fp-newspaper'); ?></span>
                </div>
                <div class="fp-pipeline-item-small">
                    <span class="fp-pipeline-dot" style="background: #f39c12;"></span>
                    <span><?php echo esc_html($pipeline['in_review']); ?> <?php _e('in revisione', 'fp-newspaper'); ?></span>
                </div>
                <div class="fp-pipeline-item-small">
                    <span class="fp-pipeline-dot" style="background: #27ae60;"></span>
                    <span><?php echo esc_html($pipeline['approved']); ?> <?php _e('approvati', 'fp-newspaper'); ?></span>
                </div>
                <div class="fp-pipeline-item-small">
                    <span class="fp-pipeline-dot" style="background: #3498db;"></span>
                    <span><?php echo esc_html($pipeline['scheduled']); ?> <?php _e('programmati', 'fp-newspaper'); ?></span>
                </div>
            </div>
            
            <div class="fp-widget-actions">
                <a href="<?php echo admin_url('admin.php?page=fp-editorial-dashboard'); ?>" class="button button-primary">
                    <?php _e('Dashboard Completa →', 'fp-newspaper'); ?>
                </a>
            </div>
        </div>
        
        <style>
            .fp-widget-row {
                display: flex;
                gap: 20px;
                margin-bottom: 15px;
            }
            .fp-widget-stat {
                flex: 1;
                text-align: center;
                padding: 10px;
                background: #f9f9f9;
                border-radius: 4px;
            }
            .fp-widget-number {
                font-size: 28px;
                font-weight: bold;
                line-height: 1;
            }
            .fp-widget-label {
                font-size: 11px;
                color: #666;
                margin-top: 5px;
            }
            .fp-widget-divider {
                height: 1px;
                background: #e0e0e0;
                margin: 15px 0;
            }
            .fp-widget-pipeline {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 10px;
                margin-bottom: 15px;
            }
            .fp-pipeline-item-small {
                display: flex;
                align-items: center;
                gap: 8px;
                font-size: 13px;
            }
            .fp-pipeline-dot {
                width: 10px;
                height: 10px;
                border-radius: 50%;
                display: inline-block;
            }
            .fp-widget-actions {
                text-align: center;
                padding-top: 10px;
            }
        </style>
        <?php
    }
    
    /**
     * Widget assegnazioni personali
     */
    public function render_assignments_widget() {
        $dashboard = new Dashboard();
        $current_user_id = get_current_user_id();
        $assignments = $dashboard->get_my_assignments($current_user_id);
        
        ?>
        <div class="fp-widget-assignments">
            <?php if (!empty($assignments)): ?>
                <ul class="fp-assignments-list">
                    <?php foreach (array_slice($assignments, 0, 5) as $post): ?>
                        <li class="fp-assignment-item">
                            <div class="fp-assignment-status">
                                <?php
                                $status_icons = [
                                    'fp_in_review' => '⏳',
                                    'fp_needs_changes' => '✏️',
                                    'fp_approved' => '✅',
                                ];
                                echo isset($status_icons[$post->post_status]) ? $status_icons[$post->post_status] : '📝';
                                ?>
                            </div>
                            <div class="fp-assignment-content">
                                <a href="<?php echo esc_url(get_edit_post_link($post->ID)); ?>">
                                    <?php echo esc_html(wp_trim_words($post->post_title, 8)); ?>
                                </a>
                                <div class="fp-assignment-meta">
                                    <?php echo esc_html(human_time_diff(strtotime($post->post_modified), current_time('timestamp'))); ?> fa
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
                
                <?php if (count($assignments) > 5): ?>
                    <div class="fp-widget-footer">
                        <a href="<?php echo admin_url('edit.php?page=fp-newspaper-workflow'); ?>">
                            <?php printf(__('Vedi tutti (%d) →', 'fp-newspaper'), count($assignments)); ?>
                        </a>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <p class="fp-widget-empty">
                    <?php _e('Nessun articolo assegnato al momento', 'fp-newspaper'); ?>
                </p>
                <a href="<?php echo admin_url('post-new.php'); ?>" class="button">
                    <?php _e('Crea Nuovo Articolo', 'fp-newspaper'); ?>
                </a>
            <?php endif; ?>
        </div>
        
        <style>
            .fp-assignments-list {
                list-style: none;
                margin: 0;
                padding: 0;
            }
            .fp-assignment-item {
                display: flex;
                align-items: flex-start;
                gap: 10px;
                padding: 10px 0;
                border-bottom: 1px solid #f0f0f0;
            }
            .fp-assignment-item:last-child {
                border-bottom: none;
            }
            .fp-assignment-status {
                font-size: 18px;
                line-height: 1;
            }
            .fp-assignment-content a {
                display: block;
                font-weight: 600;
                margin-bottom: 3px;
            }
            .fp-assignment-meta {
                font-size: 11px;
                color: #999;
            }
            .fp-widget-footer {
                text-align: center;
                padding-top: 10px;
                border-top: 1px solid #f0f0f0;
                margin-top: 10px;
            }
            .fp-widget-empty {
                text-align: center;
                color: #999;
                padding: 20px 0;
                margin-bottom: 10px;
            }
        </style>
        <?php
    }
    
    /**
     * Widget attività recente
     */
    public function render_activity_widget() {
        $dashboard = new Dashboard();
        $activities = $dashboard->get_recent_activity(5);
        
        ?>
        <div class="fp-widget-activity">
            <?php if (!empty($activities)): ?>
                <ul class="fp-activity-list">
                    <?php foreach ($activities as $activity): ?>
                        <li class="fp-activity-item-small">
                            <div class="fp-activity-time-small"><?php echo esc_html($activity['time_ago']); ?> fa</div>
                            <div class="fp-activity-text">
                                <strong><?php echo esc_html($activity['author']); ?></strong>
                                <?php echo esc_html($activity['action']); ?>:
                                <a href="<?php echo esc_url($activity['edit_link']); ?>">
                                    "<?php echo esc_html(wp_trim_words($activity['post_title'], 6)); ?>"
                                </a>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div class="fp-widget-footer">
                    <a href="<?php echo admin_url('admin.php?page=fp-editorial-dashboard'); ?>">
                        <?php _e('Vedi tutta l\'attività →', 'fp-newspaper'); ?>
                    </a>
                </div>
            <?php else: ?>
                <p class="fp-widget-empty">
                    <?php _e('Nessuna attività recente', 'fp-newspaper'); ?>
                </p>
            <?php endif; ?>
        </div>
        
        <style>
            .fp-activity-list {
                list-style: none;
                margin: 0;
                padding: 0;
            }
            .fp-activity-item-small {
                padding: 10px 0;
                border-bottom: 1px solid #f0f0f0;
            }
            .fp-activity-item-small:last-child {
                border-bottom: none;
            }
            .fp-activity-time-small {
                font-size: 11px;
                color: #999;
                margin-bottom: 3px;
            }
            .fp-activity-text {
                font-size: 13px;
                line-height: 1.5;
            }
        </style>
        <?php
    }
}


