<?php
/**
 * Social Share Tracking
 *
 * @package FPNewspaper\Social
 */

namespace FPNewspaper\Social;

use FPNewspaper\Logger;

defined('ABSPATH') || exit;

/**
 * Bottoni condivisione social con tracking analytics
 */
class ShareTracking {
    
    /**
     * Costruttore
     */
    public function __construct() {
        add_filter('the_content', [$this, 'add_share_buttons'], 10);
        add_action('wp_ajax_fp_track_share', [$this, 'ajax_track_share']);
        add_action('wp_ajax_nopriv_fp_track_share', [$this, 'ajax_track_share']);
        // NOTA: enqueue gestito da Assets.php (v1.6.0)
    }
    
    /**
     * Aggiunge bottoni share
     */
    public function add_share_buttons($content) {
        if (!is_singular('post') || !in_the_loop()) {
            return $content;
        }
        
        $post_id = get_the_ID();
        $post_url = get_permalink();
        $post_title = get_the_title();
        
        // URLs condivisione
        $facebook_url = 'https://www.facebook.com/sharer/sharer.php?u=' . urlencode($post_url);
        $twitter_url = 'https://twitter.com/intent/tweet?url=' . urlencode($post_url) . '&text=' . urlencode($post_title);
        $linkedin_url = 'https://www.linkedin.com/shareArticle?mini=true&url=' . urlencode($post_url) . '&title=' . urlencode($post_title);
        $whatsapp_url = 'https://api.whatsapp.com/send?text=' . urlencode($post_title . ' ' . $post_url);
        
        ob_start();
        ?>
        <div class="fp-share-buttons" role="group" aria-label="<?php esc_attr_e('Condividi articolo', 'fp-newspaper'); ?>">
            <div class="fp-share-label"><?php _e('Condividi:', 'fp-newspaper'); ?></div>
            <a href="<?php echo esc_url($facebook_url); ?>" 
               class="fp-share-btn fp-share-facebook fp-focus-visible" 
               data-platform="facebook" 
               data-post-id="<?php echo esc_attr($post_id); ?>"
               role="button"
               aria-label="<?php esc_attr_e('Condividi su Facebook', 'fp-newspaper'); ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                <span>Facebook</span>
            </a>
            <a href="<?php echo esc_url($twitter_url); ?>" 
               class="fp-share-btn fp-share-twitter fp-focus-visible" 
               data-platform="twitter" 
               data-post-id="<?php echo esc_attr($post_id); ?>"
               role="button"
               aria-label="<?php esc_attr_e('Condividi su Twitter/X', 'fp-newspaper'); ?>">
                <span aria-hidden="true">𝕏</span>
                <span>Twitter</span>
            </a>
            <a href="<?php echo esc_url($linkedin_url); ?>" 
               class="fp-share-btn fp-share-linkedin fp-focus-visible" 
               data-platform="linkedin" 
               data-post-id="<?php echo esc_attr($post_id); ?>"
               role="button"
               aria-label="<?php esc_attr_e('Condividi su LinkedIn', 'fp-newspaper'); ?>">
                <span aria-hidden="true">in</span>
                <span>LinkedIn</span>
            </a>
            <a href="<?php echo esc_url($whatsapp_url); ?>" 
               class="fp-share-btn fp-share-whatsapp fp-focus-visible" 
               data-platform="whatsapp" 
               data-post-id="<?php echo esc_attr($post_id); ?>"
               role="button"
               aria-label="<?php esc_attr_e('Condividi su WhatsApp', 'fp-newspaper'); ?>">
                <span aria-hidden="true">💬</span>
                <span>WhatsApp</span>
            </a>
        </div>
        <?php
        $buttons = ob_get_clean();
        
        return $content . $buttons;
    }
    
    /**
     * AJAX: Track share
     */
    public function ajax_track_share() {
        // Verifica nonce (sicurezza CSRF)
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'fp_share_nonce')) {
            wp_send_json_error(['message' => 'Invalid nonce']);
        }
        
        $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
        $platform = isset($_POST['platform']) ? sanitize_text_field($_POST['platform']) : '';
        
        if (!$post_id || !$platform) {
            wp_send_json_error(['message' => 'Missing parameters']);
        }
        
        // Incrementa contatore shares in stats table
        global $wpdb;
        $table_name = $wpdb->prefix . 'fp_newspaper_stats';
        
        $wpdb->query($wpdb->prepare("
            INSERT INTO `{$wpdb->prefix}fp_newspaper_stats` (post_id, shares)
            VALUES (%d, 1)
            ON DUPLICATE KEY UPDATE shares = shares + 1
        ", $post_id));
        
        // Log per analytics
        Logger::debug('Share tracked', [
            'post_id' => $post_id,
            'platform' => $platform,
        ]);
        
        do_action('fp_newspaper_share_tracked', $post_id, $platform);
        
        wp_send_json_success();
    }
    
    /**
     * Ottiene statistiche condivisioni
     */
    public function get_share_stats($post_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'fp_newspaper_stats';
        
        $stats = $wpdb->get_row($wpdb->prepare("
            SELECT shares FROM {$table_name}
            WHERE post_id = %d
        ", $post_id));
        
        return $stats ? (int) $stats->shares : 0;
    }
}

