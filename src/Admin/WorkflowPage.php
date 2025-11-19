<?php
/**
 * Pagina Admin Workflow
 *
 * @package FPNewspaper\Admin
 */

namespace FPNewspaper\Admin;

use FPNewspaper\Workflow\WorkflowManager;
use FPNewspaper\Workflow\Roles;

defined('ABSPATH') || exit;

/**
 * Dashboard workflow editoriale
 */
class WorkflowPage {
    
    /**
     * @var WorkflowManager
     */
    private $workflow;
    
    /**
     * Costruttore
     */
    public function __construct() {
        $this->workflow = new WorkflowManager();
        
        add_action('admin_menu', [$this, 'add_menu_page']);
    }
    
    /**
     * Aggiunge pagina menu
     */
    public function add_menu_page() {
        add_submenu_page(
            'edit.php',
            __('Workflow Editoriale', 'fp-newspaper'),
            __('📋 Workflow', 'fp-newspaper'),
            'edit_posts',
            'fp-newspaper-workflow',
            [$this, 'render_page']
        );
    }
    
    /**
     * Renderizza pagina
     */
    public function render_page() {
        if (!current_user_can('edit_posts')) {
            wp_die(__('Permessi insufficienti', 'fp-newspaper'));
        }
        
        $current_user_id = get_current_user_id();
        $my_assignments = $this->workflow->get_my_assignments($current_user_id);
        $pending_reviews = $this->workflow->get_pending_reviews();
        $upcoming_deadlines = $this->workflow->get_upcoming_deadlines(7);
        $stats = $this->workflow->get_stats();
        
        ?>
        <div class="wrap fp-workflow-page">
            <h1><?php _e('📋 Workflow Editoriale', 'fp-newspaper'); ?></h1>
            
            <!-- Statistiche -->
            <div class="fp-workflow-stats">
                <div class="fp-stat-box fp-stat-review">
                    <div class="fp-stat-number"><?php echo esc_html($stats['in_review']); ?></div>
                    <div class="fp-stat-label"><?php _e('In Revisione', 'fp-newspaper'); ?></div>
                </div>
                <div class="fp-stat-box fp-stat-changes">
                    <div class="fp-stat-number"><?php echo esc_html($stats['needs_changes']); ?></div>
                    <div class="fp-stat-label"><?php _e('Richiedono Modifiche', 'fp-newspaper'); ?></div>
                </div>
                <div class="fp-stat-box fp-stat-approved">
                    <div class="fp-stat-number"><?php echo esc_html($stats['approved']); ?></div>
                    <div class="fp-stat-label"><?php _e('Approvati', 'fp-newspaper'); ?></div>
                </div>
                <div class="fp-stat-box fp-stat-scheduled">
                    <div class="fp-stat-number"><?php echo esc_html($stats['scheduled']); ?></div>
                    <div class="fp-stat-label"><?php _e('Programmati', 'fp-newspaper'); ?></div>
                </div>
            </div>
            
            <!-- My Assignments -->
            <?php if (!empty($my_assignments)): ?>
                <div class="fp-workflow-section">
                    <h2><?php _e('🎯 Assegnati a Me', 'fp-newspaper'); ?></h2>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Articolo', 'fp-newspaper'); ?></th>
                                <th><?php _e('Autore', 'fp-newspaper'); ?></th>
                                <th><?php _e('Stato', 'fp-newspaper'); ?></th>
                                <th><?php _e('Deadline', 'fp-newspaper'); ?></th>
                                <th><?php _e('Azioni', 'fp-newspaper'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($my_assignments as $post): ?>
                                <?php
                                $deadline = get_post_meta($post->ID, WorkflowManager::META_REVIEW_DEADLINE, true);
                                $is_overdue = $deadline && strtotime($deadline) < current_time('timestamp');
                                ?>
                                <tr>
                                    <td>
                                        <strong>
                                            <a href="<?php echo esc_url(get_edit_post_link($post->ID)); ?>">
                                                <?php echo esc_html($post->post_title ?: __('(Senza titolo)', 'fp-newspaper')); ?>
                                            </a>
                                        </strong>
                                    </td>
                                    <td><?php echo esc_html(get_the_author_meta('display_name', $post->post_author)); ?></td>
                                    <td>
                                        <span class="fp-status-badge fp-status-<?php echo esc_attr($post->post_status); ?>">
                                            <?php echo esc_html($this->get_status_label($post->post_status)); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($deadline): ?>
                                            <span class="<?php echo $is_overdue ? 'fp-deadline-overdue' : ''; ?>">
                                                <?php echo esc_html(date_i18n('d/m/Y', strtotime($deadline))); ?>
                                                <?php if ($is_overdue): ?>
                                                    <span class="dashicons dashicons-warning" style="color: #d63638;"></span>
                                                <?php endif; ?>
                                            </span>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="<?php echo esc_url(get_edit_post_link($post->ID)); ?>" class="button button-small">
                                            <?php _e('Apri', 'fp-newspaper'); ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            
            <!-- Pending Reviews (solo per editor+) -->
            <?php if (Roles::can_approve() && !empty($pending_reviews)): ?>
                <div class="fp-workflow-section">
                    <h2><?php _e('⏳ In Attesa di Revisione', 'fp-newspaper'); ?></h2>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Articolo', 'fp-newspaper'); ?></th>
                                <th><?php _e('Autore', 'fp-newspaper'); ?></th>
                                <th><?php _e('Modificato', 'fp-newspaper'); ?></th>
                                <th><?php _e('Assegnato a', 'fp-newspaper'); ?></th>
                                <th><?php _e('Azioni', 'fp-newspaper'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pending_reviews as $post): ?>
                                <?php
                                $assigned_editor = get_post_meta($post->ID, WorkflowManager::META_ASSIGNED_EDITOR, true);
                                ?>
                                <tr>
                                    <td>
                                        <strong>
                                            <a href="<?php echo esc_url(get_edit_post_link($post->ID)); ?>">
                                                <?php echo esc_html($post->post_title ?: __('(Senza titolo)', 'fp-newspaper')); ?>
                                            </a>
                                        </strong>
                                    </td>
                                    <td><?php echo esc_html(get_the_author_meta('display_name', $post->post_author)); ?></td>
                                    <td><?php echo esc_html(human_time_diff(strtotime($post->post_modified), current_time('timestamp')) . ' fa'); ?></td>
                                    <td>
                                        <?php
                                        if ($assigned_editor) {
                                            $editor = get_userdata($assigned_editor);
                                            echo $editor ? esc_html($editor->display_name) : '-';
                                        } else {
                                            echo '<em>' . __('Non assegnato', 'fp-newspaper') . '</em>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <a href="<?php echo esc_url(get_edit_post_link($post->ID)); ?>" class="button button-small button-primary">
                                            <?php _e('Revisiona', 'fp-newspaper'); ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            
            <!-- Upcoming Deadlines -->
            <?php if (!empty($upcoming_deadlines)): ?>
                <div class="fp-workflow-section">
                    <h2><?php _e('⏰ Deadline Imminenti (prossimi 7 giorni)', 'fp-newspaper'); ?></h2>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Articolo', 'fp-newspaper'); ?></th>
                                <th><?php _e('Stato', 'fp-newspaper'); ?></th>
                                <th><?php _e('Deadline', 'fp-newspaper'); ?></th>
                                <th><?php _e('Giorni Rimanenti', 'fp-newspaper'); ?></th>
                                <th><?php _e('Azioni', 'fp-newspaper'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($upcoming_deadlines as $post): ?>
                                <?php
                                $deadline = get_post_meta($post->ID, WorkflowManager::META_REVIEW_DEADLINE, true);
                                $days_remaining = ceil((strtotime($deadline) - current_time('timestamp')) / DAY_IN_SECONDS);
                                $is_overdue = $days_remaining < 0;
                                ?>
                                <tr class="<?php echo $is_overdue ? 'fp-row-overdue' : ''; ?>">
                                    <td>
                                        <strong>
                                            <a href="<?php echo esc_url(get_edit_post_link($post->ID)); ?>">
                                                <?php echo esc_html($post->post_title); ?>
                                            </a>
                                        </strong>
                                    </td>
                                    <td>
                                        <span class="fp-status-badge fp-status-<?php echo esc_attr($post->post_status); ?>">
                                            <?php echo esc_html($this->get_status_label($post->post_status)); ?>
                                        </span>
                                    </td>
                                    <td><?php echo esc_html(date_i18n('d/m/Y H:i', strtotime($deadline))); ?></td>
                                    <td>
                                        <strong class="<?php echo $is_overdue ? 'fp-overdue-text' : ''; ?>">
                                            <?php
                                            if ($is_overdue) {
                                                printf(__('%d giorni in ritardo', 'fp-newspaper'), abs($days_remaining));
                                            } else {
                                                printf(_n('%d giorno', '%d giorni', $days_remaining, 'fp-newspaper'), $days_remaining);
                                            }
                                            ?>
                                        </strong>
                                    </td>
                                    <td>
                                        <a href="<?php echo esc_url(get_edit_post_link($post->ID)); ?>" class="button button-small">
                                            <?php _e('Apri', 'fp-newspaper'); ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        
        <style>
            .fp-workflow-stats {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 20px;
                margin: 20px 0;
            }
            .fp-stat-box {
                background: white;
                padding: 20px;
                border-radius: 8px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                text-align: center;
            }
            .fp-stat-number {
                font-size: 36px;
                font-weight: bold;
                margin-bottom: 5px;
            }
            .fp-stat-label {
                color: #666;
                font-size: 13px;
            }
            .fp-stat-review .fp-stat-number { color: #f39c12; }
            .fp-stat-changes .fp-stat-number { color: #e74c3c; }
            .fp-stat-approved .fp-stat-number { color: #27ae60; }
            .fp-stat-scheduled .fp-stat-number { color: #3498db; }
            
            .fp-workflow-section {
                background: white;
                padding: 20px;
                margin: 20px 0;
                border-radius: 8px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }
            .fp-workflow-section h2 {
                margin-top: 0;
                padding-bottom: 10px;
                border-bottom: 2px solid #f0f0f0;
            }
            
            .fp-status-badge {
                display: inline-block;
                padding: 4px 12px;
                border-radius: 12px;
                font-size: 12px;
                font-weight: 600;
            }
            .fp-status-draft { background: #ecf0f1; color: #7f8c8d; }
            .fp-status-fp_in_review { background: #fff3cd; color: #856404; }
            .fp-status-fp_needs_changes { background: #f8d7da; color: #721c24; }
            .fp-status-fp_approved { background: #d4edda; color: #155724; }
            .fp-status-fp_scheduled { background: #d1ecf1; color: #0c5460; }
            
            .fp-deadline-overdue { color: #d63638; font-weight: bold; }
            .fp-overdue-text { color: #d63638; }
            .fp-row-overdue { background: #fff5f5; }
        </style>
        <?php
    }
    
    /**
     * Ottiene label per stato
     */
    private function get_status_label($status) {
        $labels = [
            'draft' => __('Bozza', 'fp-newspaper'),
            'fp_in_review' => __('In Revisione', 'fp-newspaper'),
            'fp_needs_changes' => __('Richiede Modifiche', 'fp-newspaper'),
            'fp_approved' => __('Approvato', 'fp-newspaper'),
            'fp_scheduled' => __('Programmato', 'fp-newspaper'),
            'publish' => __('Pubblicato', 'fp-newspaper'),
        ];
        
        return $labels[$status] ?? $status;
    }
}


