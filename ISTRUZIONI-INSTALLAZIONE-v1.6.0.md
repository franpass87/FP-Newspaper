# 🚀 Istruzioni Installazione Plugin v1.6.0

## 📦 Pacchetto Pronto
**File**: `cronaca-di-viterbo-v1.6.0-complete.zip` (124 KB)  
**Versione**: 1.6.0  
**Data**: 14 Ottobre 2025

---

## ✨ Novità Ultime 2 Settimane

### 🎯 Nuove Funzionalità (v1.5 → v1.6)
- ✅ **Video Stories** (supporto Instagram, TikTok, YouTube)
- ✅ **Photo Galleries** con lightbox
- ✅ **AI Chatbot** (integrazione OpenAI/Claude)
- ✅ **Mappa Interattiva** avanzata
- ✅ **Sistema Votazione Pesata**
- ✅ **Petizioni e Sondaggi**

### 🔒 Fix di Sicurezza (46 Bug Risolti)
- ✅ 5 race conditions critiche eliminate
- ✅ 9 vulnerabilità di sicurezza chiuse
- ✅ Sanitizzazione completa input
- ✅ Validazione JSON da API esterne
- ✅ UPDATE atomici per contatori
- ✅ Protezione XSS e SQL injection

---

## 📋 PROCEDURA DI INSTALLAZIONE

### ⚠️ STEP 1: Backup Completo

**IMPORTANTE**: Fai sempre un backup prima di aggiornare!

```bash
# Via cPanel File Manager
# 1. Scarica cartella wp-content/plugins/cronaca-di-viterbo
# 2. Esporta database da phpMyAdmin

# Via FTP/SFTP
cd public_html/wp-content/plugins
tar -czf cronaca-di-viterbo-backup-$(date +%Y%m%d).tar.gz cronaca-di-viterbo/

# Via WP-CLI
wp db export backup-pre-v1.6.0.sql
wp plugin list > plugin-list-backup.txt
```

---

### 🗑️ STEP 2: Rimuovi Vecchia Versione

**Opzione A - Via WordPress Admin (CONSIGLIATO):**
1. Vai in **Dashboard** → **Plugin**
2. **Disattiva** "Cronaca di Viterbo" (se attivo)
3. **Elimina** il plugin
4. Conferma eliminazione

**Opzione B - Via File Manager/FTP:**
```bash
# Elimina cartella vecchia
rm -rf wp-content/plugins/cronaca-di-viterbo/
```

---

### 📤 STEP 3: Carica Nuova Versione

**Opzione A - Via WordPress Admin (FACILE):**
1. Vai in **Dashboard** → **Plugin** → **Aggiungi nuovo**
2. Clicca **Carica Plugin**
3. Seleziona file `cronaca-di-viterbo-v1.6.0-complete.zip`
4. Clicca **Installa ora**
5. Aspetta completamento upload

**Opzione B - Via cPanel File Manager:**
1. Vai in **File Manager**
2. Naviga in `public_html/wp-content/plugins/`
3. Clicca **Upload** e carica `cronaca-di-viterbo-v1.6.0-complete.zip`
4. Seleziona il file ZIP → **Extract**
5. Assicurati che la cartella si chiami esattamente `cronaca-di-viterbo`

**Opzione C - Via FTP/SFTP:**
```bash
# Decomprimi localmente
unzip cronaca-di-viterbo-v1.6.0-complete.zip

# Carica via FTP nella cartella plugins
# Destinazione: public_html/wp-content/plugins/cronaca-di-viterbo/
```

---

### ✅ STEP 4: Attiva Plugin

1. Vai in **Dashboard** → **Plugin**
2. Cerca "**Cronaca di Viterbo**"
3. Verifica che la versione sia **1.6.0**
4. Clicca **Attiva**

---

### 🔄 STEP 5: Verifica Database (Automatico)

Il plugin aggiorna automaticamente il database all'attivazione.

**Nuove tabelle create:**
- `wp_cdv_petizioni_firme`
- `wp_cdv_sondaggi_voti`
- `wp_cdv_voti_dettagli`
- `wp_cdv_subscribers`

---

### 🧹 STEP 6: Svuota Cache

**Cache Plugin:**
- WP Super Cache: Svuota cache
- W3 Total Cache: Svuota tutti i cache
- WP Rocket: Svuota cache + precarica
- LiteSpeed Cache: Purge All

**Cache Browser:**
- Chrome/Firefox: Ctrl+Shift+R (o Cmd+Shift+R su Mac)
- Safari: Cmd+Option+E

