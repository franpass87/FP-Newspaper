<?php
/**
 * Hooks e Filters per estensibilità
 *
 * @package FPNewspaper
 */

namespace FPNewspaper;

defined('ABSPATH') || exit;

/**
 * Classe per gestire hooks e filtri disponibili agli sviluppatori
 */
class Hooks {
    
    /**
     * Inizializza hooks
     */
    public static function init() {
        // Hooks già implementati nel codice, questa classe li documenta
    }
    
    /**
     * ========================================
     * ACTIONS DISPONIBILI
     * ========================================
     */
    
    /**
     * Dopo attivazione plugin
     * 
     * @param int $blog_id ID del blog (0 se single site)
     */
    // do_action('fp_newspaper_after_activation', $blog_id);
    
    /**
     * Prima della disattivazione
     */
    // do_action('fp_newspaper_before_deactivation');
    
    /**
     * Dopo salvataggio articolo
     * 
     * @param int $post_id
     * @param \WP_Post $post
     */
    // do_action('fp_newspaper_after_save_article', $post_id, $post);
    
    /**
     * Quando view viene incrementata
     * 
     * @param int $post_id
     * @param int $new_views
     */
    // do_action('fp_newspaper_view_incremented', $post_id, $new_views);
    
    /**
     * Prima di eliminare statistiche vecchie
     * 
     * @param int $days
     */
    // do_action('fp_newspaper_before_cleanup', $days);
    
    /**
     * Dopo ottimizzazione database
     */
    // do_action('fp_newspaper_after_optimization');
    
    /**
     * ========================================
     * FILTERS DISPONIBILI
     * ========================================
     */
    
    /**
     * Filtra numero articoli per pagina
     * 
     * @param int $per_page Default: 10
     * @return int
     */
    // apply_filters('fp_newspaper_articles_per_page', 10);
    
    /**
     * Filtra numero articoli featured da mostrare
     * 
     * @param int $count Default: 5
     * @return int
     */
    // apply_filters('fp_newspaper_featured_count', 5);
    
    /**
     * Filtra argomenti WP_Query per articoli
     * 
     * @param array $args
     * @return array
     */
    // apply_filters('fp_newspaper_query_args', $args);
    
    /**
     * Filtra dati articolo in REST API
     * 
     * @param array $article
     * @param int $post_id
     * @return array
     */
    // apply_filters('fp_newspaper_rest_article_data', $article, $post_id);
    
    /**
     * Filtra durata cache statistiche (secondi)
     * 
     * @param int $duration Default: 300 (5 min)
     * @return int
     */
    // apply_filters('fp_newspaper_stats_cache_duration', 300);
    
    /**
     * Filtra durata cache articoli featured (secondi)
     * 
     * @param int $duration Default: 600 (10 min)
     * @return int
     */
    // apply_filters('fp_newspaper_featured_cache_duration', 600);
    
    /**
     * Filtra cooldown rate limiting (secondi)
     * 
     * @param int $seconds Default: 30
     * @return int
     */
    // apply_filters('fp_newspaper_rate_limit_duration', 30);
    
    /**
     * Filtra giorni retention statistiche
     * 
     * @param int $days Default: 365
     * @return int
     */
    // apply_filters('fp_newspaper_stats_retention_days', 365);
    
    /**
     * Filtra colonne admin personalizzate
     * 
     * @param array $columns
     * @return array
     */
    // apply_filters('fp_newspaper_admin_columns', $columns);
    
    /**
     * Filtra bulk actions disponibili
     * 
     * @param array $actions
     * @return array
     */
    // apply_filters('fp_newspaper_bulk_actions', $actions);
}

