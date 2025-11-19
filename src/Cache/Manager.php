<?php
/**
 * Cache Manager multi-layer
 *
 * @package FPNewspaper\Cache
 */

namespace FPNewspaper\Cache;

use FPNewspaper\Logger;

defined('ABSPATH') || exit;

/**
 * Gestisce caching multi-layer con object cache + transient
 */
class Manager {
    
    /**
     * Cache group
     */
    const CACHE_GROUP = 'fp_newspaper';
    
    /**
     * Cache layers
     */
    const LAYER_OBJECT_CACHE = 'object';
    const LAYER_TRANSIENT = 'transient';
    const LAYER_ALL = 'all';
    
    /**
     * Default TTL
     */
    const DEFAULT_TTL = 3600; // 1 hour
    
    /**
     * Ottiene valore dalla cache con fallback
     *
     * @param string   $key Cache key
     * @param callable $callback Funzione da eseguire se cache miss
     * @param int      $ttl Time to live in secondi
     * @param string   $layer Layer preferito
     * @return mixed
     */
    public static function get($key, callable $callback, $ttl = self::DEFAULT_TTL, $layer = self::LAYER_ALL) {
        $start = microtime(true);
        
        // 1. Try object cache (se disponibile e richiesto)
        if (self::use_object_cache() && $layer !== self::LAYER_TRANSIENT) {
            $cached = wp_cache_get($key, self::CACHE_GROUP);
            
            if (false !== $cached) {
                $duration = (microtime(true) - $start) * 1000;
                Logger::debug("Cache HIT (object): {$key}", ['duration_ms' => $duration]);
                
                do_action('fp_newspaper_cache_hit', $key, 'object');
                return $cached;
            }
        }
        
        // 2. Try transient
        $transient_key = self::get_transient_key($key);
        $cached = get_transient($transient_key);
        
        if (false !== $cached) {
            $duration = (microtime(true) - $start) * 1000;
            Logger::debug("Cache HIT (transient): {$key}", ['duration_ms' => $duration]);
            
            // Populate object cache per prossimo request
            if (self::use_object_cache() && $layer !== self::LAYER_TRANSIENT) {
                wp_cache_set($key, $cached, self::CACHE_GROUP, $ttl);
            }
            
            do_action('fp_newspaper_cache_hit', $key, 'transient');
            return $cached;
        }
        
        // 3. Cache MISS - esegui callback
        Logger::debug("Cache MISS: {$key}");
        do_action('fp_newspaper_cache_miss', $key);
        
        $data = Logger::measure("cache_callback_{$key}", $callback);
        
        // 4. Salva in cache (entrambi i layer)
        self::set($key, $data, $ttl, $layer);
        
        $total_duration = (microtime(true) - $start) * 1000;
        Logger::performance("cache_get_{$key}", $total_duration, ['cache_miss' => true]);
        
        return $data;
    }
    
    /**
     * Imposta valore in cache
     *
     * @param string $key
     * @param mixed  $value
     * @param int    $ttl
     * @param string $layer
     * @return bool
     */
    public static function set($key, $value, $ttl = self::DEFAULT_TTL, $layer = self::LAYER_ALL) {
        $success = true;
        
        // Salva in object cache
        if (self::use_object_cache() && $layer !== self::LAYER_TRANSIENT) {
            $result = wp_cache_set($key, $value, self::CACHE_GROUP, $ttl);
            $success = $success && $result;
            
            Logger::debug("Cache SET (object): {$key}", ['ttl' => $ttl]);
        }
        
        // Salva in transient
        if ($layer !== self::LAYER_OBJECT_CACHE) {
            $transient_key = self::get_transient_key($key);
            $result = set_transient($transient_key, $value, $ttl);
            $success = $success && $result;
            
            Logger::debug("Cache SET (transient): {$key}", ['ttl' => $ttl]);
        }
        
        do_action('fp_newspaper_cache_set', $key, $ttl, $layer);
        
        return $success;
    }
    
    /**
     * Elimina valore dalla cache
     *
     * @param string $key
     * @param string $layer
     * @return bool
     */
    public static function delete($key, $layer = self::LAYER_ALL) {
        $success = true;
        
        // Delete da object cache
        if (self::use_object_cache() && $layer !== self::LAYER_TRANSIENT) {
            $result = wp_cache_delete($key, self::CACHE_GROUP);
            $success = $success && $result;
            
            Logger::debug("Cache DELETE (object): {$key}");
        }
        
        // Delete da transient
        if ($layer !== self::LAYER_OBJECT_CACHE) {
            $transient_key = self::get_transient_key($key);
            $result = delete_transient($transient_key);
            $success = $success && $result;
            
            Logger::debug("Cache DELETE (transient): {$key}");
        }
        
        do_action('fp_newspaper_cache_delete', $key, $layer);
        
        return $success;
    }
    
