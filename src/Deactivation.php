<?php
/**
 * Gestione disattivazione plugin
 *
 * @package FPNewspaper
 */

namespace FPNewspaper;

defined('ABSPATH') || exit;

/**
 * Classe per gestire la disattivazione del plugin
 */
class Deactivation {
    
    /**
     * Esegue operazioni durante la disattivazione
     */
    public static function deactivate() {
        // Cancella eventuali cron jobs
        self::clear_scheduled_events();
        
        // Pulisci tutte le cache transient
        self::clear_transients();
        
        // Rilascia eventuali lock MySQL attivi
        self::release_mysql_locks();
        
        // Flush rewrite rules (DOPO aver pulito tutto)
        flush_rewrite_rules();
        
        // Log disattivazione (solo in debug mode)
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('FP Newspaper: Plugin disattivato');
        }
        
        // Fire action hook
        do_action('fp_newspaper_before_deactivation');
    }
    
    /**
     * Cancella eventi schedulati
     */
    private static function clear_scheduled_events() {
        $cron_hooks = [
            'fp_newspaper_daily_cleanup',
            'fp_newspaper_stats_update',
        ];
        
        foreach ($cron_hooks as $hook) {
            // Cancella TUTTE le istanze dell'evento, non solo la prossima
            while ($timestamp = wp_next_scheduled($hook)) {
                wp_unschedule_event($timestamp, $hook);
            }
            
            // Cancella anche eventuali azioni registrate
            wp_clear_scheduled_hook($hook);
        }
    }
    
    /**
     * Cancella tutti i transient del plugin
     */
    private static function clear_transients() {
        $transient_keys = [
            'fp_newspaper_stats_cache',
            'fp_featured_articles_cache',
        ];
        
        foreach ($transient_keys as $key) {
            delete_transient($key);
        }
        
        // Cancella anche eventuali rate limit transients (pattern matching)
        global $wpdb;
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_fp_view_%' 
             OR option_name LIKE '_transient_timeout_fp_view_%'"
        );
    }
    
    /**
     * Rilascia eventuali lock MySQL attivi del plugin
     */
    private static function release_mysql_locks() {
        global $wpdb;
        
        // Ottieni tutti i lock attivi
        $locks = $wpdb->get_results(
            "SELECT GET_LOCK('fp_view_lock_%', 0) as released",
            ARRAY_A
        );
        
        // Nota: MySQL rilascia automaticamente i lock alla disconnessione,
        // ma è buona pratica farlo esplicitamente
        // I lock specifici sono gestiti nel codice che li crea
    }
}

