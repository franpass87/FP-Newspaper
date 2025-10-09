# Cronaca di Viterbo

**Plugin WordPress modulare per giornalismo locale partecipativo**

[![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)](CHANGELOG.md)
[![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-blue.svg)](https://wordpress.org)
[![PHP](https://img.shields.io/badge/PHP-8.0%2B-purple.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-GPL--2.0%2B-green.svg)](LICENSE)

## 📋 Descrizione

**Cronaca di Viterbo** è un plugin completo per testate giornalistiche locali che vogliono coinvolgere i cittadini. Include gestione dossier/inchieste, proposte della comunità, eventi locali, profili ambasciatori civici, con integrazione WPBakery, tracking GA4, SEO e ruoli personalizzati.

## ✨ Caratteristiche

### 📝 Custom Post Types
- **Dossier** - Inchieste giornalistiche approfondite
- **Proposte** - Idee dei cittadini con moderazione e votazione
- **Eventi** - Calendario eventi/riunioni/serate
- **Persone** - Ambasciatori civici e redazione

### 🏷️ Tassonomie
- **Quartiere** (gerarchica) - Organizzazione territoriale
- **Tematica** (flat) - Categorizzazione argomenti

### 🎨 Shortcodes + WPBakery
```
[cdv_proposta_form]        → Form invio proposte
[cdv_proposte]             → Lista proposte con votazione
[cdv_dossier_hero]         → Hero section dossier
[cdv_eventi]               → Lista eventi filtrabili
[cdv_persona_card]         → Card profilo persona
```

Tutti gli shortcodes sono integrati con **WPBakery Page Builder** nel gruppo "Cronaca di Viterbo".

### ⚡ AJAX & Real-time
- **Submit proposta**: Rate-limiting 60s per IP
- **Votazione**: Cooldown 1h per utente/IP
- **Nonce verification** su tutti gli endpoints
- **Sanitizzazione** completa input utente

### 📊 Analytics & SEO
- **GA4 Events**: `proposta_submitted`, `proposta_voted`, `dossier_read_60s`
- **JSON-LD Schema.org**: NewsArticle, Event, Person
- **Open Graph** ready

### 👥 Ruoli Personalizzati
- **CdV Editor** - Gestione completa (edit/publish tutto)
- **CdV Moderatore** - Solo moderazione proposte/commenti
- **CdV Reporter** - Creazione bozze dossier/eventi

### 🔐 Sicurezza
- Nonce verification su AJAX
- Rate limiting anti-spam
- Sanitizzazione wp_kses
- IP tracking sicuro (Cloudflare/proxy aware)
- Checkbox privacy obbligatorio

## 📦 Installazione

### Via WordPress Admin
1. Scarica il plugin da [Releases](../../releases)
2. Vai su **Plugin > Aggiungi nuovo > Carica plugin**
3. Carica il file ZIP e attiva

### Via FTP/SSH
```bash
cd wp-content/plugins
git clone [repo-url] cronaca-di-viterbo
cd cronaca-di-viterbo
composer install --no-dev --optimize-autoloader
```

### Configurazione Iniziale
1. Vai su **Moderazione > Impostazioni**
2. Abilita GA4 e Schema.org
3. Crea quartieri in **Quartieri**
4. Crea tematiche in **Tematiche**
5. Assegna ruoli agli utenti

## 🚀 Quick Start

### 1. Crea un Dossier
```
Dossier > Aggiungi nuovo
- Titolo: "Traffico in Centro Storico"
- Quartiere: Centro
- Tematica: Mobilità
```

### 2. Aggiungi Form Proposte a una Pagina
```
Opzione A (Shortcode):
[cdv_proposta_form title="Invia la tua idea"]

Opzione B (WPBakery):
Aggiungi elemento "Form Proposta" dalla categoria "Cronaca di Viterbo"
```

### 3. Visualizza Eventi Futuri
```
[cdv_eventi limit="6" upcoming="yes" quartiere="centro"]
```

## 📚 Documentazione

### Shortcodes Reference

#### `[cdv_proposta_form]`
Form per invio proposte da parte dei cittadini.

**Parametri:**
- `title` (string) - Titolo del form (default: "Invia una Proposta")

**Esempio:**
```
[cdv_proposta_form title="Hai un'idea per la città?"]
```

#### `[cdv_proposte]`
Lista proposte con pulsante votazione.

**Parametri:**
- `limit` (int) - Numero proposte (default: 10)
- `quartiere` (slug) - Filtra per quartiere
- `tematica` (slug) - Filtra per tematica
- `orderby` (string) - Ordina per: date, title (default: date)
- `order` (string) - ASC o DESC (default: DESC)

**Esempio:**
```
[cdv_proposte limit="5" quartiere="centro" orderby="date" order="DESC"]
```

#### `[cdv_eventi]`
Lista eventi con filtri.

**Parametri:**
- `limit` (int) - Numero eventi (default: 6)
- `quartiere` (slug) - Filtra per quartiere
- `tematica` (slug) - Filtra per tematica
- `upcoming` (yes/no) - Solo eventi futuri (default: yes)

**Esempio:**
```
[cdv_eventi limit="10" upcoming="yes"]
```

### AJAX API

#### Submit Proposta
```javascript
jQuery.ajax({
  url: ajaxurl,
  type: 'POST',
  data: {
    action: 'cdv_submit_proposta',
    nonce: cdvData.nonce,
    title: 'Titolo proposta',
    content: 'Descrizione',
    quartiere: 123,  // term_id
    tematica: 456,   // term_id
    privacy: 'on'
  }
});
```

#### Vote Proposta
```javascript
jQuery.ajax({
  url: ajaxurl,
  type: 'POST',
  data: {
    action: 'cdv_vote_proposta',
    nonce: cdvData.nonce,
    id: 789  // post_id
  }
});
```

### GA4 Events

Il plugin invia automaticamente eventi al `dataLayer`:

```javascript
// Proposta submitted
dataLayer.push({
  'event': 'proposta_submitted',
  'proposta_id': 123,
  'quartiere': 'Centro',
  'tematica': 'Mobilità'
});

// Proposta voted
dataLayer.push({
  'event': 'proposta_voted',
  'proposta_id': 123
});

// Dossier letto per 60s
dataLayer.push({
  'event': 'dossier_read_60s',
  'dossier_id': 456,
  'dossier_title': 'Traffico in Centro'
});
```

### Hooks & Filters

#### Actions
```php
// Dopo invio proposta
do_action('cdv_proposta_submitted', $post_id, $quartiere_id, $tematica_id);

// Dopo voto
do_action('cdv_proposta_voted', $post_id, $new_votes);
```

## 🔄 Migrazione da CV Dossier Context

Il plugin include **migrazione automatica** all'attivazione:

### Automatico ✅
- Meta chiavi: `_cv_*` → `_cdv_*`
- Opzioni: `cv_*` → `cdv_*`
- CPT: `cv_dossier` → `cdv_dossier`
- CPT: `cv_dossier_event` → `cdv_evento`

### Manuale ⚠️
- Aggiornare shortcodes: `[cv_*]` → `[cdv_*]`
- Ricreare elementi WPBakery (categoria "Cronaca di Viterbo")

### Deprecazioni
- `[cv_proposta_form]` → shim con notice
- `[cv_dossier_map]` → deprecato (roadmap 1.1)

## 🛠️ Sviluppo

### Requisiti
- PHP 8.0+
- WordPress 6.0+
- Composer
- Node.js (opzionale, per tooling)

### Setup Dev
```bash
git clone [repo] cronaca-di-viterbo
cd cronaca-di-viterbo
composer install
```

### Quality Tools
```bash
# PHPCS (WordPress Coding Standards)
composer phpcs

# PHPStan (livello 5)
composer phpstan

# Build per produzione
composer build
```

### Struttura
```
cronaca-di-viterbo/
├── src/                      # PSR-4 namespace CdV\
│   ├── PostTypes/           # CPT classes
│   ├── Taxonomies/          # Tax classes
│   ├── Shortcodes/          # Shortcode handlers
│   ├── Ajax/                # AJAX endpoints
│   ├── Services/            # Services (Schema, GA4, etc)
│   ├── Admin/               # Admin screens
│   ├── Roles/               # Capabilities
│   └── Utils/               # Helpers
├── assets/                  # CSS/JS
├── templates/               # Template parts
└── cronaca-di-viterbo.php  # Bootstrap
```

## 🗺️ Roadmap

### v1.1 (Q1 2026)
- [ ] RSVP eventi con capienza soft
- [ ] Cloudflare Turnstile / reCAPTCHA
- [ ] Mappe Leaflet (modulo opzionale)
- [ ] Sistema reputazione utenti
- [ ] Import/Export CSV

### v1.2 (Q2 2026)
- [ ] Notifiche email moderatori
- [ ] Dashboard analytics proposte
- [ ] Widget WordPress Gutenberg

## 📄 Changelog

Vedi [CHANGELOG.md](CHANGELOG.md) per tutte le versioni.

## 🤝 Contributi

Contributi benvenuti! Per favore:
1. Fork del repo
2. Crea un branch (`git checkout -b feature/amazing-feature`)
3. Commit (`git commit -m 'feat: add amazing feature'`)
4. Push (`git push origin feature/amazing-feature`)
5. Apri una Pull Request

## 📝 License

GPL-2.0-or-later © [Francesco Passeri](https://francescopasseri.com)

## 👤 Autore

**Francesco Passeri**
- Website: [francescopasseri.com](https://francescopasseri.com)
- Email: info@francescopasseri.com

## 🙏 Credits

Sviluppato per **Cronaca di Viterbo** - Giornalismo locale partecipativo.

---

**⭐ Se ti piace questo plugin, lascia una stella su GitHub!**
