<?php
/**
 * WP-CLI Commands per FP Newspaper
 *
 * @package FPNewspaper
 */

namespace FPNewspaper\CLI;

defined('ABSPATH') || exit;

/**
 * Comandi WP-CLI per gestione articoli
 */
class Commands {
    
    /**
     * Registra comandi WP-CLI
     */
    public static function register() {
        if (!defined('WP_CLI') || !WP_CLI) {
            return;
        }
        
        \WP_CLI::add_command('fp-newspaper', __CLASS__);
    }
    
    /**
     * Mostra statistiche generali
     *
     * ## EXAMPLES
     *
     *     wp fp-newspaper stats
     *
     * @when after_wp_load
     */
    public function stats($args, $assoc_args) {
        global $wpdb;
        
        $total = wp_count_posts('fp_article');
        $table_name = $wpdb->prefix . 'fp_newspaper_stats';
        
        \WP_CLI::line('');
        \WP_CLI::line(\WP_CLI::colorize('%G=== FP Newspaper - Statistiche ===%n'));
        \WP_CLI::line('');
        
        // Articoli
        \WP_CLI::line(sprintf('Articoli pubblicati: %s%d%s', 
            \WP_CLI::colorize('%B'), 
            $total->publish,
            \WP_CLI::colorize('%n')
        ));
        \WP_CLI::line(sprintf('Bozze: %d', $total->draft));
        \WP_CLI::line(sprintf('Nel cestino: %d', $total->trash));
        
        // Statistiche views
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name) {
            $stats = $wpdb->get_row(
                "SELECT 
                    COALESCE(SUM(views), 0) as total_views,
                    COALESCE(SUM(shares), 0) as total_shares,
                    COUNT(*) as tracked
                FROM $table_name"
            );
            
            \WP_CLI::line('');
            \WP_CLI::line(sprintf('Visualizzazioni totali: %s%s%s',
                \WP_CLI::colorize('%Y'),
                number_format_i18n($stats->total_views),
                \WP_CLI::colorize('%n')
            ));
            \WP_CLI::line(sprintf('Condivisioni totali: %s', number_format_i18n($stats->total_shares)));
            \WP_CLI::line(sprintf('Articoli tracciati: %s', $stats->tracked));
        }
        
