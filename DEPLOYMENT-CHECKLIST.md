# 🚀 Deployment Checklist - Cronaca di Viterbo v1.5.0

**Data**: 2025-10-09  
**Versione**: 1.5.0  
**Environment**: Production

---

## ✅ Pre-Deployment (Local/Staging)

### Verifica Codice
- [ ] ✅ Tutti i file PHP salvati senza errori sintassi
- [ ] ✅ Bootstrap aggiornato con nuove dipendenze
- [ ] ✅ JavaScript aggiornato (cdv.js)
- [ ] ✅ CSS esteso creato (cdv-extended.css)
- [ ] ✅ Template email creati (3 template)
- [ ] ✅ Versione aggiornata (1.5.0)

### Test Funzionalità Local
- [ ] Test form proposta → Submit AJAX
- [ ] Test voto proposta → Cooldown 1h
- [ ] Test form petizione → Firma + verifica duplicati
- [ ] Test milestone petizione → Email notification
- [ ] Test sondaggio → Voto + risultati real-time
- [ ] Test doppio voto sondaggio → Blocco
- [ ] Test dashboard → Statistiche corrette
- [ ] Test mappa → Markers visualizzati
- [ ] Test profilo utente → Badge e punti
- [ ] Test votazione ponderata → Calcolo peso

### Database
- [ ] ✅ 4 tabelle create: petizioni_firme, sondaggi_voti, voti_dettagli, subscribers
- [ ] Verifica indici database creati correttamente
- [ ] Test query performance (< 100ms)

---

## 📦 Backup Pre-Deployment

### Database Backup
```bash
# Via WP-CLI
wp db export backup-pre-v1.5-$(date +%Y%m%d-%H%M).sql

# Via phpMyAdmin
# Esporta tutte le tabelle con dati
# Salva in: /backups/db/cronaca-viterbo-YYYYMMDD.sql
```

- [ ] Backup database creato
- [ ] Backup verificato (dimensione > 0)
- [ ] Backup scaricato in locale

### Files Backup
```bash
# Backup plugin
cd wp-content/plugins
tar -czf ../../backups/cronaca-di-viterbo-v1.0-$(date +%Y%m%d).tar.gz cronaca-di-viterbo/

# Backup tema (se personalizzato)
tar -czf ../../backups/salient-theme-$(date +%Y%m%d).tar.gz salient/
```

- [ ] Backup plugin creato
- [ ] Backup tema creato (se modificato)
- [ ] Backup scaricato in locale

---

## 🚀 Deployment Steps

### 1. Upload Files

**Via FTP/SFTP:**
```bash
# Upload cartella plugin
/wp-content/plugins/cronaca-di-viterbo/
```

**Via Git:**
```bash
cd /path/to/wp-content/plugins
git pull origin main
# oppure
git checkout tags/v1.5.0
```

- [ ] File caricati correttamente
- [ ] Permissions corretti (755 directory, 644 file)
- [ ] File proprietà corretta (www-data:www-data)

### 2. Disattiva/Riattiva Plugin

**Via WP-CLI:**
```bash
wp plugin deactivate cronaca-di-viterbo
wp plugin activate cronaca-di-viterbo
```

**Via Admin WordPress:**
- Plugin → Plugin installati → Cronaca di Viterbo → Disattiva
- Attendi 5 secondi
- Attiva

- [ ] Plugin disattivato
- [ ] Plugin riattivato
- [ ] Nessun errore visualizzato

### 3. Verifica Tabelle Database

```bash
wp db query "SHOW TABLES LIKE 'wp_cdv_%'"
```

**Output atteso:**
```
wp_cdv_petizioni_firme
wp_cdv_sondaggi_voti
wp_cdv_voti_dettagli
wp_cdv_subscribers
```

- [ ] 4 nuove tabelle esistono
- [ ] Indici creati correttamente
- [ ] Nessun errore query

### 4. Flush Rewrite Rules

```bash
wp rewrite flush --hard
```

- [ ] Rewrite rules aggiornati
- [ ] Permalink funzionanti

### 5. Verifica Cron

```bash
# Lista cron events
wp cron event list | grep cdv_weekly_digest

# Output atteso:
# cdv_weekly_digest   [timestamp]   weekly
```

- [ ] Cron digest schedulato
- [ ] Prossima esecuzione corretta (lunedì 9:00)

---

## ✅ Post-Deployment Verification

### Test Funzionalità Critiche

#### 1. Proposte (Esistenti)
```bash
# Test via browser
# 1. Vai su una pagina con [cdv_proposta_form]
# 2. Compila form
# 3. Verifica submit AJAX
# 4. Controlla DB: SELECT * FROM wp_posts WHERE post_type='cdv_proposta' ORDER BY ID DESC LIMIT 1
```
- [ ] Form proposta funzionante
- [ ] Voto proposta funzionante
- [ ] Rate limiting attivo

