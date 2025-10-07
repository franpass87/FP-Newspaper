# Architettura Modulare del Plugin

## Panoramica

Il plugin **CV Dossier & Context** è stato completamente refactored con un'architettura modulare che segue i principi SOLID e le best practices di WordPress.

## Struttura Directory

```
wp-content/plugins/cv-dossier-context/
├── cv-dossier-context.php          # Bootstrap principale (27 righe)
├── includes/
│   ├── class-cv-plugin.php         # Orchestratore principale
│   ├── class-cv-ajax-handler.php   # Gestione richieste AJAX
│   ├── class-cv-content-filter.php # Filtri contenuto automatici
│   ├── admin/
│   │   ├── class-cv-cpt-manager.php     # Registrazione Custom Post Types
│   │   ├── class-cv-meta-boxes.php      # Gestione Meta Boxes
│   │   └── class-cv-assets-manager.php  # Enqueuing CSS/JS
│   ├── frontend/
│   │   ├── class-cv-context-card.php    # Rendering schede riassuntive
│   │   ├── class-cv-timeline.php        # Rendering timeline eventi
│   │   ├── class-cv-map-renderer.php    # Rendering mappe Leaflet
│   │   └── class-cv-shortcodes.php      # Gestione shortcodes
│   └── helpers/
│       ├── class-cv-validator.php       # Validazioni centralizzate
│       ├── class-cv-sanitizer.php       # Sanitizzazione centralizzata
│       └── class-cv-marker-helper.php   # Gestione marker mappe
├── css/
├── js/
├── docs/
└── tools/
```

## Componenti Principali

### 1. Bootstrap (`cv-dossier-context.php`)

File minimalista che:
- Carica Composer autoload (se disponibile)
- Richiede la classe principale
- Inizializza il plugin

### 2. Orchestratore (`CV_Plugin`)

Classe singleton che coordina tutti i componenti:
- **Dependency Injection**: Inietta dipendenze ai componenti
- **Lifecycle Management**: Gestisce attivazione/disattivazione
- **Component Initialization**: Inizializza tutti i manager

### 3. Helper Classes

#### `CV_Validator`
Validazioni centralizzate per:
- Date (formato YYYY-MM-DD)
- Coordinate geografiche (lat/lng)
- Status (open/closed)
- Score (0-100)
- URL immagini
- Attachment ID

#### `CV_Sanitizer`
Sanitizzazione centralizzata per:
- Altezza mappe con parsing avanzato
- Array di marker con validazione completa
- Testo alternativo immagini

#### `CV_Marker_Helper`
Gestione marker mappe:
- Preparazione dati immagini
- Estrazione marker da eventi dossier
- Cache per evitare query duplicate

### 4. Admin Components

#### `CV_CPT_Manager`
- Registrazione CPT `cv_dossier`
- Registrazione CPT `cv_dossier_event`
- Label localizzate

#### `CV_Meta_Boxes`
- Meta box Dossier (dettagli, toggle features)
- Meta box Evento (data, luogo, coordinate)
- Meta box Post (collegamento dossier, mappa)
- Salvataggio con validazione tramite Helper

#### `CV_Assets_Manager`
- Enqueuing condizionale CSS/JS frontend
- Enqueuing admin assets
- Registrazione Leaflet da CDN
- Localizzazione script con traduzioni

### 5. Frontend Components

#### `CV_Context_Card`
Rendering schede riassuntive con:
- Stato dossier
- Punteggio
- Punti chiave
- Attori coinvolti
- Form follow-up

#### `CV_Timeline`
Rendering timeline cronologica con:
- Ordinamento per data
- Filtro contenuto via hook
- Supporto HTML nei contenuti

#### `CV_Map_Renderer`
Rendering mappe Leaflet:
- Mappe articoli (marker personalizzati)
- Mappe dossier (eventi)
- Gestione errori JSON
- Accessibilità (ARIA)

#### `CV_Shortcodes`
Gestione shortcodes:
- `[cv_dossier_context]`
- `[cv_dossier_timeline]`
- `[cv_dossier_map height="400"]`

