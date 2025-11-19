<?php
/**
 * Script Migrazione: Converti CPT fp_article a post nativo
 * 
 * IMPORTANTE: Esegui BACKUP del database prima di eseguire!
 * 
 * Uso:
 * 1. Accedi via SSH/FTP alla cartella del plugin
 * 2. Esegui: php migrate-to-native-posts.php
 * 3. Oppure apri via browser: http://tuosito.com/wp-content/plugins/FP-Newspaper/migrate-to-native-posts.php
 * 
 * @package FPNewspaper
 */

// Sicurezza: impedisci accesso diretto senza autenticazione
if (php_sapi_name() !== 'cli') {
    // Se eseguito via web, richiedi autenticazione admin
    define('WP_USE_THEMES', false);
    require_once('../../../wp-load.php');
    
    if (!current_user_can('manage_options')) {
        wp_die('Accesso negato. Solo amministratori possono eseguire la migrazione.');
    }
}

// Carica WordPress se CLI
if (php_sapi_name() === 'cli') {
    require_once(dirname(__FILE__) . '/../../../wp-load.php');
}

// Flag dry run (test senza modifiche)
$dry_run = isset($argv[1]) && $argv[1] === '--dry-run';

echo "\n";
echo "═══════════════════════════════════════════════════════════════════\n";
echo "  FP NEWSPAPER - MIGRAZIONE A POST TYPE NATIVO\n";
echo "═══════════════════════════════════════════════════════════════════\n";
echo "\n";

if ($dry_run) {
    echo "🔍 MODALITÀ DRY-RUN: Nessuna modifica sarà applicata\n\n";
} else {
    echo "⚠️  ATTENZIONE: Stai per modificare il database!\n";
    echo "   Assicurati di aver fatto un BACKUP prima di procedere.\n\n";
    
    // Conferma (solo se CLI)
    if (php_sapi_name() === 'cli') {
        echo "Vuoi procedere? (s/n): ";
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        if (trim($line) !== 's' && trim($line) !== 'S') {
            echo "\n❌ Migrazione annullata.\n\n";
            exit;
        }
        fclose($handle);
        echo "\n";
    }
}

global $wpdb;

// Statistiche pre-migrazione
echo "📊 Analisi database...\n";
echo "-------------------------------------------------------------------\n";

