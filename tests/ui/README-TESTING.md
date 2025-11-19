# 🧪 FP Newspaper - UI Testing Suite Setup

**Versione**: 1.0.0  
**Data**: 3 Novembre 2025  
**Status**: Template/Skeleton - Requires Setup

---

## 📋 OVERVIEW

Questa directory contiene i test UI per FP Newspaper. I test coprono:

- ✅ Dashboard admin interattività
- ✅ Charts rendering e funzionalità
- ✅ AJAX requests e responses
- ✅ Responsive design (mobile/tablet/desktop)
- ✅ Accessibility (keyboard nav, ARIA)
- ✅ Frontend components (share, author box, etc.)

---

## 🛠️ SETUP

### Prerequisiti

```bash
# Node.js 18+ required
node --version

# Install Cypress
npm install --save-dev cypress

# Install additional dependencies
npm install --save-dev @testing-library/cypress
npm install --save-dev cypress-axe  # For accessibility testing
```

### Configurazione Cypress

Crea `cypress.config.js` nella root del plugin:

```javascript
const { defineConfig } = require('cypress');

module.exports = defineConfig({
  e2e: {
    baseUrl: 'http://localhost/wp-admin',
    specPattern: 'tests/ui/**/*.cy.js',
    supportFile: 'tests/ui/support/e2e.js',
    videosFolder: 'tests/ui/videos',
    screenshotsFolder: 'tests/ui/screenshots',
    viewportWidth: 1280,
    viewportHeight: 720,
    
    env: {
      // WordPress admin credentials
      adminUsername: 'admin',
      adminPassword: 'password',
    },
    
    setupNodeEvents(on, config) {
      // Implement node event listeners here
    },
  },
});
```

---

## 📁 STRUTTURA

```
tests/ui/
├── README-TESTING.md           # This file
├── cypress.config.js           # Cypress configuration
├── support/
│   ├── e2e.js                  # Global setup
│   └── commands.js             # Custom commands
├── fixtures/
│   └── dashboard-data.json     # Mock data
└── e2e/
    ├── dashboard.cy.js         # Dashboard tests
    ├── charts.cy.js            # Charts tests
    ├── ajax.cy.js              # AJAX tests
    ├── responsive.cy.js        # Responsive tests
    ├── accessibility.cy.js     # A11y tests
    └── frontend.cy.js          # Frontend tests
```

---

## 🧪 TEST EXAMPLES

### Dashboard Tests

Create `tests/ui/e2e/dashboard.cy.js`:

```javascript
describe('Editorial Dashboard', () => {
    beforeEach(() => {
        // Login to WordPress admin
        cy.login(Cypress.env('adminUsername'), Cypress.env('adminPassword'));
        cy.visit('/admin.php?page=fp-editorial-dashboard');
    });

    it('should load dashboard page', () => {
        cy.contains('Editorial Dashboard').should('be.visible');
        cy.get('.fp-editorial-dashboard').should('exist');
    });

    it('should render stats cards', () => {
        cy.get('.fp-stat-card').should('have.length.at.least', 3);
        cy.get('.fp-stat-number').should('not.be.empty');
    });

    it('should toggle cards on click', () => {
        cy.get('.fp-card-toggle').first().click();
        cy.get('.fp-card').first().should('have.class', 'fp-card-collapsed');
        
        cy.get('.fp-card-toggle').first().click();
        cy.get('.fp-card').first().should('not.have.class', 'fp-card-collapsed');
    });

    it('should have working filter controls', () => {
        cy.get('#fp-date-range').select('7');
        cy.get('#fp-author-filter').select('all');
        cy.get('#fp-apply-filters').should('be.visible');
    });
});
```

### Charts Tests

Create `tests/ui/e2e/charts.cy.js`:

```javascript
describe('Dashboard Charts', () => {
    beforeEach(() => {
        cy.login();
        cy.visit('/admin.php?page=fp-editorial-dashboard');
    });

    it('should render publications chart', () => {
        cy.get('#fp-publications-chart').should('be.visible');
        cy.get('#fp-publications-chart').should('have.prop', 'tagName', 'CANVAS');
    });

    it('should render productivity donut chart', () => {
        cy.get('#fp-productivity-chart').should('be.visible');
        cy.window().then((win) => {
            expect(win.FPDashboard).to.exist;
            expect(win.FPDashboard.charts.productivity).to.exist;
        });
    });

    it('should render authors bar chart', () => {
        cy.get('#fp-authors-chart').should('be.visible');
    });

    it('should update chart on filter change', () => {
        cy.get('#fp-date-range').select('7');
        cy.wait(1000); // Wait for AJAX
        // Verify chart updated
        cy.window().then((win) => {
            const chart = win.FPDashboard.charts.publications;
            expect(chart.data.labels).to.have.length.greaterThan(0);
        });
    });
});
```

