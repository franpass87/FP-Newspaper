<?php
/**
 * Sistema di logging strutturato
 *
 * @package FPNewspaper
 */

namespace FPNewspaper;

defined('ABSPATH') || exit;

/**
 * Logger per debug, performance tracking e error monitoring
 */
class Logger {
    
    /**
     * Livelli di log
     */
    const LEVEL_DEBUG   = 1;
    const LEVEL_INFO    = 2;
    const LEVEL_WARNING = 3;
    const LEVEL_ERROR   = 4;
    
    /**
     * Performance threshold (ms)
     */
    const SLOW_QUERY_THRESHOLD = 100;
    const VERY_SLOW_THRESHOLD  = 500;
    
    /**
     * Log un messaggio di debug
     *
     * @param string $message
     * @param array  $context
     */
    public static function debug($message, $context = []) {
        self::log(self::LEVEL_DEBUG, $message, $context);
    }
    
    /**
     * Log un messaggio informativo
     *
     * @param string $message
     * @param array  $context
     */
    public static function info($message, $context = []) {
        self::log(self::LEVEL_INFO, $message, $context);
    }
    
    /**
     * Log un warning
     *
     * @param string $message
     * @param array  $context
     */
    public static function warning($message, $context = []) {
        self::log(self::LEVEL_WARNING, $message, $context);
    }
    
    /**
     * Log un errore
     *
     * @param string $message
     * @param array  $context
     */
    public static function error($message, $context = []) {
        self::log(self::LEVEL_ERROR, $message, $context);
        
        // Hook per integrazioni esterne (Sentry, Slack, etc.)
        do_action('fp_newspaper_log_error', $message, $context);
    }
    
    /**
     * Track performance di un'operazione
     *
     * @param string $operation Nome operazione
     * @param float  $duration_ms Durata in millisecondi
     * @param array  $context Contesto aggiuntivo
     */
    public static function performance($operation, $duration_ms, $context = []) {
        $context['operation'] = $operation;
        $context['duration_ms'] = round($duration_ms, 2);
        
        // Categorizza in base alla durata
        if ($duration_ms > self::VERY_SLOW_THRESHOLD) {
            self::error("VERY SLOW: {$operation} took {$duration_ms}ms", $context);
            
            // Hook per alert critici
            do_action('fp_newspaper_very_slow_query', $operation, $duration_ms, $context);
            
        } elseif ($duration_ms > self::SLOW_QUERY_THRESHOLD) {
            self::warning("Slow operation: {$operation} took {$duration_ms}ms", $context);
            
        } else {
            self::debug("Performance: {$operation} took {$duration_ms}ms", $context);
        }
        
        // Salva metrica per analytics
        self::save_metric($operation, $duration_ms, $context);
    }
    
    /**
     * Wrapper per misurare automaticamente la performance di una funzione
     *
     * @param string   $operation Nome operazione
     * @param callable $callback Funzione da eseguire
     * @param array    $context Contesto
     * @return mixed Risultato della callback
     */
    public static function measure($operation, callable $callback, $context = []) {
        $start = microtime(true);
        
        try {
            $result = $callback();
            
            $duration = (microtime(true) - $start) * 1000;
            self::performance($operation, $duration, $context);
            
            return $result;
            
        } catch (\Exception $e) {
            $duration = (microtime(true) - $start) * 1000;
            
            self::error("Exception in {$operation}: {$e->getMessage()}", array_merge($context, [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'duration_ms' => $duration,
            ]));
            
            throw $e;
        }
    }
    
    /**
     * Log generico
     *
     * @param int    $level
     * @param string $message
     * @param array  $context
     */
    private static function log($level, $message, $context = []) {
        /**
         * Consente di controllare se un messaggio deve essere loggato.
         * Di default mantenendo i log più rumorosi (debug/info) disattivati
         * per evitare consumo memoria eccessivo quando WP_DEBUG è attivo.
         *
         * @param bool $log
         * @param int  $level
         */
        $allow_level = apply_filters('fp_newspaper_should_log_level', $level >= self::LEVEL_WARNING, $level);
        if (!$allow_level) {
            return;
        }
        
        $level_name = self::get_level_name($level);
        
        $log_entry = sprintf(
            '[%s] FP-Newspaper %s: %s',
            current_time('Y-m-d H:i:s'),
            $level_name,
            $message
        );
        
        // Aggiungi contesto se presente
        if (!empty($context)) {
            $log_entry .= ' | Context: ' . wp_json_encode($context);
        }
        
        // Aggiungi backtrace per errori
        if ($level >= self::LEVEL_ERROR) {
            $log_entry .= ' | Backtrace: ' . wp_debug_backtrace_summary();
        }
        
        error_log($log_entry);
        
        // Hook generico per log esterni
        do_action('fp_newspaper_log', $level, $message, $context);
    }
    
