# 🛠️ Developer Guide - FP Newspaper v1.0.0

Guida completa per sviluppatori che vogliono estendere il plugin.

---

## 🚀 Quick Start

### Struttura PSR-4

```php
namespace FPNewspaper;

// 13 classi disponibili
Plugin              // Singleton principale
Activation          // Hook attivazione
Deactivation        // Hook disattivazione
DatabaseOptimizer   // Ottimizzazione DB
Hooks               // Documentazione hooks

// Admin
Admin\MetaBoxes     // Meta box custom
Admin\Columns       // Colonne admin
Admin\BulkActions   // Azioni bulk
Admin\Settings      // Pagina impostazioni

// Post Types
PostTypes\Article   // CPT + Taxonomies

// REST
REST\Controller     // 5 endpoint REST

// CLI
CLI\Commands        // 5 comandi WP-CLI
```

---

## 🔌 WP-CLI Commands

### Statistiche
```bash
wp fp-newspaper stats
```

### Cleanup Dati Vecchi
```bash
wp fp-newspaper cleanup --days=90
wp fp-newspaper cleanup --days=180 --dry-run
```

### Ottimizza Database
```bash
wp fp-newspaper optimize
```

### Pulisci Cache
```bash
wp fp-newspaper cache-clear
```

### Genera Articoli Test
```bash
wp fp-newspaper generate --count=50
wp fp-newspaper generate --count=100 --with-meta
```

---

## 🎣 Hooks & Filters

### Actions Disponibili

#### Dopo attivazione
```php
add_action('fp_newspaper_after_activation', function($blog_id) {
    // Azioni custom dopo attivazione
    error_log("Plugin attivato su blog: $blog_id");
}, 10, 1);
```

#### Prima disattivazione
```php
add_action('fp_newspaper_before_deactivation', function() {
    // Cleanup custom prima disattivazione
}, 10);
```

#### Dopo salvataggio articolo
```php
add_action('fp_newspaper_after_save_article', function($post_id, $post) {
    // Azioni custom dopo salvataggio
    error_log("Articolo salvato: $post_id");
}, 10, 2);
```

#### Quando view viene incrementata
```php
add_action('fp_newspaper_view_incremented', function($post_id, $new_views) {
    // Notifica se raggiunte 1000 views
    if ($new_views === 1000) {
        // Invia email, notifica, etc.
    }
}, 10, 2);
```

### Filters Disponibili

#### Articoli per pagina
```php
add_filter('fp_newspaper_articles_per_page', function($per_page) {
    return 20; // Cambia da 10 a 20
});
```

#### Numero articoli featured
```php
add_filter('fp_newspaper_featured_count', function($count) {
    return 10; // Cambia da 5 a 10
});
```

#### Argomenti WP_Query
```php
add_filter('fp_newspaper_query_args', function($args) {
    $args['orderby'] = 'rand'; // Ordine casuale
    return $args;
});
```

#### Dati REST API
```php
add_filter('fp_newspaper_rest_article_data', function($article, $post_id) {
    // Aggiungi campi custom
    $article['custom_field'] = get_post_meta($post_id, '_custom', true);
    return $article;
}, 10, 2);
```

#### Durata cache
```php
// Cache statistiche (default: 5 min)
add_filter('fp_newspaper_stats_cache_duration', function($duration) {
    return 10 * MINUTE_IN_SECONDS; // 10 minuti
});

// Cache articoli featured (default: 10 min)
add_filter('fp_newspaper_featured_cache_duration', function($duration) {
    return 30 * MINUTE_IN_SECONDS; // 30 minuti
});
```

#### Rate limiting
```php
add_filter('fp_newspaper_rate_limit_duration', function($seconds) {
    return 60; // 1 view ogni 60 secondi invece di 30
});
```

#### Retention dati
```php
add_filter('fp_newspaper_stats_retention_days', function($days) {
    return 180; // Mantieni statistiche per 6 mesi invece di 1 anno
});
```

---

## 📡 REST API

### Endpoints Disponibili

#### GET /wp-json/fp-newspaper/v1/stats
Statistiche generali (richiede autenticazione admin)

**Response:**
```json
{
  "total_articles": 150,
  "total_views": 25000,
  "total_shares": 1200
}
```

#### POST /wp-json/fp-newspaper/v1/articles/{id}/view
Incrementa visualizzazioni (pubblico, rate limited)

**Request:**
```bash
curl -X POST https://sito.com/wp-json/fp-newspaper/v1/articles/123/view
```

**Response:**
```json
{
  "success": true,
  "message": "Visualizzazione registrata"
}
```

#### GET /wp-json/fp-newspaper/v1/articles/featured
Articoli in evidenza (pubblico, cached)

**Query params:**
- `per_page` - Numero articoli (max 20, default 5)

