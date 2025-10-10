# 🚀 Nuove Funzionalità v2.0 - Cronaca di Viterbo

**Data implementazione**: 2025-10-09  
**Versione**: 2.0.0  
**Status**: ✅ Completato

---

## 📋 Riepilogo

Sono state implementate **3 funzionalità major** che portano il plugin a un nuovo livello di engagement multimediale e interattività:

### 🎬 1. Video Stories
### 📸 2. Gallerie Foto
### 🤖 3. AI Chatbot Intelligente

---

## 🎬 Video Stories

### Descrizione
Sistema completo per la gestione di video brevi in stile TikTok/Instagram Stories, perfetto per citizen journalism e reportage rapidi dal territorio.

### Custom Post Type
- **Slug**: `cdv_video`
- **URL**: `/video-stories/`
- **Tassonomie**: Quartiere, Tematica, Tags
- **Capabilities**: standard (editor, author, publish)

### Funzionalità

#### Meta Box Admin
- **Upload video**: Libreria media WordPress
- **Tipo video**: 
  - Upload file
  - YouTube embed
  - Vimeo embed
  - URL esterno
- **Formati supportati**:
  - 📱 Verticale (9:16) - Stories
  - ⬜ Quadrato (1:1)
  - 🎬 Orizzontale (16:9)
- **Durata**: Max 180 secondi (3 minuti)
- **Statistiche live**:
  - 👁️ Visualizzazioni
  - ❤️ Mi piace
  - 🔗 Condivisioni

#### Shortcodes

##### `[cdv_video_stories]`
Visualizza griglia/slider di video stories.

**Parametri**:
```
limit        = 10              // Numero video
format       = "all"            // vertical, horizontal, square, all
quartiere    = ""               // slug quartiere
tematica     = ""               // slug tematica
layout       = "grid"           // grid, slider, stories
autoplay     = "no"             // yes, no
orderby      = "date"           // date, title, views, likes
order        = "DESC"           // ASC, DESC
```

**Esempi**:
```php
// Griglia video verticali del Centro
[cdv_video_stories limit="12" format="vertical" quartiere="centro"]

// Slider autoplay
[cdv_video_stories layout="slider" autoplay="yes"]

// Feed stile Stories
[cdv_video_stories layout="stories" format="vertical"]
```

#### Layout Disponibili

##### Grid (default)
- Griglia responsive (auto-fill 300px)
- Hover con overlay play
- Info complete sotto video

##### Slider
- Carousel con paginazione
- Swipe su mobile
- Navigation arrows

##### Stories
- Fullscreen verticale
- Auto-avanzamento
- Progress bar animata
- Gesture swipe left/right

#### Tracking & Analytics

**Visualizzazioni**:
- Tracciamento automatico al play
- Rate limit: 1 ora per IP/utente
- +1 punto reputazione all'autore

**Mi piace**:
- Click cuore sul video
- Rate limit: 24 ore per IP/utente
- +5 punti reputazione all'autore

#### AJAX Endpoints
- `cdv_like_video` - Metti mi piace
- `cdv_track_video_view` - Traccia visualizzazione

---

## 📸 Gallerie Foto

### Descrizione
Sistema avanzato per gallerie fotografiche con upload multiplo, layout flessibili e lightbox integrato.

### Custom Post Type
- **Slug**: `cdv_galleria`
- **URL**: `/gallerie/`
- **Tassonomie**: Quartiere, Tematica, Tags
- **Capabilities**: standard

### Funzionalità

#### Meta Box Admin

**Gestione Foto**:
- Upload multiplo da Libreria Media
- Drag & drop per riordinare
- Preview thumbnail
- Caption individuale da attachment
- Remove singola foto

**Impostazioni**:
- **Layout**: Grid, Masonry, Slider, Justified
- **Colonne**: 1-6 (per layout grid)
- **Lightbox**: On/Off
- **Fotografo**: Nome
- **Luogo**: Location scatto
- **Data scatto**: Date picker

#### Shortcodes

##### `[cdv_galleria]`
Visualizza una singola galleria fotografica.

**Parametri**:
```
id       = 0           // ID galleria (required)
layout   = ""          // override layout (grid, masonry, slider, justified)
columns  = ""          // override colonne (1-6)
lightbox = "yes"       // abilita lightbox (yes, no)
```

