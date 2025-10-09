# CV Dossier & Context overview

Gestisce dossier tematici con schede riassuntive automatiche, timeline eventi e mappe interattive. Include follow-up email per redazioni WordPress.

## Key capabilities

- **Editorial dossier hub** – Custom post type `cv_dossier` con stato, punteggio, punti chiave, attori e opzioni per mostrare scheda, timeline e mappa.
- **Event timeline management** – Custom post type `cv_dossier_event` per eventi cronologici con data, luogo, coordinate e relazione con il dossier.
- **Automatic context cards** – Schede riassuntive inserite nei post collegati con punti chiave, ultimi eventi e pulsante di approfondimento.
- **Interactive mapping** – Mappe Leaflet generate da eventi e markers personalizzati, con parametri di altezza configurabili.
- **Audience follow-up** – Form AJAX con tabella dedicata `wp_cv_dossier_followers`, validazione email e hook `cv_dossier_follow` per invii a servizi esterni.
- **Localization ready** – Text domain `cv-dossier` con caricamento automatico dei file di traduzione.

## Quick start

1. Attiva il plugin e verifica la presenza dei menu **Dossier** ed **Eventi Dossier** nell'area amministrativa.
2. Crea il primo dossier compilando stato, punteggio, punti chiave, attori e preferenze di visualizzazione.
3. Aggiungi eventi alla timeline per arricchire la scheda e generare markers di mappa.
4. Collega i post esistenti al dossier tramite la meta box dedicata o inserisci gli shortcode `[cv_dossier_context]`, `[cv_dossier_timeline]` e `[cv_dossier_map]` dove necessario.
5. Personalizza gli stili in `css/cv-dossier.css` e i comportamenti JavaScript in `js/cv-dossier.js` secondo le linee editoriali della redazione.
