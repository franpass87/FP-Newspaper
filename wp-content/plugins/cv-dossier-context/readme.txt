=== CV Dossier & Context ===
Contributors: franpass87, francescopasseri
Tags: dossier, timeline, map, journalism, follow-up
Requires at least: 6.0
Tested up to: 6.4
Requires PHP: 8.0
Stable tag: 1.0.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Gestisce dossier tematici con schede riassuntive automatiche, timeline eventi e mappe interattive. Include follow-up email per redazioni WordPress.

== Description ==

CV Dossier & Context fornisce strumenti editoriali per redazioni WordPress che seguono progetti o inchieste nel tempo. Il plugin automatizza le schede riassuntive nei post collegati a un dossier, costruisce timeline cronologiche degli eventi e crea mappe Leaflet con i luoghi principali, includendo un sistema di follow-up email per chi desidera ricevere aggiornamenti.

= Funzionalità principali =
* Custom Post Type `cv_dossier` con stato, punteggio, punti chiave e attori coinvolti.
* Custom Post Type `cv_dossier_event` per gestire eventi della timeline con data, luogo e coordinate.
* Meta box su dossier, eventi e post standard per controllare elementi contestuali e markers della mappa.
* Shortcode `[cv_dossier_context]`, `[cv_dossier_timeline]` e `[cv_dossier_map]` per inserire schede, timeline e mappe in qualsiasi contenuto.
* Sistema di follow-up AJAX con tabella dedicata `wp_cv_dossier_followers`, validazione email e hook `cv_dossier_follow` per integrazioni.
* Localizzazione `cv-dossier` con caricamento automatico dei file di traduzione.

= Text Domain =
Il plugin utilizza il text domain `cv-dossier` con percorsi di traduzione in `languages/`.

== Installation ==

1. Carica la cartella `cv-dossier-context` in `wp-content/plugins/` oppure installa il pacchetto ZIP dal pannello WordPress.
2. Attiva il plugin tramite **Plugin → Aggiungi nuovo**.
3. Configura i dossier in **Dossier**, gli eventi in **Eventi Dossier** e collega i post tramite la relativa meta box.

== Frequently Asked Questions ==

= Come posso mostrare la scheda di contesto in un articolo? =
Collega l'articolo a un dossier tramite la meta box **Dossier collegato** oppure usa lo shortcode `[cv_dossier_context id="123"]` nel contenuto.

= È possibile personalizzare l'altezza della mappa? =
Sì, imposta l'altezza desiderata nella meta box del dossier oppure passa il parametro `height` allo shortcode `[cv_dossier_map]`.

= Posso integrare il follow-up con un servizio esterno? =
Utilizza l'hook `cv_dossier_follow` per intercettare nuove iscrizioni e sincronizzarle con servizi come newsletter o CRM.

== Screenshots ==
1. Scheda riassuntiva del dossier con stato, punteggio e punti chiave.
2. Timeline degli eventi del dossier con date e luoghi.
3. Mappa interattiva con markers generati dagli eventi.

== Support ==

Per supporto editoriale o tecnico visita [https://francescopasseri.com](https://francescopasseri.com) e invia una richiesta tramite i contatti disponibili.

== Changelog ==

Consulta il file `CHANGELOG.md` per un elenco completo delle modifiche.