**Esempio**:
```php
// Galleria base
[cdv_galleria id="123"]

// Grid 4 colonne con lightbox
[cdv_galleria id="123" layout="grid" columns="4" lightbox="yes"]

// Masonry senza lightbox
[cdv_galleria id="456" layout="masonry" lightbox="no"]
```

##### `[cdv_gallerie]`
Lista di tutte le gallerie con preview.

**Parametri**:
```
limit     = 12         // Numero gallerie
quartiere = ""         // Filtra per quartiere
tematica  = ""         // Filtra per tematica
orderby   = "date"     // Ordinamento
order     = "DESC"     // ASC, DESC
```

**Esempio**:
```php
// Ultime 9 gallerie del Centro
[cdv_gallerie limit="9" quartiere="centro"]

// Tutte le gallerie Mobilità
[cdv_gallerie tematica="mobilita" orderby="title"]
```

#### Layout Disponibili

##### Grid
- Griglia responsive a colonne
- Aspect ratio preservato
- Gap uniforme

##### Masonry
- Layout Pinterest-style
- Colonne CSS (column-count)
- Altezze variabili

##### Slider
- Carousel navigabile
- Touch swipe
- Pagination dots

##### Justified
- Righe giustificate (come Flickr)
- Altezza uniforme per riga
- Larghezza adattata

#### Lightbox

**Funzionalità**:
- Click su foto → fullscreen
- Caption overlay
- Chiusura:
  - Click overlay
  - Tasto X
  - ESC keyboard
- Smooth animations
- Mobile responsive

**Tecnologia**:
- Custom CSS/JS (no librerie esterne)
- Può essere sostituito con GLightbox/PhotoSwipe

---

## 🤖 AI Chatbot Intelligente

### Descrizione
Assistente virtuale con AI per supporto cittadini, risposte automatiche e integrazione API esterne (OpenAI/Claude).

### Funzionalità

#### Interfaccia

**Floating Button**:
- Posizionato bottom-right
- Gradiente viola (brand colors)
- Badge notifiche
- Animazioni smooth
- Click per aprire/chiudere

**Chat Window**:
- 380x600px (responsive mobile fullscreen)
- Header con avatar bot
- Status "Online" con dot animato
- Area messaggi scrollabile
- Input con auto-resize
- Footer branding

#### Modalità Funzionamento

##### 1. Pattern Matching (default)
Knowledge base locale con pattern pre-definiti.

**Domande supportate**:
- Come inviare proposte
- Come votare
- Eventi in programma
- Firmare petizioni
- Sistema reputazione
- Quartieri e filtri
- Video e foto
- Aiuto generale

**Vantaggi**:
- ✅ Gratuito (no API key)
- ✅ Veloce
- ✅ Privacy (no dati esterni)
- ✅ Personalizzabile

**Svantaggi**:
- ❌ Risponde solo a pattern noti
- ❌ No conversazione naturale

##### 2. OpenAI GPT (opzionale)
Integrazione con GPT-3.5-turbo o GPT-4.

**Config**:
```
Provider: OpenAI
API Key: sk-...
Model: gpt-3.5-turbo
Max tokens: 150
Temperature: 0.7
```

**Vantaggi**:
- ✅ Conversazione naturale
- ✅ Comprensione contestuale
- ✅ Risposte creative

**Svantaggi**:
- ❌ Richiede API key (a pagamento)
- ❌ Chiamate esterne
- ❌ Latenza maggiore

##### 3. Claude AI (opzionale)
Integrazione con Claude 3 Haiku.

**Config**:
```
Provider: Claude
API Key: sk-ant-...
Model: claude-3-haiku-20240307
Max tokens: 150
```

**Vantaggi**:
- ✅ Conversazione avanzata
- ✅ Più "umano" di GPT
- ✅ Buon rapporto qualità/prezzo

**Svantaggi**:
- ❌ Richiede API key
- ❌ Disponibilità limitata in EU

#### Funzionalità Avanzate

**Quick Actions**:
- Pulsanti domande frequenti
- Un click per query comuni
- Personalizzabili via code

**Conversation History**:
- Salva ultimi 50 messaggi in localStorage
- Ripristina conversazioni giornaliere
- Clearable dall'utente

**Typing Indicator**:
- Animazione "..." mentre elabora
- Smooth UX

