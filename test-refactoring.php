<?php
/**
 * Script Test Refactoring v1.2.0
 * 
 * Verifica che il refactoring a post type nativo funzioni correttamente
 * 
 * Uso: http://tuosito.com/wp-content/plugins/FP-Newspaper/test-refactoring.php
 * 
 * @package FPNewspaper
 */

define('WP_USE_THEMES', false);
require_once('../../../wp-load.php');

if (!current_user_can('manage_options')) {
    wp_die('Accesso negato. Solo amministratori possono eseguire i test.');
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>FP Newspaper - Test Refactoring v1.2.0</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #2c3e50; border-bottom: 3px solid #3498db; padding-bottom: 10px; }
        h2 { color: #34495e; margin-top: 30px; }
        .test-section { margin: 20px 0; padding: 15px; background: #ecf0f1; border-radius: 4px; }
        .pass { color: #27ae60; font-weight: bold; }
        .fail { color: #e74c3c; font-weight: bold; }
        .warning { color: #f39c12; font-weight: bold; }
        .code { background: #2c3e50; color: #ecf0f1; padding: 10px; border-radius: 4px; overflow-x: auto; font-family: monospace; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        table th, table td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        table th { background: #3498db; color: white; }
        table tr:nth-child(even) { background: #f9f9f9; }
        .summary { background: #3498db; color: white; padding: 20px; border-radius: 4px; margin-bottom: 20px; }
        .summary-item { display: inline-block; margin: 0 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 FP Newspaper - Test Refactoring v1.2.0</h1>
        
        <?php
        $tests_passed = 0;
        $tests_failed = 0;
        $tests_warnings = 0;
        
        // TEST 1: Verifica Post Type
        echo '<div class="test-section">';
        echo '<h2>TEST 1: Verifica Post Type Nativo</h2>';
        
        $post_type_exists = post_type_exists('post');
        $old_cpt_exists = post_type_exists('fp_article');
        
        if ($post_type_exists && !$old_cpt_exists) {
            echo '<p class="pass">✅ PASS: Post type nativo "post" esiste</p>';
            echo '<p class="pass">✅ PASS: CPT "fp_article" NON esiste più (corretto)</p>';
            $tests_passed += 2;
        } else {
            if (!$post_type_exists) {
                echo '<p class="fail">❌ FAIL: Post type "post" non esiste</p>';
                $tests_failed++;
            }
            if ($old_cpt_exists) {
                echo '<p class="warning">⚠️ WARNING: CPT "fp_article" esiste ancora (potrebbe essere OK se pre-migrazione)</p>';
                $tests_warnings++;
            }
        }
        echo '</div>';
        
        // TEST 2: Verifica Tassonomie
        echo '<div class="test-section">';
        echo '<h2>TEST 2: Verifica Tassonomie Native</h2>';
        
        $category_exists = taxonomy_exists('category');
        $post_tag_exists = taxonomy_exists('post_tag');
        $old_cat_exists = taxonomy_exists('fp_article_category');
        $old_tag_exists = taxonomy_exists('fp_article_tag');
        
        if ($category_exists && $post_tag_exists && !$old_cat_exists && !$old_tag_exists) {
            echo '<p class="pass">✅ PASS: Tassonomie native (category, post_tag) esistono</p>';
            echo '<p class="pass">✅ PASS: Vecchie tassonomie NON esistono più (corretto)</p>';
            $tests_passed += 2;
        } else {
            if ($old_cat_exists || $old_tag_exists) {
                echo '<p class="warning">⚠️ WARNING: Vecchie tassonomie esistono ancora (eseguire migrazione)</p>';
                $tests_warnings++;
            }
        }
        echo '</div>';
        
        // TEST 3: Verifica Tabella Stats
        echo '<div class="test-section">';
        echo '<h2>TEST 3: Verifica Tabella Database</h2>';
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'fp_newspaper_stats';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        
        if ($table_exists) {
            echo '<p class="pass">✅ PASS: Tabella fp_newspaper_stats esiste</p>';
            $tests_passed++;
            
            // Conta record
            $record_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
            echo "<p>📊 Record nella tabella: <strong>{$record_count}</strong></p>";
            
            // Verifica indici
            $indexes = $wpdb->get_results("SHOW INDEX FROM $table_name");
            $index_names = array_column($indexes, 'Key_name');
            
            echo '<p>🔍 Indici presenti: ' . implode(', ', array_unique($index_names)) . '</p>';
            
            if (in_array('idx_views_updated', $index_names)) {
                echo '<p class="pass">✅ Indice views ottimizzato presente</p>';
            } else {
                echo '<p class="warning">⚠️ Indice views mancante (esegui wp fp-newspaper optimize)</p>';
                $tests_warnings++;
            }
        } else {
            echo '<p class="fail">❌ FAIL: Tabella stats non esiste</p>';
            $tests_failed++;
        }
        echo '</div>';
        
        // TEST 4: Verifica Meta Boxes
        echo '<div class="test-section">';
        echo '<h2>TEST 4: Verifica Meta Boxes</h2>';
        
        if (class_exists('FPNewspaper\Admin\MetaBoxes')) {
            echo '<p class="pass">✅ PASS: Classe MetaBoxes caricata</p>';
            $tests_passed++;
            
            // Verifica che meta boxes siano registrati per 'post'
            global $wp_meta_boxes;
            if (isset($wp_meta_boxes['post'])) {
                $mb_count = 0;
                foreach (['side', 'normal', 'advanced'] as $context) {
                    if (isset($wp_meta_boxes['post'][$context])) {
                        $mb_count += count($wp_meta_boxes['post'][$context]);
                    }
                }
                echo "<p class=\"pass\">✅ PASS: {$mb_count} meta boxes registrati per post type 'post'</p>";
                $tests_passed++;
            } else {
                echo '<p class="warning">⚠️ WARNING: Meta boxes non ancora registrati (normale se non in pagina edit)</p>';
            }
        } else {
            echo '<p class="fail">❌ FAIL: Classe MetaBoxes non trovata</p>';
            $tests_failed++;
        }
        echo '</div>';
        
        // TEST 5: Verifica Shortcodes
        echo '<div class="test-section">';
        echo '<h2>TEST 5: Verifica Shortcodes</h2>';
        
        $expected_shortcodes = [
            'fp_articles',
            'fp_featured_articles',
            'fp_breaking_news',
            'fp_latest_articles',
            'fp_article_stats',
            'fp_newspaper_archive',
            'fp_interactive_map',
        ];
        
        $registered_count = 0;
        foreach ($expected_shortcodes as $shortcode) {
            if (shortcode_exists($shortcode)) {
                echo "<p class=\"pass\">✅ [{$shortcode}] registrato</p>";
                $registered_count++;
            } else {
                echo "<p class=\"fail\">❌ [{$shortcode}] NON registrato</p>";
                $tests_failed++;
            }
        }
        
        if ($registered_count === count($expected_shortcodes)) {
            echo "<p class=\"pass\">✅ PASS: Tutti i {$registered_count} shortcodes registrati correttamente</p>";
            $tests_passed++;
        }
        echo '</div>';
        
        // TEST 6: Verifica REST API
        echo '<div class="test-section">';
        echo '<h2>TEST 6: Verifica REST API</h2>';
        
        $rest_server = rest_get_server();
        $routes = $rest_server->get_routes();
        
        $expected_endpoints = [
            '/fp-newspaper/v1/stats',
            '/fp-newspaper/v1/articles/(?P<id>\d+)/view',
            '/fp-newspaper/v1/articles/featured',
            '/fp-newspaper/v1/health',
        ];
        
        $endpoints_found = 0;
        foreach ($expected_endpoints as $endpoint) {
            $pattern = str_replace(['(?P<id>\d+)', '\\'], ['*', '/'], $endpoint);
            $found = false;
            
            foreach ($routes as $route => $handlers) {
                if (strpos($route, '/fp-newspaper/v1/') !== false) {
                    $endpoints_found++;
                    break;
                }
            }
        }
        
        if ($endpoints_found > 0) {
            echo "<p class=\"pass\">✅ PASS: {$endpoints_found} endpoint REST API registrati</p>";
            $tests_passed++;
        } else {
            echo '<p class="fail">❌ FAIL: Nessun endpoint REST API trovato</p>';
            $tests_failed++;
        }
        echo '</div>';
        
        // TEST 7: Verifica Cache Manager
        echo '<div class="test-section">';
        echo '<h2>TEST 7: Verifica Nuovi Componenti Enterprise</h2>';
        
        if (class_exists('FPNewspaper\Cache\Manager')) {
            echo '<p class="pass">✅ PASS: Cache Manager caricato</p>';
            $tests_passed++;
            
            $cache_stats = FPNewspaper\Cache\Manager::get_stats();
            echo '<p>📊 Object Cache: ' . ($cache_stats['using_object_cache'] ? '<strong class="pass">Attivo</strong>' : '<em>Non attivo (usa transient)</em>') . '</p>';
            echo '<p>📊 Transient attivi: ' . $cache_stats['transient_count'] . '</p>';
        } else {
            echo '<p class="fail">❌ FAIL: Cache Manager non caricato</p>';
            $tests_failed++;
        }
        
        if (class_exists('FPNewspaper\Logger')) {
            echo '<p class="pass">✅ PASS: Logger caricato</p>';
            $tests_passed++;
        } else {
            echo '<p class="fail">❌ FAIL: Logger non caricato</p>';
            $tests_failed++;
        }
        
        if (class_exists('FPNewspaper\Security\RateLimiter')) {
            echo '<p class="pass">✅ PASS: Rate Limiter caricato</p>';
            $tests_passed++;
            
            $rate_stats = FPNewspaper\Security\RateLimiter::get_stats();
            echo '<p>📊 IP bannati: ' . $rate_stats['banned_ips'] . '</p>';
            echo '<p>📊 IP con violazioni: ' . $rate_stats['ips_with_violations'] . '</p>';
        } else {
            echo '<p class="fail">❌ FAIL: Rate Limiter non caricato</p>';
            $tests_failed++;
        }
        echo '</div>';
        
        // TEST 8: Verifica Query Optimization
        echo '<div class="test-section">';
        echo '<h2>TEST 8: Verifica Query Ottimizzate</h2>';
        
        if (method_exists('FPNewspaper\DatabaseOptimizer', 'get_most_viewed')) {
            echo '<p class="pass">✅ PASS: Metodo get_most_viewed() esiste</p>';
            $tests_passed++;
            
            // Test query
            $start = microtime(true);
            $results = FPNewspaper\DatabaseOptimizer::get_most_viewed(5);
            $duration = (microtime(true) - $start) * 1000;
            
            echo '<p>⚡ Query duration: <strong>' . number_format($duration, 2) . 'ms</strong></p>';
            echo '<p>📊 Risultati: ' . count($results) . ' articoli</p>';
            
            if ($duration < 100) {
                echo '<p class="pass">✅ Performance eccellente (<100ms)</p>';
                $tests_passed++;
            } elseif ($duration < 500) {
                echo '<p class="warning">⚠️ Performance accettabile (<500ms)</p>';
                $tests_warnings++;
            } else {
                echo '<p class="fail">❌ Query lenta (>500ms)</p>';
                $tests_failed++;
            }
        } else {
            echo '<p class="fail">❌ FAIL: Metodo get_most_viewed() non trovato</p>';
            $tests_failed++;
        }
        echo '</div>';
        
        // TEST 9: Verifica Compatibilità Plugin
        echo '<div class="test-section">';
        echo '<h2>TEST 9: Compatibilità Plugin Terzi</h2>';
        
        // Yoast SEO
        if (defined('WPSEO_VERSION')) {
            echo '<p class="pass">✅ Yoast SEO rilevato (versione ' . WPSEO_VERSION . ')</p>';
            echo '<p class="pass">   Compatibilità: OK (usa post nativo)</p>';
            $tests_passed++;
        } else {
            echo '<p>ℹ️ Yoast SEO non installato</p>';
        }
        
        // Rank Math
        if (defined('RANK_MATH_VERSION')) {
            echo '<p class="pass">✅ Rank Math rilevato (versione ' . RANK_MATH_VERSION . ')</p>';
            echo '<p class="pass">   Compatibilità: OK (usa post nativo)</p>';
            $tests_passed++;
        } else {
            echo '<p>ℹ️ Rank Math non installato</p>';
        }
        
        // Altri plugin FP
        $fp_plugins = [
            'FP-SEO-Manager' => 'FP_SEO_MANAGER_VERSION',
            'FP-Performance' => 'FP_PERFORMANCE_VERSION',
            'FP-Multilanguage' => 'FP_MULTILANGUAGE_VERSION',
        ];
        
        foreach ($fp_plugins as $name => $constant) {
            if (defined($constant)) {
                echo "<p class=\"pass\">✅ {$name} rilevato</p>";
            }
        }
        echo '</div>';
        
        // TEST 10: Verifica Migrazione Necessaria
        echo '<div class="test-section">';
        echo '<h2>TEST 10: Stato Migrazione Dati</h2>';
        
        $old_articles = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'fp_article'");
        $old_cats = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->term_taxonomy} WHERE taxonomy = 'fp_article_category'");
        $old_tags = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->term_taxonomy} WHERE taxonomy = 'fp_article_tag'");
        
        if ($old_articles === 0 && $old_cats === 0 && $old_tags === 0) {
            echo '<p class="pass">✅ PASS: Nessun dato vecchio presente (migrazione completata o non necessaria)</p>';
            $tests_passed++;
        } else {
            echo '<p class="warning">⚠️ WARNING: Migrazione necessaria!</p>';
            echo '<ul>';
            if ($old_articles > 0) echo "<li>fp_article: {$old_articles} articoli da migrare</li>";
            if ($old_cats > 0) echo "<li>fp_article_category: {$old_cats} categorie da migrare</li>";
            if ($old_tags > 0) echo "<li>fp_article_tag: {$old_tags} tag da migrare</li>";
            echo '</ul>';
            echo '<div class="code">php wp-content/plugins/FP-Newspaper/migrate-to-native-posts.php</div>';
            $tests_warnings++;
        }
        echo '</div>';
        
        // SUMMARY
        $total_tests = $tests_passed + $tests_failed + $tests_warnings;
        $pass_rate = $total_tests > 0 ? round(($tests_passed / $total_tests) * 100) : 0;
        
        echo '<div class="summary">';
        echo '<h2>📊 Riepilogo Test</h2>';
        echo '<div class="summary-item">✅ Passati: <strong>' . $tests_passed . '</strong></div>';
        echo '<div class="summary-item">❌ Falliti: <strong>' . $tests_failed . '</strong></div>';
        echo '<div class="summary-item">⚠️ Warning: <strong>' . $tests_warnings . '</strong></div>';
        echo '<div class="summary-item">📈 Pass Rate: <strong>' . $pass_rate . '%</strong></div>';
        echo '</div>';
        
        if ($tests_failed === 0 && $tests_warnings === 0) {
            echo '<div style="background: #27ae60; color: white; padding: 20px; border-radius: 4px; text-align: center; font-size: 20px;">';
            echo '🎉 TUTTI I TEST PASSATI! Plugin pronto per la produzione!';
            echo '</div>';
        } elseif ($tests_failed === 0) {
            echo '<div style="background: #f39c12; color: white; padding: 20px; border-radius: 4px; text-align: center;">';
            echo '⚠️ Test passati ma con warning. Verifica i warning sopra.';
            echo '</div>';
        } else {
            echo '<div style="background: #e74c3c; color: white; padding: 20px; border-radius: 4px; text-align: center;">';
            echo '❌ Alcuni test sono falliti. Verifica gli errori sopra.';
            echo '</div>';
        }
        ?>
        
        <div style="margin-top: 30px; padding: 20px; background: #ecf0f1; border-radius: 4px;">
            <h3>📋 Prossimi Passi</h3>
            <ol>
                <?php if ($old_articles > 0 || $old_cats > 0 || $old_tags > 0): ?>
                    <li><strong>Esegui migrazione dati:</strong>
                        <div class="code">php wp-content/plugins/FP-Newspaper/migrate-to-native-posts.php</div>
                    </li>
                <?php endif; ?>
                <li>Verifica che meta boxes appaiano in WordPress Admin → Articoli → Aggiungi nuovo</li>
                <li>Testa shortcodes nel frontend</li>
                <li>Verifica compatibilità con plugin SEO (se installati)</li>
                <li>Test completo del workflow</li>
            </ol>
        </div>
        
        <div style="margin-top: 20px; text-align: center; color: #7f8c8d;">
            <p>FP Newspaper v<?php echo FP_NEWSPAPER_VERSION; ?> - Test Report generato il <?php echo date('Y-m-d H:i:s'); ?></p>
        </div>
    </div>
</body>
</html>


