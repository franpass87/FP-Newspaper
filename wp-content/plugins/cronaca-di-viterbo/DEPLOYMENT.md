# Deployment Guide - Cronaca di Viterbo v1.0.0

Guida completa per il deployment del plugin in ambiente di produzione.

## 📋 Pre-Deployment Checklist

### Requisiti Sistema
- ✅ PHP 8.0+
- ✅ WordPress 6.0+
- ✅ MySQL 5.7+ / MariaDB 10.3+
- ✅ Salient Theme (per WPBakery integration)
- ✅ WPBakery Page Builder (opzionale)

### Verifica Locale
```bash
# 1. Verifica Composer
composer validate

# 2. Build produzione
composer build

# 3. Verifica coding standards
composer phpcs

# 4. Analisi statica
composer phpstan
```

---

## 🚀 Deployment Steps

### Step 1: Backup Completo

**Database:**
```bash
# Via WP-CLI
wp db export backup-pre-cdv-$(date +%Y%m%d).sql

# Via phpMyAdmin
# Esporta tutte le tabelle wp_*
```

**Files:**
```bash
# Backup plugin vecchio
cd wp-content/plugins
tar -czf cv-dossier-context-backup-$(date +%Y%m%d).tar.gz cv-dossier-context/

# Backup tema (se modificato)
tar -czf salient-backup-$(date +%Y%m%d).tar.gz ../../themes/salient/
```

---

### Step 2: Upload Plugin

**Via FTP/SFTP:**
```bash
# Upload cartella completa
cronaca-di-viterbo/ → /wp-content/plugins/cronaca-di-viterbo/

# Verifica permessi
chmod 755 wp-content/plugins/cronaca-di-viterbo
chmod 644 wp-content/plugins/cronaca-di-viterbo/cronaca-di-viterbo.php
```

**Via Git Deploy:**
```bash
# Clone in server
cd wp-content/plugins
git clone [repo-url] cronaca-di-viterbo
cd cronaca-di-viterbo
composer install --no-dev --optimize-autoloader
```

**Via WP-CLI:**
```bash
# Se pacchettizzato come ZIP
wp plugin install cronaca-di-viterbo.zip
```

---

### Step 3: Disattiva Plugin Vecchio

```bash
# Via WP-CLI
wp plugin deactivate cv-dossier-context

# Via Admin
# Dashboard > Plugin > Disattiva "CV Dossier & Context"
```

⚠️ **NON eliminare ancora il plugin vecchio!** Serve per la migrazione.

---

### Step 4: Attiva Nuovo Plugin

```bash
# Via WP-CLI
wp plugin activate cronaca-di-viterbo

# Via Admin  
# Dashboard > Plugin > Attiva "Cronaca di Viterbo"
```

**Cosa succede all'attivazione:**
1. ✅ Esegue migrazione automatica DB (`Services\Migration::run()`)
2. ✅ Crea ruoli personalizzati (CdV Editor, Moderatore, Reporter)
3. ✅ Registra CPT e Tassonomie
4. ✅ Flush rewrite rules

---

### Step 5: Verifica Migrazione

```bash
# Controlla versione DB
wp option get cdv_db_version
# Output atteso: 1.0.0

# Controlla CPT migrati
wp post list --post_type=cdv_dossier --format=count
# Deve corrispondere al numero di cv_dossier

# Verifica meta migrati
wp db query "SELECT COUNT(*) FROM wp_postmeta WHERE meta_key LIKE '_cdv_%'"
# Deve essere > 0 se c'erano meta _cv_
```

---

### Step 6: Configurazione Admin

1. **Impostazioni Plugin:**
   ```
   Dashboard > Moderazione > Impostazioni
   - ✅ Abilita tracking GA4
   - ✅ Abilita JSON-LD Schema.org
   ```

2. **Crea Tassonomie Base:**
   ```bash
   # Via WP-CLI
   wp term create cdv_quartiere "Centro" --porcelain
   wp term create cdv_quartiere "Periferia" --parent=$(wp term create cdv_quartiere "Zona Nord" --porcelain)
   
   wp term create cdv_tematica "Mobilità" --porcelain
   wp term create cdv_tematica "Ambiente" --porcelain
   wp term create cdv_tematica "Cultura" --porcelain
   ```

3. **Assegna Ruoli:**
   ```bash
   # CdV Editor (full access)
   wp user set-role editor_user cdv_editor
   
   # CdV Moderatore (solo moderazione)
   wp user set-role moderatore_user cdv_moderatore
   
   # CdV Reporter (solo bozze)
   wp user set-role reporter_user cdv_reporter
   ```

---

### Step 7: Aggiorna Shortcodes

**Manuale (Cerca & Sostituisci):**
```
Dashboard > Strumenti > Cerca e Sostituisci (plugin Better Search Replace)

Cerca: [cv_proposta_form
Sostituisci: [cdv_proposta_form

Cerca: [cv_proposte
Sostituisci: [cdv_proposte

Cerca: [cv_dossier_hero  
Sostituisci: [cdv_dossier_hero
```

**Via WP-CLI:**
```bash
# Backup prima!
wp search-replace '[cv_proposta_form' '[cdv_proposta_form' --dry-run

# Applica se ok
wp search-replace '[cv_proposta_form' '[cdv_proposta_form'
wp search-replace '[cv_proposte' '[cdv_proposte'
wp search-replace '[cv_dossier_hero' '[cdv_dossier_hero'
```

---

### Step 8: Ricrea Elementi WPBakery

1. **Apri pagine con WPBakery:**
   ```
   Pagine > [Pagina] > Backend Editor
   ```

