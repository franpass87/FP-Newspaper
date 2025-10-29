# FP Newspaper

Plugin WordPress per gestione contenuti editoriali e pubblicazione di articoli in stile giornalistico.

## Descrizione

FP Newspaper è un plugin completo per la gestione di contenuti editoriali, con funzionalità avanzate per la pubblicazione di articoli, gestione categorie e tag, statistiche di visualizzazione e molto altro.

## Caratteristiche

- 📰 **Custom Post Type "Articolo"** con supporto completo Gutenberg
- 🏷️ **Tassonomie personalizzate** (Categorie e Tag)
- 📊 **Sistema statistiche** per tracking visualizzazioni e condivisioni
- 🔥 **Breaking News** e articoli in evidenza
- 🔌 **REST API** completa per integrazione frontend
- 📱 **Responsive** e ottimizzato per mobile
- 🌐 **Multilingua ready** con file .pot incluso
- ⚡ **PSR-4 Autoloading** via Composer

## Requisiti

- WordPress 6.0 o superiore
- PHP 7.4 o superiore
- Composer (per installazione dipendenze)

## Installazione

### 1. Via Junction (Ambiente di sviluppo)

Se stai usando l'ambiente junction:

```powershell
# La junction è già stata creata, ora installa le dipendenze
cd "C:\Users\franc\OneDrive\Desktop\FP-Newspaper"
composer install
```

### 2. Manuale

1. Clona/copia la cartella del plugin in `wp-content/plugins/`
2. Esegui `composer install` nella directory del plugin
3. Attiva il plugin dalla dashboard WordPress

## Struttura

```
FP-Newspaper/
├── assets/              # CSS e JS
│   ├── css/
│   └── js/
├── languages/           # File traduzioni
├── src/                 # Classi PSR-4
│   ├── Admin/
│   ├── PostTypes/
│   └── REST/
├── vendor/              # Dipendenze Composer
├── composer.json
├── fp-newspaper.php     # File principale
└── README.md
```

## Utilizzo

### Custom Post Type

Dopo l'attivazione, troverai una nuova voce "Articoli" nel menu admin:

- **Aggiungi Articolo**: Crea nuovi articoli con editor Gutenberg
- **Categorie**: Organizza gli articoli per categoria
- **Tag**: Aggiungi tag per una migliore categorizzazione

### Opzioni Articolo

Ogni articolo ha due meta box nella sidebar:

1. **Opzioni Articolo**:
   - Articolo in evidenza
   - Breaking News

2. **Statistiche Articolo**:
   - Visualizzazioni
   - Condivisioni

### REST API

Endpoint disponibili:

- `GET /wp-json/fp-newspaper/v1/stats` - Statistiche generali (richiede autenticazione)
- `POST /wp-json/fp-newspaper/v1/articles/{id}/view` - Incrementa visualizzazioni
- `GET /wp-json/fp-newspaper/v1/articles/featured` - Articoli in evidenza

### Shortcode

Prossimamente disponibili shortcode per visualizzare archivi e articoli in evidenza.

## Sviluppo

### Setup ambiente sviluppo

```bash
# Installa dipendenze
composer install

# Opzionale: installa dev dependencies
composer install --dev
```

### Coding Standards

Il plugin segue le WordPress Coding Standards e utilizza PSR-4 per l'autoloading.

## Database

Il plugin crea la seguente tabella:

- `wp_fp_newspaper_stats`: Statistiche articoli (visualizzazioni, condivisioni)

## Sicurezza

- ✅ Nonce verification per tutti i form
- ✅ Sanitizzazione e validazione input
- ✅ Prepared statements per query database
- ✅ Capability checks per operazioni admin
- ✅ Escape output per prevenire XSS

## Hooks e Filtri

### Actions

- `fp_newspaper_after_activation` - Dopo attivazione plugin
- `fp_newspaper_before_deactivation` - Prima di disattivazione

### Filters

- `fp_newspaper_articles_per_page` - Numero articoli per pagina (default: 10)
- `fp_newspaper_featured_count` - Numero articoli in evidenza (default: 5)

## Changelog

### 1.0.0 - 2025-10-29
- ✨ Release iniziale
- 📰 Custom post type Articolo
- 🏷️ Tassonomie Categorie e Tag
- 📊 Sistema statistiche
- 🔌 REST API

## Supporto

Per supporto e segnalazione bug, contatta: info@francescopasseri.com

## Licenza

GPL v2 or later - https://www.gnu.org/licenses/gpl-2.0.html

## Autore

**Francesco Passeri**
- Website: https://francescopasseri.com
- Email: info@francescopasseri.com

---

Made with ❤️ in Italy
