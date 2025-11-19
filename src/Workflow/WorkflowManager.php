<?php
/**
 * Workflow Manager - Gestione stati e approvazioni articoli
 *
 * @package FPNewspaper\Workflow
 */

namespace FPNewspaper\Workflow;

use FPNewspaper\Logger;

defined('ABSPATH') || exit;

/**
 * Gestisce il workflow editoriale con stati custom e sistema approvazioni
 */
class WorkflowManager {
    
    /**
     * Stati workflow custom
     */
    const STATUS_DRAFT = 'draft';
    const STATUS_IN_REVIEW = 'fp_in_review';
    const STATUS_NEEDS_CHANGES = 'fp_needs_changes';
    const STATUS_APPROVED = 'fp_approved';
    const STATUS_SCHEDULED = 'fp_scheduled';
    
    /**
     * Meta keys
     */
    const META_WORKFLOW_STATUS = '_fp_workflow_status';
    const META_ASSIGNED_EDITOR = '_fp_assigned_editor';
    const META_REVIEW_DEADLINE = '_fp_review_deadline';
    const META_WORKFLOW_HISTORY = '_fp_workflow_history';
    
    /**
     * Costruttore
     */
    public function __construct() {
        add_action('init', [$this, 'register_custom_statuses']);
        add_action('transition_post_status', [$this, 'handle_status_transition'], 10, 3);
        add_action('post_submitbox_misc_actions', [$this, 'add_workflow_ui']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        
        // AJAX handlers
        add_action('wp_ajax_fp_send_for_review', [$this, 'ajax_send_for_review']);
        add_action('wp_ajax_fp_approve_article', [$this, 'ajax_approve_article']);
        add_action('wp_ajax_fp_reject_article', [$this, 'ajax_reject_article']);
        add_action('wp_ajax_fp_request_changes', [$this, 'ajax_request_changes']);
    }
    
    /**
     * Registra stati post custom
     */
    public function register_custom_statuses() {
        // In Review
        register_post_status('fp_in_review', [
            'label'                     => _x('In Revisione', 'post status', 'fp-newspaper'),
            'public'                    => false,
            'exclude_from_search'       => true,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop(
                'In Revisione <span class="count">(%s)</span>',
                'In Revisione <span class="count">(%s)</span>',
                'fp-newspaper'
            ),
        ]);
        
        // Needs Changes
        register_post_status('fp_needs_changes', [
            'label'                     => _x('Richiede Modifiche', 'post status', 'fp-newspaper'),
            'public'                    => false,
            'exclude_from_search'       => true,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop(
                'Richiede Modifiche <span class="count">(%s)</span>',
                'Richiede Modifiche <span class="count">(%s)</span>',
                'fp-newspaper'
            ),
        ]);
        
        // Approved
        register_post_status('fp_approved', [
            'label'                     => _x('Approvato', 'post status', 'fp-newspaper'),
            'public'                    => false,
            'exclude_from_search'       => true,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop(
                'Approvato <span class="count">(%s)</span>',
                'Approvato <span class="count">(%s)</span>',
                'fp-newspaper'
            ),
        ]);
        
        // Scheduled (programmato per pubblicazione)
        register_post_status('fp_scheduled', [
            'label'                     => _x('Programmato', 'post status', 'fp-newspaper'),
            'public'                    => false,
            'exclude_from_search'       => true,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop(
                'Programmato <span class="count">(%s)</span>',
                'Programmato <span class="count">(%s)</span>',
                'fp-newspaper'
            ),
        ]);
    }
    
    /**
     * Invia articolo in revisione
     *
     * @param int $post_id
     * @param int $reviewer_id Editor che deve revisionare (opzionale)
     * @param string $deadline Deadline ISO format (opzionale)
     * @return bool|WP_Error
     */
    public function send_for_review($post_id, $reviewer_id = null, $deadline = null) {
        $post = get_post($post_id);
        
        if (!$post || 'post' !== $post->post_type) {
            return new \WP_Error('invalid_post', __('Post non valido', 'fp-newspaper'));
        }
        
        // Verifica permessi
        if (!current_user_can('edit_post', $post_id)) {
            return new \WP_Error('permission_denied', __('Permessi insufficienti', 'fp-newspaper'));
        }
        
        // Cambia stato
        wp_update_post([
            'ID' => $post_id,
            'post_status' => self::STATUS_IN_REVIEW,
        ]);
        
        // Assegna editor se specificato
        if ($reviewer_id) {
            update_post_meta($post_id, self::META_ASSIGNED_EDITOR, absint($reviewer_id));
        }
        
        // Imposta deadline se specificato
        if ($deadline) {
            update_post_meta($post_id, self::META_REVIEW_DEADLINE, sanitize_text_field($deadline));
        }
        
        // Registra nella history
        $this->add_to_history($post_id, 'sent_for_review', [
            'reviewer_id' => $reviewer_id,
            'deadline' => $deadline,
        ]);
        
        // Invia notifica all'editor
        if ($reviewer_id) {
            $this->send_notification($reviewer_id, $post_id, 'review_requested');
        }
        
        Logger::info('Article sent for review', [
            'post_id' => $post_id,
            'reviewer_id' => $reviewer_id,
            'deadline' => $deadline,
        ]);
        
        do_action('fp_newspaper_sent_for_review', $post_id, $reviewer_id);
        
        return true;
    }
    
    /**
     * Approva articolo
     *
     * @param int $post_id
     * @param string $notes Note approvazione (opzionali)
     * @return bool|WP_Error
     */
    public function approve_article($post_id, $notes = '') {
        $post = get_post($post_id);
        
        if (!$post || 'post' !== $post->post_type) {
            return new \WP_Error('invalid_post', __('Post non valido', 'fp-newspaper'));
        }
        
        // Verifica permessi (solo editor+)
        if (!current_user_can('publish_posts')) {
            return new \WP_Error('permission_denied', __('Solo gli editor possono approvare', 'fp-newspaper'));
        }
        
        // Cambia stato
        wp_update_post([
            'ID' => $post_id,
            'post_status' => self::STATUS_APPROVED,
        ]);
        
        // Registra nella history
        $this->add_to_history($post_id, 'approved', [
            'approver_id' => get_current_user_id(),
            'notes' => $notes,
        ]);
        
        // Notifica autore
        $author_id = $post->post_author;
        $this->send_notification($author_id, $post_id, 'article_approved', $notes);
        
        Logger::info('Article approved', [
            'post_id' => $post_id,
            'approver_id' => get_current_user_id(),
        ]);
        
        do_action('fp_newspaper_article_approved', $post_id, get_current_user_id());
        
        return true;
    }
    
    /**
     * Rifiuta articolo e richiedi modifiche
     *
     * @param int $post_id
     * @param string $reason Motivo rifiuto
     * @param array $changes Modifiche richieste
     * @return bool|WP_Error
     */
    public function reject_article($post_id, $reason, $changes = []) {
        $post = get_post($post_id);
        
        if (!$post || 'post' !== $post->post_type) {
            return new \WP_Error('invalid_post', __('Post non valido', 'fp-newspaper'));
        }
        
        // Verifica permessi
        if (!current_user_can('publish_posts')) {
            return new \WP_Error('permission_denied', __('Solo gli editor possono rifiutare', 'fp-newspaper'));
        }
        
        // Cambia stato
        wp_update_post([
            'ID' => $post_id,
            'post_status' => self::STATUS_NEEDS_CHANGES,
        ]);
        
        // Salva modifiche richieste
        update_post_meta($post_id, '_fp_requested_changes', $changes);
        
        // Registra nella history
        $this->add_to_history($post_id, 'rejected', [
            'reviewer_id' => get_current_user_id(),
            'reason' => $reason,
            'changes' => $changes,
        ]);
        
        // Notifica autore
        $author_id = $post->post_author;
        $this->send_notification($author_id, $post_id, 'article_rejected', $reason);
        
        Logger::info('Article rejected', [
            'post_id' => $post_id,
            'reviewer_id' => get_current_user_id(),
            'reason' => $reason,
        ]);
        
        do_action('fp_newspaper_article_rejected', $post_id, $reason);
        
        return true;
    }
    
    /**
     * Richiedi modifiche senza rifiutare completamente
     *
     * @param int $post_id
     * @param array $changes Lista modifiche richieste
     * @return bool|WP_Error
     */
    public function request_changes($post_id, $changes) {
        return $this->reject_article($post_id, __('Modifiche richieste', 'fp-newspaper'), $changes);
    }
    
    /**
     * Pubblica articolo approvato
     *
     * @param int $post_id
     * @param string $publish_date Data pubblicazione (opzionale)
     * @return bool|WP_Error
     */
    public function publish_approved($post_id, $publish_date = null) {
        $post = get_post($post_id);
        
        if (!$post || 'post' !== $post->post_type) {
            return new \WP_Error('invalid_post', __('Post non valido', 'fp-newspaper'));
        }
        
        // Verifica permessi
        if (!current_user_can('publish_posts')) {
            return new \WP_Error('permission_denied', __('Permessi insufficienti', 'fp-newspaper'));
        }
        
        // Verifica che sia approvato
        if ($post->post_status !== self::STATUS_APPROVED) {
            return new \WP_Error('not_approved', __('L\'articolo deve essere approvato prima', 'fp-newspaper'));
        }
        
        $update_data = ['ID' => $post_id];
        
        if ($publish_date) {
            $update_data['post_date'] = sanitize_text_field($publish_date);
            $update_data['post_date_gmt'] = get_gmt_from_date($publish_date);
            $update_data['post_status'] = 'future';
        } else {
            $update_data['post_status'] = 'publish';
        }
        
        wp_update_post($update_data);
        
        // Registra nella history
        $this->add_to_history($post_id, 'published', [
            'publisher_id' => get_current_user_id(),
            'publish_date' => $publish_date,
        ]);
        
        Logger::info('Article published', [
            'post_id' => $post_id,
            'publisher_id' => get_current_user_id(),
            'scheduled' => !empty($publish_date),
        ]);
        
        do_action('fp_newspaper_article_published', $post_id);
        
        return true;
    }
    
    /**
     * Ottiene articoli in attesa di revisione
     *
     * @param int $reviewer_id Filtra per reviewer (opzionale)
     * @return array
     */
    public function get_pending_reviews($reviewer_id = null) {
        $args = [
            'post_type' => 'post',
            'post_status' => self::STATUS_IN_REVIEW,
            'posts_per_page' => -1,
            'orderby' => 'modified',
            'order' => 'ASC',
        ];
        
        if ($reviewer_id) {
            $args['meta_query'] = [
                [
                    'key' => self::META_ASSIGNED_EDITOR,
                    'value' => absint($reviewer_id),
                ],
            ];
        }
        
        $query = new \WP_Query($args);
        return $query->posts;
    }
    
    /**
     * Ottiene articoli assegnati a un utente
     *
     * @param int $user_id
     * @return array
     */
    public function get_my_assignments($user_id) {
        $args = [
            'post_type' => 'post',
            'post_status' => [self::STATUS_IN_REVIEW, self::STATUS_NEEDS_CHANGES],
            'posts_per_page' => -1,
            'meta_query' => [
                'relation' => 'OR',
                [
                    'key' => self::META_ASSIGNED_EDITOR,
                    'value' => absint($user_id),
                ],
            ],
            'orderby' => 'modified',
            'order' => 'ASC',
        ];
        
        $query = new \WP_Query($args);
        return $query->posts;
    }
    
    /**
     * Ottiene deadline imminenti
     *
     * @param int $days Giorni in anticipo (default: 3)
     * @return array
     */
    public function get_upcoming_deadlines($days = 3) {
        $cutoff_date = date('Y-m-d', strtotime("+{$days} days"));
        
        $args = [
            'post_type' => 'post',
            'post_status' => [self::STATUS_IN_REVIEW, self::STATUS_APPROVED],
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => self::META_REVIEW_DEADLINE,
                    'value' => $cutoff_date,
                    'compare' => '<=',
                    'type' => 'DATE',
                ],
            ],
            'orderby' => 'meta_value',
            'order' => 'ASC',
        ];
        
