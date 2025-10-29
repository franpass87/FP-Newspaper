<?php
/**
 * Cron Jobs per manutenzione automatica
 *
 * @package FPNewspaper
 */

namespace FPNewspaper\Cron;

defined('ABSPATH') || exit;

/**
 * Gestisce i lavori schedulati
 */
class Jobs {
    
    /**
     * Registra cron jobs
     */
    public static function register() {
        add_action('init', [__CLASS__, 'schedule_events']);
        add_action('fp_newspaper_daily_cleanup', [__CLASS__, 'daily_cleanup']);
        add_action('fp_newspaper_stats_update', [__CLASS__, 'update_cached_stats']);
    }
    
    /**
     * Schedula eventi se non già schedulati
     */
    public static function schedule_events() {
        // Cleanup giornaliero (3:00 AM)
        if (!wp_next_scheduled('fp_newspaper_daily_cleanup')) {
            wp_schedule_event(
                strtotime('tomorrow 3:00 AM'),
                'daily',
                'fp_newspaper_daily_cleanup'
            );
        }
        
        // Aggiornamento statistiche cache (ogni ora)
        if (!wp_next_scheduled('fp_newspaper_stats_update')) {
            wp_schedule_event(
                time(),
                'hourly',
                'fp_newspaper_stats_update'
            );
        }
    }
    
    /**
     * Cleanup giornaliero
     */
    public static function daily_cleanup() {
        if (!class_exists('FPNewspaper\DatabaseOptimizer')) {
            return;
        }
        
        // Pulisci statistiche vecchie (365 giorni)
        $retention_days = apply_filters('fp_newspaper_stats_retention_days', 365);
        $deleted = \FPNewspaper\DatabaseOptimizer::cleanup_old_stats($retention_days);
        
        // Ottimizza tabella
        \FPNewspaper\DatabaseOptimizer::optimize_stats_table();
        
        // Pulisci transient orfani
        global $wpdb;
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_fp_view_%' 
             AND option_value = '1' 
             AND option_name NOT IN (
                 SELECT CONCAT('_transient_timeout_', SUBSTRING(option_name, 12))
                 FROM {$wpdb->options} o2
                 WHERE o2.option_name LIKE '_transient_timeout_fp_view_%'
             )"
        );
        
        // Log
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                'FP Newspaper: Daily cleanup completato - %d record rimossi',
                $deleted
            ));
        }
        
        do_action('fp_newspaper_after_daily_cleanup', $deleted);
    }
    
    /**
     * Aggiorna cache statistiche
     */
    public static function update_cached_stats() {
        // Pre-carica cache statistiche
        global $wpdb;
        $table_name = $wpdb->prefix . 'fp_newspaper_stats';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
            return;
        }
        
        $stats = $wpdb->get_row(
            "SELECT 
                COALESCE(SUM(views), 0) as total_views,
                COALESCE(SUM(shares), 0) as total_shares,
                COUNT(*) as tracked
            FROM $table_name"
        );
        
        if ($stats && !is_wp_error($stats)) {
            $cache_data = [
                'total_articles' => wp_count_posts('fp_article')->publish,
                'total_views'    => (int) $stats->total_views,
                'total_shares'   => (int) $stats->total_shares,
            ];
            
            // Cache per 1 ora (sarà aggiornato dalla prossima esecuzione)
            set_transient('fp_newspaper_stats_cache', $cache_data, HOUR_IN_SECONDS);
        }
        
        do_action('fp_newspaper_after_stats_update', $stats);
    }
}