        \WP_CLI::line('');
        \WP_CLI::success('Statistiche recuperate con successo!');
    }
    
    /**
     * Pulisce statistiche vecchie
     *
     * ## OPTIONS
     *
     * [--days=<days>]
     * : Giorni di retention (default: 365)
     *
     * [--dry-run]
     * : Simula senza cancellare
     *
     * ## EXAMPLES
     *
     *     wp fp-newspaper cleanup --days=90
     *     wp fp-newspaper cleanup --days=180 --dry-run
     *
     * @when after_wp_load
     */
    public function cleanup($args, $assoc_args) {
        $days = isset($assoc_args['days']) ? absint($assoc_args['days']) : 365;
        $dry_run = isset($assoc_args['dry-run']);
        
        if (!class_exists('FPNewspaper\DatabaseOptimizer')) {
            \WP_CLI::error('DatabaseOptimizer class not found');
            return;
        }
        
        \WP_CLI::line('');
        \WP_CLI::line(\WP_CLI::colorize('%Y=== FP Newspaper - Cleanup Statistiche ===%n'));
        \WP_CLI::line('');
        \WP_CLI::line(sprintf('Retention: %d giorni', $days));
        \WP_CLI::line(sprintf('Dry run: %s', $dry_run ? 'Sì' : 'No'));
        \WP_CLI::line('');
        
        if ($dry_run) {
            \WP_CLI::warning('SIMULAZIONE - Nessun dato sarà cancellato');
        }
        
        if (!$dry_run) {
            $deleted = \FPNewspaper\DatabaseOptimizer::cleanup_old_stats($days);
            \WP_CLI::success(sprintf('Cancellati %d record', $deleted));
        } else {
            \WP_CLI::line('In modalità dry-run - nessuna modifica effettuata');
        }
    }
    
    /**
     * Ottimizza database
     *
     * ## EXAMPLES
     *
     *     wp fp-newspaper optimize
     *
     * @when after_wp_load
     */
    public function optimize($args, $assoc_args) {
        if (!class_exists('FPNewspaper\DatabaseOptimizer')) {
            \WP_CLI::error('DatabaseOptimizer class not found');
            return;
        }
        
        \WP_CLI::line('');
        \WP_CLI::line(\WP_CLI::colorize('%G=== FP Newspaper - Ottimizzazione Database ===%n'));
        \WP_CLI::line('');
        
        $result = \FPNewspaper\DatabaseOptimizer::optimize_stats_table();
        
        if ($result) {
            \WP_CLI::success('Database ottimizzato con successo!');
            
            // Mostra report performance
            $perf = \FPNewspaper\DatabaseOptimizer::analyze_performance();
            \WP_CLI::line('');
            \WP_CLI::line('Performance Report:');
            \WP_CLI::line(sprintf('  - Record: %d', $perf['row_count']));
            \WP_CLI::line(sprintf('  - Dimensione tabella: %s', size_format($perf['table_size'])));
            \WP_CLI::line(sprintf('  - Dimensione indici: %s', size_format($perf['index_size'])));
            \WP_CLI::line(sprintf('  - Frammentazione: %s', size_format($perf['fragmentation'])));
            
            if (!empty($perf['suggestions'])) {
                \WP_CLI::line('');
                \WP_CLI::warning('Suggerimenti:');
                foreach ($perf['suggestions'] as $suggestion) {
                    \WP_CLI::line('  - ' . $suggestion);
                }
            }
        } else {
            \WP_CLI::error('Errore durante ottimizzazione');
        }
    }
    
    /**
     * Invalida tutte le cache
     *
     * ## EXAMPLES
     *
     *     wp fp-newspaper cache-clear
     *
     * @when after_wp_load
     */
    public function cache_clear($args, $assoc_args) {
        \WP_CLI::line('');
        \WP_CLI::line(\WP_CLI::colorize('%Y=== FP Newspaper - Cancellazione Cache ===%n'));
        \WP_CLI::line('');
        
        delete_transient('fp_newspaper_stats_cache');
        delete_transient('fp_featured_articles_cache');
        
        // Cancella anche rate limit transients
        global $wpdb;
        $deleted = $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_fp_view_%' 
             OR option_name LIKE '_transient_timeout_fp_view_%'"
        );
        
        \WP_CLI::success(sprintf('Cache cancellata! (%d transient rimossi)', $deleted + 2));
    }
    
    /**
     * Genera articoli di test
     *
     * ## OPTIONS
     *
     * [--count=<count>]
     * : Numero di articoli da creare (default: 10)
     *
     * [--with-meta]
     * : Aggiungi meta featured/breaking random
     *
     * ## EXAMPLES
     *
     *     wp fp-newspaper generate --count=50
     *     wp fp-newspaper generate --count=100 --with-meta
     *
     * @when after_wp_load
     */
    public function generate($args, $assoc_args) {
        $count = isset($assoc_args['count']) ? absint($assoc_args['count']) : 10;
        $with_meta = isset($assoc_args['with-meta']);
        
        if ($count > 1000) {
            \WP_CLI::error('Massimo 1000 articoli per volta');
            return;
        }
        
        \WP_CLI::line('');
        \WP_CLI::line(\WP_CLI::colorize('%G=== FP Newspaper - Generazione Articoli ===%n'));
        \WP_CLI::line('');
        
        $progress = \WP_CLI\Utils\make_progress_bar('Creazione articoli', $count);
        
        for ($i = 1; $i <= $count; $i++) {
            $post_id = wp_insert_post([
                'post_title'   => 'Articolo Test ' . $i . ' - ' . date('Y-m-d H:i:s'),
                'post_content' => 'Contenuto di test per articolo numero ' . $i . '. ' . wp_generate_password(200, false),
                'post_excerpt' => 'Excerpt articolo test numero ' . $i,
                'post_status'  => 'publish',
                'post_type'    => 'fp_article',
                'post_author'  => 1,
            ]);
            
            if ($with_meta && $post_id) {
                // 20% featured
                if (rand(1, 100) <= 20) {
                    update_post_meta($post_id, '_fp_featured', '1');
                }
                
                // 10% breaking
                if (rand(1, 100) <= 10) {
                    update_post_meta($post_id, '_fp_breaking_news', '1');
                }
            }
            
            $progress->tick();
        }
        
        $progress->finish();
        
        \WP_CLI::success(sprintf('Creati %d articoli di test!', $count));
    }
}

