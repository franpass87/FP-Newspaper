# 📋 Guida Workflow Editoriale e Calendario - FP Newspaper v1.3.0

Documentazione completa del sistema di workflow editoriale e calendario pubblicazioni.

---

## 📋 Indice

1. [Workflow Editoriale](#workflow-editoriale)
2. [Ruoli Editoriali](#ruoli-editoriali)
3. [Stati Articolo](#stati-articolo)
4. [Note Interne](#note-interne)
5. [Calendario Editoriale](#calendario-editoriale)
6. [Casi d'Uso](#casi-duso)

---

## 📝 Workflow Editoriale

### Stati del Workflow

```
┌──────────┐
│  BOZZA   │
└────┬─────┘
     │ Redattore clicca "Invia in Revisione"
     ↓
┌────────────────┐
│  IN REVISIONE  │
└────┬───────────┘
     │
     ├─→ Editor clicca "Approva" ──────┐
     │                                   ↓
     │                            ┌─────────────┐
     │                            │  APPROVATO  │
     │                            └──────┬──────┘
     │                                   │ Caporedattore pubblica
     │                                   ↓
     │                            ┌─────────────┐
     │                            │ PUBBLICATO  │
     │                            └─────────────┘
     │
     └─→ Editor clicca "Richiedi Modifiche" ─┐
                                               ↓
                                        ┌───────────────────┐
                                        │ RICHIEDE MODIFICHE│
                                        └─────────┬─────────┘
                                                  │ Redattore modifica
                                                  │ e reinvia
                                                  ↓
                                           (torna a IN REVISIONE)
```

### Flusso Completo

1. **Redattore** scrive bozza
2. **Redattore** clicca "Invia in Revisione"
3. **Editor** riceve notifica email
4. **Editor** revisiona e:
   - **Approva** → passa a "Approvato"
   - **Richiede Modifiche** → torna al redattore
5. **Caporedattore** programma pubblicazione
6. Articolo viene **pubblicato** automaticamente alla data/ora impostata

---

## 👥 Ruoli Editoriali

### 1. Redattore (`fp_redattore`)

**Può:**
- ✅ Scrivere nuovi articoli
- ✅ Salvare bozze
- ✅ Modificare propri articoli in bozza
- ✅ Inviare articoli in revisione
- ✅ Aggiungere note interne
- ✅ Caricare media

**NON può:**
- ❌ Pubblicare articoli
- ❌ Approvare/rifiutare
- ❌ Modificare articoli altrui
- ❌ Gestire categorie/tag

**Uso ideale:** Giornalisti, content writer, contributori

---

### 2. Editor (`fp_editor`)

**Può:**
- ✅ Tutto quello che può il Redattore +
- ✅ **Approvare** articoli
- ✅ **Rifiutare** articoli
- ✅ Richiedere modifiche
- ✅ Modificare articoli altrui
- ✅ Moderare commenti
- ✅ Gestire categorie/tag
- ✅ Vedere dashboard workflow

**NON può:**
- ❌ Pubblicare articoli (solo approva)
- ❌ Eliminare articoli pubblicati

**Uso ideale:** Responsabili sezione, desk editor

---

### 3. Caporedattore (`fp_caporedattore`)

**Può:**
- ✅ **Tutto** (permessi completi)
- ✅ Approvare e **pubblicare**
- ✅ Modificare/eliminare qualsiasi articolo
- ✅ Assegnare articoli ad editor
- ✅ Programmare pubblicazioni
- ✅ Gestire calendario editoriale
- ✅ Accesso completo a dashboard

**Uso ideale:** Responsabili redazione, direttori

---

### 4. Administrator (nativo WordPress)

**Può:** Tutto + gestione sistema

---

## 🔄 Stati Articolo

### Stati Disponibili

| Stato | Slug | Visibilità | Descrizione |
|-------|------|------------|-------------|
| **Bozza** | `draft` | Privato | Articolo in scrittura |
| **In Revisione** | `fp_in_review` | Privato | In attesa approvazione editor |
| **Richiede Modifiche** | `fp_needs_changes` | Privato | Rifiutato, richiede modifiche |
| **Approvato** | `fp_approved` | Privato | Approvato, pronto per pubblicazione |
| **Programmato** | `fp_scheduled` | Privato | Schedulato per data futura |
| **Pubblicato** | `publish` | Pubblico | Live sul sito |

### Cambio Stato

**Da Editor Articolo:**

```php
// Pulsanti appaiono nel Publish Box (sidebar destra)
```

**Programmaticamente:**

```php
use FPNewspaper\Workflow\WorkflowManager;

$workflow = new WorkflowManager();

// Invia in revisione
$workflow->send_for_review($post_id, $editor_id, '2025-11-10 18:00:00');

// Approva
$workflow->approve_article($post_id, 'Ottimo lavoro!');

// Rifiuta
$workflow->reject_article($post_id, 'Mancano fonti', [
    'Aggiungere link alle fonti',
    'Verificare dati statistici'
]);

// Pubblica
$workflow->publish_approved($post_id, '2025-11-15 08:00:00');
```

---

## 📝 Note Interne

### Caratteristiche

- 📝 Visibili **solo al team** editoriale
- 🔒 **NON pubbliche** (sicure)
- @ **Menzioni** utenti (notifica automatica)
- 📧 **Email** a utenti menzionati
- 🗑️ **Eliminabili** dall'autore

### Utilizzo

**Interfaccia:**

Apri un articolo → Meta Box "Note Redazionali (Interne)"

**Aggiungere nota:**
```
Scrivi nota... Usa @username per menzionare
[Aggiungi Nota]
```

**Menzionare utenti:**
```
@mario controlla i dati della tabella
@laura aggiungi foto
```

**Programmaticamente:**

```php
use FPNewspaper\Workflow\InternalNotes;

$notes = new InternalNotes();

// Aggiungi nota
$notes->add_note($post_id, '@mario verifica le fonti');

// Ottieni note
$all_notes = $notes->get_notes($post_id);

// Conta note
$count = $notes->count_notes($post_id);
```

---

## 📅 Calendario Editoriale

### Caratteristiche

- 📅 **Vista Mensile/Settimanale** con FullCalendar
- 🎨 **Colori per stato** (bozza, revisione, approvato, etc.)
- 🖱️ **Drag & Drop** per riprogrammare articoli
- ⏰ **Rilevamento conflitti** (stesso slot)
- 📥 **Export iCal** (Google Calendar, Outlook)
- 🖨️ **Stampa** calendario

### Accesso

```
WordPress Admin → Articoli → 📅 Calendario
```

### Pianificare Articolo

**Metodo 1: Dal Calendario**
1. Crea articolo e salvalo come bozza
2. Vai al Calendario
3. L'articolo appare se ha data futura
4. Trascina per riprogrammare

**Metodo 2: Programmaticamente**

```php
use FPNewspaper\Editorial\Calendar;

$calendar = new Calendar();

// Programma articolo
$calendar->schedule_article(
    $post_id,
    '2025-11-15 08:00:00',  // Data/ora
    'morning',              // Slot: morning/afternoon/evening
    $author_id              // Autore assegnato (opzionale)
);

// Verifica conflitti
$conflicts = $calendar->check_schedule_conflicts(
    '2025-11-15 08:00:00',
    'morning'
);

if (!empty($conflicts)) {
    echo 'Slot già occupato da: ' . $conflicts[0]->post_title;
}

// Ottieni slot disponibili
$available = $calendar->get_available_slots('2025-11-15');
// Ritorna: ['morning' => ['available' => true, 'count' => 0], ...]
```

### Export iCal

```php
// Via interfaccia: Pulsante "Esporta iCal"

// Programmaticamente:
$ical_content = $calendar->export_to_ical('2025-11-01', '2025-11-30');
header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: attachment; filename="calendario.ics"');
echo $ical_content;
```

---

## 💡 Casi d'Uso

### Caso 1: Redattore Scrive Articolo

```
1. Redattore: WordPress Admin → Articoli → Aggiungi nuovo
2. Scrive articolo
3. Aggiunge note interne: "@editor controlla statistiche sezione economia"
4. Clicca "Invia in Revisione"
5. Editor riceve email "Nuovo articolo da revisionare"
```

### Caso 2: Editor Revisiona

```
1. Editor: Apre email notifica
2. Clicca link articolo
3. Legge articolo
4. Legge note interne
5. Opzione A: Clicca "Approva" + aggiunge nota "Ottimo lavoro!"
   Opzione B: Clicca "Richiedi Modifiche" + motivo "Mancano fonti"
```

### Caso 3: Caporedattore Programma Pubblicazione

```
1. Caporedattore: WordPress Admin → Articoli → 📅 Calendario
2. Vede articoli approvati
3. Drag & drop su data desiderata
4. Articolo viene programmato automaticamente
5. Sistema pubblica automaticamente all'orario impostato
```

### Caso 4: Deadline Imminenti

```
1. Caporedattore: WordPress Admin → Articoli → 📋 Workflow
2. Sezione "Deadline Imminenti" mostra articoli in scadenza
3. Vede articoli in rosso (scaduti)
4. Clicca "Apri" per gestire
```

---

## 🔔 Notifiche Email

### Eventi che Attivano Notifiche

| Evento | Destinatario | Contenuto |
|--------|--------------|-----------|
| **Inviato in revisione** | Editor assegnato | "Nuovo articolo da revisionare" |
| **Articolo approvato** | Autore originale | "Il tuo articolo è stato approvato" |
| **Richieste modifiche** | Autore originale | "Modifiche richieste: [motivo]" |
| **Menzione in nota** | Utente menzionato | "Sei stato menzionato: [@username]" |
| **Deadline imminente** | Editor/Autore | "Articolo in scadenza tra 2 giorni" |

### Configurazione

Le notifiche usano `wp_mail()` di WordPress.

Per configurare SMTP: usa plugin come **WP Mail SMTP**.

---

## 🔧 API e Hook

### Hook Workflow

```php
// Dopo invio in revisione
add_action('fp_newspaper_sent_for_review', function($post_id, $reviewer_id) {
    // Custom logic
}, 10, 2);

// Dopo approvazione
add_action('fp_newspaper_article_approved', function($post_id, $approver_id) {
    // Custom logic
}, 10, 2);

// Dopo rifiuto
add_action('fp_newspaper_article_rejected', function($post_id, $reason) {
    // Custom logic
}, 10, 2);

// Dopo pubblicazione
add_action('fp_newspaper_article_published', function($post_id) {
    // Custom logic (es: auto-post social media)
}, 10, 1);

// Cambio stato generico
add_action('fp_newspaper_status_transition', function($post_id, $new_status, $old_status) {
    // Track tutte le transizioni
}, 10, 3);
```

### Hook Calendario

```php
// Dopo scheduling
add_action('fp_newspaper_article_scheduled', function($post_id, $scheduled_date) {
    // Custom logic
}, 10, 2);

// Dopo unscheduling
add_action('fp_newspaper_article_unscheduled', function($post_id) {
    // Custom logic
}, 10, 1);
```

### Hook Note

```php
// Dopo aggiunta nota
add_action('fp_newspaper_note_added', function($post_id, $note) {
    // $note contiene: timestamp, user_id, content, mentions
}, 10, 2);
```

---

## 📊 Dashboard e Statistiche

### Pagina Workflow

```
WordPress Admin → Articoli → 📋 Workflow
```

**Mostra:**
- 📊 Statistiche (In Revisione, Modifiche, Approvati, Programmati)
- 🎯 Assegnati a Me
- ⏳ In Attesa di Revisione (solo editor+)
- ⏰ Deadline Imminenti

### Pagina Calendario

```
WordPress Admin → Articoli → 📅 Calendario
```

**Mostra:**
- 📅 Calendario mensile/settimanale
- 📊 Statistiche mese corrente
- 🎨 Eventi colorati per stato
- 🖱️ Drag & drop per riprogrammare

---

## 🎯 Best Practices

### Per Redattori

1. ✅ Scrivi bozza completa prima di inviare in revisione
2. ✅ Usa note interne per domande/chiarimenti
3. ✅ Menziona @editor per richieste urgenti
4. ✅ Controlla deadline assegnate

### Per Editor

1. ✅ Revisiona entro 24-48h
2. ✅ Usa "Richiedi Modifiche" con feedback dettagliato
3. ✅ Aggiungi note costruttive
4. ✅ Approva solo se realmente pronto

### Per Caporedattori

1. ✅ Pianifica pubblicazioni con 1-2 settimane anticipo
2. ✅ Usa calendario per bilanciare contenuti
3. ✅ Monitora deadline imminenti daily
4. ✅ Assegna articoli uniformemente al team

---

## 🔐 Sicurezza e Permessi

### Capability Checks

Tutti i metodi verificano permessi:

```php
// Invia in revisione: richiede edit_posts
if (!current_user_can('edit_posts')) return;

// Approva/rifiuta: richiede publish_posts
if (!current_user_can('publish_posts')) return;

// Pubblica: richiede publish_posts
if (!current_user_can('publish_posts')) return;
```

### AJAX Security

Tutti endpoint AJAX usano:
- ✅ Nonce verification
- ✅ Capability checks
- ✅ Input sanitization

---

## 📈 Metriche e Analytics

### Statistiche Workflow

```php
use FPNewspaper\Workflow\WorkflowManager;

$workflow = new WorkflowManager();
$stats = $workflow->get_stats();

// Ritorna:
// [
//   'in_review' => 5,
//   'needs_changes' => 2,
//   'approved' => 8,
//   'scheduled' => 12
// ]
```

### Statistiche Calendario

```php
use FPNewspaper\Editorial\Calendar;

$calendar = new Calendar();
$stats = $calendar->get_month_stats('2025-11');

// Ritorna:
// [
//   'total' => 25,
//   'by_status' => ['fp_approved' => 10, 'publish' => 15],
//   'by_day' => ['2025-11-01' => 2, '2025-11-02' => 3, ...]
// ]
```

---

## 🧪 Testing

### Test Workflow

```bash
# Via WP-CLI
wp eval "
\$wf = new FPNewspaper\Workflow\WorkflowManager();
\$result = \$wf->send_for_review(123, 2);
var_dump(\$result);
"
```

### Test Calendario

```bash
# Ottieni eventi
wp eval "
\$cal = new FPNewspaper\Editorial\Calendar();
\$events = \$cal->get_calendar_events('2025-11-01', '2025-11-30');
echo 'Eventi trovati: ' . count(\$events);
"
```

---

## 🆘 Troubleshooting

### Problema: "Stati custom non appaiono"

**Soluzione:**
```bash
# Riattiva plugin
wp plugin deactivate fp-newspaper
wp plugin activate fp-newspaper

# Flush rewrite rules
wp rewrite flush
```

### Problema: "Notifiche non arrivano"

**Soluzione:**
1. Verifica `wp_mail()` funzioni
2. Installa **WP Mail SMTP**
3. Controlla spam folder
4. Verifica email destinatario corretta

### Problema: "Calendario non carica eventi"

**Soluzione:**
1. Apri Console JavaScript (F12)
2. Verifica errori AJAX
3. Controlla nonce validity
4. Verifica permessi utente (`edit_posts`)

---

## 📚 Esempi Avanzati

### Integrazione con Digital Marketing Suite

```php
// Auto-post su social quando articolo pubblicato
add_action('fp_newspaper_article_published', function($post_id) {
    if (function_exists('fp_marketing_auto_post')) {
        fp_marketing_auto_post($post_id, ['facebook', 'twitter']);
    }
});
```

### Integrazione con FP-SEO-Manager

```php
// Auto-check SEO quando approvato
add_action('fp_newspaper_article_approved', function($post_id) {
    if (function_exists('fp_seo_analyze_post')) {
        $score = fp_seo_analyze_post($post_id);
        if ($score < 70) {
            // Aggiungi nota interna
            $notes = new FPNewspaper\Workflow\InternalNotes();
            $notes->add_note($post_id, "⚠️ SEO Score basso: {$score}/100");
        }
    }
});
```

### Deadline Reminder Automatico

```php
// Cron daily: invia reminder deadline
add_action('wp', function() {
    if (!wp_next_scheduled('fp_deadline_reminder')) {
        wp_schedule_event(time(), 'daily', 'fp_deadline_reminder');
    }
});

add_action('fp_deadline_reminder', function() {
    $workflow = new FPNewspaper\Workflow\WorkflowManager();
    $deadlines = $workflow->get_upcoming_deadlines(2); // Prossimi 2 giorni
    
    foreach ($deadlines as $post) {
        $editor_id = get_post_meta($post->ID, '_fp_assigned_editor', true);
        if ($editor_id) {
            // Invia reminder email
            $editor = get_userdata($editor_id);
            wp_mail(
                $editor->user_email,
                'Reminder: Deadline imminente',
                "L'articolo '{$post->post_title}' è in scadenza!"
            );
        }
    }
});
```

---

## 🎓 Tutorial Video (Placeholder)

```
// TODO: Creare video tutorial
// - Setup ruoli
// - Flusso completo redattore → editor → pubblicazione
// - Uso calendario
// - Best practices
```

---

## 📖 FAQ

**Q: Posso avere più editor su un articolo?**  
A: Attualmente no, ma puoi usare note interne per collaborazione.

**Q: Posso personalizzare gli stati?**  
A: Sì, puoi registrare stati custom aggiuntivi con `register_post_status()`.

**Q: Il calendario funziona su mobile?**  
A: Sì, FullCalendar è responsive, ma meglio da desktop.

**Q: Posso disabilitare il workflow?**  
A: Sì, disattivando il plugin o non usando i pulsanti workflow.

**Q: I ruoli custom sono reversibili?**  
A: Sì, vengono rimossi alla disinstallazione plugin.

---

**Versione Documento**: 1.0  
**Data**: 2025-11-01  
**Valido per**: FP Newspaper v1.3.0


