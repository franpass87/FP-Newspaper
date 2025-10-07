# Procedura di Rollback

Se per qualsiasi motivo fosse necessario tornare alla versione precedente (monolitica) del plugin, seguire questi semplici passi:

## ⚠️ Quando fare Rollback

- Problemi di compatibilità imprevisti
- Comportamenti anomali non risolvibili
- Necessità di debugging del codice originale

## 🔄 Procedura

### Passo 1: Backup Sicurezza
Prima di procedere, fare backup completo del database (precauzione):
```bash
wp db export backup-before-rollback.sql
```

### Passo 2: Ripristino File Principale
```bash
cd wp-content/plugins/cv-dossier-context/
cp cv-dossier-context.php.backup cv-dossier-context.php
```

### Passo 3: Ricarica Plugin
1. Vai in **Plugin** nel pannello WordPress
2. Disattiva "CV Dossier & Context"
3. Riattiva "CV Dossier & Context"

### Passo 4: Verifica Funzionamento
Controlla che:
- [ ] I dossier si visualizzano correttamente
- [ ] Gli eventi timeline funzionano
- [ ] Le mappe si caricano
- [ ] I form follow-up rispondono
- [ ] Gli shortcodes funzionano

## 📁 File Backup

Il file originale è salvato come:
```
cv-dossier-context.php.backup
```

Contiene tutto il codice monolitico funzionante della versione 1.0.2.

## 🗑️ Pulizia (Opzionale)

Se decidi di rimanere sulla versione monolitica, puoi rimuovere i file modulari:

```bash
# ATTENZIONE: Questo comando rimuove permanentemente i file modulari
rm -rf includes/
```

**Nota**: Tieni i file modulari anche se fai rollback, non occupano molto spazio e potrebbero tornare utili in futuro.

## 🐛 Segnalazione Problemi

Se hai dovuto fare rollback a causa di bug, per favore segnala:
1. Descrizione del problema
2. Passi per riprodurlo
3. Messaggi di errore (se presenti)
4. Versione WordPress
5. Versione PHP

## ✅ Ritorno alla Versione Modulare

Se dopo il rollback vuoi tornare alla versione modulare:

```bash
# Ripristina il bootstrap modulare
rm cv-dossier-context.php
git checkout cv-dossier-context.php
# oppure ricrea manualmente il file con 27 righe
```

## 🔒 Garanzia Zero-Dati-Loss

Il refactoring NON modifica:
- ❌ Struttura database
- ❌ Meta keys salvati
- ❌ Custom Post Types
- ❌ Tassonomie
- ❌ Opzioni WordPress
- ❌ Dati utente

Quindi il rollback è **completamente sicuro** e **reversibile** in qualsiasi momento.

## 📞 Supporto

Per assistenza con rollback o problemi:
- Documentazione: `docs/modular-architecture.md`
- File riepilogo: `REFACTORING-SUMMARY.md`
- Backup originale: `cv-dossier-context.php.backup`