# 🚀 Deploy Checklist - FP Newspaper v1.5.0

**Versione**: 1.5.0  
**Data**: 2025-11-01  
**Target**: Production / Staging

---

## 📋 PRE-DEPLOY

### Backup

- [ ] Backup database completo
  ```bash
  wp db export backup-pre-v1.5.0-$(date +%Y%m%d-%H%M%S).sql
  ```

- [ ] Backup cartella plugin attuale
  ```bash
  cd wp-content/plugins
  cp -r FP-Newspaper FP-Newspaper-backup-v1.4.0
  ```

- [ ] Backup file wp-config.php (sicurezza)

- [ ] Verifica spazio disco disponibile

---

## 📦 DEPLOY

### File da Caricare

**Cartella Completa**: `wp-content/plugins/FP-Newspaper/`

**File Nuovi v1.5.0** (28 file):

```
src/
├── Templates/
│   ├── StoryFormats.php          ← NUOVO
│   └── index.php                 ← NUOVO
│
├── Authors/
│   ├── AuthorManager.php         ← NUOVO
│   └── index.php                 ← NUOVO
│
├── Editorial/
│   ├── Desks.php                 ← NUOVO
│   └── (Calendar.php, Dashboard.php esistenti v1.3-1.4)
│
├── Related/
│   ├── RelatedArticles.php       ← NUOVO
│   └── index.php                 ← NUOVO
│
├── Media/
│   ├── CreditsManager.php        ← NUOVO
│   └── index.php                 ← NUOVO
│
└── Social/
    ├── ShareTracking.php         ← NUOVO
    └── index.php                 ← NUOVO
```

**File Modificati**:

```
src/Plugin.php                    ← Integrazione componenti
fp-newspaper.php                  ← Versione 1.5.0
CHANGELOG.md                      ← v1.5.0 entry
```

**File Documentazione Nuovi**:

```
RELEASE-NOTES-v1.5.0.md          ← NUOVO
DEPLOY-v1.5.0-CHECKLIST.md       ← NUOVO (questo)
ULTIMATE-SESSION-SUMMARY.md       ← NUOVO
```

---

### Metodo Upload

#### Opzione 1: FTP/SFTP

```bash
# 1. Connetti via SFTP
sftp user@yoursite.com

# 2. Vai alla cartella plugin
cd public_html/wp-content/plugins/

# 3. Backup vecchia versione
rename FP-Newspaper FP-Newspaper-backup

# 4. Upload nuova versione
put -r FP-Newspaper/

# 5. Verifica permissions
chmod -R 755 FP-Newspaper/
```

#### Opzione 2: WP-CLI (Recommended)

```bash
# SSH al server
ssh user@yoursite.com

# Vai a WordPress root
cd /path/to/wordpress

# Backup
wp plugin deactivate fp-newspaper
cp -r wp-content/plugins/FP-Newspaper wp-content/plugins/FP-Newspaper-backup-v1.4.0

# Upload nuova versione (via scp/rsync da locale)
# Sul tuo computer locale:
scp -r FP-Newspaper/ user@yoursite.com:/path/to/wordpress/wp-content/plugins/

# Torna a SSH server
# Riattiva
wp plugin activate fp-newspaper

# Flush
wp cache flush
wp rewrite flush
```

#### Opzione 3: Git Deploy (Pro)

```bash
# Sul server, cartella plugin
cd wp-content/plugins/FP-Newspaper

# Pull v1.5.0
git fetch origin
git checkout v1.5.0

# O merge main
git pull origin main

# Composer (se dipendenze produzione)
composer install --no-dev --optimize-autoloader

# Riattiva
wp plugin deactivate fp-newspaper
wp plugin activate fp-newspaper

# Flush
wp cache flush
wp rewrite flush
```

---

## ⚙️ POST-DEPLOY

### 1. Riattivazione Plugin (CRITICO!)

```bash
wp plugin deactivate fp-newspaper
wp plugin activate fp-newspaper
```

**Perché?**  
Riattivare registra:
- ✅ Nuova tassonomia `fp_desk`
- ✅ Ruoli custom (se non esistenti)
- ✅ Capability
- ✅ Rewrite rules

### 2. Flush Cache e Rewrite

