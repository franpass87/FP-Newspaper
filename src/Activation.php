<?php
/**
 * Gestione attivazione plugin
 *
 * @package FPNewspaper
 */

namespace FPNewspaper;

defined('ABSPATH') || exit;

/**
 * Classe per gestire l'attivazione del plugin
 */
class Activation {
    
    /**
     * Esegue operazioni durante l'attivazione
     */
    public static function activate() {
        // Verifica requisiti minimi
        self::check_requirements();
        
        // Se è multisite, attiva su tutti i siti se network activated
        $networkwide = isset($_GET['networkwide']) ? absint($_GET['networkwide']) : 0;
        
        if (is_multisite() && $networkwide === 1) {
            global $wpdb;
            $blog_ids = $wpdb->get_col("SELECT blog_id FROM {$wpdb->blogs}");
            
            foreach ($blog_ids as $blog_id) {
                $blog_id = absint($blog_id); // Sanitizza anche blog_id
                switch_to_blog($blog_id);
                self::activate_single_site();
                restore_current_blog();
            }
        } else {
            self::activate_single_site();
        }
    }
    
    /**
     * Attivazione per singolo sito
     */
    private static function activate_single_site() {
        // Crea tabelle database se necessario
        self::create_tables();
        
        // Registra post types e tassonomie (necessario prima di flush_rewrite_rules)
        if (class_exists('FPNewspaper\PostTypes\Article')) {
            PostTypes\Article::register_post_type();
            PostTypes\Article::register_taxonomies();
        }
        
        // Crea pagine predefinite
        self::create_default_pages();
        
        // Imposta opzioni predefinite
        self::set_default_options();
        
        // Flush rewrite rules (DOPO aver registrato i post types)
        flush_rewrite_rules();
        
        // Imposta flag prima attivazione
        if (!get_option('fp_newspaper_activated')) {
            add_option('fp_newspaper_activated', true);
            add_option('fp_newspaper_activation_date', current_time('mysql'));
        }
        
        // Ottimizza database dopo creazione tabelle
        if (class_exists('FPNewspaper\DatabaseOptimizer')) {
            DatabaseOptimizer::optimize_stats_table();
        }
        
        // Log attivazione
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $site_id = is_multisite() ? get_current_blog_id() : 0;
            error_log("FP Newspaper: Plugin attivato con successo (Site ID: $site_id)");
        }
    }
    
    /**
     * Verifica requisiti minimi
     */
    private static function check_requirements() {
        // Verifica versione PHP
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            deactivate_plugins(FP_NEWSPAPER_BASENAME);
            wp_die(
                __('FP Newspaper richiede PHP 7.4 o superiore.', 'fp-newspaper'),
                __('Errore attivazione plugin', 'fp-newspaper'),
                ['back_link' => true]
            );
        }
        
        // Verifica versione WordPress
        if (version_compare(get_bloginfo('version'), '6.0', '<')) {
            deactivate_plugins(FP_NEWSPAPER_BASENAME);
            wp_die(
                __('FP Newspaper richiede WordPress 6.0 o superiore.', 'fp-newspaper'),
                __('Errore attivazione plugin', 'fp-newspaper'),
                ['back_link' => true]
            );
        }
    }
    
    /**
     * Crea tabelle database
     */
    private static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        // Tabella per statistiche articoli
        $table_name = $wpdb->prefix . 'fp_newspaper_stats';
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            post_id bigint(20) UNSIGNED NOT NULL,
            views bigint(20) UNSIGNED DEFAULT 0,
            shares bigint(20) UNSIGNED DEFAULT 0,
            last_updated datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY post_id (post_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $result = dbDelta($sql);
        
        // Log risultato per debug
        if (defined('WP_DEBUG') && WP_DEBUG && !empty($result)) {
            error_log('FP Newspaper: Tabella database creata/aggiornata - ' . print_r($result, true));
        }
        
        // Verifica che la tabella sia stata creata
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
            error_log('FP Newspaper: ERRORE - Impossibile creare tabella ' . $table_name);
        }
    }
    
    /**
     * Crea pagine predefinite
     */
    private static function create_default_pages() {
        // Verifica se la pagina archivio esiste già
        $archive_page = get_page_by_path('archivio-notizie');
        
        if (!$archive_page) {
            // Determina autore (fallback a primo admin se nessun utente corrente)
            $author_id = get_current_user_id();
            if (!$author_id) {
                $admins = get_users(['role' => 'administrator', 'number' => 1]);
                $author_id = !empty($admins) ? $admins[0]->ID : 1;
            }
            
            $page_id = wp_insert_post([
                'post_title'   => __('Archivio Notizie', 'fp-newspaper'),
                'post_name'    => 'archivio-notizie',
                'post_content' => '[fp_newspaper_archive]',
                'post_status'  => 'publish',
                'post_type'    => 'page',
                'post_author'  => $author_id,
            ], true); // true per restituire WP_Error in caso di errore
            
            // Gestisci errori
            if (is_wp_error($page_id)) {
                error_log('FP Newspaper: Errore creazione pagina archivio - ' . $page_id->get_error_message());
            } elseif (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('FP Newspaper: Pagina archivio creata con ID: ' . $page_id);
            }
        }
    }
    
    /**
     * Imposta opzioni predefinite
     */
    private static function set_default_options() {
        $default_options = [
            'fp_newspaper_version' => [
                'value' => FP_NEWSPAPER_VERSION,
                'autoload' => true
            ],
            'fp_newspaper_installed_date' => [
                'value' => current_time('mysql'),
                'autoload' => false
            ],
            'fp_newspaper_articles_per_page' => [
                'value' => 10,
                'autoload' => true
            ],
            'fp_newspaper_enable_comments' => [
                'value' => true,
                'autoload' => true
            ],
            'fp_newspaper_enable_sharing' => [
                'value' => true,
                'autoload' => true
            ],
        ];
        
        foreach ($default_options as $key => $option) {
            if (false === get_option($key)) {
                add_option(
                    $key, 
                    $option['value'], 
                    '', 
                    $option['autoload'] ? 'yes' : 'no'
                );
            }
        }
    }
}