**Cache Cloudflare (se attivo):**
1. Login Cloudflare
2. Vai nel tuo sito
3. Caching → Purge Everything

---

### ✅ STEP 7: Verifica Funzionamento

Controlla che tutto funzioni:

1. **Dashboard Admin**
   - Vai in **Cronaca di Viterbo** → **Dashboard**
   - Verifica che i widget carichino

2. **Nuove Funzionalità v1.6**
   - **Video Stories**: Crea nuovo Video Story (Dashboard → Video Stories)
   - **Photo Gallery**: Crea nuova Galleria (Dashboard → Gallerie Foto)
   - **AI Chatbot**: Verifica widget in frontend (se configurato)
   - **Mappa**: Controlla che la mappa funzioni su pagine con shortcode `[cdv_mappa]`

3. **Shortcodes Funzionanti**
   ```
   [cdv_proposte]
   [cdv_proposta_form]
   [cdv_mappa]
   [cdv_petizioni]
   [cdv_petizione_form id="X"]
   [cdv_sondaggio id="X"]
   [cdv_video_stories]
   [cdv_galleria_foto id="X"]
   [cdv_user_profile]
   [cdv_chatbot]
   ```

4. **Funzioni AJAX**
   - Prova a votare una proposta
   - Prova a firmare una petizione
   - Prova a votare un sondaggio
   - Verifica che i contatori si aggiornino

5. **Console Browser**
   - Apri DevTools (F12)
   - Controlla **Console** per errori JavaScript
   - Controlla **Network** per errori AJAX

---

## 🐛 Troubleshooting

### Errore "Plugin non trovato"
→ Assicurati che la cartella si chiami esattamente `cronaca-di-viterbo`

### Errore "Errori PHP"
→ Verifica requisiti: PHP 8.0+, WordPress 6.0+

### Shortcode non funzionano
→ Svuota cache e ricarica pagina con Ctrl+Shift+R

### Video Stories / Gallerie non visibili
→ Controlla permessi: Dashboard → Cronaca di Viterbo → Impostazioni

### Contatori non si aggiornano
→ Fix race conditions implementato in v1.6.0 - dovrebbe funzionare

### Database non si aggiorna
→ Disattiva e riattiva il plugin manualmente

### Mappa non carica
→ Verifica che ci siano proposte/petizioni con coordinate geografiche

---

## 📊 Verifica Versione Installata

```php
// Nel footer del tuo sito o in wp-admin
// Dovresti vedere: Version 1.6.0
```

Oppure verifica nel file:
`wp-content/plugins/cronaca-di-viterbo/cronaca-di-viterbo.php`

```php
* Version: 1.6.0
```

---

## 🔧 Configurazione AI Chatbot (Opzionale)

Se vuoi usare l'AI Chatbot:

1. Vai in **Cronaca di Viterbo** → **Impostazioni**
2. Tab **AI Chatbot**
3. Inserisci API Key:
   - OpenAI (GPT-4)
   - Anthropic (Claude)
4. Salva impostazioni
5. Aggiungi widget o shortcode `[cdv_chatbot]`

---

## 📞 Supporto

In caso di problemi:

1. Verifica i requisiti sistema
2. Controlla log errori: `/wp-content/debug.log` (se WP_DEBUG attivo)
3. Disattiva altri plugin per test conflitti
4. Contatta supporto con:
   - Versione WordPress
   - Versione PHP
   - Log errori
   - Screenshot del problema

---

## ✅ Checklist Post-Installazione

- [ ] Plugin attivato e versione 1.6.0 visibile
- [ ] Database aggiornato (verificato in PhpMyAdmin)
- [ ] Cache svuotata (plugin + browser + CDN)
- [ ] Shortcodes funzionanti in frontend
- [ ] Dashboard admin carica correttamente
- [ ] AJAX funziona (voti, firme, sondaggi)
- [ ] Video Stories funzionano (se usate)
- [ ] Photo Galleries funzionano (se usate)
- [ ] Mappa interattiva carica
- [ ] Nessun errore JavaScript in console
- [ ] Nessun errore PHP nei log

---

## 🎉 Completato!

Il plugin **Cronaca di Viterbo v1.6.0** è ora installato con:
- ✅ 46 bug fix di sicurezza
- ✅ 6 nuove funzionalità principali
- ✅ Performance ottimizzate
- ✅ Codice production-ready

**Buon lavoro! 🚀**
