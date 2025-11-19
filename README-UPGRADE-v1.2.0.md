# 🔄 Guida Upgrade a FP Newspaper v1.2.0

## ⚠️ IMPORTANTE: LEGGI PRIMA DI AGGIORNARE

La versione 1.2.0 introduce un **cambiamento architetturale importante** che migliora drasticamente la compatibilità con WordPress e i suoi plugin.

---

## 🎯 Cosa Cambia

### PRIMA (v1.1.0)
- ❌ Custom Post Type `fp_article` separato
- ❌ Tassonomie `fp_article_category` e `fp_article_tag`
- ❌ Incompatibile con plugin SEO (Yoast, Rank Math)
- ❌ Template tema richiedono customizzazione

### DOPO (v1.2.0)
- ✅ Usa **post type nativo** `post` di WordPress
- ✅ Usa categorie e tag nativi
- ✅ **Completamente compatibile** con tutti i plugin
- ✅ Template tema funzionano automaticamente

---

## 📋 Procedura di Upgrade

### Passo 1: BACKUP DATABASE ⚠️

**CRITICO**: Fai backup completo prima di procedere!

```bash
# Via WP-CLI
wp db export backup-pre-upgrade-$(date +%Y%m%d).sql

# Via plugin (es: UpdraftPlus, BackWPup)
# Oppure via pannello hosting
```

### Passo 2: Aggiorna Plugin

```bash
# Via WordPress Admin
# Plugin → Aggiorna (se update disponibile)

# Via FTP/SSH
# Sostituisci cartella plugin con nuova versione
```

### Passo 3: Esegui Migrazione Dati

**OPZIONE A: Via CLI** (raccomandato)

```bash
cd wp-content/plugins/FP-Newspaper
php migrate-to-native-posts.php
```

**Test prima (dry-run):**
```bash
php migrate-to-native-posts.php --dry-run
```

**OPZIONE B: Via Browser**

Accedi a (solo per admin):
```
http://tuosito.com/wp-content/plugins/FP-Newspaper/migrate-to-native-posts.php
```

### Passo 4: Verifica Migrazione

Controlla che:

- [ ] Gli articoli appaiono in **Articoli** (menu WordPress nativo)
- [ ] Categorie e tag sono presenti
- [ ] Meta boxes (Opzioni, Localizzazione, Statistiche) appaiono
- [ ] Shortcodes funzionano nel frontend
- [ ] Statistiche (views/shares) sono preservate
- [ ] Plugin SEO (Yoast/Rank Math) riconoscono i post

### Passo 5: Test Plugin SEO

**Se usi Yoast SEO:**
1. Apri un articolo
2. Verifica che il pannello Yoast appaia
3. Controlla SEO score
4. Salva e verifica meta tags in frontend

**Se usi Rank Math:**
1. Apri un articolo
2. Verifica pannello Rank Math
3. Test SEO analysis
4. Verifica sitemap XML

---

## 🔧 Troubleshooting

### Problema: "Articoli non appaiono"

**Soluzione:**
```bash
# Flush rewrite rules
wp rewrite flush

# Oppure via browser
# Settings → Permalinks → Salva
```

### Problema: "Meta boxes non appaiono"

**Soluzione:**
```bash
# Pulisci cache
wp cache flush

# Oppure via plugin
# Settings → Cache → Purge All
```

### Problema: "Categorie vuote"

**Verifica:**
```sql
-- Controlla se migrazione tassonomie è completa
SELECT taxonomy, COUNT(*) 
FROM wp_term_taxonomy 
WHERE taxonomy IN ('category', 'post_tag', 'fp_article_category', 'fp_article_tag')
GROUP BY taxonomy;
```

Se vedi ancora `fp_article_category`:
```bash
# Riesegui script migrazione
php migrate-to-native-posts.php
```

### Problema: "Statistiche perse"

**Verifica:**
```sql
-- Controlla tabella stats
SELECT COUNT(*) FROM wp_fp_newspaper_stats;
```

Se 0 record, le stats erano in postmeta. Non sono perse:
```bash
# Esegui migrazione stats
wp fp-newspaper optimize
```

---

## ↩️ Rollback (se necessario)

Se qualcosa va storto:

### Passo 1: Ripristina Database

```bash
wp db import backup-pre-upgrade-YYYYMMDD.sql
```

### Passo 2: Downgrade Plugin

```bash
# Reinstalla v1.1.0 da backup
# Oppure da GitHub releases
```

### Passo 3: Flush Cache

```bash
wp cache flush
wp rewrite flush
```