**Rate Limiting**:
- 3 secondi tra messaggi consecutivi
- Previene spam

**Analytics**:
- Track apertura chat (GA4)
- Track messaggi inviati
- Lunghezza messaggi

#### AJAX Endpoint
- `cdv_chatbot_message` - Invia messaggio e ricevi risposta

### Context Awareness

Il bot conosce il contesto della piattaforma:

```
"Sei ViterboBot, assistente virtuale di Cronaca di Viterbo.

Funzionalità:
- Proposte cittadini con votazione
- Petizioni digitali
- Eventi locali
- Sondaggi pubblici
- Sistema reputazione con badge
- Video stories e gallerie foto

Rispondi sempre in italiano, amichevole e conciso (max 100 parole)."
```

### Impostazioni Admin

**Dashboard CdV > Chatbot Settings**:
- ☑️ Abilita/Disabilita chatbot
- 🤖 Provider AI (None, OpenAI, Claude)
- 🔑 API Key (se provider esterno)
- 🧪 Test connessione
- 🗑️ Clear cronologia conversazioni

---

## 📦 File Implementati

### PostTypes (2 file)
```
src/PostTypes/VideoStory.php        // CPT Video
src/PostTypes/GalleriaFoto.php      // CPT Galleria
```

### Shortcodes (2 file)
```
src/Shortcodes/VideoStories.php     // Shortcode video
src/Shortcodes/GalleriaFoto.php     // Shortcode gallerie
```

### Services (1 file)
```
src/Services/AIChatbot.php          // AI Bot logic
```

### Ajax (1 file)
```
src/Ajax/VideoActions.php           // Like & views video
```

### Assets CSS (2 file)
```
assets/css/cdv-media.css            // Video & Foto styles
assets/css/cdv-chatbot.css          // Chatbot styles
```

### Assets JS (2 file)
```
assets/js/cdv-media.js              // Video & Foto scripts
assets/js/cdv-chatbot.js            // Chatbot script
```

### Totale: **10 file nuovi**

---

## 🎨 Design & UX

### Video Stories
- **Card design**: Arrotondate con shadow
- **Hover**: Lift effect + play button
- **Colors**: 
  - Primary: #667eea
  - Secondary: #764ba2
  - Accent: #e74c3c (likes)
- **Icons**: Dashicons WordPress
- **Animations**: Smooth transitions 0.3s

### Gallerie Foto
- **Layout moderno**: Grid responsive
- **Lightbox**: Dark overlay + zoom animation
- **Caption**: Overlay bottom translucent
- **Meta info**: Icone + spacing generoso

### Chatbot
- **Gradient brand**: #667eea → #764ba2
- **Bubble chat**: Standard iOS-like
- **Typing dots**: Animazione bounce
- **Status dot**: Pulse animation
- **Mobile**: Fullscreen su <480px

---

## 🔒 Sicurezza

### Video Stories
- ✅ Nonce verification AJAX
- ✅ Capability check uploads
- ✅ File type validation (video only)
- ✅ Rate limiting (1h views, 24h likes)
- ✅ Sanitize URLs
- ✅ Escape output

### Gallerie Foto
- ✅ Nonce verification
- ✅ Capability check
- ✅ File type (image only)
- ✅ Sanitize IDs
- ✅ Escape HTML/URLs

### Chatbot
- ✅ Nonce verification
- ✅ Rate limiting (3s cooldown)
- ✅ Input sanitization
- ✅ API key sicure (non esposte frontend)
- ✅ HTTPS only per API calls
- ✅ XSS prevention (wp_kses)

---

## 📊 Statistiche & Analytics

### Video Tracking
```javascript
// GA4 Events automatici
dataLayer.push({
  'event': 'video_view',
  'video_id': 123,
  'video_title': 'Traffico Centro',
  'quartiere': 'Centro'
});

dataLayer.push({
  'event': 'video_liked',
  'video_id': 123
});
```

### Chatbot Tracking
```javascript
// GA4 Events
gtag('event', 'chatbot_opened', {
  'event_category': 'Engagement'
});

gtag('event', 'chatbot_message_sent', {
  'event_category': 'Engagement',
  'value': message_length
});
```

---

## 🚀 Utilizzo

### Video Stories

