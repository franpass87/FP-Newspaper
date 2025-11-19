# 📊 Guida Editorial Dashboard - FP Newspaper v1.4.0

Centro di controllo completo per la redazione con metriche real-time, grafici e monitoring.

---

## 🎯 Accesso Dashboard

### Dashboard Principale

```
WordPress Admin → 📊 Editorial (menu principale)
```

**Oppure**:
```
URL diretta: /wp-admin/admin.php?page=fp-editorial-dashboard
```

### Widget Dashboard WordPress

```
WordPress Admin → Dashboard (home)
```

Vedrai 3 nuovi widget:
- 📊 Statistiche Editoriali
- 🎯 I Miei Articoli
- 🔔 Attività Recente

---

## 📊 COMPONENTI DASHBOARD

### 1. Overview Stats (Cards in Alto)

**Mostra:**
- 📝 **Pubblicati Oggi** - Articoli live oggi
- 📅 **Questa Settimana** - Ultimi 7 giorni
- 📊 **Questo Mese** - Ultimi 30 giorni
- ⏱️ **Media Giornaliera** - Throughput redazione

**Colori:**
- Blu = Oggi
- Verde = Settimana
- Azzurro = Mese
- Arancione = Media

---

### 2. Grafico Trend Pubblicazioni

**Caratteristiche:**
- 📈 Linea ultimi 30 giorni
- Interattivo (tooltip on hover)
- Smooth animation
- Responsive

**Usa:**
- Identificare picchi/cali produzione
- Trend stagionali
- Pattern settimanali

---

### 3. Pipeline Editoriale

**Flow Visuale:**
```
[Bozze] → [In Revisione] → [Approvati] → [Programmati]
   12          5               8             15
```

**Metriche Extra:**
- Pubblicati (30gg)
- Tempo medio pubblicazione (ore)

**Usa:**
- Monitorare bottleneck
- Identificare blocchi workflow
- Bilanciare carico lavoro

---

### 4. Activity Feed

**Mostra:**
- Ultime 10 azioni redazione
- Chi ha fatto cosa
- Quanto tempo fa
- Link diretto all'articolo

**Esempio:**
```
3 minuti fa
Mario Rossi ha inviato in revisione: "Nuova legge sul clima"
[In Revisione]

1 ora fa
Laura Bianchi è stato approvato: "Intervista al Sindaco"
[Approvato]
```

**Auto-refresh:** Ogni 5 minuti

---

### 5. Trending Articles (48h)

**Algoritmo:**
```
Velocity = Views / Ore dalla pubblicazione
```