---

## ✅ Checklist Post-Upgrade

Dopo la migrazione, verifica:

### Admin
- [ ] Menu "Articoli" visibile
- [ ] Lista articoli popolata
- [ ] Categorie e tag presenti
- [ ] Meta boxes funzionanti (Opzioni, Localizzazione, Stats)
- [ ] Bulk actions funzionanti
- [ ] Colonne admin visibili

### Frontend
- [ ] Shortcodes funzionano
  - [ ] `[fp_articles]`
  - [ ] `[fp_featured_articles]`
  - [ ] `[fp_breaking_news]`
  - [ ] `[fp_latest_articles]`
  - [ ] `[fp_article_stats]`
  - [ ] `[fp_newspaper_archive]`
  - [ ] `[fp_interactive_map]`
- [ ] Widget sidebar funziona
- [ ] Template tema visualizza articoli
- [ ] Statistiche (views) funzionano

### SEO
- [ ] Yoast SEO riconosce articoli
- [ ] Meta title/description settabili
- [ ] Sitemap XML include articoli
- [ ] Schema.org markup presente
- [ ] Open Graph tags presenti

### Compatibilità
- [ ] Tema funziona
- [ ] Altri plugin compatibili
- [ ] RSS feed funziona
- [ ] Ricerca WordPress trova articoli

---

## 📊 Statistiche Migrazione

Lo script fornirà output tipo:

```
═══════════════════════════════════════════════════════════════════
  FP NEWSPAPER - MIGRAZIONE A POST TYPE NATIVO
═══════════════════════════════════════════════════════════════════

📊 Analisi database...
-------------------------------------------------------------------
  📄 Articoli fp_article trovati: 523
  📁 Categorie fp_article_category: 12
  🏷️  Tag fp_article_tag: 45

🔄 Inizio migrazione...
-------------------------------------------------------------------

1️⃣  Conversione Post Type (fp_article → post)
   ✅ Convertiti 523 articoli

2️⃣  Conversione Categorie (fp_article_category → category)
   ✅ Convertite 12 categorie

3️⃣  Conversione Tag (fp_article_tag → post_tag)
   ✅ Convertiti 45 tag

4️⃣  Pulizia Cache e Rewrite Rules
   ✅ Rewrite rules aggiornate
   ✅ Object cache pulita
   ✅ Cache plugin pulita

5️⃣  Verifica Post-Migrazione
   ✅ Migrazione completata con successo!
   ✅ Tutti i dati sono stati convertiti correttamente

═══════════════════════════════════════════════════════════════════
  ✅ MIGRAZIONE COMPLETATA!
═══════════════════════════════════════════════════════════════════
```

---

## 🎁 Benefici dell'Upgrade

### Compatibilità
- ✅ Yoast SEO funziona al 100%
- ✅ Rank Math funziona al 100%
- ✅ Tutti i plugin WordPress compatibili
- ✅ Template tema automatici

### UX Migliorata
- ✅ Un solo menu "Articoli" (invece di duplicati)
- ✅ Categorie/tag unificati
- ✅ Feed RSS unico
- ✅ Ricerca integrata

### Manutenzione
- ✅ Meno codice da mantenere
- ✅ Architettura standard WordPress
- ✅ Aggiornamenti futuri più semplici

---

## 💡 FAQ

**Q: Perderò dati con la migrazione?**  
A: No. Lo script preserva tutti i dati: articoli, meta fields, statistiche, categorie, tag.

**Q: È reversibile?**  
A: Sì, ripristinando il backup database.

**Q: Quanto tempo richiede?**  
A: 10-30 secondi per 500 articoli, 1-2 minuti per 5000 articoli.

**Q: Posso testare prima?**  
A: Sì, usa `--dry-run` flag per test senza modifiche.

**Q: Il sito sarà offline?**  
A: No. La migrazione è istantanea (< 1 secondo downtime).

**Q: Devo modificare il tema?**  
A: No. Il tema funzionerà meglio (usa template post nativi).

**Q: Gli URL cambiano?**  
A: Solo se avevi `/articoli/` nell'URL (diventa standard WordPress).

---

## 🆘 Supporto

Se hai problemi:

1. Controlla questa guida
2. Verifica `CHANGELOG.md`
3. Leggi `REFACTORING-USE-NATIVE-POSTS.md`
4. Apri issue su GitHub
5. Email: info@francescopasseri.com

---

**Versione Documento**: 1.0  
**Data**: 2025-11-01  
**Valido per**: FP Newspaper v1.2.0