    /**
     * Invalida cache di un articolo specifico
     *
     * @param int $post_id
     */
    public static function invalidate_article($post_id) {
        $keys = [
            "article_{$post_id}_stats",
            "article_{$post_id}_card",
            "article_{$post_id}_meta",
        ];
        
        foreach ($keys as $key) {
            self::delete($key);
        }
        
        Logger::info("Article cache invalidated", ['post_id' => $post_id]);
        do_action('fp_newspaper_article_cache_invalidated', $post_id);
    }
    
    /**
     * Invalida cache liste (featured, breaking news, etc.)
     */
    public static function invalidate_lists() {
        $keys = [
            'featured_articles_cache',
            'breaking_news_cache',
            'latest_articles_cache',
        ];
        
        foreach ($keys as $key) {
            self::delete($key);
        }
        
        Logger::info("Lists cache invalidated");
        do_action('fp_newspaper_lists_cache_invalidated');
    }
    
    /**
     * Invalida cache statistiche
     */
    public static function invalidate_stats() {
        self::delete('newspaper_stats_cache');
        
        Logger::info("Stats cache invalidated");
        do_action('fp_newspaper_stats_cache_invalidated');
    }
    
    /**
     * Invalida TUTTA la cache (nuclear option)
     */
    public static function flush_all() {
        // Flush object cache group
        if (self::use_object_cache()) {
            wp_cache_flush_group(self::CACHE_GROUP);
        }
        
        // Delete tutti i transient del plugin
        global $wpdb;
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_fp_newspaper_%' 
             OR option_name LIKE '_transient_timeout_fp_newspaper_%'"
        );
        
        Logger::warning("ALL cache flushed (nuclear)");
        do_action('fp_newspaper_cache_flushed');
    }
    
    /**
     * Cache warming: pre-carica cache critiche
     */
    public static function warm_cache() {
        Logger::info("Cache warming started");
        
        // Pre-carica articoli featured
        self::get('featured_articles_cache', function() {
            $args = [
                'post_type'      => 'post',
                'post_status'    => 'publish',
                'posts_per_page' => 20,
                'meta_query'     => [
                    [
                        'key'   => '_fp_featured',
                        'value' => '1',
                    ],
                ],
                'orderby'        => 'date',
                'order'          => 'DESC',
            ];
            
            $query = new \WP_Query($args);
            return $query->posts;
        }, 600); // 10 minuti
        
        // Pre-carica statistiche globali
        self::get('newspaper_stats_cache', function() {
            global $wpdb;
            
            return [
                'total_articles' => (int) $wpdb->get_var(
                    "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'post' AND post_status = 'publish'"
                ),
                'total_views' => (int) $wpdb->get_var(
                    "SELECT SUM(views) FROM {$wpdb->prefix}fp_newspaper_stats"
                ),
                'total_shares' => (int) $wpdb->get_var(
                    "SELECT SUM(shares) FROM {$wpdb->prefix}fp_newspaper_stats"
                ),
            ];
        }, 300); // 5 minuti
        
        Logger::info("Cache warming completed");
        do_action('fp_newspaper_cache_warmed');
    }
    
    /**
     * Statistiche cache
     *
     * @return array
     */
    public static function get_stats() {
        global $wpdb;
        
        // Conta transient attivi
        $transient_count = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_fp_newspaper_%'"
        );
        
        return [
            'using_object_cache' => self::use_object_cache(),
            'transient_count'    => $transient_count,
            'cache_group'        => self::CACHE_GROUP,
        ];
    }
    
    /**
     * Verifica se object cache è disponibile
     *
     * @return bool
     */
    private static function use_object_cache() {
        return wp_using_ext_object_cache() && function_exists('wp_cache_get');
    }
    
    /**
     * Genera transient key con prefisso
     *
     * @param string $key
     * @return string
     */
    private static function get_transient_key($key) {
        return 'fp_newspaper_' . $key;
    }
    
    /**
     * Ottiene cache key per articolo
     *
     * @param int    $post_id
     * @param string $suffix
     * @return string
     */
    public static function get_article_key($post_id, $suffix = '') {
        return "article_{$post_id}" . ($suffix ? "_{$suffix}" : '');
    }
}

