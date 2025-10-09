# Changelog

Tutte le modifiche significative a questo progetto saranno documentate in questo file.

Il formato è basato su [Keep a Changelog](https://keepachangelog.com/it/1.0.0/),
e questo progetto aderisce a [Semantic Versioning](https://semver.org/lang/it/).

## [1.0.0] - 2025-10-09

### 🎉 Release Iniziale - Refactoring Completo

#### Added (Nuove funzionalità)
- **CPT (Custom Post Types)**
  - `cdv_dossier` - Dossier/Inchieste giornalistiche
  - `cdv_proposta` - Proposte dei cittadini con moderazione
  - `cdv_evento` - Eventi, riunioni, serate
  - `cdv_persona` - Ambasciatori civici e redazione

- **Tassonomie**
  - `cdv_quartiere` - Tassonomia gerarchica per quartieri
  - `cdv_tematica` - Tassonomia flat per tematiche

- **Shortcodes**
  - `[cdv_proposta_form]` - Form AJAX invio proposte
  - `[cdv_proposte]` - Lista proposte con votazione
  - `[cdv_dossier_hero]` - Hero section dossier
  - `[cdv_eventi]` - Lista eventi filtrabili
  - `[cdv_persona_card]` - Card persona singola

- **WPBakery Integration**
  - Mapping completo shortcodes come elementi Visual Composer
  - Categoria dedicata "Cronaca di Viterbo"
  - Parametri configurabili per ogni elemento

- **AJAX Handlers**
  - `cdv_submit_proposta` - Invio proposta con rate-limit 60s
  - `cdv_vote_proposta` - Votazione con cooldown 1h per utente/IP

- **Services**
  - Schema.org JSON-LD (NewsArticle, Event, Person)
  - GA4 Tracking (proposta_submitted, proposta_voted, dossier_read_60s)
  - Security helpers (IP detection, rate-limiting)
  - Sanitization utilities

- **Roles & Capabilities**
  - CdV Editor - Gestione completa
  - CdV Moderatore - Solo moderazione
  - CdV Reporter - Creazione bozze

- **Admin Screens**
  - Coda Moderazione proposte
  - Pagina Impostazioni (GA4, Schema, future integrations)

- **Migration & Compatibility**
  - Migrazione automatica da cv_ a cdv_ (meta, options, CPT)
  - Shim per shortcodes legacy con deprecation notices
  - Versionamento DB con `cdv_db_version`

#### Changed (Modifiche)
- **Namespace**: Da classi globali `CV_*` a PSR-4 `CdV\`
- **Text Domain**: Da `cv-dossier` a `cronaca-di-viterbo`
- **Slug**: Da `cv-dossier-context` a `cronaca-di-viterbo`
- **Prefissi**: Da `cv_` a `cdv_` (meta, options, handles)
- **Architettura**: Da includes/ a PSR-4 src/ structure

#### Deprecated (Deprecazioni)
- Shortcode `[cv_proposta_form]` → usa `[cdv_proposta_form]`
- Shortcode `[cv_dossier_map]` → funzionalità mappe deprecata

#### Removed (Rimozioni)
- Modulo mappe integrato (spostato in roadmap 1.1 come opzionale)
- Dipendenze da file includes/ vecchi

#### Security (Sicurezza)
- Nonce verification su tutti gli AJAX endpoints
- Rate limiting per prevenire spam (60s submit, 1h vote)
- Sanitizzazione completa input utente
- IP tracking sicuro con header proxy/cloudflare
- Checkbox privacy obbligatorio

#### Fixed (Bug Fix)
- N/A (prima release)

---

## [Unreleased] - Roadmap 1.1

### Planned
- [ ] RSVP eventi con capienza soft e conferma email
- [ ] Cloudflare Turnstile / reCAPTCHA integration
- [ ] Mappe Leaflet per eventi/dossier (modulo opzionale)
- [ ] Sistema reputazione utenti (badge)
- [ ] Import/Export CSV per ambasciatori/eventi
- [ ] WP-CLI command `wp cdv migrate`

---

## Note di Migrazione da CV Dossier Context

### Compatibilità Automatica
Il plugin include migrazioni automatiche all'attivazione:
- Meta chiavi: `_cv_*` → `_cdv_*`
- Opzioni: `cv_*` → `cdv_*`
- CPT: `cv_dossier` → `cdv_dossier`
- CPT: `cv_dossier_event` → `cdv_evento`

### Azioni Manuali Richieste
- [ ] Aggiornare shortcodes nelle pagine: `[cv_*]` → `[cdv_*]`
- [ ] Verificare elementi WPBakery (ricreare da categoria "Cronaca di Viterbo")
- [ ] Controllare riferimenti diretti a classi `CV_*` in custom code

### Breaking Changes
⚠️ **Attenzione**: Modulo mappe deprecato. Se utilizzato:
1. Esportare dati markers/coordinate
2. Attendere roadmap 1.1 per modulo Leaflet opzionale
3. Contattare sviluppatore per assistenza migrazione

---

## [Legacy] - Versioni Precedenti

### [0.x.x] - CV Dossier Context
Versioni precedenti del plugin (pre-refactoring).
Consultare CHANGELOG.md.old per storico completo.