    /**
     * Verifica se il livello dovrebbe essere loggato
     *
     * @param int $level
     * @return bool
     */
    private static function should_log($level) {
        // Errori sempre loggati
        if ($level >= self::LEVEL_ERROR) {
            return true;
        }
        
        // Altri livelli solo se WP_DEBUG
        if (defined('WP_DEBUG') && WP_DEBUG) {
            return true;
        }
        
        // Filtrabile per controllo esterno
        return apply_filters('fp_newspaper_should_log', false, $level);
    }
    
    /**
     * Ottiene il nome del livello di log
     *
     * @param int $level
     * @return string
     */
    private static function get_level_name($level) {
        $names = [
            self::LEVEL_DEBUG   => 'DEBUG',
            self::LEVEL_INFO    => 'INFO',
            self::LEVEL_WARNING => 'WARNING',
            self::LEVEL_ERROR   => 'ERROR',
        ];
        
        return $names[$level] ?? 'UNKNOWN';
    }
    
    /**
     * Salva metrica per analytics
     *
     * @param string $operation
     * @param float  $duration_ms
     * @param array  $context
     */
    private static function save_metric($operation, $duration_ms, $context) {
        // Salva in transient per raccolta metriche
        $metrics_key = 'fp_newspaper_metrics_' . date('Y-m-d-H');
        $metrics = get_transient($metrics_key) ?: [];
        
        $metrics[] = [
            'timestamp'   => current_time('timestamp'),
            'operation'   => $operation,
            'duration_ms' => $duration_ms,
            'context'     => $context,
        ];
        
        // Limita a 100 metriche per ora
        if (count($metrics) > 100) {
            array_shift($metrics);
        }
        
        set_transient($metrics_key, $metrics, HOUR_IN_SECONDS);
        
        // Hook per export metriche
        do_action('fp_newspaper_metric_saved', $operation, $duration_ms, $context);
    }
    
    /**
     * Ottiene statistiche performance aggregate
     *
     * @param string $operation Opzionale: filtra per operazione
     * @param int    $hours Ore da analizzare (default 24)
     * @return array
     */
    public static function get_performance_stats($operation = null, $hours = 24) {
        $all_metrics = [];
        
        // Raccogli metriche dalle ultime N ore
        for ($i = 0; $i < $hours; $i++) {
            $hour_key = 'fp_newspaper_metrics_' . date('Y-m-d-H', strtotime("-{$i} hours"));
            $hour_metrics = get_transient($hour_key);
            
            if (is_array($hour_metrics)) {
                $all_metrics = array_merge($all_metrics, $hour_metrics);
            }
        }
        
        // Filtra per operazione se specificato
        if ($operation !== null) {
            $all_metrics = array_filter($all_metrics, function($metric) use ($operation) {
                return $metric['operation'] === $operation;
            });
        }
        
        if (empty($all_metrics)) {
            return [
                'count' => 0,
                'avg' => 0,
                'min' => 0,
                'max' => 0,
                'p95' => 0,
            ];
        }
        
        // Calcola statistiche
        $durations = array_column($all_metrics, 'duration_ms');
        sort($durations);
        
        $count = count($durations);
        $p95_index = (int) ceil($count * 0.95) - 1;
        
        return [
            'count' => $count,
            'avg'   => round(array_sum($durations) / $count, 2),
            'min'   => round(min($durations), 2),
            'max'   => round(max($durations), 2),
            'p95'   => round($durations[$p95_index], 2),
        ];
    }
    
    /**
     * Pulisce metriche vecchie
     */
    public static function cleanup_old_metrics() {
        global $wpdb;
        
        // Rimuovi transient più vecchi di 48 ore
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_fp_newspaper_metrics_%' 
             AND option_name NOT LIKE '%". date('Y-m-d') ."%'
             AND option_name NOT LIKE '%". date('Y-m-d', strtotime('-1 day')) ."%'"
        );
        
        self::info('Old metrics cleaned up');
    }
}


