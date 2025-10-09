# Changelog Dettagliato v1.2 - v1.5

## [1.5.0] - 2025-10-09

### 🗺️ Added - Mappe Geolocalizzate
- **Shortcode `[cdv_mappa]`** per visualizzare mappa interattiva Leaflet
- Marker differenziati per tipo (proposte, eventi, petizioni)
- Popup informativi con link ai contenuti
- Filtri per quartiere e tematica
- Auto-fit bounds per visualizzazione ottimale
- Supporto coordinate GPS nei post

### ⚖️ Added - Votazione Ponderata
- Sistema voti con **peso variabile** basato su:
  - Residenza quartiere (x2.0)
  - Utente verificato (x1.5)
  - Anzianità 1 anno (x1.2)
  - Anzianità 2+ anni (x1.5)
- Tabella `wp_cdv_voti_dettagli` per tracking dettagliato
- Meta box admin con breakdown voti:
  - Voti semplici vs ponderati
  - Peso medio
  - Numero residenti/verificati
- Hook `cdv_vote_weight` per personalizzazioni
- Leaderboard proposte per voti ponderati

---

## [1.4.0] - 2025-10-09

### 📊 Added - Sondaggi & Consultazioni
- **CPT `cdv_sondaggio`** con meta box opzioni dinamiche
- Selezione singola o multipla
- Scadenza temporale
- Risultati in tempo reale (opzionali)
- Tabella `wp_cdv_sondaggi_voti` per raccolta voti
- Prevenzione doppio voto per utente/IP
- AJAX endpoint `cdv_vota_sondaggio`
- Shortcode `[cdv_sondaggio_form id="123"]`
- Grafici a barre risultati
- Admin interface con editor JavaScript per opzioni

---

## [1.3.0] - 2025-10-09

### ✍️ Added - Petizioni Digitali
- **CPT `cdv_petizione`** per raccolta firme online
- Tabella `wp_cdv_petizioni_firme` per firmatari
- Soglia obiettivo e scadenza configurabili
- Barra progresso real-time
- Rate limiting 60s per firma
- Verifica email duplicata
- Hook milestone (50, 100, 250, 500, 1000, 5000)
- Notifiche email automatiche milestone
- AJAX endpoint `cdv_firma_petizione`
- Shortcodes:
  - `[cdv_petizione_form id="123"]`
  - `[cdv_petizioni limit="10" status="aperte" orderby="firme"]`

### 🏅 Added - Sistema Reputazione & Gamification
- Sistema punti utente con 4 livelli:
  - Cittadino (0-100 punti)
  - Attivista (100-500)
  - Leader (500-2000)
  - Ambasciatore (2000+)
- **8 badge achievements**:
  - 🎯 Primo Cittadino (prima proposta)
  - 🏘️ Guardiano del Quartiere (10+ proposte)
  - 📢 Voce Popolare (100+ voti ricevuti)
  - ✊ Attivista (5+ eventi partecipati)
  - ✍️ Firmatario Seriale (10+ petizioni)
  - 🗳️ Democratico (20+ sondaggi)
  - ⭐ Influencer Civico (500+ voti)
  - 🚀 Pioniere (primi 100 utenti)
- Punti per azioni automatici
- Log attività ultimi 100 movimenti
- Display badge in profilo admin
- Hooks: `cdv_points_added`, `cdv_badge_awarded`, `cdv_level_up`

### 👥 Added - Profili Utente Pubblici
- Shortcode `[cdv_user_profile user_id="123"]`
- Statistiche: proposte, voti ricevuti, badge
- Griglia badge ottenuti + da sbloccare
- Lista proposte recenti utente
- Display livello e punti
- Avatar integrato

---

## [1.2.0] - 2025-10-09

### 🏛️ Added - Risposta Amministrazione
- **CPT `cdv_risposta_amm`** per risposte ufficiali
- 5 stati: In Valutazione, Accettata, Respinta, In Corso, Completata
- Campi amministrativi:
  - Budget allocato (€)
  - Timeline implementazione
  - Delibera/Atto collegato
  - Ufficio competente
  - Referente comunale
  - Data risposta
