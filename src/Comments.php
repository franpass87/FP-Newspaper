<?php
/**
 * Sistema Commenti Avanzato
 *
 * @package FPNewspaper
 */

namespace FPNewspaper;

defined('ABSPATH') || exit;

/**
 * Migliora sistema commenti per articoli
 */
class Comments {
    
    /**
     * Costruttore
     */
    public function __construct() {
        // Filtri commenti
        add_filter('comment_form_defaults', [$this, 'customize_comment_form'], 10, 1);
        add_filter('get_comment_author_link', [$this, 'verified_comment_author'], 10, 3);
        
        // Hooks commenti
        add_action('comment_post', [$this, 'on_comment_post'], 10, 2);
        add_action('pre_comment_approved', [$this, 'moderate_long_comments'], 10, 2);
        
        // Stili commenti
        add_action('wp_enqueue_scripts', [$this, 'enqueue_comment_styles']);
        
        // Meta boxes commenti
        add_action('add_meta_boxes_comment', [$this, 'add_comment_meta_boxes']);
        add_action('edit_comment', [$this, 'save_comment_meta']);
    }
    
    /**
     * Personalizza form commenti
     */
    public function customize_comment_form($defaults) {
        // Solo per articoli
        if (get_post_type() !== 'post') {
            return $defaults;
        }
        
        $defaults['title_reply'] = __('Lascia un Commento', 'fp-newspaper');
        $defaults['title_reply_to'] = __('Rispondi a %s', 'fp-newspaper');
        $defaults['cancel_reply_link'] = __('Annulla risposta', 'fp-newspaper');
        
        // Placeholder personalizzati
        $defaults['comment_field'] = '<p class="comment-form-comment">
            <label for="comment">' . __('Commento *', 'fp-newspaper') . '</label>
            <textarea id="comment" name="comment" cols="45" rows="8" maxlength="65525" placeholder="' . esc_attr__('Lascia un commento...', 'fp-newspaper') . '"></textarea>
        </p>';
        
        return $defaults;
    }
    
    /**
     * Mostra badge "Verificato" per autori articoli
     */
    public function verified_comment_author($return, $author, $comment_id) {
        $comment = get_comment($comment_id);
        
        if (!$comment) {
            return $return;
        }
        
        $post_id = $comment->comment_post_ID;
        $post = get_post($post_id);
        
        if (!$post || is_wp_error($post) || get_post_type($post_id) !== 'post') {
            return $return;
        }
        
        // Se il commentatore è l'autore dell'articolo, mostra badge verificato
        if ($comment->user_id > 0 && $comment->user_id === $post->post_author) {
            $return .= ' <span class="fp-verified-badge" title="' . esc_attr__('Autore Articolo', 'fp-newspaper') . '">✓ Verificato</span>';
        }
        
        return $return;
    }
    
    /**
     * Hook quando un commento viene inserito
     */
    public function on_comment_post($comment_id, $comment_approved) {
        $comment = get_comment($comment_id);
        
        if (!$comment || !isset($comment->comment_post_ID)) {
            return;
        }
        
        // Solo per articoli
        if (get_post_type($comment->comment_post_ID) !== 'post') {
            return;
        }
        
        // Incrementa contatore commenti per l'articolo
        $comment_count = get_post_meta($comment->comment_post_ID, '_fp_article_comment_count', true);
        $comment_count = $comment_count ? (int) $comment_count : 0;
        update_post_meta($comment->comment_post_ID, '_fp_article_comment_count', $comment_count + 1);
        
        // Fire action
        do_action('fp_newspaper_comment_posted', $comment_id, $comment_approved, $comment);
    }
    
    /**
     * Modera commenti molto lunghi
     */
    public function moderate_long_comments($approved, $commentdata) {
        // Solo per articoli
        $post = get_post($commentdata['comment_post_ID']);
        if (!$post || is_wp_error($post) || get_post_type($post->ID) !== 'post') {
            return $approved;
        }
        
        // Se commento supera 1000 caratteri, metti in moderazione
        $max_length = apply_filters('fp_newspaper_comment_max_length', 1000);
        
        if (strlen($commentdata['comment_content']) > $max_length) {
            return 0; // Requires moderation
        }
        
        return $approved;
    }
    
    /**
     * Enqueue stili commenti
     */
    public function enqueue_comment_styles() {
        if (!is_singular('post')) {
            return;
        }
        
        wp_add_inline_style('fp-newspaper-frontend', '
            /* Verified Badge */
            .fp-verified-badge {
                display: inline-block;
                background: #00a32a;
                color: white;
                font-size: 11px;
                font-weight: 600;
                padding: 2px 8px;
                border-radius: 3px;
                margin-left: 5px;
                vertical-align: middle;
            }
            
            /* Featured Comments */
            .fp-comment-featured {
                background: #fff8e5;
                border-left: 4px solid #dba617;
                padding: 15px;
                margin: 15px 0;
                border-radius: 4px;
            }
            
            .fp-comment-featured-title {
                color: #dba617;
                font-weight: 600;
                font-size: 14px;
                margin-bottom: 10px;
                display: flex;
                align-items: center;
                gap: 6px;
            }
            
            /* Comment Form Enhancement */
            .comment-form textarea {
                transition: border-color 0.3s;
            }
            
            .comment-form textarea:focus {
                border-color: #2271b1;
                box-shadow: 0 0 0 1px #2271b1;
            }
            
            /* Comment Stats */
            .fp-comment-stats {
                display: flex;
                gap: 20px;
                margin: 20px 0;
                padding: 15px;
                background: #f6f7f7;
                border-radius: 8px;
                font-size: 14px;
                color: #646970;
            }
            
            .fp-comment-stat {
                display: flex;
                align-items: center;
                gap: 8px;
            }
            
            .fp-comment-stat-number {
                font-weight: 600;
                color: #2271b1;
            }
        ');
    }
    
    /**
     * Aggiunge meta box per commenti
     */
    public function add_comment_meta_boxes() {
        add_meta_box(
            'fp_comment_options',
            __('Opzioni Commento', 'fp-newspaper'),
            [$this, 'render_comment_meta_box'],
            'comment',
            'normal',
            'default'
        );
    }
    
    /**
     * Renderizza meta box commento
     */
    public function render_comment_meta_box($comment) {
        wp_nonce_field('fp_comment_meta_box', 'fp_comment_meta_box_nonce');
        
        $is_featured = get_comment_meta($comment->comment_ID, '_fp_comment_featured', true);
        ?>
        <p>
            <label>
                <input type="checkbox" name="fp_comment_featured" value="1" <?php checked($is_featured, '1'); ?>>
                <?php _e('Commento in evidenza', 'fp-newspaper'); ?>
            </label>
            <span class="description"><?php _e('Il commento verrà mostrato in modo più prominente sotto l\'articolo.', 'fp-newspaper'); ?></span>
        </p>
        <?php
    }
    
    /**
     * Salva meta commento
     */
    public function save_comment_meta($comment_id) {
        if (!isset($_POST['fp_comment_meta_box_nonce']) || 
            !wp_verify_nonce($_POST['fp_comment_meta_box_nonce'], 'fp_comment_meta_box')) {
            return;
        }
        
        if (!current_user_can('edit_comment', $comment_id)) {
            return;
        }
        
        $featured = isset($_POST['fp_comment_featured']) && $_POST['fp_comment_featured'] === '1' ? '1' : '0';
        update_comment_meta($comment_id, '_fp_comment_featured', $featured);
    }
}