```bash
# Flush cache
wp cache flush

# Flush rewrite rules
wp rewrite flush

# Flush object cache (se Redis/Memcached)
wp cache flush

# Clear OPcache (se PHP OPcache attivo)
# Tramite admin o:
php -r "opcache_reset();"
```

### 3. Verifica Tecnica

```bash
# Check plugin status
wp plugin list | grep fp-newspaper
# Output: fp-newspaper | active | 1.5.0

# Check database
wp db query "SHOW TABLES LIKE '%fp_newspaper%'"
# Deve mostrare: wp_fp_newspaper_stats

# Check taxonomy
wp term list fp_desk
# Deve funzionare (anche se vuoto)

# Check roles
wp role list | grep fp_
# Deve mostrare: fp_redattore, fp_editor, fp_caporedattore
```

### 4. Setup Iniziale

#### A. Crea Desk Redazionali

WordPress Admin:
1. Vai a **Articoli → Desk Redazionali**
2. Crea desk principali:

```
Nome: Politica
Slug: politica
Editor: [seleziona editor responsabile]

Nome: Cronaca
Slug: cronaca
Editor: [seleziona editor]

Nome: Esteri
Slug: esteri
Editor: [seleziona editor]

Nome: Economia
Slug: economia

Nome: Sport
Slug: sport

Nome: Cultura
Slug: cultura

Nome: Tecnologia
Slug: tecnologia
```

#### B. Completa Profili Autori

Per ogni autore/redattore:

1. **Utenti → [Nome Utente] → Profilo**
2. Scorri a **📰 Profilo Autore FP Newspaper**
3. Compila:
   - Badge (es: Inviato Speciale)
   - Bio Breve (max 160 char)
   - Bio Completa
   - Aree Competenza (es: Politica, Economia)
   - Twitter: `@username`
   - LinkedIn: URL completo
   - Facebook: URL completo
4. **Aggiorna Profilo**

#### C. Test Articolo con Formato

1. **Articoli → Aggiungi Nuovo**
2. Sidebar → **📰 Formato Articolo**
3. Seleziona: **🎤 Intervista**
4. **Salva Bozza** (importante!)
5. Compila campi:
   - Intervistato: "Mario Rossi"
   - Ruolo/Carica: "Sindaco di Roma"
6. **Pubblica**
7. Visualizza articolo frontend → verifica tutto funzioni

---

## ✅ VERIFICA FRONTEND

### Checklist Visiva

Apri un articolo pubblicato e verifica:

- [ ] **Social Share Buttons** visibili dopo contenuto
  - [ ] Bottone Facebook
  - [ ] Bottone Twitter
  - [ ] Bottone LinkedIn
  - [ ] Bottone WhatsApp
  - [ ] Click apre popup share

- [ ] **Author Box** visibile dopo social buttons
  - [ ] Avatar autore
  - [ ] Nome + Badge (se presente)
  - [ ] Bio breve
  - [ ] N° articoli
  - [ ] Link social (se compilati)

- [ ] **Related Articles** visibili dopo author box
  - [ ] Titolo "📚 Articoli Correlati"
  - [ ] 4 articoli (se disponibili)
  - [ ] Thumbnail + titolo + data
  - [ ] Hover effect funzionante

- [ ] **Formato Articolo** (se selezionato)
  - [ ] Classe CSS applicata (ispeziona: `story-format-{tipo}`)
  - [ ] Campi specifici mostrati (se formato speciale)

---

## 🔍 DEBUG (Se Problemi)

### Plugin Non Attivo

```bash
# Check errors
wp plugin list | grep fp-newspaper

# Force activate
wp plugin activate fp-newspaper --skip-plugins

# Check fatal errors
tail -f wp-content/debug.log
```

### Tassonomia Desk Non Funziona

```bash
# Flush rewrite
wp rewrite flush

# Check registro
wp term list fp_desk

# Se errore, riattiva plugin
wp plugin deactivate fp-newspaper
wp plugin activate fp-newspaper
```

### Author Box Non Appare

1. Verifica tema non rimuove `the_content` filter
2. Check profilo autore compilato
3. Verifica articolo tipo "post" (non custom)
4. Disattiva altri plugin (test conflitti)

### Related Articles Vuoti

1. Verifica articolo ha categorie/tag
2. Verifica esistono altri articoli correlati
3. Check cache (flush: `wp cache flush`)

### Social Share Non Traccia