### 6. Core Components

#### `CV_AJAX_Handler`
Gestione endpoint AJAX:
- Follow-up dossier
- Validazione nonce
- Sanitizzazione input
- Action hook `cv_dossier_follow`

#### `CV_Content_Filter`
Filtro automatico `the_content`:
- Inserimento schede in post
- Inserimento mappe in post
- Inserimento componenti in dossier
- Rispetto toggle utente

## Principi Applicati

### Single Responsibility Principle (SRP)
Ogni classe ha una sola responsabilità:
- `CV_Validator` → Solo validazioni
- `CV_CPT_Manager` → Solo CPT
- `CV_Map_Renderer` → Solo rendering mappe

### Dependency Injection
Le dipendenze sono iniettate via costruttore:
```php
class CV_Shortcodes {
    public function __construct( 
        $context_renderer, 
        $timeline_renderer, 
        $map_renderer, 
        $assets 
    ) {
        // ...
    }
}
```

### Separation of Concerns
- **Admin** (backend) separato da **Frontend**
- **Helpers** riutilizzabili in tutto il plugin
- **Renderers** indipendenti dalla logica business

### DRY (Don't Repeat Yourself)
- Validazioni centralizzate in `CV_Validator`
- Sanitizzazione centralizzata in `CV_Sanitizer`
- Helper riutilizzabili

## Vantaggi dell'Architettura

### ✅ Manutenibilità
- Codice organizzato in moduli logici
- Facile individuare e modificare funzionalità
- Riduzione accoppiamento

### ✅ Testabilità
- Componenti facilmente testabili in isolamento
- Dipendenze mockabili
- Logica separata da presentazione

### ✅ Riusabilità
- Helper utilizzabili in contesti diversi
- Renderer componibili
- Logica business indipendente

### ✅ Scalabilità
- Facile aggiungere nuovi componenti
- Struttura estendibile
- Chiara gerarchia delle responsabilità

### ✅ Performance
- Caricamento lazy delle dipendenze
- Cache marker per evitare query duplicate
- Enqueuing condizionale assets

## Migrazione dal Monolite

### Prima (File Monolitico)
```
cv-dossier-context.php    1497 righe
- Classe unica con ~10 responsabilità
- Logica mista admin/frontend
- Helper privati non riutilizzabili
- Difficile da testare
```

### Dopo (Architettura Modulare)
```
cv-dossier-context.php      27 righe (bootstrap)
includes/
├── 14 classi specializzate
├── Media ~200-400 righe per classe
├── Responsabilità chiare e separate
└── Completamente testabile
```

## Estensione del Plugin

### Aggiungere un nuovo Renderer
1. Creare classe in `includes/frontend/`
2. Iniettare dipendenze nel costruttore
3. Registrare in `CV_Plugin::init_components()`

### Aggiungere nuova Validazione
Aggiungere metodo statico a `CV_Validator`:
```php
public static function validate_custom( $value ) {
    // Logica di validazione
    return $validated_value;
}
```

### Aggiungere nuovo Meta Box
Aggiungere metodi in `CV_Meta_Boxes`:
```php
public function render_custom_meta_box( $post ) {
    // Rendering
}

private function save_custom_meta( $post_id ) {
    // Salvataggio
}
```

## Hooks Disponibili

### Actions
- `cv_dossier_follow` - Dopo iscrizione follow-up

### Filters
- `cv_dossier_timeline_item_content` - Contenuto evento timeline

## Compatibilità

✅ Mantiene piena compatibilità backward con:
- Shortcodes esistenti
- Meta keys database
- Hook WordPress
- Template theme

## Testing

La nuova architettura facilita:
- **Unit Testing**: Ogni classe testabile in isolamento
- **Integration Testing**: Componenti testabili insieme
- **Mocking**: Dipendenze facilmente sostituibili

## Conclusioni

La modularizzazione ha trasformato un file monolitico di ~1500 righe in un'architettura pulita, mantenibile e scalabile, seguendo le best practices moderne di sviluppo WordPress.