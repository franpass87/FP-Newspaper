<?php
/**
 * Database Optimizer - Gestisce indici e ottimizzazioni database
 *
 * @package FPNewspaper
 */

namespace FPNewspaper;

defined('ABSPATH') || exit;

/**
 * Ottimizza struttura database per performance
 */
class DatabaseOptimizer {
    
    /**
     * Ottimizza la tabella stats con indici composti
     */
    public static function optimize_stats_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'fp_newspaper_stats';
        
        // Verifica esistenza tabella
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
            return false;
        }
        
        // Ottieni indici esistenti
        $existing_indexes = $wpdb->get_results("SHOW INDEX FROM $table_name");
        
        // Verifica che il risultato sia valido
        if (is_wp_error($existing_indexes) || !is_array($existing_indexes)) {
            return false;
        }
        
        $index_names = array_column($existing_indexes, 'Key_name');
        
        // Verifica che array_column non sia fallito
        if (!is_array($index_names)) {
            $index_names = [];
        }
        
        // Aggiungi indice composto per query ordinate per views
        if (!in_array('idx_views_updated', $index_names)) {
            $wpdb->query("
                ALTER TABLE $table_name 
                ADD INDEX idx_views_updated (views DESC, last_updated DESC)
            ");
        }
        
        // Aggiungi indice composto per query ordinate per shares
        if (!in_array('idx_shares_updated', $index_names)) {
            $wpdb->query("
                ALTER TABLE $table_name 
                ADD INDEX idx_shares_updated (shares DESC, last_updated DESC)
            ");
        }
        
        // Ottimizza tabella (defragmenta)
        // Nota: OPTIMIZE TABLE non supporta prepared statements in alcuni DB
        $wpdb->query($wpdb->prepare("OPTIMIZE TABLE %1s", $table_name));
        
        return true;
    }
    
    /**
     * Analizza performance query e suggerisce ottimizzazioni
     * 
     * @return array Report ottimizzazioni
     */
    public static function analyze_performance() {
        global $wpdb;
        
        $report = [
            'table_size' => 0,
            'index_size' => 0,
            'row_count' => 0,
            'fragmentation' => 0,
            'suggestions' => []
        ];
        
        $table_name = $wpdb->prefix . 'fp_newspaper_stats';
        
        // Ottieni informazioni tabella
        $table_status = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                TABLE_ROWS as row_count,
                DATA_LENGTH as data_length,
                INDEX_LENGTH as index_length,
                DATA_FREE as data_free
             FROM information_schema.TABLES 
             WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s",
            DB_NAME,
            $table_name
        ));
        
        if ($table_status) {
            $report['row_count'] = (int) $table_status->row_count;
            $report['table_size'] = (int) $table_status->data_length;
            $report['index_size'] = (int) $table_status->index_length;
            $report['fragmentation'] = (int) $table_status->data_free;
            
            // Suggerimenti
            if ($report['fragmentation'] > 1048576) { // > 1MB
                $report['suggestions'][] = 'Frammentazione elevata: eseguire OPTIMIZE TABLE';
            }
            
            if ($report['row_count'] > 10000 && $report['index_size'] < $report['table_size'] * 0.1) {
                $report['suggestions'][] = 'Pochi indici per tabella grande: considerare indici aggiuntivi';
            }
        }
        
        return $report;
    }
    
    /**
     * Pulisce statistiche vecchie (data retention)
     * 
     * @param int $days Giorni di retention (default: 365)
     * @return int Numero record eliminati
     */
    public static function cleanup_old_stats($days = 365) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'fp_newspaper_stats';
        
        // Elimina statistiche per post non più esistenti
        $deleted = $wpdb->query("
            DELETE s FROM $table_name s
            LEFT JOIN {$wpdb->posts} p ON s.post_id = p.ID
            WHERE p.ID IS NULL
        ");
        
        // Elimina statistiche per post in trash da più di X giorni
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        $deleted += $wpdb->query($wpdb->prepare("
            DELETE s FROM $table_name s
            INNER JOIN {$wpdb->posts} p ON s.post_id = p.ID
            WHERE p.post_status = 'trash' 
            AND p.post_modified < %s
        ", $cutoff_date));
        
        return $deleted;
    }
    
    /**
     * Migra dati da postmeta a stats table
     * Questa è una migrazione una tantum per performance
     * 
     * @return array Risultato migrazione
     */
    public static function migrate_meta_to_stats() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'fp_newspaper_stats';
        
        Logger::info('Starting meta to stats migration');
        
        // Migra views e shares da postmeta a tabella stats
        $result = $wpdb->query("
            INSERT INTO {$table_name} (post_id, views, shares, last_updated)
            SELECT 
                pm1.post_id,
                COALESCE(CAST(pm1.meta_value AS UNSIGNED), 0) as views,
                COALESCE(CAST(pm2.meta_value AS UNSIGNED), 0) as shares,
                NOW()
            FROM {$wpdb->postmeta} pm1
            LEFT JOIN {$wpdb->postmeta} pm2 
                ON pm1.post_id = pm2.post_id 
                AND pm2.meta_key = '_fp_shares'
            WHERE pm1.meta_key = '_fp_views'
            AND pm1.post_id IN (
                SELECT ID FROM {$wpdb->posts} 
                WHERE post_type = 'post' 
                AND post_status = 'publish'
            )
            ON DUPLICATE KEY UPDATE
                views = VALUES(views),
                shares = VALUES(shares),
                last_updated = NOW()
        ");
        
        $migrated = $wpdb->rows_affected;
        
        Logger::info('Meta to stats migration completed', ['migrated' => $migrated]);
        
        return [
            'success' => $result !== false,
            'migrated' => $migrated,
        ];
    }
    
    /**
     * Query ottimizzata: articoli più visti
     * Usa stats table invece di postmeta
     * 
     * @param int $limit Numero risultati (default: 10)
     * @param int $offset Offset (default: 0)
     * @return array
     */
    public static function get_most_viewed($limit = 10, $offset = 0) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'fp_newspaper_stats';
        $limit = absint($limit);
        $offset = absint($offset);
        
        $results = $wpdb->get_results($wpdb->prepare("
            SELECT 
                p.ID,
                p.post_title,
                p.post_date,
                s.views,
                s.shares,
                s.last_updated
            FROM {$wpdb->posts} p
            INNER JOIN {$table_name} s ON p.ID = s.post_id
            WHERE p.post_type = 'post' 
            AND p.post_status = 'publish'
            ORDER BY s.views DESC
            LIMIT %d OFFSET %d
        ", $limit, $offset));
        
        return $results ?: [];
    }
    
    /**
     * Query ottimizzata: articoli più condivisi
     * 
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public static function get_most_shared($limit = 10, $offset = 0) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'fp_newspaper_stats';
        $limit = absint($limit);
        $offset = absint($offset);
        
        $results = $wpdb->get_results($wpdb->prepare("
            SELECT 
                p.ID,
                p.post_title,
                p.post_date,
                s.views,
                s.shares,
                s.last_updated
            FROM {$wpdb->posts} p
            INNER JOIN {$table_name} s ON p.ID = s.post_id
            WHERE p.post_type = 'post' 
            AND p.post_status = 'publish'
            AND s.shares > 0
            ORDER BY s.shares DESC
            LIMIT %d OFFSET %d
        ", $limit, $offset));
        
        return $results ?: [];
    }
    
    /**
     * Query ottimizzata: trending articles
     * Articoli con crescita rapida nelle ultime 24h
     * 
     * @param int $limit
     * @return array
     */
    public static function get_trending($limit = 10) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'fp_newspaper_stats';
        $limit = absint($limit);
        
        // Articoli pubblicati nelle ultime 48h con molte views
        $results = $wpdb->get_results($wpdb->prepare("
            SELECT 
                p.ID,
                p.post_title,
                p.post_date,
                s.views,
                s.shares,
                (s.views / (TIMESTAMPDIFF(HOUR, p.post_date, NOW()) + 1)) as velocity
            FROM {$wpdb->posts} p
            INNER JOIN {$table_name} s ON p.ID = s.post_id
            WHERE p.post_type = 'post' 
            AND p.post_status = 'publish'
            AND p.post_date > DATE_SUB(NOW(), INTERVAL 48 HOUR)
            AND s.views > 10
            ORDER BY velocity DESC
            LIMIT %d
        ", $limit));
        
        return $results ?: [];
    }
    
    /**
     * Ottiene statistiche globali (ottimizzato)
     * 
     * @return array
     */
    public static function get_global_stats() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'fp_newspaper_stats';
        
        $stats = $wpdb->get_row("
            SELECT 
                COUNT(DISTINCT s.post_id) as total_articles,
                COALESCE(SUM(s.views), 0) as total_views,
                COALESCE(SUM(s.shares), 0) as total_shares,
                COALESCE(AVG(s.views), 0) as avg_views_per_article,
                MAX(s.views) as max_views
            FROM {$table_name} s
            INNER JOIN {$wpdb->posts} p ON s.post_id = p.ID
            WHERE p.post_type = 'post' 
            AND p.post_status = 'publish'
        ", ARRAY_A);
        
        return $stats ?: [
            'total_articles' => 0,
            'total_views' => 0,
            'total_shares' => 0,
            'avg_views_per_article' => 0,
            'max_views' => 0,
        ];
    }
}







