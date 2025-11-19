<?php
/**
 * Plugin Name: FP Newspaper
 * Plugin URI: https://francescopasseri.com
 * Description: Plugin per gestione contenuti editoriali e pubblicazione di articoli in stile giornalistico
 * Version: 1.6.0
 * Author: Francesco Passeri
 * Author URI: https://francescopasseri.com
 * Text Domain: fp-newspaper
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace FPNewspaper;

defined('ABSPATH') || exit;

// Costanti del plugin (protette da ridefinizione)
if (!defined('FP_NEWSPAPER_VERSION')) {
    define('FP_NEWSPAPER_VERSION', '1.6.0');
}
if (!defined('FP_NEWSPAPER_FILE')) {
    define('FP_NEWSPAPER_FILE', __FILE__);
}
if (!defined('FP_NEWSPAPER_DIR')) {
    define('FP_NEWSPAPER_DIR', plugin_dir_path(__FILE__));
}
if (!defined('FP_NEWSPAPER_URL')) {
    define('FP_NEWSPAPER_URL', plugin_dir_url(__FILE__));
}
if (!defined('FP_NEWSPAPER_BASENAME')) {
    define('FP_NEWSPAPER_BASENAME', plugin_basename(__FILE__));
}

// Autoload PSR-4 via Composer
$autoload_path = FP_NEWSPAPER_DIR . 'vendor/autoload.php';
if (file_exists($autoload_path)) {
    require_once $autoload_path;
} else {
    add_action('admin_notices', function() {
        if (!current_user_can('activate_plugins')) {
            return;
        }
        echo '<div class="notice notice-error"><p>';
        echo '<strong>' . esc_html__('FP Newspaper:', 'fp-newspaper') . '</strong> ';
        echo esc_html__('Esegui', 'fp-newspaper') . ' <code>composer install</code> ';
        echo esc_html__('nella cartella del plugin.', 'fp-newspaper');
        echo '</p></div>';
    });
    return;
}

// Inizializza il plugin
add_action('plugins_loaded', function() {
    // Carica traduzioni
    load_plugin_textdomain('fp-newspaper', false, dirname(FP_NEWSPAPER_BASENAME) . '/languages');
    
    // Inizializza il plugin principale
    Plugin::get_instance();
}, 10);

// Supporto multisite: attiva su nuovi blog
if (is_multisite()) {
    add_action('wpmu_new_blog', function($blog_id) {
        if (is_plugin_active_for_network(FP_NEWSPAPER_BASENAME)) {
            switch_to_blog($blog_id);
            Activation::activate();
            restore_current_blog();
        }
    }, 10, 1);
    
    // Cleanup quando un blog viene eliminato
    add_action('delete_blog', function($blog_id) {
        switch_to_blog($blog_id);
        // Non cancellare dati, solo cleanup transients
        delete_transient('fp_newspaper_stats_cache');
        delete_transient('fp_featured_articles_cache');
        restore_current_blog();
    }, 10, 1);
}

// Hook di attivazione
register_activation_hook(__FILE__, function() {
    if (class_exists('FPNewspaper\Activation')) {
        Activation::activate();
    }
});

// Hook di disattivazione
register_deactivation_hook(__FILE__, function() {
    if (class_exists('FPNewspaper\Deactivation')) {
        Deactivation::deactivate();
    }
});