#### Creare un Video
1. **Admin** > Video Stories > Aggiungi nuovo
2. Inserisci titolo e descrizione
3. Seleziona quartiere/tematica
4. **Meta Box** > Clicca "Carica Video dalla Libreria"
5. Scegli formato (verticale/quadrato/orizzontale)
6. Imposta durata (opzionale)
7. Pubblica

#### Visualizzare Video
```php
// In pagina o post
[cdv_video_stories limit="6" layout="grid"]

// In template PHP
<?php echo do_shortcode('[cdv_video_stories format="vertical" layout="stories"]'); ?>

// Widget sidebar (future feature)
```

### Gallerie Foto

#### Creare una Galleria
1. **Admin** > Gallerie Foto > Aggiungi nuova
2. Titolo + excerpt + contenuto
3. Seleziona quartiere/tematica
4. **Meta Box** > "Aggiungi/Modifica Foto"
5. Seleziona multiple foto (Ctrl+Click)
6. Riordina drag & drop
7. Imposta layout (Grid 3 colonne default)
8. Abilita lightbox
9. Opzionale: Fotografo, luogo, data
10. Pubblica

#### Visualizzare Galleria
```php
// Singola galleria
[cdv_galleria id="456"]

// Lista tutte gallerie
[cdv_gallerie limit="12" quartiere="centro"]
```

### Chatbot

#### Abilitare Chatbot
1. **Admin** > CdV > Impostazioni > Chatbot
2. ☑️ Abilita chatbot
3. Scegli provider:
   - **None**: Pattern matching (gratis)
   - **OpenAI**: Inserisci API key OpenAI
   - **Claude**: Inserisci API key Anthropic
4. (Opzionale) Test connessione
5. Salva

#### Personalizzare Risposte
Modifica file: `src/Services/AIChatbot.php`

Metodo: `get_response_patterns()`

Aggiungi pattern:
```php
array(
    'keywords' => array( 'orari', 'apertura', 'chiusura' ),
    'context'  => array( 'ufficio', 'comune' ),
    'responses' => array(
        'Gli uffici comunali sono aperti Lun-Ven 9:00-13:00 e Mar-Gio 15:00-17:00.',
    ),
),
```

---

## 🔌 API & Hooks

### Video Stories

#### Actions
```php
// Dopo like video
do_action( 'cdv_video_liked', $video_id, $user_id );

// Dopo view video
do_action( 'cdv_video_viewed', $video_id, $user_id );
```

#### Filters
```php
// Modifica query video stories
apply_filters( 'cdv_video_stories_query_args', $args, $atts );

// Modifica output card video
apply_filters( 'cdv_video_card_html', $html, $video_id );
```

### Gallerie Foto

#### Actions
```php
// Dopo salvataggio galleria
do_action( 'cdv_gallery_saved', $gallery_id, $photo_ids );
```

#### Filters
```php
// Modifica query gallerie
apply_filters( 'cdv_gallerie_query_args', $args, $atts );

// Modifica output foto
apply_filters( 'cdv_photo_item_html', $html, $photo_id, $gallery_id );
```

### Chatbot

#### Filters
```php
// Modifica risposta bot
apply_filters( 'cdv_chatbot_response', $response, $user_message );

// Aggiungi pattern custom
apply_filters( 'cdv_chatbot_patterns', $patterns );

// Modifica context AI
apply_filters( 'cdv_chatbot_context', $context );
```

---

## 🧪 Testing

### Checklist Video

- [ ] Upload video da libreria
- [ ] Embed YouTube
- [ ] Embed Vimeo
- [ ] Formato verticale display corretto
- [ ] Play/pause funziona
- [ ] Like incrementa counter
- [ ] View tracciata correttamente
- [ ] Punti reputazione assegnati
- [ ] Layout grid responsive
- [ ] Layout slider swipeable
- [ ] Layout stories fullscreen

### Checklist Gallerie

- [ ] Upload multiplo foto
- [ ] Drag & drop riordino
- [ ] Remove foto singola
- [ ] Layout grid X colonne
- [ ] Layout masonry
- [ ] Lightbox apre corretto
- [ ] Lightbox chiude (X, ESC, overlay)
- [ ] Caption display
- [ ] Lista gallerie con preview
- [ ] Responsive mobile

### Checklist Chatbot

