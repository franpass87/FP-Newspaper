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
        $index_names = array_column($existing_indexes, 'Key_name');
        
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
        $wpdb->query("OPTIMIZE TABLE $table_name");
        
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
}


