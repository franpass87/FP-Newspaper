# 📊 FP Newspaper - UX Metrics Tracking

**Versione**: 1.0.0  
**Data**: 3 Novembre 2025  
**Status**: Implementato - Requires Activation

---

## 🎯 OVERVIEW

Sistema di tracking delle **Core Web Vitals** e metriche UX personalizzate per monitorare le performance dell'esperienza utente.

### Metriche Traciate

#### Core Web Vitals (Google)
- ✅ **LCP** (Largest Contentful Paint) - Velocità caricamento visivo
- ✅ **FID** (First Input Delay) - Reattività interazione
- ✅ **CLS** (Cumulative Layout Shift) - Stabilità visiva

#### Additional Metrics
- ✅ **FCP** (First Contentful Paint)
- ✅ **TTFB** (Time to First Byte)
- ✅ **DOM Content Loaded**
- ✅ **Window Load Time**
- ✅ **Time to First Interaction**
- ✅ **Resource Loading** (CSS, JS, Images)
- ✅ **JavaScript Errors**

---

## 🚀 ATTIVAZIONE

### 1. Enqueue Script

Aggiungi a `src/Assets.php`:

```php
/**
 * Enqueue UX metrics tracking (optional)
 */
public function enqueue_ux_metrics() {
    // Only on frontend
    if (is_admin()) {
        return;
    }
    
    // Check if tracking is enabled
    if (!get_option('fp_newspaper_track_ux_metrics', false)) {
        return;
    }
    
    wp_enqueue_script(
        'fp-newspaper-ux-metrics',
        FP_NEWSPAPER_URL . 'assets/js/ux-metrics.js',
        [],
        FP_NEWSPAPER_VERSION,
        true // In footer
    );
    
    // Localize config
    wp_localize_script('fp-newspaper-ux-metrics', 'fpNewsConfig', [
        'trackMetrics' => true,
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('fp_ux_metrics'),
    ]);
}
```

Aggiungi hook nel costruttore:

```php
add_action('wp_enqueue_scripts', [$this, 'enqueue_ux_metrics'], 20);
```

### 2. AJAX Handler (Optional - Store in DB)

Aggiungi a `src/Plugin.php` o crea nuova classe `src/Analytics.php`:

```php
/**
 * Handle UX metrics tracking
 */
public function handle_ux_metric() {
    // Security check
    check_ajax_referer('fp_ux_metrics', 'nonce');
    
    // Get data
    $metric_name = sanitize_text_field($_POST['metric_name'] ?? '');
    $metric_value = absint($_POST['metric_value'] ?? 0);
    $metric_rating = sanitize_text_field($_POST['metric_rating'] ?? 'neutral');
    $page_url = esc_url_raw($_POST['page_url'] ?? '');
    $user_agent = sanitize_text_field($_POST['user_agent'] ?? '');
    
    // Store in custom table or post meta
    global $wpdb;
    
    $wpdb->insert(
        $wpdb->prefix . 'fp_ux_metrics',
        [
            'metric_name' => $metric_name,
            'metric_value' => $metric_value,
            'metric_rating' => $metric_rating,
            'page_url' => $page_url,
            'user_agent' => $user_agent,
            'tracked_at' => current_time('mysql'),
        ],
        ['%s', '%d', '%s', '%s', '%s', '%s']
    );
    
    wp_send_json_success(['tracked' => true]);
}

// Register action
add_action('wp_ajax_fp_track_ux_metric', [$this, 'handle_ux_metric']);
add_action('wp_ajax_nopriv_fp_track_ux_metric', [$this, 'handle_ux_metric']);
```

### 3. Create Database Table

Aggiungi in `src/Activation.php`:

```php
/**
 * Create UX metrics table
 */
private function create_ux_metrics_table() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'fp_ux_metrics';
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        metric_name varchar(50) NOT NULL,
        metric_value int(11) NOT NULL,
        metric_rating varchar(20) DEFAULT 'neutral',
        page_url varchar(500) DEFAULT '',
        user_agent varchar(500) DEFAULT '',
        tracked_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY metric_name (metric_name),
        KEY metric_rating (metric_rating),
        KEY tracked_at (tracked_at)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
```

---

## 📊 INTEGRAZIONE GOOGLE ANALYTICS

### Google Analytics 4 (GA4)

Il tracking è automatico se GA4 è installato:

```html
<!-- GA4 già configurato sul sito -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-XXXXXXXXXX"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', 'G-XXXXXXXXXX');
</script>

<!-- FP UX Metrics invierà automaticamente eventi -->
```

Eventi inviati:
- `LCP` - event_category: 'Web Vitals'
- `FID` - event_category: 'Web Vitals'
- `CLS` - event_category: 'Web Vitals'
- `FCP` - event_category: 'Web Vitals'
- `TTFB` - event_category: 'Web Vitals'