### AJAX Tests

Create `tests/ui/e2e/ajax.cy.js`:

```javascript
describe('AJAX Functionality', () => {
    beforeEach(() => {
        cy.login();
        cy.visit('/admin.php?page=fp-editorial-dashboard');
    });

    it('should refresh dashboard via AJAX', () => {
        cy.intercept('POST', '**/admin-ajax.php', (req) => {
            if (req.body.includes('fp_refresh_dashboard')) {
                req.alias = 'refreshDashboard';
            }
        });

        cy.get('#fp-refresh-dashboard').click();
        
        cy.wait('@refreshDashboard').its('response.statusCode').should('eq', 200);
        cy.get('.fp-dashboard-loading').should('be.visible');
        cy.get('.notice-success').should('contain', 'aggiornato');
    });

    it('should handle AJAX errors gracefully', () => {
        cy.intercept('POST', '**/admin-ajax.php', {
            statusCode: 500,
            body: { success: false, data: { message: 'Server error' } }
        });

        cy.get('#fp-refresh-dashboard').click();
        cy.get('.notice-error').should('be.visible');
    });
});
```

### Responsive Tests

Create `tests/ui/e2e/responsive.cy.js`:

```javascript
describe('Responsive Design', () => {
    beforeEach(() => {
        cy.login();
        cy.visit('/admin.php?page=fp-editorial-dashboard');
    });

    const viewports = [
        { name: 'mobile', width: 375, height: 667 },
        { name: 'tablet', width: 768, height: 1024 },
        { name: 'desktop', width: 1280, height: 720 },
    ];

    viewports.forEach(({ name, width, height }) => {
        it(`should render correctly on ${name}`, () => {
            cy.viewport(width, height);
            cy.get('.fp-editorial-dashboard').should('be.visible');
            
            // Check stats grid responsiveness
            if (name === 'mobile') {
                cy.get('.fp-stats-grid').should('have.css', 'grid-template-columns', '1fr');
            }
            
            // Check charts are responsive
            cy.get('.fp-chart-container canvas').each(($canvas) => {
                cy.wrap($canvas).should('have.prop', 'width').and('be.greaterThan', 0);
            });
        });
    });

    it('should have touch-friendly buttons on mobile', () => {
        cy.viewport('iphone-x');
        cy.get('.fp-share-btn').each(($btn) => {
            cy.wrap($btn).should('have.css', 'min-height').and('match', /44px/);
        });
    });
});
```

### Accessibility Tests

Create `tests/ui/e2e/accessibility.cy.js`:

```javascript
describe('Accessibility', () => {
    beforeEach(() => {
        cy.login();
        cy.visit('/admin.php?page=fp-editorial-dashboard');
        cy.injectAxe(); // From cypress-axe
    });

    it('should pass WCAG 2.1 AA standards', () => {
        cy.checkA11y();
    });

    it('should have proper ARIA labels', () => {
        cy.get('.fp-card-toggle').each(($btn) => {
            cy.wrap($btn).should('have.attr', 'aria-label');
        });

        cy.get('.fp-share-btn').each(($btn) => {
            cy.wrap($btn).should('have.attr', 'aria-label');
        });
    });

    it('should be keyboard navigable', () => {
        cy.get('body').tab(); // Tab to first focusable element
        cy.focused().should('have.class', 'button'); // Check focus

        // Tab through all interactive elements
        cy.get('.fp-card-toggle').first().focus().type('{enter}');
        cy.get('.fp-card').first().should('have.class', 'fp-card-collapsed');
    });

    it('should have visible focus indicators', () => {
        cy.get('.button').first().focus();
        cy.focused().should('have.css', 'outline').and('not.eq', 'none');
    });

    it('should respect prefers-reduced-motion', () => {
        cy.visit('/admin.php?page=fp-editorial-dashboard', {
            onBeforeLoad(win) {
                Object.defineProperty(win, 'matchMedia', {
                    value: () => ({
                        matches: true,
                        media: '(prefers-reduced-motion: reduce)',
                    }),
                });
            },
        });

        // Check animations are disabled
        cy.get('.fp-stat-card').should('have.css', 'transition-duration', '0.01ms');
    });
});
```

### Frontend Tests

Create `tests/ui/e2e/frontend.cy.js`:

```javascript
describe('Frontend Components', () => {
    beforeEach(() => {
        cy.visit('/sample-post/'); // Visit a test post
    });

    it('should render author box', () => {
        cy.get('.fp-author-box').should('be.visible');
        cy.get('.fp-author-name').should('not.be.empty');
        cy.get('.fp-author-bio').should('not.be.empty');
    });

    it('should render related articles', () => {
        cy.get('.fp-related-articles').should('be.visible');
        cy.get('.fp-related-item').should('have.length.at.least', 1);
    });

    it('should track share button clicks', () => {
        cy.intercept('POST', '**/admin-ajax.php', (req) => {
            if (req.body.includes('fp_track_share')) {
                req.alias = 'trackShare';
            }
        });

        cy.get('.fp-share-facebook').click();
        cy.wait('@trackShare').its('response.statusCode').should('eq', 200);
        cy.get('.fp-share-facebook').should('have.class', 'fp-success');
    });

    it('should lazy load images', () => {
        cy.get('.fp-related-thumb img').should('have.attr', 'loading', 'lazy');
    });

    it('should animate on scroll', () => {
        cy.get('.fp-author-box').should('have.class', 'fp-fade-in');
        cy.scrollTo(0, 500);
        cy.get('.fp-author-box').should('have.class', 'fp-visible');
    });
});
```

---

## 🚀 RUNNING TESTS

### Comandi

```bash
# Open Cypress GUI
npx cypress open

# Run all tests headless
npx cypress run

# Run specific test file
npx cypress run --spec "tests/ui/e2e/dashboard.cy.js"

# Run with specific browser
npx cypress run --browser chrome

# Generate coverage report
npx cypress run --coverage
```

### CI/CD Integration

Add to `.github/workflows/cypress.yml`:

```yaml
name: Cypress Tests

on: [push, pull_request]

jobs:
  cypress-run:
    runs-on: ubuntu-latest
    
    steps:
      - name: Checkout
        uses: actions/checkout@v3
      
      - name: Setup WordPress
        uses: wp-cli/wordpress-install-action@v2
        with:
          php-version: '8.1'
          wordpress-version: 'latest'
      
      - name: Install plugin
        run: |
          wp plugin activate fp-newspaper
      
      - name: Cypress run
        uses: cypress-io/github-action@v5
        with:
          config-file: tests/ui/cypress.config.js
          spec: tests/ui/e2e/**/*.cy.js
      
      - name: Upload screenshots
        uses: actions/upload-artifact@v3
        if: failure()
        with:
          name: cypress-screenshots
          path: tests/ui/screenshots
```

---

## 📊 COVERAGE

### Setup Coverage

```bash
npm install --save-dev @cypress/code-coverage
```

Add to `cypress.config.js`:

```javascript
require('@cypress/code-coverage/task')(on, config);
```

### Generate Reports

```bash
# Run tests with coverage
npx cypress run --coverage

# View coverage report
open coverage/lcov-report/index.html
```

---

## 🔍 DEBUGGING

### Visual Debugging

```javascript
// In test file
cy.pause(); // Pause test execution
cy.debug(); // Open DevTools
cy.screenshot('debug-screenshot');
```

### Custom Commands

Add to `tests/ui/support/commands.js`:

```javascript
// Login command
Cypress.Commands.add('login', (username, password) => {
    username = username || Cypress.env('adminUsername');
    password = password || Cypress.env('adminPassword');
    
    cy.visit('/wp-login.php');
    cy.get('#user_login').type(username);
    cy.get('#user_pass').type(password);
    cy.get('#wp-submit').click();
    cy.url().should('include', '/wp-admin');
});

// Tab key simulation
Cypress.Commands.add('tab', { prevSubject: 'optional' }, (subject) => {
    cy.wrap(subject).trigger('keydown', { keyCode: 9, which: 9, key: 'Tab' });
});
```

---

## 📈 BEST PRACTICES

1. **Test Isolation**: Ogni test deve essere indipendente
2. **Data Fixtures**: Usa mock data per test consistenti
3. **Wait Strategies**: Preferisci `cy.wait('@alias')` a `cy.wait(1000)`
4. **Selectors**: Usa `data-test-id` invece di classi CSS
5. **Assertions**: Sempre esplicite e descrittive
6. **Cleanup**: Ripulisci dati di test dopo ogni run

---

## 📝 TODO

- [ ] Setup Cypress environment
- [ ] Write custom commands
- [ ] Create mock data fixtures
- [ ] Implement all test suites
- [ ] Add visual regression testing (Percy/Applitools)
- [ ] Setup CI/CD pipeline
- [ ] Generate coverage reports
- [ ] Document test scenarios

---

## 🤝 CONTRIBUTING

Per aggiungere nuovi test:

1. Crea file `*.cy.js` in `tests/ui/e2e/`
2. Scrivi test descrittivi
3. Verifica che passino localmente
4. Submit PR con coverage report

---

**Status**: Template pronto per implementazione  
**Next Step**: Run `npm install cypress` e inizia testing! 🚀

