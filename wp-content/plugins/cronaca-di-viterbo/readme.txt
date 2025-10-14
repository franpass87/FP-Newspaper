=== Cronaca di Viterbo ===
Contributors: francescopasseri
Tags: giornalismo, proposte, eventi, dossier, wpbakery
Requires at least: 6.0
Tested up to: 6.4
Requires PHP: 8.0
Stable tag: 1.6.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Plugin modulare per il giornale "Cronaca di Viterbo": dossier, proposte cittadini, eventi, ambasciatori, WPBakery, GA4 e SEO.

== Description ==

**Cronaca di Viterbo** è un plugin WordPress completo e modulare progettato per testate giornalistiche locali. Gestisce:

* **Dossier**: Inchieste giornalistiche con schede riassuntive
* **Proposte**: Idee dei cittadini con moderazione e votazione
* **Eventi**: Micro-eventi, riunioni e serate
* **Persone**: Ambasciatori civici e redazione

= Caratteristiche principali =

* ✅ **Custom Post Types**: Dossier, Proposte, Eventi, Persone
* ✅ **Tassonomie**: Quartiere (gerarchica), Tematica (flat)
* ✅ **Shortcodes + WPBakery**: Integrazione completa con Visual Composer
* ✅ **AJAX Forms**: Invio proposte con rate-limiting e moderazione
* ✅ **Votazioni**: Sistema like con cooldown orario
* ✅ **GA4 Tracking**: Eventi personalizzati per analytics
* ✅ **SEO/Schema.org**: JSON-LD per NewsArticle, Event, Person
* ✅ **Ruoli personalizzati**: Editor, Moderatore, Reporter
* ✅ **Migrazioni**: Compatibilità retroattiva con versioni precedenti
* ✅ **Sicurezza**: Nonce, sanitizzazione, rate-limiting

= Shortcodes disponibili =

* `[cdv_proposta_form]` - Form per invio proposte
* `[cdv_proposte]` - Lista proposte con votazione
* `[cdv_dossier_hero]` - Hero section per dossier
* `[cdv_eventi]` - Lista eventi filtrabili
* `[cdv_persona_card]` - Card singola persona

Tutti gli shortcodes sono mappati come elementi WPBakery nel gruppo "Cronaca di Viterbo".

= AJAX Actions =

* `cdv_submit_proposta` - Invio proposta (rate-limit 60s)
* `cdv_vote_proposta` - Voto proposta (cooldown 1h)

= GA4 Events =

* `proposta_submitted` - Proposta inviata
* `proposta_voted` - Proposta votata
* `dossier_read_60s` - Dossier letto per 60s

= Ruoli =

* **CdV Editor**: Gestione completa di tutti i CPT
* **CdV Moderatore**: Solo moderazione proposte
* **CdV Reporter**: Creazione bozze dossier/eventi

== Installation ==

1. Carica il plugin nella directory `/wp-content/plugins/cronaca-di-viterbo/`
2. Attiva il plugin dal menu 'Plugin' in WordPress
3. Vai su **Moderazione > Impostazioni** per configurare
4. Crea quartieri e tematiche dalle rispettive voci di menu
5. Inizia a creare dossier, eventi e persone
6. Usa gli shortcodes nelle pagine o con WPBakery

== Frequently Asked Questions ==

= Serve WPBakery per funzionare? =

No, gli shortcodes funzionano indipendentemente. WPBakery aggiunge solo integrazione visuale.

= Come funziona la moderazione proposte? =

Le proposte inviate dai cittadini entrano in stato "pending". Vai su **Moderazione** per approvarle/modificarle.

= Come aggiungo tracking GA4? =

Il plugin invia eventi al dataLayer. Assicurati di avere GTM o GA4 configurato sul sito.

= È compatibile con il vecchio plugin CV Dossier? =

Sì! Il plugin include migrazioni automatiche e shim per retrocompatibilità.

== Changelog ==

= 1.6.0 - 2025-10-13 =
* 🔒 **Security Audit Completo**: 46 bug risolti in 11 iterazioni
* 🔴 **CRITICAL**: Risolte 5 race conditions con UPDATE atomici SQL
* 🟠 **HIGH**: Chiuse 9 vulnerabilità di sicurezza (XSS, upload validation, input sanitization)
* 🟡 **MEDIUM**: 22 fix per robustezza (JSON, WP_Error, null checks)
* 🟢 **LOW**: 10 best practice SQL (backticks su tutte le query)
* ✅ **28 file ottimizzati** - Enterprise Production-Ready

= 1.5.0 - 2025-10-12 =
* Aggiunto sistema reputazione e badge utenti
* Aggiunta votazione ponderata per proposte
* Aggiunto AI Chatbot (OpenAI/Claude)
* Aggiunte Video Stories (Instagram/TikTok)
* Aggiunte gallerie foto avanzate
* Performance ottimizzate con caching

= 1.0.0 - 2025-10-09 =
* 🎉 **Release iniziale**
* feat: CPT Dossier, Proposta, Evento, Persona
* feat: Tassonomie Quartiere e Tematica
* feat: Shortcodes completi con WPBakery integration
* feat: AJAX forms con rate-limiting e sicurezza
* feat: Sistema votazione proposte con cooldown
* feat: GA4 tracking events
* feat: JSON-LD Schema.org per SEO
* feat: Ruoli personalizzati (Editor, Moderatore, Reporter)
* feat: Coda moderazione admin
* feat: Migrazioni da plugin precedente
* feat: Compatibilità retroattiva

== Roadmap 1.1 ==

* [ ] RSVP per eventi con capienza soft
* [ ] Cloudflare Turnstile / reCAPTCHA
* [ ] Mappe Leaflet per eventi/dossier
* [ ] Sistema reputazione utenti
* [ ] Import/Export CSV

== Upgrade Notice ==

= 1.0.0 =
Prima release stabile. Backup consigliato prima dell'aggiornamento.

== Screenshots ==

1. Form invio proposta con validazione
2. Lista proposte con votazioni
3. Hero dossier con metadata
4. Coda moderazione admin
5. Impostazioni plugin

== Credits ==

Sviluppato da **Francesco Passeri** per Cronaca di Viterbo.

== Privacy Policy ==

Il plugin raccoglie:
* IP address per rate-limiting (transient 60s-1h)
* Dati form proposte (titolo, contenuto, tassonomie)
* Eventi GA4 (anonimi)

Nessun dato viene inviato a servizi esterni.