### Visualizzare in GA4

1. Vai su **Reports** → **Engagement** → **Events**
2. Filtra per category: `Web Vitals`
3. Crea **Custom Report** per dashboard metriche

---

## 📈 DASHBOARD METRICHE ADMIN

### Aggiungere Widget Dashboard

Crea `src/Admin/UXMetricsDashboard.php`:

```php
<?php
namespace FPNewspaper\Admin;

class UXMetricsDashboard {
    
    public function __construct() {
        add_action('admin_menu', [$this, 'add_menu_page']);
    }
    
    public function add_menu_page() {
        add_submenu_page(
            'fp-editorial-dashboard',
            __('UX Metrics', 'fp-newspaper'),
            __('📊 UX Metrics', 'fp-newspaper'),
            'manage_options',
            'fp-ux-metrics',
            [$this, 'render_page']
        );
    }
    
    public function render_page() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'fp_ux_metrics';
        
        // Last 30 days average
        $metrics = $wpdb->get_results("
            SELECT 
                metric_name,
                AVG(metric_value) as avg_value,
                COUNT(*) as count,
                SUM(CASE WHEN metric_rating = 'good' THEN 1 ELSE 0 END) as good_count,
                SUM(CASE WHEN metric_rating = 'needs-improvement' THEN 1 ELSE 0 END) as needs_improvement_count,
                SUM(CASE WHEN metric_rating = 'poor' THEN 1 ELSE 0 END) as poor_count
            FROM $table
            WHERE tracked_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY metric_name
            ORDER BY metric_name
        ");
        
        ?>
        <div class="wrap">
            <h1><?php _e('📊 UX Metrics Dashboard', 'fp-newspaper'); ?></h1>
            
            <div class="fp-ux-metrics-grid">
                <?php foreach ($metrics as $metric): ?>
                    <div class="fp-ux-metric-card">
                        <h3><?php echo esc_html($metric->metric_name); ?></h3>
                        <div class="fp-metric-value">
                            <?php echo number_format($metric->avg_value, 0); ?> ms
                        </div>
                        <div class="fp-metric-breakdown">
                            <span class="good"><?php echo number_format(($metric->good_count / $metric->count) * 100, 1); ?>% Good</span>
                            <span class="needs-improvement"><?php echo number_format(($metric->needs_improvement_count / $metric->count) * 100, 1); ?>% Needs Improvement</span>
                            <span class="poor"><?php echo number_format(($metric->poor_count / $metric->count) * 100, 1); ?>% Poor</span>
                        </div>
                        <div class="fp-metric-total">
                            <?php printf(__('%s samples', 'fp-newspaper'), number_format_i18n($metric->count)); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Chart: Trend over time -->
            <div class="fp-card">
                <div class="fp-card-header">
                    <h3><?php _e('Trend (30 giorni)', 'fp-newspaper'); ?></h3>
                </div>
                <div class="fp-card-body">
                    <canvas id="fp-ux-trend-chart" style="max-height: 400px;"></canvas>
                </div>
            </div>
        </div>
        
        <style>
            .fp-ux-metrics-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 20px;
                margin: 30px 0;
            }
            .fp-ux-metric-card {
                background: white;
                padding: 20px;
                border: 1px solid #ccc;
                border-radius: 8px;
            }
            .fp-metric-value {
                font-size: 36px;
                font-weight: bold;
                color: #2271b1;
                margin: 10px 0;
            }
            .fp-metric-breakdown {
                font-size: 12px;
                margin: 10px 0;
            }
            .fp-metric-breakdown span {
                display: block;
                padding: 4px 0;
            }
            .fp-metric-breakdown .good { color: #10b981; }
            .fp-metric-breakdown .needs-improvement { color: #f59e0b; }
            .fp-metric-breakdown .poor { color: #ef4444; }
        </style>
        <?php
    }
}
```

---

## 🎯 THRESHOLDS (Google)

### Core Web Vitals

| Metric | Good | Needs Improvement | Poor |
|--------|------|-------------------|------|
| **LCP** | ≤ 2.5s | 2.5s - 4.0s | > 4.0s |
| **FID** | ≤ 100ms | 100ms - 300ms | > 300ms |
| **CLS** | ≤ 0.1 | 0.1 - 0.25 | > 0.25 |
| **FCP** | ≤ 1.8s | 1.8s - 3.0s | > 3.0s |
| **TTFB** | ≤ 800ms | 800ms - 1800ms | > 1800ms |

---

## 🔧 PERSONALIZZAZIONE

### Disabilitare Specifiche Metriche

Modifica `ux-metrics.js`:

```javascript
trackCoreWebVitals() {
    this.trackLCP();
    // this.trackFID(); // Disabilitato
    this.trackCLS();
    this.trackFCP();
    // this.trackTTFB(); // Disabilitato
}
```

