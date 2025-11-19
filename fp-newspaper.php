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

// Autoload PSR-4 via Composer o fallback
$autoload_path = FP_NEWSPAPER_DIR . 'vendor/autoload.php';
if (file_exists($autoload_path)) {
    require_once $autoload_path;
} else {
    // Autoloader di fallback PSR-4 (funziona senza Composer)
    spl_autoload_register(function ($class) {
        // Namespace base del plugin
        $prefix = 'FPNewspaper\\';
        $base_dir = FP_NEWSPAPER_DIR . 'src/';
        
        // Verifica se la classe usa il namespace del plugin
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            // Namespace diverso, non gestito da questo autoloader
            return;
        }
        
        // Ottieni il nome della classe relativo
        $relative_class = substr($class, $len);
        
        // Sostituisci namespace separators con directory separators
        $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
        
        // Carica il file se esiste
        if (file_exists($file)) {
            require_once $file;
        }
    });
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

