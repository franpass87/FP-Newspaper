# Cronaca di Viterbo v1.5.0 🎉

**Plugin WordPress Completo per Giornalismo Locale Partecipativo**

[![Version](https://img.shields.io/badge/version-1.5.0-blue.svg)](CHANGELOG-v1.2-1.5.md)
[![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-blue.svg)](https://wordpress.org)
[![PHP](https://img.shields.io/badge/PHP-8.0%2B-purple.svg)](https://php.net)

---

## 🚀 Novità v1.5.0

### Funzionalità Implementate

#### 🏛️ Risposta Amministrazione
- Risposte ufficiali dell'amministrazione comunale
- Stati: In Valutazione, Accettata, Respinta, In Corso, Completata
- Budget, timeline, delibere, referenti

#### ✍️ Petizioni Digitali
- Raccolta firme online con soglia obiettivo
- Barra progresso real-time
- Notifiche milestone automatiche

#### 📊 Sondaggi & Consultazioni
- Selezione singola/multipla
- Risultati in tempo reale
- Grafici interattivi

#### 🏅 Sistema Reputazione
- 4 livelli utente (Cittadino → Ambasciatore)
- 8 badge achievements
- Punti automatici per azioni

#### 👥 Profili Pubblici
- Statistiche utente
- Badge ottenuti/da sbloccare
- Proposte recenti

#### 📧 Notifiche Email
- Alert automatici
- Digest settimanale
- Template personalizzabili

#### 📊 Dashboard Trasparenza
- 6 statistiche chiave
- Grafici interattivi
- Tempo risposta amministrazione

#### 🗺️ Mappe Geolocalizzate
- Leaflet maps interattive
- Marker per proposte/eventi/petizioni
- Filtri quartiere/tematica

#### ⚖️ Votazione Ponderata
- Peso voto variabile (1.0x - 6.0x)
- Bonus residenza quartiere
- Tracking dettagliato voti

---

## 📦 Installazione Rapida

### 1. Requisiti
- WordPress 6.0+
- PHP 8.0+
- MySQL 5.7+ / MariaDB 10.3+

### 2. Upload Plugin
```bash
cd wp-content/plugins
git clone [repo-url] cronaca-di-viterbo
cd cronaca-di-viterbo
```

### 3. Attivazione
```bash
# Via WP-CLI
wp plugin activate cronaca-di-viterbo

# Via Admin WordPress
Plugin > Plugin installati > Cronaca di Viterbo > Attiva
```

### 4. Setup Iniziale
1. Vai su **Quartieri** → Crea quartieri città
2. Vai su **Tematiche** → Crea tematiche discussione
3. Vai su **Moderazione > Impostazioni** → Configura GA4/Schema
4. Vai su **Dashboard CdV** → Verifica statistiche

---

## 🎯 Shortcodes Completi

### Proposte
```php
[cdv_proposta_form title="Invia la tua idea"]
[cdv_proposte limit="10" quartiere="centro" orderby="date"]
```

### Petizioni
```php
[cdv_petizione_form id="123"]
[cdv_petizioni limit="10" status="aperte" orderby="firme"]
```

### Sondaggi
```php
[cdv_sondaggio_form id="123"]
```

### Eventi
```php
[cdv_eventi limit="6" upcoming="yes" quartiere="centro"]
```

### Dossier
```php
[cdv_dossier_hero]
```

### Profilo Utente
```php
[cdv_user_profile user_id="123"]
[cdv_user_profile] // utente corrente
```

### Dashboard
```php
[cdv_dashboard periodo="30"]
```

### Mappa
```php
[cdv_mappa tipo="proposte" quartiere="centro" height="600px"]
[cdv_mappa tipo="eventi" center="42.4175,12.1089" zoom="13"]
[cdv_mappa tipo="tutti"]
```

---

## 🗄️ Database Schema

### Tabelle Create Automaticamente

```sql
-- Firme Petizioni
wp_cdv_petizioni_firme (9 campi + indici)

-- Voti Sondaggi
wp_cdv_sondaggi_voti (7 campi + indici)

-- Voti Dettagliati (Ponderati)
wp_cdv_voti_dettagli (10 campi + indici)

-- Subscribers
wp_cdv_subscribers (5 campi + indici)
```

### Meta Keys Principali

```php
// Risposta Amministrazione
_cdv_proposta_id, _cdv_status, _cdv_budget, _cdv_timeline

// Petizione
_cdv_soglia_firme, _cdv_firme_count, _cdv_deadline

// Sondaggio
_cdv_options, _cdv_scadenza, _cdv_multiplo

// Votazione
_cdv_weighted_votes

// Mappa
_cdv_latitudine, _cdv_longitudine

// User
cdv_points, cdv_level, cdv_badges
```

---

## 🪝 Hooks Disponibili

### Actions
```php
// Notifiche
do_action('cdv_risposta_pubblicata', $risposta_id, $proposta_id);
do_action('cdv_petizione_milestone', $petizione_id, $firme);
do_action('cdv_petizione_firmata', $petizione_id, $email, $user_id);
do_action('cdv_sondaggio_votato', $sondaggio_id, $options, $user_id);

// Reputazione
do_action('cdv_points_added', $user_id, $points, $total);
do_action('cdv_badge_awarded', $user_id, $badge);
do_action('cdv_level_up', $user_id, $new_level, $old_level);

// Votazione
do_action('cdv_after_vote', $proposta_id, $user_id, $weight);

// Cron
do_action('cdv_weekly_digest'); // Lunedì 9:00
```

### Filters
```php
// Votazione ponderata
apply_filters('cdv_vote_weight', 1.0, $user_id, $proposta_id);
apply_filters('cdv_final_vote_weight', $weight, $user_id, $proposta_id);
```

---

## ⚙️ Configurazione Avanzata

### Votazione Ponderata

Imposta quartiere residenza utente:
```php
update_user_meta($user_id, 'cdv_quartiere_residenza', $term_id);
```

Verifica utente:
```php
update_user_meta($user_id, 'cdv_verified', 1);
```

Peso voto risultante:
- Base: 1.0x
- + Residente quartiere: x2.0
- + Utente verificato: x1.5
- + Anzianità 1 anno: x1.2
- + Anzianità 2+ anni: x1.5
- **MAX**: 6.0x (residente + verificato + 2 anni)

### Coordinate GPS

Aggiungi coordinate a proposta/evento:
```php
update_post_meta($post_id, '_cdv_latitudine', 42.4175);
update_post_meta($post_id, '_cdv_longitudine', 12.1089);
```

### Cron Digest

Verifica schedulazione:
```bash
wp cron event list | grep cdv_weekly_digest
```

Trigger manuale:
```bash
wp cron event run cdv_weekly_digest
```

---

## 🔐 Sicurezza

### Misure Implementate
- ✅ Nonce verification (tutte le form AJAX)
- ✅ Capability check (edit_post, manage_options)
- ✅ Rate limiting (60s firma, 1h voto)
- ✅ Input sanitization (wp_kses, sanitize_*)
- ✅ Output escaping (esc_html, esc_attr, esc_url)
- ✅ SQL prepared statements
- ✅ IP tracking sicuro (proxy/Cloudflare aware)
- ✅ Email validation
- ✅ Privacy checkbox obbligatorio

---

## 📊 Analytics & Tracking

### GA4 Events Automatici
```javascript
// Proposta submitted
dataLayer.push({
  event: 'proposta_submitted',
  proposta_id: 123,
  quartiere: 'Centro',
  tematica: 'Mobilità'
});

// Proposta voted
dataLayer.push({
  event: 'proposta_voted',
  proposta_id: 123
});

// Petizione firmata
dataLayer.push({
  event: 'petizione_firmata',
  petizione_id: 123,
  firme_totali: 150
});

// Sondaggio votato
dataLayer.push({
  event: 'sondaggio_votato',
  sondaggio_id: 123
});

// Dossier letto 60s
dataLayer.push({
  event: 'dossier_read_60s',
  dossier_id: 456
});
```

---

## 🎨 Customizzazione

### CSS Custom
```css
/* Override stili plugin */
.cdv-petizione-progress {
  background: #custom-color;
}

.cdv-badge-earned {
  border: 2px solid gold;
}
```

### Template Override

Copia template in tema:
```
your-theme/
└── cronaca-di-viterbo/
    ├── email/
    │   ├── risposta-amministrazione.php
    │   └── weekly-digest.php
    └── partials/
        ├── petizione-card.php
        └── sondaggio-results.php
```

---

## 🚀 Performance

### Ottimizzazioni
- Query DB indicizzate
- Lazy loading immagini
- Conditional assets loading
- Caching ready (transient API)

### Raccomandazioni
```php
// Redis/Memcached per caching
wp cache set('cdv_dashboard_stats_30', $stats, 3600);

// CDN per Leaflet
https://unpkg.com/leaflet@1.9.4/dist/
```

---

## 🧪 Testing

### Test Funzionalità

#### Petizioni
```bash
# 1. Crea petizione
wp post create --post_type=cdv_petizione \
  --post_title="Riqualificazione Parco" \
  --post_status=publish \
  --meta_input='{"_cdv_soglia_firme":100}'

# 2. Firma petizione
# (via form frontend)

# 3. Verifica milestone
# (email automatica a 50, 100 firme)
```

#### Sondaggi
```bash
# 1. Crea sondaggio
# 2. Vota da utente loggato
# 3. Verifica prevenzione doppio voto
# 4. Controlla risultati real-time
```

#### Reputazione
```bash
# 1. Pubblica proposta
# 2. Verifica +50 punti + badge "Primo Cittadino"
# 3. Ricevi 100 voti
# 4. Verifica badge "Voce Popolare"
```

---

## 📝 Migrazione

### Da v1.0.0 a v1.5.0

**Automatico** (all'attivazione):
```bash
# Eseguito automaticamente:
- Creazione 4 tabelle DB
- Flush rewrite rules
- Migrazione meta keys cv_ → cdv_
- Aggiunta capabilities ruoli
```

**Manuale** (opzionale):
1. Impostare coordinate GPS per proposte/eventi:
   ```php
   update_post_meta($post_id, '_cdv_latitudine', 42.4175);
   update_post_meta($post_id, '_cdv_longitudine', 12.1089);
   ```

2. Configurare quartiere residenza utenti:
   ```php
   update_user_meta($user_id, 'cdv_quartiere_residenza', $term_id);
   ```

3. Aggiornare shortcodes (se usavi vecchi):
   ```
   [cv_proposta_form] → [cdv_proposta_form]
   ```

---

## 📚 Documentazione

### File Disponibili
- `FEATURE-SUGGESTIONS.md` - Roadmap funzionalità future
- `IMPLEMENTATION-SUMMARY.md` - Riepilogo implementazione
- `CHANGELOG-v1.2-1.5.md` - Changelog dettagliato
- `HOOKS.md` - Documentazione completa hook
- `DEPLOYMENT.md` - Guida deployment produzione

### API Reference

**Votazione Ponderata**:
```php
use CdV\Services\VotazioneAvanzata;

// Get peso voto utente
$weight = VotazioneAvanzata::calculate_vote_weight(1.0, $user_id, $proposta_id);

// Get voti ponderati proposta
$total = VotazioneAvanzata::get_proposta_weighted_votes($proposta_id);

// Get breakdown voti
$stats = VotazioneAvanzata::get_votes_breakdown($proposta_id);
```

**Reputazione**:
```php
use CdV\Services\Reputazione;

// Add punti
Reputazione::add_points($user_id, 50, 'Proposta pubblicata');

// Award badge
Reputazione::award_badge($user_id, 'primo_cittadino');

// Get livello
$level = Reputazione::get_user_level_label($user_id);
```

---

## 🐛 Troubleshooting

### Petizione non salva firme
```bash
# Verifica tabella
wp db query "SHOW TABLES LIKE 'wp_cdv_petizioni_firme'"

# Ricrea tabella
wp eval 'CdV\PostTypes\Petizione::create_firme_table();'
```

### Sondaggio non conta voti
```bash
# Verifica tabella
wp db query "SHOW TABLES LIKE 'wp_cdv_sondaggi_voti'"

# Ricrea tabella
wp eval 'CdV\PostTypes\Sondaggio::create_votes_table();'
```

### Mappa non carica
```javascript
// Verifica Leaflet in console
console.log(typeof L);

// Check errori console
// Verifica coordinate: _cdv_latitudine e _cdv_longitudine
```

### Digest non parte
```bash
# Verifica cron
wp cron event list

# Trigger manuale
wp cron event run cdv_weekly_digest

# Re-schedule
wp cron event delete cdv_weekly_digest
wp plugin deactivate cronaca-di-viterbo
wp plugin activate cronaca-di-viterbo
```

---

## 🤝 Supporto

### Community
- GitHub Issues: [link]
- Forum WordPress: [link]
- Documentazione: [link]

### Professional
- Email: info@francescopasseri.com
- Website: https://francescopasseri.com

---

## 📄 License

GPL-2.0-or-later © Francesco Passeri

---

## 🙏 Credits

- **Sviluppato da**: Francesco Passeri
- **Implementazione v1.5**: Background Agent (Claude Sonnet 4.5)
- **Contributors**: Community Cronaca di Viterbo

---

## 🎉 Cosa Fare Ora

1. ✅ **Attiva il plugin**
2. ✅ **Configura quartieri e tematiche**
3. ✅ **Testa form proposta**
4. ✅ **Crea prima petizione**
5. ✅ **Pubblica primo sondaggio**
6. ✅ **Configura coordinate GPS**
7. ✅ **Personalizza template email**
8. ✅ **Integra GA4**
9. ✅ **Vai in produzione!**

**Il plugin è production-ready! 🚀**

---

**⭐ Se ti piace, lascia una stella su GitHub!**