- [ ] Toggle apre/chiude chat
- [ ] Quick action invia messaggio
- [ ] Typing indicator appare
- [ ] Risposta pattern matching
- [ ] (Se API) Risposta OpenAI
- [ ] (Se API) Risposta Claude
- [ ] Rate limiting funziona
- [ ] Cronologia salva localStorage
- [ ] Responsive mobile fullscreen
- [ ] GA4 events trackati

---

## 🐛 Troubleshooting

### Video non si carica
**Problema**: Video non appare o errore "Video non disponibile"

**Soluzioni**:
1. Verifica URL video valido
2. Controlla formato supportato (MP4, WebM)
3. Per YouTube/Vimeo: usa URL embed
4. Controlla permessi file (chmod 644)
5. Verifica upload_max_filesize PHP

### Galleria vuota
**Problema**: "Nessuna foto in questa galleria"

**Soluzioni**:
1. Verifica foto selezionate e salvate
2. Controlla meta `_cdv_gallery_photos` in database
3. Verifica attachments non eliminati
4. Rigenera thumbnails (plugin Regenerate Thumbnails)

### Chatbot non risponde
**Problema**: Errore o nessuna risposta

**Soluzioni**:
1. Verifica nonce corretta
2. Controlla AJAX URL corretta
3. Se API: verifica API key valida
4. Controlla rate limit non attivo
5. Console browser per errori JS
6. Network tab per errori AJAX

### Lightbox non si apre
**Problema**: Click foto non apre lightbox

**Soluzioni**:
1. Verifica `lightbox="yes"` in shortcode
2. Controlla JS caricato (cdv-media.js)
3. Console per errori JavaScript
4. Disabilita altri plugin lightbox (conflitto)

---

## 📈 Performance

### Ottimizzazioni Video

- ✅ Lazy loading video (preload="metadata")
- ✅ Poster image per preview
- ✅ Auto-pause out of viewport (IntersectionObserver)
- ✅ Conditional loading JS (solo dove serve)
- ✅ Transient cache per views/likes
- ✅ Database index su meta_key video stats

### Ottimizzazioni Gallerie

- ✅ Lazy loading images (loading="lazy")
- ✅ Responsive images (srcset automatic WordPress)
- ✅ CSS columns per masonry (no JS)
- ✅ Lightbox CSS-only (no librerie pesanti)

### Ottimizzazioni Chatbot

- ✅ LocalStorage per cronologia (no DB queries)
- ✅ Debounce typing (no chiamate eccessive)
- ✅ Rate limiting (3s cooldown)
- ✅ Conditional load (solo frontend pubblico)
- ✅ CSS/JS minificato

---

## 🔮 Future Enhancements

### Video Stories (v2.1)
- [ ] Live streaming integrazione
- [ ] Remix/Duet (come TikTok)
- [ ] Filters & stickers
- [ ] Voice-over recording
- [ ] Auto-subtitles (AI)
- [ ] Analytics dashboard (admin)
- [ ] Trending videos widget
- [ ] Video playlists

### Gallerie Foto (v2.2)
- [ ] Watermark automatico
- [ ] Exif data display (camera, lens, settings)
- [ ] Before/After slider
- [ ] 360° photo viewer
- [ ] Image optimization on-upload
- [ ] Bulk edit captions
- [ ] Gallery templates
- [ ] Print-ready export

### Chatbot (v2.3)
- [ ] Voice input/output (speech-to-text)
- [ ] Multi-language support
- [ ] Sentiment analysis
- [ ] Lead capture form
- [ ] Appointment booking
- [ ] FAQ auto-learning
- [ ] Admin dashboard analytics
- [ ] WhatsApp/Telegram integration

---

## 📞 Supporto

Per domande o problemi:

1. Controlla questa documentazione
2. Verifica logs WordPress (WP_DEBUG)
3. Console browser per errori JS
4. Network tab per AJAX issues
5. Contatta sviluppatore

---

## 🎉 Conclusione

Con queste **3 funzionalità major** il plugin Cronaca di Viterbo diventa una **piattaforma multimediale completa** per:

✅ **Engagement visuale** (video stories)  
✅ **Storytelling fotografico** (gallerie professionali)  
✅ **Supporto 24/7** (chatbot AI)  

**Pronto per il lancio in produzione!** 🚀

---

*Documentazione compilata: 2025-10-09*  
*Versione: 2.0.0*  
*Autore: AI Assistant + Francesco Passeri*
