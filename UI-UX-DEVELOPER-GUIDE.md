# 🎨 FP Newspaper - UI/UX Developer Guide

**Versione**: 1.7.0  
**Data**: 3 Novembre 2025  
**Target**: Sviluppatori, Designer, Contributors

---

## 📑 INDICE

1. [Design System](#design-system)
2. [Component Library](#component-library)
3. [CSS Variables](#css-variables)
4. [Best Practices](#best-practices)
5. [JavaScript APIs](#javascript-apis)
6. [Accessibility Guidelines](#accessibility-guidelines)
7. [Examples & Code Snippets](#examples--code-snippets)

---

## 🎨 DESIGN SYSTEM

### Filosofia Design

FP Newspaper utilizza un design system basato su principi di **coerenza, accessibilità e scalabilità**:

- **Mobile-first responsive design**
- **8px spacing system** per consistenza verticale
- **CSS Variables** per theming e personalizzazione
- **WordPress Admin UI compatible**
- **Dark mode ready**

### Color Palette

#### Brand Colors
```css
--fp-color-primary: #2271b1;      /* WordPress Blue */
--fp-color-primary-dark: #135e96;  /* Hover state */
--fp-color-primary-light: #4a8fc7; /* Light variant */
--fp-color-primary-lighter: #e5f0f7; /* Background tint */
```

#### Status Colors
```css
--fp-color-success: #10b981;  /* Green - Actions completed */
--fp-color-error: #ef4444;    /* Red - Errors, warnings critical */
--fp-color-warning: #f59e0b;  /* Orange - Attention needed */
--fp-color-info: #3b82f6;     /* Blue - Informational */
```

#### Neutral Colors
```css
--fp-color-text: #2c3e50;       /* Primary text */
--fp-color-text-light: #666666;  /* Secondary text */
--fp-color-text-muted: #999999;  /* Tertiary/disabled text */

--fp-color-bg-light: #f9f9f9;    /* Light background */
--fp-color-bg-white: #ffffff;    /* White background */
--fp-color-bg-gray: #f0f0f1;     /* Gray background */

--fp-color-border: #dcdcde;      /* Default borders */
--fp-color-border-light: #eeeeee; /* Light borders */
```

### Typography Scale

```css
--fp-font-size-xs: 11px;    /* Small labels, badges */
--fp-font-size-sm: 13px;    /* Body text small */
--fp-font-size-base: 16px;  /* Body text */
--fp-font-size-lg: 18px;    /* Subheadings */
--fp-font-size-xl: 24px;    /* Headings */
--fp-font-size-xxl: 32px;   /* Hero text */
```

### Spacing System (8px Grid)

```css
--fp-spacing-xs: 8px;   /* Tight spacing */
--fp-spacing-sm: 16px;  /* Small spacing */
--fp-spacing-md: 24px;  /* Medium spacing */
--fp-spacing-lg: 32px;  /* Large spacing */
--fp-spacing-xl: 40px;  /* Extra large spacing */
--fp-spacing-xxl: 48px; /* Hero spacing */
```

### Responsive Breakpoints

```css
--fp-screen-sm: 640px;   /* Tablet portrait */
--fp-screen-md: 768px;   /* Tablet landscape */
--fp-screen-lg: 1024px;  /* Desktop */
--fp-screen-xl: 1280px;  /* Large desktop */
```

---

## 🧩 COMPONENT LIBRARY

### 1. Cards

#### Basic Card

```html
<div class="fp-card">
    <div class="fp-card-header">
        <h3 class="fp-card-title">Titolo Card</h3>
        <button class="fp-card-toggle" aria-label="Toggle card">
            <span class="dashicons dashicons-arrow-down"></span>
        </button>
    </div>
    <div class="fp-card-body">
        <!-- Content here -->
        <p>Contenuto della card</p>
    </div>
</div>
```

#### Card Variants

```html
<!-- Primary Card (blue accent) -->
<div class="fp-card fp-card-primary">...</div>

<!-- Success Card (green accent) -->
<div class="fp-card fp-card-success">...</div>

<!-- Warning Card (orange accent) -->
<div class="fp-card fp-card-warning">...</div>

<!-- Error Card (red accent) -->
<div class="fp-card fp-card-error">...</div>
```

#### Stats Card

```html
<div class="fp-stat-card">
    <div class="fp-stat-icon">
        <span class="dashicons dashicons-chart-line"></span>
    </div>
    <div class="fp-stat-number">1,234</div>
    <div class="fp-stat-label">Articoli Totali</div>
    <div class="fp-stat-change positive">
        <span class="dashicons dashicons-arrow-up-alt"></span>
        +12.5%
    </div>
</div>
```

### 2. Buttons

#### Primary Button

```html
<button class="button button-primary">
    <span class="dashicons dashicons-plus-alt"></span>
    Azione Principale
</button>
```

#### Share Button (Frontend)

```html
<a href="https://facebook.com/sharer..." 
   class="fp-share-btn fp-share-facebook"
   data-platform="facebook"
   data-post-id="123"
   aria-label="Condividi su Facebook">
    <svg aria-hidden="true"><!-- Icon --></svg>
    <span>Facebook</span>
</a>
```

#### Button States

```css
/* Default */
.button { ... }

/* Hover */
.button:hover { ... }

/* Loading */
.button.fp-loading {
    position: relative;
    color: transparent;
}

.button.fp-loading::after {
    content: '';
    /* Spinner animation */
}

/* Success */
.button.fp-success {
    background: var(--fp-color-success) !important;
}
```

### 3. Alerts & Notices

```html
<!-- Success Alert -->
<div class="fp-alert success">
    <div class="fp-alert-icon">✓</div>
    <div class="fp-alert-content">
        <div class="fp-alert-title">Operazione Completata</div>
        <div class="fp-alert-message">I dati sono stati salvati con successo.</div>
    </div>
</div>

<!-- Warning Alert -->
<div class="fp-alert warning">
    <div class="fp-alert-icon">⚠</div>
    <div class="fp-alert-content">
        <div class="fp-alert-title">Attenzione</div>
        <div class="fp-alert-message">Alcuni dati potrebbero non essere aggiornati.</div>
        <div class="fp-alert-actions">
            <button class="button button-small">Aggiorna Ora</button>
        </div>
    </div>
</div>

<!-- Error Alert -->
<div class="fp-alert error">
    <div class="fp-alert-icon">✕</div>
    <div class="fp-alert-content">
        <div class="fp-alert-title">Errore</div>
        <div class="fp-alert-message">Impossibile completare l'operazione.</div>
    </div>
</div>
```

### 4. Activity Feed

```html
<ul class="fp-activity-feed">
    <li class="fp-activity-item">
        <div class="fp-activity-icon">
            <span class="dashicons dashicons-admin-post"></span>
        </div>
        <div class="fp-activity-content">
            <div class="fp-activity-title">Nuovo articolo pubblicato</div>
            <div class="fp-activity-meta">
                <a href="#">Mario Rossi</a> • 5 minuti fa
            </div>
        </div>
    </li>
    <!-- More items... -->
</ul>
```

### 5. Tables

```html
<table class="fp-dashboard-table fp-sortable-table">
    <thead>
        <tr>
            <th data-sort="name">Nome</th>
            <th data-sort="count">Articoli</th>
            <th data-sort="status">Stato</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td data-column="name">
                <img src="avatar.jpg" class="fp-table-avatar" alt="">
                Mario Rossi
            </td>
            <td data-column="count">42</td>
            <td data-column="status">
                <span class="fp-table-badge published">Pubblicato</span>
            </td>
        </tr>
    </tbody>
</table>
```

### 6. Charts

```html
<div class="fp-chart-container fp-chart-large">
    <canvas id="my-chart"></canvas>
</div>

<script>
new Chart(document.getElementById('my-chart'), {
    type: 'line',
    data: {
        labels: ['Gen', 'Feb', 'Mar'],
        datasets: [{
            label: 'Articoli',
            data: [10, 20, 15],
            borderColor: 'var(--fp-color-primary)',
            backgroundColor: 'rgba(34, 113, 177, 0.1)'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false
    }
});
</script>
```

### 7. Filters

```html
<div class="fp-dashboard-filters">
    <div class="fp-filter-group">
        <label class="fp-filter-label">Periodo</label>
        <select class="fp-filter-select" id="fp-date-range">
            <option value="7">Ultimi 7 giorni</option>
            <option value="30" selected>Ultimi 30 giorni</option>
            <option value="90">Ultimi 90 giorni</option>
        </select>
    </div>
    
    <div class="fp-filter-group">
        <label class="fp-filter-label">Autore</label>
        <select class="fp-filter-select" id="fp-author-filter">
            <option value="all">Tutti</option>
            <option value="1">Mario Rossi</option>
        </select>
    </div>
    
    <div class="fp-filter-actions">
        <button class="button button-primary" id="fp-apply-filters">
            Applica Filtri
        </button>
    </div>
</div>
```

### 8. Empty States

```html
<div class="fp-empty-state">
    <span class="dashicons dashicons-admin-post"></span>
    <div class="fp-empty-state-title">Nessun Articolo Trovato</div>
    <div class="fp-empty-state-message">
        Non ci sono articoli che corrispondono ai tuoi criteri.
    </div>
    <button class="button button-primary">Crea Nuovo Articolo</button>
</div>
```

### 9. Loading States

```html
<!-- Loading Overlay -->
<div id="fp-dashboard-loading" class="fp-dashboard-loading visible">
    <span class="fp-loading-spinner"></span>
    <span>Caricamento...</span>
</div>

<!-- Skeleton Loader -->
<div class="fp-skeleton" style="width: 200px; height: 16px;"></div>
<div class="fp-skeleton" style="width: 150px; height: 16px; margin-top: 8px;"></div>
```

---

## 🎨 CSS VARIABLES USAGE

### Spacing

```css
/* Mobile-first padding */
.component {
    padding: var(--fp-spacing-sm);
}

/* Desktop larger padding */
@media (min-width: 640px) {
    .component {
        padding: var(--fp-spacing-lg);
    }
}

/* Gap in flex/grid */
.grid {
    display: grid;
    gap: var(--fp-spacing-md);
}
```

### Colors

```css
/* Background */
.card {
    background: var(--fp-color-bg-white);
    border: 1px solid var(--fp-color-border);
}

/* Text */
.title {
    color: var(--fp-color-text);
}

.subtitle {
    color: var(--fp-color-text-light);
}

/* Status */
.success {
    color: var(--fp-color-success);
    background: color-mix(in srgb, var(--fp-color-success) 10%, transparent);
}
```

### Typography

```css
.heading {
    font-size: var(--fp-font-size-xl);
    font-weight: var(--fp-font-weight-bold);
    line-height: var(--fp-line-height-tight);
}

.body-text {
    font-size: var(--fp-font-size-base);
    line-height: var(--fp-line-height-relaxed);
}
```

### Shadows

```css
.card {
    box-shadow: var(--fp-shadow-sm);
}

.card:hover {
    box-shadow: var(--fp-shadow-md);
}

.modal {
    box-shadow: var(--fp-shadow-xl);
}
```

---

## ✨ BEST PRACTICES

### 1. Mobile-First Responsive Design

```css
/* ✅ CORRETTO - Mobile first */
.element {
    flex-direction: column; /* Mobile default */
    padding: var(--fp-spacing-sm);
}

@media (min-width: 640px) {
    .element {
        flex-direction: row; /* Desktop enhancement */
        padding: var(--fp-spacing-lg);
    }
}

/* ❌ SBAGLIATO - Desktop first */
.element {
    flex-direction: row; /* Assume desktop */
    padding: var(--fp-spacing-lg);
}

@media (max-width: 639px) {
    .element {
        flex-direction: column; /* Override for mobile */
    }
}
```

### 2. Touch-Friendly Targets

```css
/* ✅ CORRETTO - Touch target 44x44px minimum */
.button {
    min-height: 44px;
    min-width: 44px;
    padding: 12px 20px;
}

/* ❌ SBAGLIATO - Too small for touch */
.button {
    padding: 4px 8px; /* < 44px */
}
```

### 3. Accessibility

```html
<!-- ✅ CORRETTO - ARIA labels -->
<button class="fp-share-btn" 
        aria-label="Condividi su Facebook"
        data-platform="facebook">
    <svg aria-hidden="true">...</svg>
    <span>Facebook</span>
</button>

<!-- ✅ CORRETTO - Focus states -->
<style>
.button:focus {
    outline: 3px solid var(--fp-color-primary);
    outline-offset: 2px;
}

/* Only keyboard focus, not mouse */
.button:focus:not(:focus-visible) {
    outline: none;
}
</style>

<!-- ✅ CORRETTO - Semantic HTML -->
<main role="main">
    <section aria-labelledby="stats-title">
        <h2 id="stats-title">Statistiche</h2>
        <!-- Content -->
    </section>
</main>
```

### 4. Performance

```javascript
// ✅ CORRETTO - Debounce scroll events
let scrollTimeout;
window.addEventListener('scroll', () => {
    clearTimeout(scrollTimeout);
    scrollTimeout = setTimeout(() => {
        // Handle scroll
    }, 100);
});

// ✅ CORRETTO - Intersection Observer for lazy load
const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            loadContent(entry.target);
            observer.unobserve(entry.target);
        }
    });
});

document.querySelectorAll('.lazy').forEach(el => {
    observer.observe(el);
});
```

### 5. CSS Organization

```css
/* ✅ CORRETTO - Organized by sections */

/* ========================================
   COMPONENT NAME
   ======================================== */

/* Base styles */
.component {
    ...
}

/* Variants */
.component.variant { ... }

/* States */
.component:hover { ... }
.component:focus { ... }
.component.is-active { ... }

/* Responsive */
@media (min-width: 640px) {
    .component { ... }
}
```

---

## 🔧 JAVASCRIPT APIs

### Dashboard API

```javascript
// Access global dashboard object
if (window.FPDashboard) {
    
    // Refresh dashboard
    FPDashboard.refreshDashboard();
    
    // Show notice
    FPDashboard.showNotice('Operazione completata', 'success');
    
    // Access chart instances
    console.log(FPDashboard.charts.publications);
    
    // Update chart data
    FPDashboard.charts.publications.data.labels = ['New', 'Labels'];
    FPDashboard.charts.publications.update();
}
```

### Frontend API

```javascript
// Access global frontend object
if (window.FPNewspaper) {
    
    // Cookie management
    FPNewspaper.setCookie('preference', 'value', 365);
    const value = FPNewspaper.getCookie('preference');
    
    // Share button tracking (automatic)
    // Just add data attributes:
    // <button class="fp-share-btn" data-platform="facebook" data-post-id="123">
}

// Dark mode toggle
if (window.FPNewspaperDarkMode) {
    FPNewspaperDarkMode.init();
}
```

### AJAX Requests

```javascript
// Dashboard refresh
jQuery.post(fpDashboardData.ajaxurl, {
    action: 'fp_refresh_dashboard',
    nonce: fpDashboardData.nonce,
    date_range: 30,
    author: 'all'
})
.done(function(response) {
    if (response.success) {
        console.log('Data:', response.data);
    }
})
.fail(function(xhr, status, error) {
    console.error('Error:', error);
});
```

---

## ♿ ACCESSIBILITY GUIDELINES

### 1. Keyboard Navigation

✅ **Tutti gli elementi interattivi devono essere accessibili da tastiera**

```html
<!-- ✅ CORRETTO -->
<button tabindex="0">Click me</button>
<a href="#section">Link</a>

<!-- ❌ SBAGLIATO -->
<div onclick="...">Click me</div> <!-- Not keyboard accessible -->
```

### 2. Focus Indicators

✅ **Focus states devono essere chiari e visibili**

```css
/* ✅ CORRETTO */
.button:focus {
    outline: 3px solid var(--fp-color-primary);
    outline-offset: 2px;
}

/* ❌ SBAGLIATO */
.button:focus {
    outline: none; /* Never remove outlines without alternative */
}
```

### 3. Screen Reader Support

```html
<!-- Screen reader only text -->
<span class="fp-sr-only">Testo nascosto visivamente ma leggibile</span>

<!-- Hide decorative content -->
<svg aria-hidden="true">...</svg>
<span class="emoji" aria-hidden="true">📊</span>

<!-- Descriptive labels -->
<button aria-label="Chiudi modale">
    <span aria-hidden="true">×</span>
</button>
```

### 4. Color Contrast

✅ **Contrasto minimo 4.5:1 per testo normale, 3:1 per testo large**

```css
/* ✅ CORRETTO - Buon contrasto */
.text {
    color: #2c3e50; /* Dark gray on white = 12:1 */
    background: white;
}

/* ⚠️ ATTENZIONE - Basso contrasto */
.text-light {
    color: #999; /* Light gray on white = 2.8:1 - Solo per large text */
}
```

---

## 💡 EXAMPLES & CODE SNIPPETS

### Complete Dashboard Widget

```php
// In your plugin file
public function add_dashboard_widget() {
    ?>
    <div class="fp-card fp-card-primary">
        <div class="fp-card-header">
            <h3 class="fp-card-title">
                <span class="dashicons dashicons-chart-line"></span>
                Statistiche Giornaliere
            </h3>
            <button class="fp-card-toggle" aria-label="Toggle widget">
                <span class="dashicons dashicons-arrow-down"></span>
            </button>
        </div>
        <div class="fp-card-body">
            <div class="fp-stats-grid">
                <div class="fp-stat-card">
                    <div class="fp-stat-number">42</div>
                    <div class="fp-stat-label">Articoli</div>
                    <div class="fp-stat-change positive">
                        <span class="dashicons dashicons-arrow-up-alt"></span>
                        +12%
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
}
```

### Interactive Chart with AJAX

```javascript
// Initialize chart
const ctx = document.getElementById('my-chart');
const myChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: ['Mon', 'Tue', 'Wed'],
        datasets: [{
            label: 'Views',
            data: [10, 20, 15],
            borderColor: 'var(--fp-color-primary)'
        }]
    }
});

// Update with AJAX
function updateChart() {
    jQuery.post(ajaxurl, {
        action: 'get_chart_data',
        nonce: myData.nonce
    })
    .done(function(response) {
        if (response.success) {
            myChart.data.labels = response.data.labels;
            myChart.data.datasets[0].data = response.data.values;
            myChart.update('active'); // Animated update
        }
    });
}
```

### Responsive Grid Layout

```html
<div class="fp-grid-4">
    <div class="fp-card">Card 1</div>
    <div class="fp-card">Card 2</div>
    <div class="fp-card">Card 3</div>
    <div class="fp-card">Card 4</div>
</div>

<style>
.fp-grid-4 {
    display: grid;
    grid-template-columns: 1fr; /* Mobile: 1 column */
    gap: var(--fp-spacing-md);
}

@media (min-width: 768px) {
    .fp-grid-4 {
        grid-template-columns: repeat(2, 1fr); /* Tablet: 2 columns */
    }
}

@media (min-width: 1024px) {
    .fp-grid-4 {
        grid-template-columns: repeat(4, 1fr); /* Desktop: 4 columns */
    }
}
</style>
```

---

## 🚀 QUICK START CHECKLIST

Quando crei un nuovo componente UI:

- [ ] Usa CSS Variables del design system
- [ ] Implementa mobile-first responsive
- [ ] Touch targets min 44x44px
- [ ] Aggiungi ARIA labels appropriati
- [ ] Focus states visibili
- [ ] Test keyboard navigation
- [ ] Verifica contrasto colori (4.5:1)
- [ ] Testa con screen reader
- [ ] Supporta prefers-reduced-motion
- [ ] Documenta componente in questo file

---

## 📚 RISORSE

### Tools

- **Contrast Checker**: [WebAIM](https://webaim.org/resources/contrastchecker/)
- **Accessibility Testing**: [WAVE](https://wave.webaim.org/)
- **Chart.js Docs**: [chartjs.org](https://www.chartjs.org/)
- **WordPress UI**: [wordpress.org/gutenberg](https://wordpress.org/gutenberg/)

### Standards

- **WCAG 2.1 AA**: [W3C Guidelines](https://www.w3.org/WAI/WCAG21/quickref/)
- **WordPress Coding Standards**: [make.wordpress.org](https://make.wordpress.org/core/handbook/best-practices/)
- **Apple HIG**: [developer.apple.com/design](https://developer.apple.com/design/human-interface-guidelines/)

---

## 🤝 CONTRIBUTING

Per contribuire nuovi componenti:

1. Segui design system esistente
2. Scrivi CSS accessibile e responsive
3. Aggiungi documentazione qui
4. Testa su mobile/tablet/desktop
5. Verifica accessibilità (WCAG AA)
6. Submit PR con screenshots

---

**Ultima revisione**: 3 Novembre 2025  
**Versione guida**: 1.0.0  
**Prossimo update**: Quando vengono aggiunti nuovi componenti

**Domande?** Consulta il team o apri un issue su GitHub.