#### 2. Petizioni (NUOVO)
```bash
# Test via browser
# 1. Crea petizione via admin
# 2. Aggiungi shortcode [cdv_petizione_form id="X"]
# 3. Firma petizione
# 4. Verifica DB: SELECT * FROM wp_cdv_petizioni_firme ORDER BY ID DESC LIMIT 1
```
- [ ] Creazione petizione OK
- [ ] Form firma funzionante
- [ ] Firma salvata in DB
- [ ] Barra progresso aggiornata
- [ ] Email milestone (test con 50 firme)

#### 3. Sondaggi (NUOVO)
```bash
# Test via browser
# 1. Crea sondaggio via admin con 3 opzioni
# 2. Aggiungi shortcode [cdv_sondaggio_form id="X"]
# 3. Vota
# 4. Verifica DB: SELECT * FROM wp_cdv_sondaggi_voti ORDER BY ID DESC LIMIT 1
```
- [ ] Creazione sondaggio OK
- [ ] Voto funzionante
- [ ] Risultati real-time aggiornati
- [ ] Doppio voto bloccato

#### 4. Dashboard (NUOVO)
```bash
# Test via browser
# 1. Vai su pagina Dashboard CdV (admin)
# 2. Verifica statistiche caricate
# 3. Aggiungi [cdv_dashboard] in pagina pubblica
```
- [ ] Dashboard admin caricato
- [ ] 6 statistiche visualizzate
- [ ] Grafici quartieri/tematiche funzionanti
- [ ] Shortcode pubblico funzionante

#### 5. Profilo Utente (NUOVO)
```bash
# Test via browser
# 1. Pubblica proposta da utente test
# 2. Verifica punti: SELECT meta_value FROM wp_usermeta WHERE meta_key='cdv_points' AND user_id=X
# 3. Aggiungi [cdv_user_profile] in pagina
```
- [ ] Punti assegnati (+50 proposta)
- [ ] Badge "Primo Cittadino" assegnato
- [ ] Profilo pubblico visualizzato

#### 6. Mappa (NUOVO)
```bash
# Test via browser
# 1. Aggiungi coordinate a proposta/evento (lat/lng)
# 2. Aggiungi [cdv_mappa tipo="proposte"]
# 3. Verifica marker sulla mappa
```
- [ ] Mappa Leaflet caricata
- [ ] Marker visualizzati
- [ ] Popup funzionanti
- [ ] Filtri funzionanti

#### 7. Votazione Ponderata (NUOVO)
```bash
# Test via browser
# 1. Imposta quartiere residenza utente
# 2. Vota proposta stesso quartiere
# 3. Verifica DB: SELECT * FROM wp_cdv_voti_dettagli ORDER BY ID DESC LIMIT 1
# 4. Controlla weight > 1.0
```
- [ ] Peso voto calcolato (residente = 2.0x)
- [ ] Voto salvato in dettagli
- [ ] Meta box admin mostra breakdown

---

## 🔧 Configurazione Post-Deployment

### 1. Impostazioni Plugin
- [ ] Vai su **Moderazione > Impostazioni**
- [ ] Abilita GA4 tracking (se necessario)
- [ ] Abilita JSON-LD Schema
- [ ] Salva impostazioni

### 2. Crea Tassonomie Base
```bash
# Via WP-CLI
wp term create cdv_quartiere "Centro" --description="Quartiere centro storico"
wp term create cdv_quartiere "Periferia Nord"
wp term create cdv_tematica "Mobilità"
wp term create cdv_tematica "Ambiente"
wp term create cdv_tematica "Cultura"
```

- [ ] Quartieri creati (almeno 3)
- [ ] Tematiche create (almeno 5)

### 3. Configura Coordinate GPS (Opzionale)
```bash
# Esempio: aggiungi coordinate a proposte esistenti
wp post meta update 123 _cdv_latitudine 42.4175
wp post meta update 123 _cdv_longitudine 12.1089
```

- [ ] Coordinate aggiunte a post test
- [ ] Mappa visualizza marker

### 4. Configura Utenti Test
```bash
# Imposta quartiere residenza
wp user meta update 1 cdv_quartiere_residenza 5 # ID term quartiere

# Verifica utente
wp user meta update 1 cdv_verified 1
```

- [ ] Quartiere residenza impostato
- [ ] Badge verificato (opzionale)

---

## 📧 Test Email