**Response:**
```json
[
  {
    "id": 123,
    "title": "Titolo articolo",
    "excerpt": "Estratto...",
    "permalink": "https://...",
    "thumbnail": "https://...",
    "date": "2025-10-29T12:00:00+00:00",
    "author": {
      "id": 1,
      "name": "Admin"
    }
  }
]
```

#### GET /wp-json/fp-newspaper/v1/health
Health check per monitoring (richiede autenticazione)

**Response:**
```json
{
  "status": "healthy",
  "timestamp": "2025-10-29T12:00:00+00:00",
  "version": "1.0.0",
  "checks": {
    "database": {"status": "ok", "message": "Table exists"},
    "post_type": {"status": "ok", "message": "Post type registered"},
    "cache": {"status": "ok", "message": "Cache working"},
    "performance": {
      "status": "ok",
      "row_count": 1234,
      "suggestions": []
    }
  }
}
```

---

## 💾 Database

### Tabella: wp_fp_newspaper_stats

```sql
CREATE TABLE wp_fp_newspaper_stats (
  id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  post_id bigint(20) UNSIGNED NOT NULL,
  views bigint(20) UNSIGNED DEFAULT 0,
  shares bigint(20) UNSIGNED DEFAULT 0,
  last_updated datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY post_id (post_id),
  KEY idx_views_updated (views DESC, last_updated DESC),
  KEY idx_shares_updated (shares DESC, last_updated DESC)
);
```

### Query Dirette

```php
global $wpdb;
$table_name = $wpdb->prefix . 'fp_newspaper_stats';

// Ottieni views di un articolo
$views = $wpdb->get_var($wpdb->prepare(
    "SELECT views FROM $table_name WHERE post_id = %d",
    $post_id
));

// Top 10 articoli più visti
$top = $wpdb->get_results(
    "SELECT post_id, views FROM $table_name 
     ORDER BY views DESC LIMIT 10"
);
```

---

## 🎨 Estendere il Plugin

### Esempio: Aggiungere Custom Column

```php
add_filter('fp_newspaper_admin_columns', function($columns) {
    $columns['custom_col'] = 'Mia Colonna';
    return $columns;
});

add_action('manage_fp_article_posts_custom_column', function($column, $post_id) {
    if ($column === 'custom_col') {
        echo get_post_meta($post_id, '_custom_meta', true);
    }
}, 10, 2);
```

### Esempio: Notifica a 1000 Views

```php
add_action('fp_newspaper_view_incremented', function($post_id, $views) {
    if ($views === 1000) {
        $post = get_post($post_id);
        $author_email = get_the_author_meta('user_email', $post->post_author);
        
        wp_mail(
            $author_email,
            'Congratulazioni! 1000 visualizzazioni!',
            "Il tuo articolo '{$post->post_title}' ha raggiunto 1000 visualizzazioni!"
        );
    }
}, 10, 2);
```

### Esempio: Custom Bulk Action

```php
add_filter('fp_newspaper_bulk_actions', function($actions) {
    $actions['custom_action'] = 'Mia Azione Custom';
    return $actions;
});
```

---

## 🧪 Testing

### PHPUnit (setup suggerito)

```php
class ArticleTest extends WP_UnitTestCase {
    public function test_article_creation() {
        $post_id = $this->factory->post->create([
            'post_type' => 'fp_article'
        ]);
        
        $this->assertNotEmpty($post_id);
        $this->assertEquals('fp_article', get_post_type($post_id));
    }
}
```

### Test REST API

```bash
# Test endpoint stats
curl -u admin:password http://sito.local/wp-json/fp-newspaper/v1/stats

# Test health check
curl -u admin:password http://sito.local/wp-json/fp-newspaper/v1/health

# Test increment views
curl -X POST http://sito.local/wp-json/fp-newspaper/v1/articles/123/view
```

---

## 🔧 Troubleshooting

### Problema: Cache non funziona

```php
// Verifica object cache
if (wp_using_ext_object_cache()) {
    echo 'Object cache attivo';
} else {
    echo 'Solo database transients';
}

// Pulisci cache manualmente
wp fp-newspaper cache-clear
```

### Problema: Rate limiting troppo aggressivo

```php
add_filter('fp_newspaper_rate_limit_duration', function($seconds) {
    return 10; // Ridotto a 10 secondi
});
```

### Problema: Database lento

```bash
# Ottimizza tabella
wp fp-newspaper optimize

# Pulisci statistiche vecchie
wp fp-newspaper cleanup --days=90
```

---

## 📚 Resources

- **Main Documentation:** README.md
- **Security Policy:** SECURITY.md
- **Audit Reports:** *-AUDIT-REPORT.md files
- **Hooks Reference:** src/Hooks.php

---

## 🤝 Contributing

Il plugin è progettato per essere esteso. Usa hooks e filtri invece di modificare il core.

---

**Happy coding!** 🚀

Francesco Passeri  
info@francescopasseri.com

