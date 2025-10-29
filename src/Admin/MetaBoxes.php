<?php
/**
 * Gestione Meta Boxes
 *
 * @package FPNewspaper
 */

namespace FPNewspaper\Admin;

defined('ABSPATH') || exit;

/**
 * Classe per gestire i meta box personalizzati
 */
class MetaBoxes {
    
    /**
     * Costruttore
     */
    public function __construct() {
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
        add_action('save_post', [$this, 'save_meta_boxes']);
    }
    
    /**
     * Aggiunge meta boxes
     */
    public function add_meta_boxes() {
        add_meta_box(
            'fp_article_options',
            __('Opzioni Articolo', 'fp-newspaper'),
            [$this, 'render_article_options'],
            'fp_article',
            'side',
            'default'
        );
        
        add_meta_box(
            'fp_article_stats',
            __('Statistiche Articolo', 'fp-newspaper'),
            [$this, 'render_article_stats'],
            'fp_article',
            'side',
            'default'
        );
    }
    
    /**
     * Renderizza meta box opzioni articolo
     *
     * @param \WP_Post $post
     */
    public function render_article_options($post) {
        wp_nonce_field('fp_article_options_nonce', 'fp_article_options_nonce');
        
        $featured = get_post_meta($post->ID, '_fp_featured', true);
        $breaking = get_post_meta($post->ID, '_fp_breaking_news', true);
        ?>
        <p>
            <label>
                <input type="checkbox" name="fp_featured" value="1" <?php checked($featured, '1'); ?>>
                <?php _e('Articolo in evidenza', 'fp-newspaper'); ?>
            </label>
        </p>
        <p>
            <label>
                <input type="checkbox" name="fp_breaking_news" value="1" <?php checked($breaking, '1'); ?>>
                <?php _e('Breaking News', 'fp-newspaper'); ?>
            </label>
        </p>
        <?php
    }
    
    /**
     * Renderizza meta box statistiche articolo
     *
     * @param \WP_Post $post
     */
    public function render_article_stats($post) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'fp_newspaper_stats';
        
        // Verifica che la tabella esista
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        
        $views = 0;
        $shares = 0;
        
        if ($table_exists) {
            $stats = $wpdb->get_row($wpdb->prepare(
                "SELECT views, shares FROM $table_name WHERE post_id = %d",
                $post->ID
            ));
            
            if ($stats && !is_wp_error($stats)) {
                $views = (int) $stats->views;
                $shares = (int) $stats->shares;
            }
        }
        ?>
        <p>
            <strong><?php _e('Visualizzazioni:', 'fp-newspaper'); ?></strong><br>
            <?php echo number_format_i18n($views); ?>
        </p>
        <p>
            <strong><?php _e('Condivisioni:', 'fp-newspaper'); ?></strong><br>
            <?php echo number_format_i18n($shares); ?>
        </p>
        <?php if (!$table_exists): ?>
            <p class="description" style="color: #d63638;">
                <?php _e('Tabella statistiche non trovata. Disattiva e riattiva il plugin.', 'fp-newspaper'); ?>
            </p>
        <?php endif; ?>
        <?php
    }
    
    /**
     * Salva meta boxes
     *
     * @param int $post_id
     */
    public function save_meta_boxes($post_id) {
        // Verifica nonce
        if (!isset($_POST['fp_article_options_nonce']) || 
            !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['fp_article_options_nonce'])), 'fp_article_options_nonce')) {
            return;
        }
        
        // Verifica post type
        if (!isset($_POST['post_type']) || 'fp_article' !== $_POST['post_type']) {
            return;
        }
        
        // Verifica autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Verifica permessi
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Verifica che non sia una revisione
        if (wp_is_post_revision($post_id)) {
            return;
        }
        
        // Salva featured (sanitizzato)
        $featured = isset($_POST['fp_featured']) && '1' === $_POST['fp_featured'] ? '1' : '0';
        update_post_meta($post_id, '_fp_featured', sanitize_text_field($featured));
        
        // Salva breaking news (sanitizzato)
        $breaking = isset($_POST['fp_breaking_news']) && '1' === $_POST['fp_breaking_news'] ? '1' : '0';
        update_post_meta($post_id, '_fp_breaking_news', sanitize_text_field($breaking));
    }
}