### Aggiungere Metriche Custom

```javascript
// In trackCustomMetrics()
this.trackButtonClicks();

trackButtonClicks() {
    document.querySelectorAll('.important-button').forEach(btn => {
        btn.addEventListener('click', () => {
            this.sendMetric('ImportantButtonClick', performance.now());
        });
    });
}
```

### Filtrare per Pagina

```javascript
// Solo su singoli post
if (document.body.classList.contains('single-post')) {
    this.init();
}
```

---

## 📊 ANALISI DATI

### Query Utili

```sql
-- Media metriche per pagina
SELECT 
    page_url,
    AVG(CASE WHEN metric_name = 'LCP' THEN metric_value END) as avg_lcp,
    AVG(CASE WHEN metric_name = 'FID' THEN metric_value END) as avg_fid,
    AVG(CASE WHEN metric_name = 'CLS' THEN metric_value * 1000 END) / 1000 as avg_cls,
    COUNT(*) as samples
FROM wp_fp_ux_metrics
WHERE tracked_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY page_url
ORDER BY avg_lcp DESC
LIMIT 20;

-- Trend giornaliero
SELECT 
    DATE(tracked_at) as date,
    metric_name,
    AVG(metric_value) as avg_value,
    COUNT(*) as count
FROM wp_fp_ux_metrics
WHERE tracked_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY DATE(tracked_at), metric_name
ORDER BY date DESC, metric_name;

-- Performance per device
SELECT 
    CASE 
        WHEN user_agent LIKE '%Mobile%' THEN 'Mobile'
        WHEN user_agent LIKE '%Tablet%' THEN 'Tablet'
        ELSE 'Desktop'
    END as device_type,
    AVG(CASE WHEN metric_name = 'LCP' THEN metric_value END) as avg_lcp,
    COUNT(*) as samples
FROM wp_fp_ux_metrics
WHERE tracked_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY device_type;
```

---

## 🚀 OTTIMIZZAZIONI CONSIGLIATE

### Basate sui Risultati

#### LCP Alto (> 2.5s)
- ✅ Ottimizza immagini (WebP, lazy load)
- ✅ Usa CDN
- ✅ Preload risorse critiche
- ✅ Riduci CSS/JS render-blocking

#### FID Alto (> 100ms)
- ✅ Riduci JavaScript main thread
- ✅ Code splitting
- ✅ Web Workers per task pesanti
- ✅ Debounce event handlers

#### CLS Alto (> 0.1)
- ✅ Specifica dimensioni immagini
- ✅ Evita inserimenti DOM dinamici
- ✅ Usa `font-display: swap`
- ✅ Reserve space per ads/widgets

---

## 🔒 PRIVACY & GDPR

### Compliance

- ✅ Nessun dato personale tracciato
- ✅ Solo metriche anonime performance
- ✅ User Agent sanitizzato
- ✅ Opt-out disponibile

### Opt-Out

Aggiungi impostazione in Settings:

```php
add_settings_field(
    'fp_newspaper_track_ux_metrics',
    __('Track UX Metrics', 'fp-newspaper'),
    function() {
        $value = get_option('fp_newspaper_track_ux_metrics', false);
        ?>
        <label>
            <input type="checkbox" name="fp_newspaper_track_ux_metrics" value="1" <?php checked($value, true); ?>>
            <?php _e('Enable UX metrics tracking (anonymous performance data)', 'fp-newspaper'); ?>
        </label>
        <?php
    },
    'fp_newspaper_settings',
    'fp_newspaper_analytics'
);
```

---

## 📚 RISORSE

### Documentation
- [Web Vitals](https://web.dev/vitals/)
- [Core Web Vitals](https://web.dev/vitals-tools/)
- [Lighthouse](https://developers.google.com/web/tools/lighthouse)
- [Chrome DevTools Performance](https://developer.chrome.com/docs/devtools/performance/)

### Tools
- [PageSpeed Insights](https://pagespeed.web.dev/)
- [WebPageTest](https://www.webpagetest.org/)
- [Chrome User Experience Report](https://developers.google.com/web/tools/chrome-user-experience-report)

---

## ✅ CHECKLIST ATTIVAZIONE

- [ ] Enqueue script in Assets.php
- [ ] Creare tabella database
- [ ] Implementare AJAX handler
- [ ] Testare invio metriche
- [ ] Verificare ricezione in GA4
- [ ] Creare dashboard admin
- [ ] Documentare per team
- [ ] Monitorare per 30 giorni
- [ ] Analizzare risultati
- [ ] Implementare ottimizzazioni

---

**Status**: Implementato e pronto all'uso 🚀  
**Effort**: 1-2 ore setup completo  
**Impact**: Visibilità completa performance UX

**Next**: Attiva tracking e monitora per identificare aree di ottimizzazione!