2. **Sostituisci elementi deprecati:**
   - Rimuovi vecchi elementi "CV Dossier"
   - Aggiungi nuovi da categoria "Cronaca di Viterbo"

3. **Verifica parametri:**
   - Controlla che filtri quartiere/tematica siano corretti
   - Salva e pubblica

---

### Step 9: Test Funzionalità

#### Test Form Proposta
```bash
# 1. Visita pagina con [cdv_proposta_form]
# 2. Compila form (senza privacy) → deve bloccare
# 3. Compila con privacy → successo
# 4. Riprova entro 60s → rate-limit attivo
# 5. Verifica proposta in pending
```

#### Test Votazione
```bash
# 1. Visita pagina con [cdv_proposte]
# 2. Clicca 👍 su una proposta → voto incrementato
# 3. Riprova → cooldown 1h attivo
```

#### Test JSON-LD
```bash
# Verifica su dossier singolo
curl -s https://sito.test/dossier/esempio/ | grep '@type'
# Output: "@type": "NewsArticle"

# Valida con Google
# https://search.google.com/test/rich-results
```

#### Test GA4
```bash
# 1. Apri console browser (F12)
# 2. Digita: dataLayer
# 3. Invia proposta → verifica evento proposta_submitted
# 4. Vota proposta → verifica evento proposta_voted
# 5. Leggi dossier 60s → verifica evento dossier_read_60s
```

---

### Step 10: Pulizia (dopo 7 giorni di test)

```bash
# Solo se tutto funziona correttamente!

# 1. Elimina plugin vecchio
wp plugin delete cv-dossier-context

# 2. Pulisci opzioni legacy (opzionale)
wp db query "DELETE FROM wp_options WHERE option_name LIKE 'cv_%'"

# 3. Verifica shortcodes deprecati rimasti
wp db query "SELECT ID, post_title FROM wp_posts WHERE post_content LIKE '%[cv_%' AND post_status='publish'"
# Se ce ne sono, aggiornali manualmente
```

---

## 🔧 Troubleshooting

### Problema: Migrazione non eseguita
```bash
# Forza riesecuzione
wp option delete cdv_db_version
wp plugin deactivate cronaca-di-viterbo
wp plugin activate cronaca-di-viterbo
```

### Problema: CPT non visibili
```bash
# Flush rewrite rules
wp rewrite flush
```

### Problema: 404 su dossier
```bash
# Verifica slug
wp post list --post_type=cdv_dossier --fields=ID,post_name

# Re-save permalinks
# Dashboard > Impostazioni > Permalink > Salva
```

### Problema: AJAX non funziona
```bash
# Verifica nonce in console
console.log(cdvData.nonce)

# Verifica endpoint
console.log(cdvData.ajaxUrl)
# Output: https://sito.test/wp-admin/admin-ajax.php

# Verifica CORS (se API esterna)
# Deve essere same-origin
```

### Problema: WPBakery elementi mancanti
```bash
# Verifica WPBakery attivo
wp plugin is-active js_composer
# Se no: wp plugin activate js_composer

# Verifica costante
wp eval 'echo defined("WPB_VC_VERSION") ? "OK" : "NO";'
```

---

## 📊 Monitoring Post-Deploy

### Metriche da Monitorare (7 giorni)

1. **Proposte:**
   ```bash
   wp post list --post_type=cdv_proposta --post_status=pending --format=count
   # Dovrebbe crescere se form funziona
   ```

2. **Votazioni:**
   ```bash
   wp post meta list [ID_PROPOSTA] --keys=_cdv_votes
   # Verifica incrementi
   ```

3. **Errori PHP:**
   ```bash
   tail -f /var/log/php-error.log | grep cdv
   ```

4. **GA4 Events (Google Analytics):**
   - Verifica eventi custom in Realtime
   - Confronta con baseline pre-migrazione

5. **Performance:**
   ```bash
   # Query Monitor plugin
   # Verifica tempo caricamento pagine con shortcodes
   ```

---

## 🔄 Rollback Plan

Se qualcosa va storto:

```bash
# 1. Disattiva nuovo plugin
wp plugin deactivate cronaca-di-viterbo

# 2. Riattiva vecchio plugin
wp plugin activate cv-dossier-context

# 3. Restore DB backup
wp db import backup-pre-cdv-YYYYMMDD.sql

# 4. Flush rewrite
wp rewrite flush

# 5. Pulisci cache
wp cache flush
```

---

## ✅ Post-Deploy Success Criteria

- [ ] CPT cdv_dossier, cdv_proposta, cdv_evento, cdv_persona visibili
- [ ] Tassonomie quartiere e tematica funzionanti
- [ ] Form proposta accetta invii (rate-limit attivo)
- [ ] Votazioni funzionanti (cooldown 1h)
- [ ] JSON-LD presente su singoli (validato Google)
- [ ] Eventi GA4 nel dataLayer
- [ ] WPBakery elementi visibili e funzionanti
- [ ] Coda moderazione accessibile e popolata
- [ ] Ruoli personalizzati assegnabili
- [ ] Nessun errore PHP nei log
- [ ] Performance stabile (< +100ms rispetto a baseline)

---

## 📞 Support

- **Documentazione**: `/wp-content/plugins/cronaca-di-viterbo/README.md`
- **Hooks**: `/wp-content/plugins/cronaca-di-viterbo/HOOKS.md`
- **Changelog**: `/wp-content/plugins/cronaca-di-viterbo/CHANGELOG.md`
- **Issues**: [Repository Issues]

---

**Deployment checklist completata:** ___/___  
**Deploy by:** _______________  
**Date:** _______________  
**Environment:** Production / Staging  
**Rollback tested:** Yes / No
