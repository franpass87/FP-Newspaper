# 📦 Assets - Cronaca di Viterbo

Questa directory contiene tutti gli assets modulari (JavaScript e CSS) del plugin Cronaca di Viterbo.

## 📁 Struttura Directory

```
assets/
├── css/
│   ├── components/          # Componenti CSS Frontend
│   │   ├── cards.css       # Card (proposte, eventi, persone)
│   │   ├── forms.css       # Form e pulsanti
│   │   ├── layouts.css     # Layout e griglie
│   │   └── responsive.css  # Media queries
│   │
│   ├── admin/              # Componenti CSS Admin
│   │   ├── dashboard.css   # Dashboard amministrazione
│   │   ├── settings.css    # Pagine impostazioni
│   │   └── tables.css      # Tabelle e moderazione
│   │
│   ├── main.css            # Entry point CSS frontend
│   ├── admin-main.css      # Entry point CSS admin
│   ├── cdv.css             # [LEGACY] CSS originale
│   └── cdv-admin.css       # [LEGACY] Admin CSS originale
│
└── js/
    ├── modules/            # Moduli JavaScript Frontend
    │   ├── analytics-tracker.js  # Tracking GA4
    │   ├── form-handler.js       # Gestione form proposte
    │   ├── petition-handler.js   # Gestione petizioni
    │   ├── poll-handler.js       # Gestione sondaggi
    │   ├── utils.js              # Funzioni utility
    │   └── voting-system.js      # Sistema votazione
    │
    ├── admin/              # Moduli JavaScript Admin
    │   ├── dashboard.js    # Dashboard amministrazione
    │   ├── moderation.js   # Moderazione contenuti
    │   └── settings.js     # Gestione impostazioni
    │
    ├── main.js             # Entry point JS frontend
    ├── admin-main.js       # Entry point JS admin
    ├── cdv.js              # [LEGACY] JS originale
    └── cdv-admin.js        # [LEGACY] Admin JS originale
```

## 🚀 Caricamento Assets

### Frontend

Gli assets frontend vengono caricati **condizionalmente** in base al contenuto della pagina:

- **Sempre caricati:**
  - `utils.js` - Funzioni utility
  - `analytics-tracker.js` - Tracking GA4
  - `main.js` - Entry point

- **Condizionali:**
  - `form-handler.js` - Solo se presente `[cdv_proposta_form]`
  - `voting-system.js` - Solo se presente `[cdv_proposte_list]`
  - `petition-handler.js` - Solo se presente `[cdv_petizione_form]` o `[cdv_petizioni]`
  - `poll-handler.js` - Solo se presente `[cdv_sondaggio_form]`

### Admin

Gli assets admin vengono caricati **solo nelle pagine amministrative** del plugin.

## 🔧 Development

### Lavorare con i Moduli

#### Aggiungere un Nuovo Modulo JavaScript

1. **Crea il file modulo** in `js/modules/nuovo-modulo.js`:

```javascript
(function($) {
    'use strict';

    const NuovoModulo = {
        init() {
            console.log('Nuovo modulo inizializzato');
        }
    };

    window.CdV = window.CdV || {};
    window.CdV.NuovoModulo = NuovoModulo;

})(jQuery);
```

2. **Registra in Bootstrap.php**:

```php
wp_enqueue_script(
    'cdv-nuovo-modulo',
    CDV_PLUGIN_URL . 'assets/js/modules/nuovo-modulo.js',
    [ 'jquery' ],
    CDV_VERSION,
    true
);
```

3. **Inizializza in main.js**:

```javascript
if (window.CdV.NuovoModulo) {
    window.CdV.NuovoModulo.init();
}
```

#### Aggiungere un Nuovo Componente CSS

1. **Crea il file** in `css/components/nuovo-componente.css`:

```css
.nuovo-componente {
    /* Stili */
}
```

2. **Importa in main.css**:

```css
@import url('components/nuovo-componente.css');
```

### Build System

Il plugin include un sistema di build per concatenare e minificare gli assets.

#### Comandi Disponibili

```bash
# Build una volta
npm run build
# oppure
bash build.sh

# Watch per rebuild automatico
npm run watch
# oppure
bash watch.sh

# Development (build + watch)
npm run dev
```

#### Installare Tool di Minificazione

```bash
# CSS minification
npm install -g csso-cli

# JavaScript minification
npm install -g uglify-js

# File watching
npm install -g chokidar-cli
```

#### Struttura Build

Il comando `npm run build` genera:

```
build/
├── css/
│   ├── frontend.css        # CSS concatenato
│   ├── frontend.min.css    # CSS minificato
│   ├── admin.css           # Admin CSS concatenato
│   └── admin.min.css       # Admin CSS minificato
└── js/
    ├── frontend.js         # JS concatenato
    ├── frontend.min.js     # JS minificato
    ├── admin.js            # Admin JS concatenato
    └── admin.min.js        # Admin JS minificato
```

## 📚 Moduli Disponibili

### JavaScript Frontend

#### 1. **Utils** (`modules/utils.js`)

Funzioni utility condivise.

```javascript
// Debounce
const debouncedFn = CdV.Utils.debounce(myFunction, 300);

// Throttle
const throttledFn = CdV.Utils.throttle(myFunction, 300);

// Notifiche
CdV.Utils.showNotification('Messaggio', 'success');

// Copia negli appunti
CdV.Utils.copyToClipboard('testo');

// Formatta numero
CdV.Utils.formatNumber(1000); // "1.000"
```

