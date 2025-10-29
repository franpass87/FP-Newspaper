<?php
/**
 * Disinstallazione plugin FP Newspaper
 * 
 * @package FPNewspaper
 */

// Se questo file viene chiamato direttamente, abort.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * Funzione di uninstall per singolo sito
 */
function fp_newspaper_uninstall_single_site() {
    global $wpdb;
    
    // 1. Cancella TUTTE le opzioni del plugin
    $options_to_delete = [
        'fp_newspaper_version',
        'fp_newspaper_installed_date',
        'fp_newspaper_activation_date',
        'fp_newspaper_activated',
        'fp_newspaper_articles_per_page',
        'fp_newspaper_enable_comments',
        'fp_newspaper_enable_sharing',
    ];
    
    foreach ($options_to_delete as $option) {
        delete_option($option);
        
        // Se multisite, cancella anche site meta
        if (is_multisite()) {
            delete_site_option($option);
        }
    }
    
    // 2. Cancella post meta (con prepared statement per sicurezza)
    $wpdb->query($wpdb->prepare(
        "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE %s OR meta_key LIKE %s",
        $wpdb->esc_like('_fp_featured') . '%',
        $wpdb->esc_like('_fp_breaking') . '%'
    ));
    
    // 3. Cancella TUTTI i transient del plugin (inclusi rate limit)
    $wpdb->query($wpdb->prepare(
        "DELETE FROM {$wpdb->options} 
         WHERE option_name LIKE %s 
         OR option_name LIKE %s
         OR option_name LIKE %s
         OR option_name LIKE %s",
        $wpdb->esc_like('_transient_fp_newspaper_') . '%',
        $wpdb->esc_like('_transient_timeout_fp_newspaper_') . '%',
        $wpdb->esc_like('_transient_fp_view_') . '%',
        $wpdb->esc_like('_transient_timeout_fp_view_') . '%'
    ));
    
    // 4. Cancella site transients se multisite
    if (is_multisite()) {
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->sitemeta} 
             WHERE meta_key LIKE %s 
             OR meta_key LIKE %s",
            $wpdb->esc_like('_site_transient_fp_newspaper_') . '%',
            $wpdb->esc_like('_site_transient_timeout_fp_newspaper_') . '%'
        ));
    }
    
    // 5. Cancella scheduled events
    $cron_hooks = [
        'fp_newspaper_daily_cleanup',
        'fp_newspaper_stats_update',
    ];
    
    foreach ($cron_hooks as $hook) {
        wp_clear_scheduled_hook($hook);
    }
    
    // 6. Cancella tabelle custom (opzionale, controllato da opzione)
    // Solo se l'utente ha esplicitamente richiesto cancellazione completa
    $delete_data = get_option('fp_newspaper_delete_data_on_uninstall', false);
    
    if ($delete_data) {
        $table_name = $wpdb->prefix . 'fp_newspaper_stats';
        $wpdb->query("DROP TABLE IF EXISTS `{$table_name}`");
        
        // Cancella anche i post (se richiesto)
        $delete_posts = get_option('fp_newspaper_delete_posts_on_uninstall', false);
        if ($delete_posts) {
            $wpdb->query($wpdb->prepare(
                "DELETE p, pm FROM {$wpdb->posts} p
                 LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                 WHERE p.post_type = %s",
                'fp_article'
            ));
            
            // Cancella relazioni tassonomie
            $wpdb->query($wpdb->prepare(
                "DELETE tr FROM {$wpdb->term_relationships} tr
                 LEFT JOIN {$wpdb->posts} p ON tr.object_id = p.ID
                 WHERE p.ID IS NULL"
            ));
        }
    }
    
    // 7. Pulisci object cache se presente
    if (function_exists('wp_cache_flush')) {
        wp_cache_flush();
    }
    
    // 8. Flush rewrite rules
    flush_rewrite_rules();
    
    // 9. Log disinstallazione (solo in debug mode)
    if (defined('WP_DEBUG') && WP_DEBUG) {
        $site_id = is_multisite() ? get_current_blog_id() : 'single';
        error_log("FP Newspaper: Plugin completamente disinstallato (Site: $site_id)");
    }
}

// Esegui uninstall
if (is_multisite()) {
    // Multisite: uninstall da tutti i siti
    global $wpdb;
    $blog_ids = $wpdb->get_col("SELECT blog_id FROM {$wpdb->blogs}");
    
    foreach ($blog_ids as $blog_id) {
        switch_to_blog($blog_id);
        fp_newspaper_uninstall_single_site();
        restore_current_blog();
    }
} else {
    // Single site
    fp_newspaper_uninstall_single_site();
}