        $query = new \WP_Query($args);
        return $query->posts;
    }
    
    /**
     * Ottiene history workflow di un articolo
     *
     * @param int $post_id
     * @return array
     */
    public function get_workflow_history($post_id) {
        $history = get_post_meta($post_id, self::META_WORKFLOW_HISTORY, true);
        return is_array($history) ? $history : [];
    }
    
    /**
     * Aggiunge evento alla history
     *
     * @param int $post_id
     * @param string $action
     * @param array $data
     */
    private function add_to_history($post_id, $action, $data = []) {
        $history = $this->get_workflow_history($post_id);
        
        $history[] = [
            'timestamp' => current_time('timestamp'),
            'datetime' => current_time('mysql'),
            'action' => $action,
            'user_id' => get_current_user_id(),
            'user_name' => wp_get_current_user()->display_name,
            'data' => $data,
        ];
        
        update_post_meta($post_id, self::META_WORKFLOW_HISTORY, $history);
    }
    
    /**
     * Handler transizione stati
     *
     * @param string $new_status
     * @param string $old_status
     * @param \WP_Post $post
     */
    public function handle_status_transition($new_status, $old_status, $post) {
        if ('post' !== $post->post_type) {
            return;
        }
        
        if ($new_status === $old_status) {
            return;
        }
        
        // Log transizione
        Logger::info('Post status transition', [
            'post_id' => $post->ID,
            'from' => $old_status,
            'to' => $new_status,
        ]);
        
        // Aggiungi a history
        $this->add_to_history($post->ID, 'status_changed', [
            'from' => $old_status,
            'to' => $new_status,
        ]);
        
        do_action('fp_newspaper_status_transition', $post->ID, $new_status, $old_status);
    }
    
    /**
     * Invia notifica email
     *
     * @param int $user_id
     * @param int $post_id
     * @param string $type Tipo notifica
     * @param string $message Messaggio opzionale
     */
    private function send_notification($user_id, $post_id, $type, $message = '') {
        $user = get_userdata($user_id);
        $post = get_post($post_id);
        
        if (!$user || !$post) {
            return;
        }
        
        $subject = '';
        $body = '';
        $post_link = get_edit_post_link($post_id, 'raw');
        
        switch ($type) {
            case 'review_requested':
                $subject = sprintf(__('[%s] Nuovo articolo da revisionare', 'fp-newspaper'), get_bloginfo('name'));
                $body = sprintf(
                    __("Ciao %s,\n\nTi è stato assegnato un nuovo articolo da revisionare:\n\n'%s'\n\nApri l'articolo: %s\n\nGrazie!", 'fp-newspaper'),
                    $user->display_name,
                    $post->post_title,
                    $post_link
                );
                break;
                
            case 'article_approved':
                $subject = sprintf(__('[%s] Il tuo articolo è stato approvato', 'fp-newspaper'), get_bloginfo('name'));
                $body = sprintf(
                    __("Ciao %s,\n\nIl tuo articolo '%s' è stato approvato!\n\n%s\n\nApri l'articolo: %s", 'fp-newspaper'),
                    $user->display_name,
                    $post->post_title,
                    $message ? "Note: {$message}" : '',
                    $post_link
                );
                break;
                
            case 'article_rejected':
                $subject = sprintf(__('[%s] Modifiche richieste per il tuo articolo', 'fp-newspaper'), get_bloginfo('name'));
                $body = sprintf(
                    __("Ciao %s,\n\nSono state richieste modifiche per l'articolo '%s'.\n\nMotivo: %s\n\nApri l'articolo: %s", 'fp-newspaper'),
                    $user->display_name,
                    $post->post_title,
                    $message,
                    $post_link
                );
                break;
        }
        
        if ($subject && $body) {
            wp_mail($user->user_email, $subject, $body);
            
            Logger::debug('Workflow notification sent', [
                'user_id' => $user_id,
                'post_id' => $post_id,
                'type' => $type,
            ]);
        }
    }
    
    /**
     * Aggiunge UI workflow nel publish box
     *
     * @param \WP_Post $post
     */
    public function add_workflow_ui($post) {
        if ('post' !== $post->post_type) {
            return;
        }
        
        $current_status = $post->post_status;
        $assigned_editor = get_post_meta($post->ID, self::META_ASSIGNED_EDITOR, true);
        $deadline = get_post_meta($post->ID, self::META_REVIEW_DEADLINE, true);
        
        ?>
        <div class="misc-pub-section fp-workflow-section">
            <strong><?php _e('Workflow Editoriale:', 'fp-newspaper'); ?></strong>
            
            <div style="margin-top: 10px;">
                <label>
                    <?php _e('Stato:', 'fp-newspaper'); ?>
                    <strong><?php echo esc_html($this->get_status_label($current_status)); ?></strong>
                </label>
            </div>
            
            <?php if ($assigned_editor): ?>
                <div style="margin-top: 5px;">
                    <label>
                        <?php _e('Assegnato a:', 'fp-newspaper'); ?>
                        <?php
                        $editor = get_userdata($assigned_editor);
                        echo $editor ? esc_html($editor->display_name) : '-';
                        ?>
                    </label>
                </div>
            <?php endif; ?>
            
            <?php if ($deadline): ?>
                <div style="margin-top: 5px;">
                    <label>
                        <?php _e('Deadline:', 'fp-newspaper'); ?>
                        <?php echo esc_html(date_i18n('d/m/Y H:i', strtotime($deadline))); ?>
                    </label>
                </div>
            <?php endif; ?>
            
            <div class="fp-workflow-actions" style="margin-top: 10px;">
                <?php if ($current_status === 'draft'): ?>
                    <button type="button" class="button fp-send-review" data-post-id="<?php echo esc_attr($post->ID); ?>">
                        <?php _e('Invia in Revisione', 'fp-newspaper'); ?>
                    </button>
                <?php endif; ?>
                
                <?php if ($current_status === self::STATUS_IN_REVIEW && current_user_can('publish_posts')): ?>
                    <button type="button" class="button button-primary fp-approve" data-post-id="<?php echo esc_attr($post->ID); ?>">
                        <?php _e('Approva', 'fp-newspaper'); ?>
                    </button>
                    <button type="button" class="button fp-reject" data-post-id="<?php echo esc_attr($post->ID); ?>">
                        <?php _e('Richiedi Modifiche', 'fp-newspaper'); ?>
                    </button>
                <?php endif; ?>
                
                <?php if ($current_status === self::STATUS_APPROVED && current_user_can('publish_posts')): ?>
                    <button type="button" class="button button-primary fp-publish-now" data-post-id="<?php echo esc_attr($post->ID); ?>">
                        <?php _e('Pubblica Ora', 'fp-newspaper'); ?>
                    </button>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Ottiene label localizzata per stato
     *
     * @param string $status
     * @return string
     */
    private function get_status_label($status) {
        $labels = [
            'draft' => __('Bozza', 'fp-newspaper'),
            self::STATUS_IN_REVIEW => __('In Revisione', 'fp-newspaper'),
            self::STATUS_NEEDS_CHANGES => __('Richiede Modifiche', 'fp-newspaper'),
            self::STATUS_APPROVED => __('Approvato', 'fp-newspaper'),
            self::STATUS_SCHEDULED => __('Programmato', 'fp-newspaper'),
            'publish' => __('Pubblicato', 'fp-newspaper'),
        ];
        
        return $labels[$status] ?? $status;
    }
    
    /**
     * Enqueue assets
     */
    public function enqueue_assets($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) {
            return;
        }
        
        global $post;
        if (!$post || 'post' !== $post->post_type) {
            return;
        }
        
        wp_add_inline_script('jquery', $this->get_inline_js());
    }
    
    /**
     * JavaScript inline per workflow actions
     */
    private function get_inline_js() {
        ob_start();
        ?>
        jQuery(document).ready(function($) {
            // Send for review
            $('.fp-send-review').on('click', function(e) {
                e.preventDefault();
                var postId = $(this).data('post-id');
                
                if (confirm('<?php echo esc_js(__('Inviare questo articolo in revisione?', 'fp-newspaper')); ?>')) {
                    $.post(ajaxurl, {
                        action: 'fp_send_for_review',
                        post_id: postId,
                        nonce: '<?php echo wp_create_nonce('fp_workflow_nonce'); ?>'
                    }, function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert(response.data || 'Errore');
                        }
                    });
                }
            });
            
            // Approve
            $('.fp-approve').on('click', function(e) {
                e.preventDefault();
                var postId = $(this).data('post-id');
                var notes = prompt('<?php echo esc_js(__('Note approvazione (opzionali):', 'fp-newspaper')); ?>');
                
                $.post(ajaxurl, {
                    action: 'fp_approve_article',
                    post_id: postId,
                    notes: notes || '',
                    nonce: '<?php echo wp_create_nonce('fp_workflow_nonce'); ?>'
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data || 'Errore');
                    }
                });
            });
            
            // Reject
            $('.fp-reject').on('click', function(e) {
                e.preventDefault();
                var postId = $(this).data('post-id');
                var reason = prompt('<?php echo esc_js(__('Motivo del rifiuto:', 'fp-newspaper')); ?>');
                
                if (!reason) return;
                
                $.post(ajaxurl, {
                    action: 'fp_reject_article',
                    post_id: postId,
                    reason: reason,
                    nonce: '<?php echo wp_create_nonce('fp_workflow_nonce'); ?>'
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data || 'Errore');
                    }
                });
            });
        });
        <?php
        return ob_get_clean();
    }
    
    /**
     * AJAX: Send for review
     */
    public function ajax_send_for_review() {
        check_ajax_referer('fp_workflow_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Permessi insufficienti', 'fp-newspaper'));
        }
        
        $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
        
        $result = $this->send_for_review($post_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        wp_send_json_success(__('Articolo inviato in revisione', 'fp-newspaper'));
    }
    
    /**
     * AJAX: Approve article
     */
    public function ajax_approve_article() {
        check_ajax_referer('fp_workflow_nonce', 'nonce');
        
        if (!current_user_can('publish_posts')) {
            wp_send_json_error(__('Solo gli editor possono approvare', 'fp-newspaper'));
        }
        
        $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
        $notes = isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '';
        
        $result = $this->approve_article($post_id, $notes);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        wp_send_json_success(__('Articolo approvato', 'fp-newspaper'));
    }
    
    /**
     * AJAX: Reject article
     */
    public function ajax_reject_article() {
        check_ajax_referer('fp_workflow_nonce', 'nonce');
        
        if (!current_user_can('publish_posts')) {
            wp_send_json_error(__('Solo gli editor possono rifiutare', 'fp-newspaper'));
        }
        
        $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
        $reason = isset($_POST['reason']) ? sanitize_textarea_field($_POST['reason']) : '';
        
        $result = $this->reject_article($post_id, $reason);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        wp_send_json_success(__('Modifiche richieste', 'fp-newspaper'));
    }
    
    /**
     * Ottiene statistiche workflow
     *
     * @return array
     */
    public function get_stats() {
        return [
            'in_review' => $this->count_by_status(self::STATUS_IN_REVIEW),
            'needs_changes' => $this->count_by_status(self::STATUS_NEEDS_CHANGES),
            'approved' => $this->count_by_status(self::STATUS_APPROVED),
            'scheduled' => $this->count_by_status(self::STATUS_SCHEDULED),
        ];
    }
    
    /**
     * Conta articoli per stato
     *
     * @param string $status
     * @return int
     */
    private function count_by_status($status) {
        $count = wp_count_posts('post');
        return isset($count->$status) ? (int) $count->$status : 0;
    }
}