#### 2. **Analytics Tracker** (`modules/analytics-tracker.js`)

Gestione tracking GA4.

```javascript
// Traccia proposta
AnalyticsTracker.trackPropostaSubmitted(123);

// Traccia voto
AnalyticsTracker.trackPropostaVoted(123);

// Traccia firma petizione
AnalyticsTracker.trackPetizioneSigned(123);

// Evento personalizzato
AnalyticsTracker.trackCustomEvent('categoria', 'azione', 'label');
```

#### 3. **Form Handler** (`modules/form-handler.js`)

Gestione form proposte AJAX.

- Validazione automatica
- Invio AJAX
- Gestione errori
- Integrazione analytics

#### 4. **Voting System** (`modules/voting-system.js`)

Sistema di votazione proposte.

- Prevenzione voti duplicati
- Aggiornamento contatori real-time
- Integrazione analytics

#### 5. **Petition Handler** (`modules/petition-handler.js`)

Gestione petizioni.

- Firma petizioni
- Progress bar
- Milestone tracking
- Celebrazione obiettivi

#### 6. **Poll Handler** (`modules/poll-handler.js`)

Gestione sondaggi.

- Votazione opzioni
- Visualizzazione risultati
- Grafici percentuali
- Integrazione analytics

### JavaScript Admin

#### 1. **Dashboard** (`admin/dashboard.js`)

- Refresh statistiche
- Quick actions
- Gestione widget

#### 2. **Moderation** (`admin/moderation.js`)

- Bulk actions
- Approvazione/rifiuto contenuti
- Filtri tabelle

#### 3. **Settings** (`admin/settings.js`)

- Tabs navigazione
- Toggle switches
- Color pickers
- Import/Export settings

## 🎨 CSS Components

### Frontend

- **Forms** - Stili form, pulsanti, notifiche
- **Cards** - Card proposte, eventi, persone
- **Layouts** - Hero, contenitori, griglie
- **Responsive** - Media queries, utility

### Admin

- **Dashboard** - Widget, statistiche, charts
- **Settings** - Form settings, tabs, toggles
- **Tables** - Tabelle, pagination, bulk actions

## 🔄 Caricamento Condizionale

Il plugin ottimizza le performance caricando solo i moduli necessari:

```php
// In Bootstrap.php
if (has_shortcode($content, 'cdv_proposta_form')) {
    wp_enqueue_script('cdv-form-handler', ...);
}
```

### Shortcode → Modulo Mapping

| Shortcode | Moduli Caricati |
|-----------|----------------|
| `[cdv_proposta_form]` | form-handler.js |
| `[cdv_proposte_list]` | voting-system.js |
| `[cdv_petizione_form]` | petition-handler.js |
| `[cdv_petizioni]` | petition-handler.js |
| `[cdv_sondaggio_form]` | poll-handler.js |

## 🐛 Debug

### Attivare Debug Mode

In `wp-config.php`:

```php
define('SCRIPT_DEBUG', true);
```

Questo caricherà le versioni non minificate degli assets.

### Verificare Moduli Caricati

Apri console browser:

```javascript
// Verifica namespace
console.log(window.CdV);

// Verifica moduli
console.log(Object.keys(window.CdV));

// Verifica analytics
console.log(window.AnalyticsTracker);
```

### Log Automatico

Il plugin logga automaticamente i moduli inizializzati:

```
CdV: Moduli inizializzati: FormHandler, VotingSystem, Utils
```

## 📈 Performance

### Ottimizzazioni Implementate

✅ **Caricamento condizionale** - Solo moduli necessari  
✅ **Minificazione** - File compressi in produzione  
✅ **Lazy init** - Inizializzazione on-demand  
✅ **Dependency management** - Dipendenze ottimizzate  
✅ **Code splitting** - Moduli separati  

### Best Practices

1. **Usa `SCRIPT_DEBUG`** in development
2. **Build prima del deploy** in produzione
3. **Minifica assets** per performance
4. **Lazy load** moduli non critici
5. **Cache busting** con versioning

## 🔒 Sicurezza

Tutti i moduli JavaScript:

- ✅ Usano **nonce** per AJAX
- ✅ **Validano** input utente
- ✅ **Escapano** output HTML
- ✅ **Verificano** permessi

## 📝 Convenzioni

### JavaScript

- Usa **namespace** `window.CdV.*`
- **IIFE** per encapsulation
- **ES6** syntax (const, let, arrow functions)
- **JSDoc** per documentazione

### CSS

- Prefisso **`cdv-`** per classi
- **BEM** naming convention (opzionale)
- **Mobile-first** responsive
- **CSS custom properties** per variabili

## 🤝 Contributing

Per contribuire agli assets:

1. Fork e clone repository
2. Crea branch feature
3. Modifica moduli in `assets/`
4. Testa modifiche
5. Run build: `npm run build`
6. Commit e push
7. Crea pull request

## 📚 Risorse

- [Documentazione Modularizzazione](../docs/modularization.md)
- [Quick Reference](../QUICK-REFERENCE.md)
- [Changelog](../CHANGELOG.md)

---

**Ultimo aggiornamento:** 2025-10-09  
**Versione:** 1.5.0