### 1. Email Risposta Amministrazione
```bash
# 1. Crea risposta amministrazione a una proposta
# 2. Pubblica
# 3. Controlla email autore proposta
```
- [ ] Email ricevuta
- [ ] Template HTML corretto
- [ ] Link funzionanti

### 2. Email Milestone Petizione
```bash
# 1. Firma petizione fino a 50 firme
# 2. Controlla email autore petizione
```
- [ ] Email milestone ricevuta
- [ ] Statistiche corrette
- [ ] Bottoni social funzionanti

### 3. Email Digest Settimanale
```bash
# Trigger manuale cron
wp cron event run cdv_weekly_digest
```
- [ ] Email digest inviata (controlla DB subscribers)
- [ ] Contenuti corretti
- [ ] Link disiscrizione funzionante

---

## 🔍 Monitoring Post-Deployment

### Prima Ora
- [ ] Monitor error logs: `tail -f /var/log/apache2/error.log`
- [ ] Monitor PHP errors: `wp-content/debug.log`
- [ ] Check database queries slow: MySQL slow query log
- [ ] Test AJAX endpoints (petizioni, sondaggi)

### Primo Giorno
- [ ] Verifica metriche GA4 (se configurato)
- [ ] Check utilizzo CPU/RAM server
- [ ] Verifica crescita database size
- [ ] Test carico pagina (< 2s)
- [ ] Raccogliere feedback utenti

### Prima Settimana
- [ ] Analizza log errori giornalieri
- [ ] Verifica cron digest (lunedì mattina)
- [ ] Monitor rate petizioni/sondaggi
- [ ] Check engagement badge/punti
- [ ] Performance monitoring continuo

---

## 🐛 Troubleshooting

### Se qualcosa va storto...

#### Errore 500 dopo attivazione
```bash
# 1. Controlla error log
tail -100 /var/log/apache2/error.log

# 2. Verifica PHP version
php -v  # Deve essere >= 8.0

# 3. Disattiva plugin
wp plugin deactivate cronaca-di-viterbo

# 4. Ripristina backup
wp db import backup-pre-v1.5-YYYYMMDD.sql
```

#### Tabelle non create
```bash
# Forza creazione tabelle
wp eval 'CdV\PostTypes\Petizione::create_firme_table();'
wp eval 'CdV\PostTypes\Sondaggio::create_votes_table();'
wp eval 'CdV\Services\VotazioneAvanzata::init();'
```

#### AJAX non funziona
```bash
# 1. Verifica nonce
# Console browser: console.log(cdvData.nonce)

# 2. Verifica AJAX URL
# Console browser: console.log(cdvData.ajaxUrl)

# 3. Test endpoint diretto
curl -X POST https://sito.test/wp-admin/admin-ajax.php \
  -d "action=cdv_firma_petizione&nonce=xxx&petizione_id=1..."
```

#### Cron non parte
```bash
# 1. Verifica cron schedulato
wp cron event list

# 2. Re-schedule
wp cron event delete cdv_weekly_digest
wp plugin deactivate cronaca-di-viterbo
wp plugin activate cronaca-di-viterbo

# 3. Trigger manuale
wp cron event run cdv_weekly_digest
```

---

## 📊 Success Metrics

### Obiettivi Prime 24h
- [ ] Zero errori critici
- [ ] Almeno 1 petizione creata
- [ ] Almeno 1 sondaggio creato
- [ ] Almeno 10 firme raccolte
- [ ] Almeno 5 voti in sondaggi
- [ ] Dashboard visualizzato correttamente

### Obiettivi Prima Settimana
- [ ] Digest settimanale inviato con successo
- [ ] Almeno 1 milestone petizione raggiunta
- [ ] Almeno 1 badge assegnato
- [ ] Mappa visualizza 10+ marker
- [ ] Zero downtime

---

## ✅ Final Sign-Off

**Deployment completato da**: _______________  
**Data**: _______________  
**Ora**: _______________

**Checklist completata**: [ ]  
**Test superati**: [ ]  
**Monitoring attivo**: [ ]  
**Team notificato**: [ ]

**Note aggiuntive**:
```
_________________________________________________
_________________________________________________
_________________________________________________
```

---

## 🎉 Post-Deployment

### Comunicazioni
- [ ] Email team: "Plugin v1.5.0 deployed con successo"
- [ ] Post social: "Nuove funzionalità disponibili"
- [ ] Newsletter utenti: "Scopri petizioni e sondaggi"
- [ ] Documentazione aggiornata su wiki

### Next Steps
- [ ] Pianificare training team redazione
- [ ] Raccogliere feedback utenti (1 settimana)
- [ ] Monitorare analytics (2 settimane)
- [ ] Pianificare v1.6 features

---

**🚀 Buon deployment!**

*Ultimo aggiornamento: 2025-10-09*
