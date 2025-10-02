# CV Dossier & Context

| | |
|---|---|
| **Name** | CV Dossier & Context |
| **Version** | 1.0.2 |
| **Author** | [Francesco Passeri](https://francescopasseri.com) |
| **Requires WordPress** | 6.0 |
| **Tested up to** | 6.4 |
| **Requires PHP** | 8.0 |
| **License** | GPLv2 or later |
| **Text Domain** | `cv-dossier` |

## What it does

Gestisce dossier tematici con schede riassuntive automatiche, timeline eventi e mappe interattive. Include follow-up email per redazioni WordPress.

## About

CV Dossier & Context estende WordPress con strumenti editoriali per creare e mantenere dossier tematici. Il plugin automatizza schede riassuntive nei post correlati, integra timeline cronologiche degli eventi e visualizza mappe interattive con i luoghi chiave, includendo anche un sistema di follow-up via email per i lettori interessati.

## Features

- Custom Post Type `cv_dossier` per organizzare dossier con stato, punteggio, punti chiave e attori coinvolti.
- Custom Post Type `cv_dossier_event` per eventi della timeline con data, luogo e coordinate geografiche.
- Meta box per collegare rapidamente i post redazionali ai dossier e gestire markers di mappa personalizzati.
- Shortcode dedicati (`[cv_dossier_context]`, `[cv_dossier_timeline]`, `[cv_dossier_map]`) con parametri per id e altezza della mappa.
- Sistema di follow-up AJAX con tabella dedicata `wp_cv_dossier_followers`, validazione email e hook `cv_dossier_follow`.
- Integrazione con Leaflet.js per mappe responsive, inclusa gestione marker, popup e fallback di errore.
- Localizzazione `cv-dossier` con caricamento automatico dei file MO/PO.

## Installation

1. Copia la cartella `cv-dossier-context` in `wp-content/plugins/`.
2. Accedi a **Plugin → Aggiungi nuovo** e attiva *CV Dossier & Context*.
3. All'attivazione viene creata la tabella `wp_cv_dossier_followers` e vengono registrati i custom post type e le meta box necessarie.

## Usage

### Creare e gestire un dossier
1. Vai in **Dossier** e crea un nuovo elemento.
2. Compila stato, punteggio, punti chiave e attori coinvolti.
3. Seleziona quali componenti (scheda contesto, timeline, mappa) rendere visibili automaticamente.

### Gestire eventi di timeline
1. Vai in **Eventi Dossier** per aggiungere eventi cronologici.
2. Inserisci data, luogo, eventuali coordinate e collega l'evento al dossier.
3. Gli eventi popolano automaticamente timeline e mappa del dossier.

### Collegare un post a un dossier
1. Modifica un post standard e usa la meta box **Dossier collegato**.
2. Seleziona il dossier pertinente per mostrare la scheda riassuntiva nel contenuto.
3. Facoltativamente gestisci markers di mappa e stato di visualizzazione.

### Shortcode disponibili
- `[cv_dossier_context id="123"]` visualizza la scheda riassuntiva del dossier.
- `[cv_dossier_timeline id="123"]` mostra la timeline degli eventi.
- `[cv_dossier_map id="123" height="400"]` rende disponibile una mappa Leaflet con i marker degli eventi.

## Hooks & Filters

| Tipo | Nome | Descrizione |
|------|------|-------------|
| Action | `cv_dossier_follow` | Scatta dopo una nuova iscrizione di follow-up e riceve `$dossier_id` e `$email`. |
| Filter | `cv_dossier_timeline_item_content` | Permette di modificare il contenuto di ciascun evento nella timeline prima del rendering. |

## Support

Per assistenza e richieste personalizzate visita [https://francescopasseri.com](https://francescopasseri.com) e utilizza i canali di contatto disponibili.

## Development scripts

Esegui le attività ripetibili di sincronizzazione e changelog con i comandi:

```bash
composer sync:author # aggiorna i metadati autore (usa APPLY=true per scrivere)
composer sync:docs   # sincronizza documentazione (usa APPLY=true per scrivere)
composer changelog:from-git
```

## Assumptions

- Compatibilità WordPress verificata fino alla versione 6.4 sulla base dell'ambiente disponibile durante la revisione.
- L'assistenza è fornita tramite il sito personale di Francesco Passeri in assenza di una pagina issue pubblica dedicata.
