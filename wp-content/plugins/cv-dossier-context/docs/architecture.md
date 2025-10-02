# Architecture

## Custom post types

- **`cv_dossier`** – pubblica, visibile nel REST API, archivio dedicato `dossier`. Supporta titolo, editor, estratto, autore, thumbnail e meta personalizzate per stato (`_cv_status`), punteggio (`_cv_score`), punti chiave (`_cv_facts`), attori (`_cv_actors`), markers mappa (`_cv_map_markers`) e flag di visibilità (`_cv_show_context`, `_cv_show_timeline`, `_cv_show_map`).
- **`cv_dossier_event`** – privato lato frontend, con UI amministrativa. Conserva data (`_cv_date`), luogo (`_cv_place`), coordinate (`_cv_lat`, `_cv_lng`) e relazione padre con il dossier.

## Meta box e opzioni

- **Dettagli dossier** – gestione stato, punteggio, punti chiave, attori, attivazione scheda/timeline/mappa e altezza mappa.
- **Dettagli evento** – inserimento di data, luogo, coordinate e dossier associato.
- **Dossier collegato (post standard)** – selezione rapida del dossier e gestione markers specifici per articolo con validazione e sanificazione.
- **Mappa interattiva (post standard)** – controllo della mappa contestuale e marker personalizzati con sanitizzazione approfondita e validazioni su latitudine/longitudine.

## Database

- Tabella dedicata `wp_cv_dossier_followers` creata all'attivazione per memorizzare email e ID dossier dei follower. Chiavi: primaria `id`, unique `dossier_email`, indice `dossier_id`.

## Shortcodes

- `cv_dossier_context` – genera la scheda riassuntiva del dossier con stato, punteggio, punti chiave, attori e pulsanti di azione.
- `cv_dossier_timeline` – costruisce una timeline verticale degli eventi associati ordinati per data.
- `cv_dossier_map` – produce una mappa Leaflet con marker derivati dagli eventi o da marker personalizzati, supporta parametro `height` e fallback di errore.

## Hooks chiave

- **Action `cv_dossier_follow`** – eseguita dopo l'iscrizione al follow-up, con parametri `$dossier_id` e `$email` per integrazioni esterne.
- **Filter `cv_dossier_timeline_item_content`** – permette di modificare il contenuto del singolo evento prima del rendering frontend.

## Flussi principali

1. **Attivazione** – crea tabella follower, registra CPT, salva versione in opzione `cv_dossier_version`.
2. **Salvataggio dossier/evento/post** – validazione nonce, sanificazione dei campi, aggiornamento metadati, generazione markers e gestione toggle.
3. **Frontend** – enqueuing condizionale di CSS/JS, registrazione Leaflet da CDN, localizzazione script con nonce/AJAX URL, generazione markup per schede, timeline, mappe e follow form.
4. **Follow-up AJAX** – endpoint `cv_follow_dossier` con verifica nonce, validazioni email, controllo duplicati, memorizzazione nella tabella dedicata, invio notifiche e trigger dell'action `cv_dossier_follow`.

## Assets e localizzazione

- CSS principale in `css/cv-dossier.css`, JavaScript in `js/cv-dossier.js` con gestione form, GA4 events, validazioni e helper per mappe.
- Text domain `cv-dossier` caricato via `load_plugin_textdomain` con path `languages/`.