- Collegamento proposta
- Hook `cdv_risposta_pubblicata` per notifiche
- Helper status label e colori

### 📧 Added - Sistema Notifiche Email
- Notifica risposta amministrazione
- Notifica milestone petizioni
- Notifica nuovo evento in quartiere
- Notifica proposta approvata
- **Digest settimanale automatico** (cron lunedì 9:00)
- Template email HTML personalizzabili
- Tabella `wp_cdv_subscribers` per gestione
- Hooks: `cdv_weekly_digest`

### 📊 Added - Dashboard Analytics Pubblici
- Menu admin "Dashboard CdV"
- Shortcode `[cdv_dashboard periodo="30"]`
- 6 statistiche chiave:
  - Proposte totali + nel periodo
  - Tasso accettazione amministrazione
  - Firme petizioni totali
  - Cittadini attivi
  - Voti totali
  - Tempo medio risposta
- Grafici a barre:
  - Top 5 quartieri per partecipazione
  - Top 5 tematiche discusse
- Tabelle:
  - Top 5 proposte più votate
  - Ultime 5 risposte amministrazione
- Filtro periodo customizzabile

---

## 🔧 Core Updates (Tutte le Versioni)

### File Modificati

#### `src/Bootstrap.php`
- ✅ Aggiunti 3 CPT (RispostaAmministrazione, Petizione, Sondaggio)
- ✅ Aggiunti 5 shortcodes (petizioni, sondaggi, profilo, mappa, dashboard)
- ✅ Aggiunti 2 AJAX handlers (firma, vota sondaggio)
- ✅ Aggiunti 3 Services (Notifiche, Reputazione, VotazioneAvanzata)
- ✅ Aggiunti meta boxes hooks
- ✅ Creazione tabelle DB in activate()
- ✅ Nonce aggiornato a `cdv_ajax_nonce`
- ✅ Stringhe localizzate JS

#### `assets/js/cdv.js`
- ✅ Handler AJAX firma petizione
- ✅ Handler AJAX vota sondaggio
- ✅ Update real-time risultati
- ✅ Character counter textarea
- ✅ Tooltips dinamici
- ✅ Lazy loading immagini
- ✅ Smooth scroll anchors
- ✅ GA4 tracking 60s read dossier

---

## 🗄️ Database Schema Updates

### Nuove Tabelle (3)

```sql
-- Firme Petizioni
CREATE TABLE wp_cdv_petizioni_firme (
  id bigint(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  petizione_id bigint(20) UNSIGNED NOT NULL,
  user_id bigint(20) UNSIGNED DEFAULT 0,
  nome varchar(200),
  cognome varchar(200),
  email varchar(200) NOT NULL,
  comune varchar(200),
  motivazione text,
  privacy_accepted tinyint(1),
  verified tinyint(1) DEFAULT 0,
  ip_address varchar(100),
  user_agent text,
  created_at datetime DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_signature (petizione_id, email)
);

-- Voti Sondaggi
CREATE TABLE wp_cdv_sondaggi_voti (
  id bigint(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sondaggio_id bigint(20) UNSIGNED NOT NULL,
  option_index int(11) NOT NULL,
  user_id bigint(20) UNSIGNED DEFAULT 0,
  user_identifier varchar(200),
  ip_address varchar(100),
  created_at datetime DEFAULT CURRENT_TIMESTAMP,
  KEY sondaggio_id (sondaggio_id),
  KEY user_identifier (user_identifier)
);

-- Voti Dettagliati (Ponderati)
CREATE TABLE wp_cdv_voti_dettagli (
  id bigint(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  proposta_id bigint(20) UNSIGNED NOT NULL,
  user_id bigint(20) UNSIGNED NOT NULL,
  weight float NOT NULL DEFAULT 1.0,
  is_resident tinyint(1) DEFAULT 0,
  is_verified tinyint(1) DEFAULT 0,
  account_age_months int(11) DEFAULT 0,
  motivazione text,
  ip_address varchar(100),
  created_at datetime DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_vote (proposta_id, user_id)
);

-- Subscribers (placeholder)
CREATE TABLE wp_cdv_subscribers (
  id bigint(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email varchar(200) NOT NULL UNIQUE,
  quartieri text,
  tematiche text,
  active tinyint(1) DEFAULT 1,
  created_at datetime DEFAULT CURRENT_TIMESTAMP
);
```

