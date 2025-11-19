<?php
/**
 * Assets Manager - Gestione enqueue CSS/JS
 *
 * @package FPNewspaper
 * @version 1.6.0
 */

namespace FPNewspaper;

defined('ABSPATH') || exit;

/**
 * Gestisce caricamento assets (CSS, JS) frontend e admin
 */
class Assets {
    
    /**
     * Costruttore
     */
    public function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend'], 10);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin'], 10);
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend() {
        // Carica solo su single post
        if (!is_singular('post')) {
            return;
        }
        
        // 1. Design System (CSS Variables)
        wp_enqueue_style(
            'fp-newspaper-design-system',
            FP_NEWSPAPER_URL . 'assets/css/design-system.css',
            [],
            FP_NEWSPAPER_VERSION,
            'all'
        );
        
        // 2. Frontend Styles (dipende da design system)
        wp_enqueue_style(
            'fp-newspaper-frontend',
            FP_NEWSPAPER_URL . 'assets/css/frontend.css',
            ['fp-newspaper-design-system'],
            FP_NEWSPAPER_VERSION,
            'all'
        );
        
        // 3. Frontend JavaScript (dipende da jQuery)
        wp_enqueue_script(
            'fp-newspaper-frontend',
            FP_NEWSPAPER_URL . 'assets/js/frontend.js',
            ['jquery'],
            FP_NEWSPAPER_VERSION,
            true // in footer
        );
        
        // 4. Localizza dati per JavaScript
        $this->localize_frontend_scripts();
    }
    
    /**
     * Localizza script frontend
     */
    private function localize_frontend_scripts() {
        // Config generale
        wp_localize_script('fp-newspaper-frontend', 'fpNewsConfig', [
            'version' => FP_NEWSPAPER_VERSION,
            'animations' => apply_filters('fp_newspaper_enable_animations', true),
            'lazyLoad' => apply_filters('fp_newspaper_enable_lazy_load', true),
        ]);
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin($hook) {
        // Global admin styles
        wp_enqueue_style(
            'fp-newspaper-admin-global',
            FP_NEWSPAPER_URL . 'assets/css/admin-global.css',
            [],
            FP_NEWSPAPER_VERSION
        );
        
        // Dashboard page
        if ('toplevel_page_fp-editorial-dashboard' === $hook) {
            $this->enqueue_dashboard_assets();
        }
        
        // Post edit screen
        if (in_array($hook, ['post.php', 'post-new.php'])) {
            $this->enqueue_post_editor_assets();
        }
    }
    
    /**
     * Enqueue dashboard assets
     */
    private function enqueue_dashboard_assets() {
        // Chart.js già enqueued da EditorialDashboardPage
        // Ma aggiungiamo eventuali styles custom
        
        wp_enqueue_style(
            'fp-newspaper-admin-dashboard',
            FP_NEWSPAPER_URL . 'assets/css/admin-dashboard.css',
            ['fp-newspaper-admin-global'],
            FP_NEWSPAPER_VERSION
        );
        
        wp_enqueue_script(
            'fp-newspaper-admin-dashboard',
            FP_NEWSPAPER_URL . 'assets/js/admin-dashboard.js',
            ['jquery'],
            FP_NEWSPAPER_VERSION,
            true
        );
    }
    
    /**
     * Enqueue post editor assets
     */
    private function enqueue_post_editor_assets() {
        global $post;
        
        if (!$post || 'post' !== get_post_type($post)) {
            return;
        }
        
        wp_enqueue_style(
            'fp-newspaper-admin-editor',
            FP_NEWSPAPER_URL . 'assets/css/admin-editor.css',
            [],
            FP_NEWSPAPER_VERSION
        );
        
        wp_enqueue_script(
            'fp-newspaper-admin-editor',
            FP_NEWSPAPER_URL . 'assets/js/admin-editor.js',
            ['jquery'],
            FP_NEWSPAPER_VERSION,
            true
        );
        
        // Localizza dati editor
        wp_localize_script('fp-newspaper-admin-editor', 'fpEditorData', [
            'postId' => $post->ID,
            'nonce' => wp_create_nonce('fp_editor_nonce'),
        ]);
    }
    
    /**
     * Preload critical assets
     */
    public function add_resource_hints($hints, $relation_type) {
        if ('preconnect' === $relation_type) {
            // Preconnect a CDN se usati
            if (defined('FP_NEWSPAPER_CDN_URL')) {
                $hints[] = FP_NEWSPAPER_CDN_URL;
            }
        }
        
        if ('dns-prefetch' === $relation_type) {
            // DNS prefetch per Chart.js CDN
            $hints[] = 'https://cdn.jsdelivr.net';
        }
        
        return $hints;
    }
    
    /**
     * Inline critical CSS (opzionale, per performance)
     */
    public function inline_critical_css() {
        if (!is_singular('post')) {
            return;
        }
        
        // Critical CSS inline (above the fold)
        $critical_css = '
        .fp-author-box{display:flex;gap:16px;padding:24px;background:#f9f9f9;border-left:4px solid #2271b1;border-radius:6px;}
        ';
        
        // Output inline
        echo '<style id="fp-newspaper-critical">' . wp_strip_all_tags($critical_css) . '</style>';
    }
}