$count_fp_articles = (int) $wpdb->get_var("
    SELECT COUNT(*) FROM {$wpdb->posts} 
    WHERE post_type = 'fp_article'
");

$count_fp_categories = (int) $wpdb->get_var("
    SELECT COUNT(*) FROM {$wpdb->term_taxonomy} 
    WHERE taxonomy = 'fp_article_category'
");

$count_fp_tags = (int) $wpdb->get_var("
    SELECT COUNT(*) FROM {$wpdb->term_taxonomy} 
    WHERE taxonomy = 'fp_article_tag'
");

echo sprintf("  📄 Articoli fp_article trovati: %d\n", $count_fp_articles);
echo sprintf("  📁 Categorie fp_article_category: %d\n", $count_fp_categories);
echo sprintf("  🏷️  Tag fp_article_tag: %d\n", $count_fp_tags);
echo "\n";

if ($count_fp_articles === 0 && $count_fp_categories === 0 && $count_fp_tags === 0) {
    echo "✅ Nessun dato da migrare. Il plugin sta già usando post nativi!\n\n";
    exit;
}

echo "🔄 Inizio migrazione...\n";
echo "-------------------------------------------------------------------\n";

// 1. MIGRAZIONE POST TYPE
if ($count_fp_articles > 0) {
    echo "\n1️⃣  Conversione Post Type (fp_article → post)\n";
    
    if (!$dry_run) {
        $updated_posts = $wpdb->query("
            UPDATE {$wpdb->posts} 
            SET post_type = 'post' 
            WHERE post_type = 'fp_article'
        ");
        
        if ($updated_posts === false) {
            echo "   ❌ ERRORE: {$wpdb->last_error}\n";
            exit(1);
        }
        
        echo "   ✅ Convertiti {$updated_posts} articoli\n";
    } else {
        echo "   🔍 [DRY-RUN] Verrebbero convertiti {$count_fp_articles} articoli\n";
    }
}

// 2. MIGRAZIONE TASSONOMIE
if ($count_fp_categories > 0) {
    echo "\n2️⃣  Conversione Categorie (fp_article_category → category)\n";
    
    if (!$dry_run) {
        $updated_cats = $wpdb->query("
            UPDATE {$wpdb->term_taxonomy} 
            SET taxonomy = 'category' 
            WHERE taxonomy = 'fp_article_category'
        ");
        
        if ($updated_cats === false) {
            echo "   ❌ ERRORE: {$wpdb->last_error}\n";
            exit(1);
        }
        
        echo "   ✅ Convertite {$updated_cats} categorie\n";
    } else {
        echo "   🔍 [DRY-RUN] Verrebbero convertite {$count_fp_categories} categorie\n";
    }
}

if ($count_fp_tags > 0) {
    echo "\n3️⃣  Conversione Tag (fp_article_tag → post_tag)\n";
    
    if (!$dry_run) {
        $updated_tags = $wpdb->query("
            UPDATE {$wpdb->term_taxonomy} 
            SET taxonomy = 'post_tag' 
            WHERE taxonomy = 'fp_article_tag'
        ");
        
        if ($updated_tags === false) {
            echo "   ❌ ERRORE: {$wpdb->last_error}\n";
            exit(1);
        }
        
        echo "   ✅ Convertiti {$updated_tags} tag\n";
    } else {
        echo "   🔍 [DRY-RUN] Verrebbero convertiti {$count_fp_tags} tag\n";
    }
}

// 4. PULIZIA CACHE E REWRITE RULES
if (!$dry_run) {
    echo "\n4️⃣  Pulizia Cache e Rewrite Rules\n";
    
    // Flush rewrite rules
    flush_rewrite_rules();
    echo "   ✅ Rewrite rules aggiornate\n";
    
    // Pulisci cache WordPress
    if (function_exists('wp_cache_flush')) {
        wp_cache_flush();
        echo "   ✅ Object cache pulita\n";
    }
    
    // Pulisci transient FP Newspaper
    delete_transient('fp_newspaper_stats_cache');
    delete_transient('fp_featured_articles_cache');
    $wpdb->query("
        DELETE FROM {$wpdb->options} 
        WHERE option_name LIKE '_transient_fp_newspaper_%' 
        OR option_name LIKE '_transient_timeout_fp_newspaper_%'
    ");
    echo "   ✅ Cache plugin pulita\n";
}

// 5. VERIFICA POST-MIGRAZIONE
if (!$dry_run) {
    echo "\n5️⃣  Verifica Post-Migrazione\n";
    
    $remaining_fp_articles = (int) $wpdb->get_var("
        SELECT COUNT(*) FROM {$wpdb->posts} 
        WHERE post_type = 'fp_article'
    ");
    
    $remaining_fp_cats = (int) $wpdb->get_var("
        SELECT COUNT(*) FROM {$wpdb->term_taxonomy} 
        WHERE taxonomy = 'fp_article_category'
    ");
    
    $remaining_fp_tags = (int) $wpdb->get_var("
        SELECT COUNT(*) FROM {$wpdb->term_taxonomy} 
        WHERE taxonomy = 'fp_article_tag'
    ");
    
    if ($remaining_fp_articles === 0 && $remaining_fp_cats === 0 && $remaining_fp_tags === 0) {
        echo "   ✅ Migrazione completata con successo!\n";
        echo "   ✅ Tutti i dati sono stati convertiti correttamente\n";
    } else {
        echo "   ⚠️  ATTENZIONE: Rimangono dati non migrati:\n";
        if ($remaining_fp_articles > 0) {
            echo "      - fp_article: {$remaining_fp_articles}\n";
        }
        if ($remaining_fp_cats > 0) {
            echo "      - fp_article_category: {$remaining_fp_cats}\n";
        }
        if ($remaining_fp_tags > 0) {
            echo "      - fp_article_tag: {$remaining_fp_tags}\n";
        }
    }
}

echo "\n═══════════════════════════════════════════════════════════════════\n";

if ($dry_run) {
    echo "  ℹ️  MIGRAZIONE DRY-RUN COMPLETATA (nessuna modifica applicata)\n";
    echo "  📝 Rimuovi --dry-run per eseguire la migrazione reale\n";
} else {
    echo "  ✅ MIGRAZIONE COMPLETATA!\n";
}

echo "═══════════════════════════════════════════════════════════════════\n";
echo "\n";

if (!$dry_run) {
    echo "📋 Passi Successivi:\n";
    echo "\n";
    echo "1. Verifica che gli articoli appaiano in 'Articoli' (post nativi)\n";
    echo "2. Verifica che categorie/tag siano correttamente assegnati\n";
    echo "3. Testa i shortcodes nel frontend\n";
    echo "4. Verifica le statistiche (views/shares)\n";
    echo "5. Testa la compatibilità con plugin SEO (Yoast, Rank Math)\n";
    echo "\n";
    echo "⚠️  Se qualcosa non funziona, ripristina il backup del database!\n";
    echo "\n";
}

echo "🎉 Fatto!\n\n";