### Nuove Meta Keys

```php
// Risposta Amministrazione
_cdv_proposta_id
_cdv_status
_cdv_budget
_cdv_timeline
_cdv_delibera
_cdv_ufficio
_cdv_referente
_cdv_data_risposta

// Petizione
_cdv_soglia_firme
_cdv_firme_count
_cdv_deadline
_cdv_aperta

// Sondaggio
_cdv_options (serialized)
_cdv_scadenza
_cdv_multiplo
_cdv_mostra_risultati
_cdv_aperto

// Proposte (nuovo)
_cdv_weighted_votes

// Mappa
_cdv_latitudine
_cdv_longitudine

// User Meta
cdv_points
cdv_level
cdv_badges (serialized)
cdv_points_log (serialized)
cdv_quartiere_residenza
cdv_verified
```

---

## 🎯 Nuovi Shortcodes Completi

```php
// Petizioni
[cdv_petizione_form id="123"]
[cdv_petizioni limit="10" quartiere="centro" tematica="mobilita" status="aperte" orderby="firme"]

// Sondaggi
[cdv_sondaggio_form id="123"]

// Profilo
[cdv_user_profile user_id="123"]
[cdv_user_profile] // current user

// Dashboard
[cdv_dashboard periodo="30"]

// Mappa
[cdv_mappa tipo="proposte" quartiere="centro" height="600px" center="42.4175,12.1089" zoom="13"]
[cdv_mappa tipo="eventi"]
[cdv_mappa tipo="petizioni"]
[cdv_mappa tipo="tutti"]
```

---

## 🔌 Nuovi AJAX Endpoints

```javascript
// Firma Petizione
cdv_firma_petizione
// Data: petizione_id, nome, cognome, email, comune, motivazione, privacy

// Vota Sondaggio
cdv_vota_sondaggio
// Data: sondaggio_id, options[] (array indici)
```

---

## 🪝 Nuovi WordPress Hooks

### Actions
```php
// Notifiche
do_action('cdv_risposta_pubblicata', $risposta_id, $proposta_id);
do_action('cdv_petizione_milestone', $petizione_id, $firme_count);
do_action('cdv_petizione_firmata', $petizione_id, $email, $user_id);
do_action('cdv_sondaggio_votato', $sondaggio_id, $options, $user_id);

// Reputazione
do_action('cdv_points_added', $user_id, $points, $new_total);
do_action('cdv_badge_awarded', $user_id, $badge_slug);
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

## 📈 Performance & Security

### Ottimizzazioni
- ✅ Query DB ottimizzate con indici
- ✅ Rate limiting (60s firma, 1h voto)
- ✅ Lazy loading immagini
- ✅ Nonce verification completa
- ✅ Input sanitization (wp_kses)
- ✅ Output escaping
- ✅ SQL prepared statements
- ✅ Unique keys per prevenire duplicati

---

## 🚀 Migration Notes

### Da v1.0.0 a v1.5.0

**Automatico** (all'attivazione):
- ✅ Creazione 4 nuove tabelle DB
- ✅ Flush rewrite rules
- ✅ Aggiunta capabilities ai ruoli

**Manuale** (consigliato):
1. Testare funzionalità petizioni in staging
2. Verificare cron `cdv_weekly_digest`
3. Configurare coordinate GPS per proposte/eventi esistenti (opzionale)
4. Impostare quartiere residenza utenti per voti ponderati (opzionale)

---

## ⚠️ Breaking Changes

**Nessuno** - Tutte le funzionalità sono additive e backward compatible.

---

## 📝 TODO Future

### v1.6 (Q1 2026)
- [ ] RSVP eventi con QR code
- [ ] PWA support
- [ ] Moderazione AI
- [ ] Export CSV dati

---

**Total Files Added**: 16  
**Total Lines of Code**: ~4,000  
**Database Tables**: +4  
**New Shortcodes**: +5  
**New Hooks**: +15