**Mostra:**
- Rank (#1, #2, #3...)
- Titolo articolo
- Views totali
- Velocity (views/ora)

**Usa:**
- Identificare successi
- Replicare format vincenti
- Content strategy data-driven

---

### 6. Performance Team

**Tabella Autori (30 giorni):**
| Autore | Pubblicati | In Revisione | Bozze | Totale |
|--------|------------|--------------|-------|--------|
| Mario Rossi | **15** | 2 | 3 | 20 |
| Laura Bianchi | **12** | 1 | 2 | 15 |

**Usa:**
- Identificare top performers
- Bilanciare carico
- Gamification team

---

### 7. Prossime Pubblicazioni (7 giorni)

**Mostra:**
- Data e ora pubblicazione
- Titolo articolo
- Autore

**Esempio:**
```
┌──────┬─────────────────────────────┐
│ 15   │ "Breaking: Nuova scoperta"  │
│ Nov  │ Mario Rossi                 │
│ 08:00│                             │
└──────┴─────────────────────────────┘
```

---

### 8. Sistema Alert

**Tipi di Alert:**

🔴 **Deadline Scadute**
```
❌ 3 articoli in ritardo!
[Visualizza]
```

🟡 **Molti in Attesa**
```
⚠️ 12 articoli in attesa di revisione
[Revisiona]
```

🔵 **Backlog Alto**
```
ℹ️ Backlog alto: 52 articoli in lavorazione
[Gestisci]
```

---

### 9. Quick Actions

Pulsanti rapidi:
- **Nuovo Articolo** (blu)
- **Workflow** (arancione)
- **Calendario** (verde)
- **Tutti gli Articoli** (grigio)

---

## 🎛️ WIDGET DASHBOARD WORDPRESS

### Widget 1: Statistiche Editoriali

**Posizione:** WordPress Dashboard (home)

**Mostra:**
```
┌─────────────┬─────────────┐
│ Pubblicati  │    Questa   │
│    Oggi     │  Settimana  │
│     5       │     28      │
└─────────────┴─────────────┘

Pipeline:
● 12 bozze
● 5 in revisione
● 8 approvati
● 15 programmati

[Dashboard Completa →]
```

### Widget 2: I Miei Articoli

**Mostra:**
```
⏳ "Intervista Sindaco"
   2 ore fa

✏️ "Rapporto economia Q3"
   1 giorno fa

✅ "Breaking: Nuova legge"
   3 giorni fa

Vedi tutti (8) →
```

### Widget 3: Attività Recente

**Mostra:**
```
5 min fa
Mario Rossi ha inviato in revisione:
"Nuova scoperta scientifica"

1 ora fa
Laura Bianchi è stato pubblicato:
"Intervista esclusiva"
```

---

## 📊 API & PROGRAMMAZIONE

### Ottenere Dati Dashboard

```php
use FPNewspaper\Editorial\Dashboard;

$dashboard = new Dashboard();

// Tutti i dati (cached 5 min)
$all_data = $dashboard->get_dashboard_data();

// Solo overview
$overview = $dashboard->get_overview_stats();
// Ritorna: ['published_today', 'published_week', 'published_month', 'avg_per_day', 'drafts']

// Pipeline stats
$pipeline = $dashboard->get_pipeline_stats();
// Ritorna: ['in_review', 'needs_changes', 'approved', 'scheduled', 'drafts']

// Team performance
$team = $dashboard->get_team_performance();
// Ritorna: ['top_authors', 'avg_time_to_publish', 'by_status']

// Activity feed
$activity = $dashboard->get_recent_activity(10);

// Trending
$trending = $dashboard->get_trending_articles(5);

// Upcoming publications
$upcoming = $dashboard->get_upcoming_publications();

// Alert
$alerts = $dashboard->get_alerts();
```

### Dati Grafici

```php
// Dati per Chart.js (30 giorni)
$chart_data = $dashboard->get_chart_data(30);
// Ritorna: ['labels' => [...], 'datasets' => [...]]

// Per 7 giorni
$chart_data_week = $dashboard->get_chart_data(7);
```

### Statistiche Autori

```php
// Top 10 autori
$authors = $dashboard->get_author_stats(10);

foreach ($authors as $author) {
    echo "{$author->display_name}: {$author->published} articoli\n";
}
```

### Metriche Produttività

```php
// Ultimo mese
$prod = $dashboard->get_productivity_metrics('month');
// Ritorna: ['total_articles', 'published', 'in_review', 'approved', 'avg_time_hours']

// Ultima settimana
$prod_week = $dashboard->get_productivity_metrics('week');
```

---

## 🔧 PERSONALIZZAZIONE

### Modificare Cache TTL

```php
// Nel functions.php del tema
add_filter('fp_dashboard_cache_ttl', function() {
    return 600; // 10 minuti invece di 5
});
```

### Aggiungere Alert Custom

```php
add_filter('fp_dashboard_alerts', function($alerts) {
    // Aggiungi alert custom
    if (my_custom_condition()) {
        $alerts[] = [
            'type' => 'warning',
            'icon' => 'flag',
            'message' => 'Il mio alert custom',
            'action_text' => 'Vai',
            'action_link' => admin_url('edit.php'),
        ];
    }
    
    return $alerts;
});
```

### Modificare Numero Trending

```php
// Nel EditorialDashboardPage
// Cambia da 5 a 10:
$data['trending'] = $this->dashboard->get_trending_articles(10);
```

---

## 📈 METRICHE E KPI

### KPI Principali

| Metrica | Target Consigliato | Come Migliorare |
|---------|-------------------|-----------------|
| **Pubblicati/giorno** | 3-5 | Assumere redattori, pianificare meglio |
| **Tempo pubblicazione** | <24h | Workflow più veloce, meno revisioni |
| **Backlog** | <30 | Pubblicare più spesso, archiviare vecchi |
| **Tasso approvazione** | >80% | Training redattori, linee guida chiare |

### Interpretare le Metriche

**Pipeline bilanciata:**
```
Bozze: 10-20
In Revisione: 5-10  ← Deve fluire veloce
Approvati: 5-15
Programmati: 10-30
```

**Pipeline con bottleneck:**
```
Bozze: 5
In Revisione: 25  ← PROBLEMA: Troppi in attesa
Approvati: 2
Programmati: 5
```

**Azione**: Assumere più editor, velocizzare revisioni

---

## 🎯 CASI D'USO

### Caso 1: Monitoraggio Quotidiano (Caporedattore)

```
1. Login WordPress
2. Vedi widget "Statistiche Editoriali" in home
3. Check pubblicati oggi vs target
4. Se sotto target → Vai a Calendario
5. Programma articoli approvati
```

### Caso 2: Revisione Settimanale (Manager)

```
1. Apri Dashboard Completa
2. Analizza grafico trend 30 giorni
3. Identifica picchi/cali
4. Check performance team
5. Identifica top/bottom performers
6. Planning reunion con dati concreti
```

### Caso 3: Daily Standup (Team)

```
1. Apri Workflow page
2. Check "Assegnati a Me"
3. Check "Deadline Imminenti"
4. Prioritize lavoro giornata
5. Update team su Slack/Email
```

---

## 🔍 TROUBLESHOOTING

### Dashboard non carica dati

**Verifica:**
```php
// Check cache
var_dump(CacheManager::get_stats());

// Flush e riprova
CacheManager::delete('editorial_dashboard_data');
```

### Grafico non appare

**Verifica:**
1. Console JavaScript (F12)
2. Errore caricamento Chart.js?
3. Check `fpDashboardData` è definito

**Fix:**
```bash
# Flush cache browser
Ctrl + F5
```

### Widget non appaiono

**Verifica:**
```
Dashboard → Screen Options (in alto) →
✅ Spunta widget FP Newspaper
```

### Statistiche errate

**Fix:**
```bash
# Flush cache
wp cache flush

# Rigenera stats
wp fp-newspaper optimize
```

---

## 🚀 OTTIMIZZAZIONI

### Performance Query

Tutte le query dashboard usano:
- ✅ Prepared statements
- ✅ INDEX sui campi ordinati
- ✅ LIMIT per evitare full table scan
- ✅ Cache 5 minuti

### Ridurre Load

```php
// Aumenta cache TTL
add_filter('fp_dashboard_cache_ttl', function() {
    return 900; // 15 minuti
});

// Disabilita auto-refresh
// Commenta nel file EditorialDashboardPage.php:
// setInterval(..., 300000);
```

---

## 📚 ESEMPI AVANZATI

### Export Metriche PDF (Futuro)

```php
// Hook per generare report PDF
add_action('admin_init', function() {
    if (isset($_GET['fp_export_dashboard_pdf'])) {
        $dashboard = new FPNewspaper\Editorial\Dashboard();
        $data = $dashboard->get_dashboard_data();
        
        // Usa TCPDF/mPDF per generare PDF
        // ...
    }
});
```

### Slack Integration

```php
// Invia daily stats su Slack
add_action('wp', function() {
    if (!wp_next_scheduled('fp_daily_slack_report')) {
        wp_schedule_event(strtotime('08:00'), 'daily', 'fp_daily_slack_report');
    }
});

add_action('fp_daily_slack_report', function() {
    $dashboard = new FPNewspaper\Editorial\Dashboard();
    $stats = $dashboard->get_quick_stats();
    
    $message = sprintf(
        "📊 Report Giornaliero:\n" .
        "✅ Pubblicati oggi: %d\n" .
        "📝 In pipeline: %d\n" .
        "🎯 Media: %.1f/giorno",
        $stats['published_today'],
        $stats['pipeline_total'],
        $stats['avg_per_day']
    );
    
    wp_remote_post('https://hooks.slack.com/services/YOUR/WEBHOOK/URL', [
        'body' => json_encode(['text' => $message])
    ]);
});
```

### Custom Metrics

```php
// Aggiungi metriche custom
add_filter('fp_dashboard_overview_stats', function($stats) {
    global $wpdb;
    
    // Aggiungi metric custom
    $stats['with_featured_image'] = (int) $wpdb->get_var("
        SELECT COUNT(*) FROM {$wpdb->posts} p
        INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
        WHERE p.post_type = 'post'
        AND p.post_status = 'publish'
        AND pm.meta_key = '_thumbnail_id'
        AND DATE(p.post_date) >= CURDATE() - INTERVAL 30 DAY
    ");
    
    return $stats;
});
```

---

## 🎨 PERSONALIZZAZIONE UI

### Modificare Colori

Aggiungi CSS custom in `functions.php`:

```php
add_action('admin_head', function() {
    if (get_current_screen()->id !== 'toplevel_page_fp-editorial-dashboard') {
        return;
    }
    ?>
    <style>
        .fp-card-primary { border-color: #your-color !important; }
        .fp-stat-value { color: #your-color !important; }
    </style>
    <?php
});
```

### Aggiungere Sezioni

```php
// Dopo "Prossime Pubblicazioni", aggiungi:
add_action('fp_dashboard_after_upcoming', function($data) {
    ?>
    <div class="fp-dashboard-row">
        <div class="fp-custom-section">
            <h3>La Mia Sezione Custom</h3>
            <!-- Tuo contenuto -->
        </div>
    </div>
    <?php
});
```

---

## 📊 METRICHE DISPONIBILI

### Via API

```php
$dashboard = new FPNewspaper\Editorial\Dashboard();

// Overview (performance generale)
$overview = $dashboard->get_overview_stats();

// Team (autori performance)
$team = $dashboard->get_team_performance();

// Pipeline (workflow stato)
$pipeline = $dashboard->get_pipeline_stats();

// Activity (azioni recenti)
$activity = $dashboard->get_recent_activity(20);

// Trending (articoli hot)
$trending = $dashboard->get_trending_articles(10);

// Deadlines (scadenze)
$deadlines = $dashboard->get_upcoming_deadlines(14);

// Alerts (warning/errori)
$alerts = $dashboard->get_alerts();

// Backlog (articoli in lavorazione)
$backlog = $dashboard->get_backlog_count();

// Upcoming (prossime pubblicazioni)
$upcoming = $dashboard->get_upcoming_publications();

// Productivity (metriche efficienza)
$prod = $dashboard->get_productivity_metrics('month');

// Chart data (per grafici)
$chart = $dashboard->get_chart_data(30);

// Author stats (top autori)
$authors = $dashboard->get_author_stats(20);
```

---

## 🎯 BEST PRACTICES

### Per Caporedattori

1. ✅ Check dashboard **ogni mattina**
2. ✅ Monitor alert e agisci subito
3. ✅ Analizza trend settimanalmente
4. ✅ Usa dati per planning reunion
5. ✅ Identifica bottleneck e risolvi

### Per Editor

1. ✅ Check "I Miei Articoli" daily
2. ✅ Prioritize articoli con deadline
3. ✅ Monitor backlog in revisione
4. ✅ Target: <24h per revisione

### Per Redattori

1. ✅ Check assegnazioni personali
2. ✅ Rispetta deadline
3. ✅ Monitor trending per ispirazione
4. ✅ Confronta performance con team (gamification)

---

## 📱 ACCESSIBILITÀ

### Mobile/Tablet

Dashboard responsive ma **ottimizzata per desktop**.

Per mobile: Usa widget WordPress Dashboard (più compatti).

### Keyboard Navigation

- `Tab` = Naviga tra sezioni
- `Enter` = Apri link
- `Ctrl+R` = Refresh

---

## 🔗 INTEGRAZIONE TERZE PARTI

### Google Data Studio

```php
// Esponi metriche via REST API
add_action('rest_api_init', function() {
    register_rest_route('fp-newspaper/v1', '/dashboard-metrics', [
        'methods' => 'GET',
        'callback' => function() {
            $dashboard = new FPNewspaper\Editorial\Dashboard();
            return $dashboard->get_dashboard_data();
        },
        'permission_callback' => function() {
            return current_user_can('manage_options');
        }
    ]);
});

// Importa in Google Data Studio via URL:
// https://tuosito.com/wp-json/fp-newspaper/v1/dashboard-metrics
```

### Tableau

Esporta dati per Tableau:

```php
// CSV export
add_action('admin_init', function() {
    if (isset($_GET['fp_export_metrics_csv'])) {
        $dashboard = new FPNewspaper\Editorial\Dashboard();
        $authors = $dashboard->get_author_stats(100);
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="metrics.csv"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Author', 'Published', 'Drafts', 'Total']);
        
        foreach ($authors as $author) {
            fputcsv($output, [
                $author->display_name,
                $author->published,
                $author->drafts,
                $author->total_articles
            ]);
        }
        
        fclose($output);
        exit;
    }
});
```

---

## 🎉 CONCLUSIONE

**Editorial Dashboard v1.4.0** fornisce:

✅ **Visibilità completa** redazione  
✅ **Metriche real-time** per decisioni data-driven  
✅ **Monitoring** performance team  
✅ **Alert proattivi** per problemi  
✅ **Grafici interattivi** per trend analysis  
✅ **Quick actions** per produttività  

**Target Users:**
- Caporedattori (monitoring generale)
- Editor (assegnazioni e review)
- Manager (metriche e KPI)
- Team (visibilità stato lavori)

---

**Versione Documento**: 1.0  
**Data**: 2025-11-01  
**Valido per**: FP Newspaper v1.4.0


