# CV Dossier & Context Plugin

Un plugin WordPress per Cronaca di Viterbo che gestisce dossier tematici con schede riassuntive automatiche, timeline, mappe e sistema di follow-up.

## Caratteristiche

### Custom Post Types
- **Dossier** (`cv_dossier`): Contenuti principali dei dossier
- **Eventi Dossier** (`cv_dossier_event`): Eventi timeline collegati ai dossier

### Meta Fields
#### Dossier
- **Stato**: Aperto/Chiuso
- **Score**: Percentuale promesse mantenute (0-100%)
- **Punti chiave**: Lista di bullet points
- **Attori/Enti**: Elenco soggetti coinvolti

#### Eventi
- **Data**: Data dell'evento (YYYY-MM-DD)
- **Luogo**: Nome del luogo
- **Coordinate**: Latitudine e longitudine per la mappa
- **Dossier di appartenenza**: Parent relationship

### Frontend Features

#### Context Cards
Le schede contesto vengono automaticamente inserite nei post collegati a un dossier e mostrano:
- Badge stato (aperto/chiuso)
- Titolo del dossier
- Score delle promesse mantenute
- Punti chiave principali
- Attori/Enti coinvolti
- Data ultimo evento
- Pulsante per visualizzare tutto il dossier
- Form per seguire gli aggiornamenti

#### Shortcodes

**Context Card**
```
[cv_dossier_context id="123"]
```

**Timeline**
```
[cv_dossier_timeline id="123"]
```

**Mappa**
```
[cv_dossier_map id="123" height="400"]
```

### Sistema Follow
- Tabella database dedicata per i follower
- Form AJAX per l'iscrizione
- Hook `cv_dossier_follow` per integrazioni esterne (es. Brevo)
- Validazione email lato client e server

### Integrazione Mappe
- Utilizza Leaflet.js per le mappe interattive
- Markers automatici basati sugli eventi con coordinate
- Popup informativi con dettagli evento
- Auto-fit della vista per includere tutti i markers

## Installazione

1. Caricare la cartella del plugin in `wp-content/plugins/`
2. Attivare il plugin dal pannello WordPress
3. Il plugin creerà automaticamente:
   - I custom post types
   - La tabella database per i follower
   - Le cartelle CSS e JS con gli asset

## Utilizzo

### Creare un Dossier
1. Andare su "Dossier" nel menu admin
2. Creare un nuovo dossier compilando i campi meta
3. Impostare stato, score, punti chiave e attori

### Aggiungere Eventi Timeline
1. Andare su "Eventi Dossier" nel menu admin
2. Creare un nuovo evento
3. Collegarlo al dossier appropriato
4. Inserire data, luogo e coordinate se disponibili

### Collegare Post a Dossier
1. Durante la modifica di un post
2. Utilizzare la meta box "Dossier collegato" nella sidebar
3. Selezionare il dossier di riferimento
4. La context card apparirà automaticamente nel post

## Personalizzazione

### CSS
Il file `/css/cv-dossier.css` contiene tutti gli stili e può essere personalizzato.

### JavaScript
Il file `/js/cv-dossier.js` gestisce l'interattività frontend e include:
- Gestione form follow
- Tracking analytics (Google Analytics 4)
- Validazione email
- Accessibility enhancements

### Hook per Sviluppatori

**Azione dopo follow dossier**
```php
add_action('cv_dossier_follow', function($dossier_id, $email) {
    // Integrazione con servizi esterni
    // es. aggiungere a lista Brevo/Mailchimp
});
```

## Database

### Tabella wp_cv_dossier_followers
```sql
CREATE TABLE wp_cv_dossier_followers (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    dossier_id BIGINT UNSIGNED NOT NULL,
    email VARCHAR(190) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY dossier_email (dossier_id, email),
    KEY dossier_id (dossier_id)
);
```

## Sicurezza

- Tutti gli input sono sanitizzati
- Nonce verification per le azioni AJAX
- Controlli permissions per edit/save
- Escape output HTML
- Validazione email server-side

## Browser Support

- Chrome/Edge (latest)
- Firefox (latest)  
- Safari (latest)
- IE11+ (con degradazione graceful)

## Dipendenze

- WordPress 5.0+
- jQuery (incluso in WordPress)
- Leaflet.js (caricato automaticamente dal CDN)

## Licenza

GPLv2 or later
## Release process

1. Aggiorna il codice e assicurati che i test/lint passino.
2. Esegui il build script con il bump desiderato, ad esempio:
   ```bash
   bash build.sh --bump=patch
   ```
   oppure imposta manualmente la versione:
   ```bash
   bash build.sh --set-version=1.2.3
   ```
3. Carica lo zip generato in `build/` nell'admin di WordPress oppure allegalo alla release.
4. Per rilasci automatizzati, crea e push un tag `vX.Y.Z` su GitHub: il workflow `build-plugin-zip.yml` produrrà lo zip come artifact `plugin-zip`.