1. Verifica jQuery caricato
2. Check console browser (F12)
3. Verifica AJAX URL corretto
4. Test con diverso browser

---

## 📊 MONITORING POST-DEPLOY

### Prime 24 Ore

Monitora:

- [ ] **Errori PHP**: `tail -f wp-content/debug.log`
- [ ] **Errori JS**: Console browser (F12)
- [ ] **Performance**: Tempo caricamento articoli
- [ ] **Database**: Query slow log
- [ ] **Cache Hit Rate**: Se Redis/Memcached

### Metriche Success

```bash
# Stats usage desk (dopo 1 settimana)
wp term list fp_desk --field=count

# Stats formati (dopo 1 settimana)
# WordPress Admin → 📊 Editorial → Statistiche Formati

# Click social share (check DB)
wp db query "SELECT SUM(shares) FROM wp_fp_newspaper_stats"
```

---

## 🔄 ROLLBACK (Se Necessario)

### Quick Rollback

```bash
# Disattiva v1.5
wp plugin deactivate fp-newspaper

# Ripristina backup
cd wp-content/plugins
rm -rf FP-Newspaper
mv FP-Newspaper-backup-v1.4.0 FP-Newspaper

# Riattiva v1.4
wp plugin activate fp-newspaper

# Flush
wp cache flush
wp rewrite flush

# Restore DB (se necessario)
wp db import backup-pre-v1.5.0.sql
```

**NOTA**: Rollback sicuro. I nuovi meta (desk, formati, etc.) saranno ignorati ma non persi.

---

## 📞 SUPPORT

### Issue Comuni

| Problema | Soluzione |
|----------|-----------|
| Desk non appare | Riattiva plugin + flush rewrite |
| Author box non mostra | Compila profilo autore |
| Related vuoti | Aggiungi categorie/tag |
| Share non funziona | Verifica jQuery + console JS |
| Performance slow | Attiva cache (Redis recommended) |

### Log

```bash
# Enable debug (wp-config.php)
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);

# Tail log
tail -f wp-content/debug.log
```

---

## ✅ COMPLETION CHECKLIST

### Deploy Completato Quando:

- [x] File caricati
- [x] Plugin riattivato
- [x] Cache pulita
- [x] Rewrite flushed
- [x] Desk creati
- [x] Editor assegnati ai desk
- [x] Profili autori completati
- [x] Test articolo con formato
- [x] Verifica frontend (share, author, related)
- [x] Monitoring attivo
- [x] Backup confermato
- [x] Team notificato

---

## 🎊 POST-DEPLOY COMMUNICATION

### Email Team (Template)

```
Oggetto: 🚀 FP Newspaper v1.5.0 Deployed!

Ciao Team,

FP Newspaper è stato aggiornato alla v1.5.0 con 6 nuove funzionalità:

✅ Story Formats - Scegli formato articolo (Intervista, Reportage, etc.)
✅ Profili Autori - Bio estesa + social + badge professionali
✅ Desk Redazionali - Organizzazione per sezioni (Politica, Cronaca, etc.)
✅ Articoli Correlati - Box automatico fine articolo
✅ Crediti Media - Gestione crediti foto/video
✅ Social Share - Bottoni condivisione + tracking

📚 Guide:
- Release Notes: wp-content/plugins/FP-Newspaper/RELEASE-NOTES-v1.5.0.md
- Changelog: wp-content/plugins/FP-Newspaper/CHANGELOG.md

🎯 Azioni Richieste:
1. Completa il tuo profilo: Utenti → [Nome] → Profilo
2. Familiarizza con formati articolo: Articoli → Aggiungi → Formato Articolo
3. Check desk assegnati: Articoli → Desk Redazionali

Per supporto: [tuo-contatto]

Buon lavoro! 📰
```

---

## 📈 KPI v1.5.0

Track dopo 30 giorni:

| Metrica | Target | Attuale |
|---------|--------|---------|
| Articoli con formato selezionato | >50% | - |
| Autori con profilo completo | >80% | - |
| Articoli assegnati a desk | >90% | - |
| Share button clicks | >100/mese | - |
| Related articles CTR | >5% | - |
| Media con crediti | >70% | - |

---

**🏆 DEPLOY COMPLETE! BUON LAVORO! 🚀**

---

**Checklist by**: Francesco Passeri  
**Version**: 1.5.0  
**Date**: 2025-11-01


