<?php
/**
 * Sistema Note Interne per Redazione
 *
 * @package FPNewspaper\Workflow
 */

namespace FPNewspaper\Workflow;

use FPNewspaper\Logger;

defined('ABSPATH') || exit;

/**
 * Gestisce note interne e comunicazioni tra redattori
 */
class InternalNotes {
    
    /**
     * Meta key per note
     */
    const META_INTERNAL_NOTES = '_fp_internal_notes';
    
    /**
     * Costruttore
     */
    public function __construct() {
        add_action('add_meta_boxes', [$this, 'add_meta_box']);
        add_action('save_post', [$this, 'save_notes'], 10, 2);
        add_action('wp_ajax_fp_add_note', [$this, 'ajax_add_note']);
        add_action('wp_ajax_fp_delete_note', [$this, 'ajax_delete_note']);
    }
    
    /**
     * Aggiunge meta box note interne
     */
    public function add_meta_box() {
        add_meta_box(
            'fp_internal_notes',
            __('📝 Note Redazionali (Interne)', 'fp-newspaper'),
            [$this, 'render_meta_box'],
            'post',
            'normal',
            'high'
        );
    }
    
    /**
     * Renderizza meta box
     *
     * @param \WP_Post $post
     */
    public function render_meta_box($post) {
        wp_nonce_field('fp_internal_notes_nonce', 'fp_internal_notes_nonce');
        
        $notes = $this->get_notes($post->ID);
        $current_user = wp_get_current_user();
        
        ?>
        <div class="fp-internal-notes-container">
            <p class="description">
                <?php _e('Note visibili solo al team editoriale. NON pubbliche.', 'fp-newspaper'); ?>
            </p>
            
            <!-- Note List -->
            <div class="fp-notes-list" id="fp-notes-list">
                <?php if (empty($notes)): ?>
                    <p class="fp-no-notes">
                        <em><?php _e('Nessuna nota ancora. Aggiungi la prima!', 'fp-newspaper'); ?></em>
                    </p>
                <?php else: ?>
                    <?php foreach ($notes as $index => $note): ?>
                        <div class="fp-note-item" data-note-index="<?php echo esc_attr($index); ?>">
                            <div class="fp-note-header">
                                <div class="fp-note-author">
                                    <?php
                                    $author = get_userdata($note['user_id']);
                                    echo get_avatar($note['user_id'], 24);
                                    echo ' <strong>' . esc_html($author ? $author->display_name : 'Utente sconosciuto') . '</strong>';
                                    ?>
                                </div>
                                <div class="fp-note-meta">
                                    <span class="fp-note-date">
                                        <?php echo esc_html(human_time_diff($note['timestamp'], current_time('timestamp'))) . ' fa'; ?>
                                    </span>
                                    <?php if ($note['user_id'] === get_current_user_id() || current_user_can('delete_others_posts')): ?>
                                        <button type="button" class="fp-note-delete" data-note-index="<?php echo esc_attr($index); ?>">
                                            <?php _e('Elimina', 'fp-newspaper'); ?>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="fp-note-content">
                                <?php echo nl2br(esc_html($note['content'])); ?>
                                
                                <?php if (!empty($note['mentions'])): ?>
                                    <div class="fp-note-mentions">
                                        <?php _e('Menzionati:', 'fp-newspaper'); ?>
                                        <?php foreach ($note['mentions'] as $user_id): ?>
                                            <?php
                                            $mentioned_user = get_userdata($user_id);
                                            if ($mentioned_user) {
                                                echo '<span class="fp-mention">@' . esc_html($mentioned_user->display_name) . '</span> ';
                                            }
                                            ?>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Add Note Form -->
            <div class="fp-add-note-form">
                <textarea 
                    id="fp-new-note-content" 
                    placeholder="<?php esc_attr_e('Scrivi una nota... Usa @nome per menzionare qualcuno', 'fp-newspaper'); ?>"
                    rows="3"
                    class="fp-note-textarea"
                ></textarea>
                <button type="button" class="button button-primary fp-add-note-btn" data-post-id="<?php echo esc_attr($post->ID); ?>">
                    <?php _e('Aggiungi Nota', 'fp-newspaper'); ?>
                </button>
            </div>
        </div>
        
        <style>
            .fp-internal-notes-container { margin: 15px 0; }
            .fp-notes-list { margin: 15px 0; max-height: 400px; overflow-y: auto; }
            .fp-note-item { background: #f9f9f9; padding: 12px; margin-bottom: 10px; border-left: 3px solid #2271b1; border-radius: 3px; }
            .fp-note-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
            .fp-note-author { display: flex; align-items: center; gap: 8px; }
            .fp-note-meta { display: flex; align-items: center; gap: 10px; font-size: 12px; color: #666; }
            .fp-note-delete { color: #b32d2e; background: none; border: none; cursor: pointer; text-decoration: underline; font-size: 11px; }
            .fp-note-delete:hover { color: #d63638; }
            .fp-note-content { color: #333; line-height: 1.6; }
            .fp-note-mentions { margin-top: 8px; font-size: 11px; color: #666; }
            .fp-mention { background: #e7f3ff; color: #2271b1; padding: 2px 6px; border-radius: 3px; margin-right: 4px; }
            .fp-no-notes { text-align: center; padding: 20px; color: #666; }
            .fp-add-note-form { margin-top: 15px; }
            .fp-note-textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 3px; font-family: inherit; }
            .fp-add-note-btn { margin-top: 8px; }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Add note
            $('.fp-add-note-btn').on('click', function() {
                var postId = $(this).data('post-id');
                var content = $('#fp-new-note-content').val().trim();
                
                if (!content) {
                    alert('<?php echo esc_js(__('Inserisci il contenuto della nota', 'fp-newspaper')); ?>');
                    return;
                }
                
                $.post(ajaxurl, {
                    action: 'fp_add_note',
                    post_id: postId,
                    content: content,
                    nonce: '<?php echo wp_create_nonce('fp_notes_nonce'); ?>'
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data || 'Errore');
                    }
                });
            });
            
            // Delete note
            $('.fp-note-delete').on('click', function() {
                if (!confirm('<?php echo esc_js(__('Eliminare questa nota?', 'fp-newspaper')); ?>')) {
                    return;
                }
                
                var noteIndex = $(this).data('note-index');
                var postId = $('.fp-add-note-btn').data('post-id');
                
                $.post(ajaxurl, {
                    action: 'fp_delete_note',
                    post_id: postId,
                    note_index: noteIndex,
                    nonce: '<?php echo wp_create_nonce('fp_notes_nonce'); ?>'
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data || 'Errore');
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Salva note (non usato con AJAX ma manteniamo per compatibilità)
     */
    public function save_notes($post_id, $post) {
        // Verifiche standard
        if (!isset($_POST['fp_internal_notes_nonce']) || 
            !wp_verify_nonce($_POST['fp_internal_notes_nonce'], 'fp_internal_notes_nonce')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
    }
    
    /**
     * Ottiene tutte le note di un post
     *
     * @param int $post_id
     * @return array
     */
    public function get_notes($post_id) {
        $notes = get_post_meta($post_id, self::META_INTERNAL_NOTES, true);
        return is_array($notes) ? $notes : [];
    }
    
    /**
     * Aggiunge una nota
     *
     * @param int $post_id
     * @param string $content
     * @param int $user_id
     * @return bool
     */
    public function add_note($post_id, $content, $user_id = null) {
        $user_id = $user_id ?? get_current_user_id();
        $notes = $this->get_notes($post_id);
        
        // Estrai menzioni (@username)
        $mentions = $this->extract_mentions($content);
        
        $note = [
            'timestamp' => current_time('timestamp'),
            'datetime' => current_time('mysql'),
            'user_id' => $user_id,
            'content' => sanitize_textarea_field($content),
            'mentions' => $mentions,
        ];
        
        $notes[] = $note;
        update_post_meta($post_id, self::META_INTERNAL_NOTES, $notes);
        
        // Notifica utenti menzionati
        foreach ($mentions as $mentioned_user_id) {
            $this->send_mention_notification($mentioned_user_id, $post_id, $content, $user_id);
        }
        
        Logger::debug('Internal note added', [
            'post_id' => $post_id,
            'user_id' => $user_id,
            'mentions' => count($mentions),
        ]);
        
        do_action('fp_newspaper_note_added', $post_id, $note);
        
        return true;
    }
    
    /**
     * Elimina una nota
     *
     * @param int $post_id
     * @param int $note_index
     * @return bool
     */
    public function delete_note($post_id, $note_index) {
        $notes = $this->get_notes($post_id);
        
        if (!isset($notes[$note_index])) {
            return false;
        }
        
        // Verifica permessi
        $note = $notes[$note_index];
        if ($note['user_id'] !== get_current_user_id() && !current_user_can('delete_others_posts')) {
            return false;
        }
        
        unset($notes[$note_index]);
        $notes = array_values($notes); // Re-index
        
        update_post_meta($post_id, self::META_INTERNAL_NOTES, $notes);
        
        Logger::debug('Internal note deleted', [
            'post_id' => $post_id,
            'note_index' => $note_index,
        ]);
        
        return true;
    }
    
    /**
     * Estrae menzioni (@username) dal testo
     *
     * @param string $content
     * @return array User IDs
     */
    private function extract_mentions($content) {
        $mentions = [];
        
        // Pattern: @username o @"Nome Cognome"
        preg_match_all('/@([a-zA-Z0-9_-]+)/', $content, $matches);
        
        if (!empty($matches[1])) {
            foreach ($matches[1] as $username) {
                $user = get_user_by('login', $username);
                if ($user) {
                    $mentions[] = $user->ID;
                }
            }
        }
        
        return array_unique($mentions);
    }
    
    /**
     * Invia notifica per menzione
     *
     * @param int $mentioned_user_id
     * @param int $post_id
     * @param string $content
     * @param int $author_id
     */
    private function send_mention_notification($mentioned_user_id, $post_id, $content, $author_id) {
        $mentioned_user = get_userdata($mentioned_user_id);
        $author = get_userdata($author_id);
        $post = get_post($post_id);
        
        if (!$mentioned_user || !$author || !$post) {
            return;
        }
        
        $subject = sprintf(
            __('[%s] Sei stato menzionato in una nota', 'fp-newspaper'),
            get_bloginfo('name')
        );
        
        $body = sprintf(
            __("Ciao %s,\n\n%s ti ha menzionato in una nota sull'articolo '%s':\n\n%s\n\nApri l'articolo: %s", 'fp-newspaper'),
            $mentioned_user->display_name,
            $author->display_name,
            $post->post_title,
            $content,
            get_edit_post_link($post_id, 'raw')
        );
        
        wp_mail($mentioned_user->user_email, $subject, $body);
    }
    
    /**
     * AJAX: Aggiungi nota
     */
    public function ajax_add_note() {
        check_ajax_referer('fp_notes_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Permessi insufficienti', 'fp-newspaper'));
        }
        
        $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
        $content = isset($_POST['content']) ? wp_unslash($_POST['content']) : '';
        
        if (!$post_id || empty($content)) {
            wp_send_json_error(__('Dati mancanti', 'fp-newspaper'));
        }
        
        $result = $this->add_note($post_id, $content);
        
        if ($result) {
            wp_send_json_success(__('Nota aggiunta', 'fp-newspaper'));
        } else {
            wp_send_json_error(__('Errore aggiunta nota', 'fp-newspaper'));
        }
    }
    
    /**
     * AJAX: Elimina nota
     */
    public function ajax_delete_note() {
        check_ajax_referer('fp_notes_nonce', 'nonce');
        
        $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
        $note_index = isset($_POST['note_index']) ? absint($_POST['note_index']) : -1;
        
        if (!$post_id || $note_index < 0) {
            wp_send_json_error(__('Dati mancanti', 'fp-newspaper'));
        }
        
        $result = $this->delete_note($post_id, $note_index);
        
        if ($result) {
            wp_send_json_success(__('Nota eliminata', 'fp-newspaper'));
        } else {
            wp_send_json_error(__('Impossibile eliminare la nota', 'fp-newspaper'));
        }
    }
    
    /**
     * Conta note per post
     *
     * @param int $post_id
     * @return int
     */
    public function count_notes($post_id) {
        $notes = $this->get_notes($post_id);
        return count($notes);
    }
}


