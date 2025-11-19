# Contributing to FP Newspaper

Grazie per il tuo interesse nel contribuire a FP Newspaper! 🎉

## 📋 Come Contribuire

### Segnalare Bug

1. Verifica che il bug non sia già stato segnalato nelle [Issues](https://github.com/franpass87/FP-Newspaper/issues)
2. Apri una nuova issue usando il template "Bug Report"
3. Includi:
   - Descrizione dettagliata del problema
   - Steps per riprodurre
   - Comportamento atteso vs attuale
   - Screenshots se applicabile
   - Versione WordPress e PHP
   - Theme e altri plugin attivi

### Suggerire Funzionalità

1. Apri una issue con label "enhancement"
2. Descrivi:
   - Caso d'uso della funzionalità
   - Beneficio per gli utenti
   - Implementazione suggerita (opzionale)

### Pull Requests

1. **Fork** il repository
2. Crea un **branch** per la tua feature:
   ```bash
   git checkout -b feature/AmazingFeature
   ```

3. **Sviluppa** seguendo le linee guida:
   - WordPress Coding Standards
   - PSR-4 per autoloading
   - PHPDoc completo
   - Sanitizza input
   - Escape output
   - Use prepared statements

4. **Testa** le modifiche:
   - Zero errori PHP
   - Compatibilità WordPress 6.0+
   - Compatibilità PHP 7.4-8.3
   - Test su single site e multisite

5. **Commit** con messaggi chiari:
   ```bash
   git commit -m "Add: Descrizione feature"
   git commit -m "Fix: Descrizione bug fix"
   git commit -m "Refactor: Descrizione refactoring"
   ```

6. **Push** al tuo fork:
   ```bash
   git push origin feature/AmazingFeature
   ```

7. Apri una **Pull Request** su GitHub

## 💻 Coding Standards

### PHP

```php
// ✅ BUONO
if ( ! empty( $variable ) ) {
    $result = sanitize_text_field( $variable );
    echo esc_html( $result );
}

// ❌ CATTIVO
if(!empty($variable)) {
    echo $variable;  // Non sanitizzato!
}
```

### Security

- ✅ **SEMPRE** sanitizza input: `sanitize_text_field()`, `absint()`, etc.
- ✅ **SEMPRE** escape output: `esc_html()`, `esc_url()`, `wp_kses_post()`
- ✅ **SEMPRE** usa prepared statements per query DB
- ✅ **SEMPRE** verifica nonce nei form
- ✅ **SEMPRE** controlla capabilities

### Naming Conventions

- **Classi:** PascalCase (`MyClassName`)
- **Metodi:** snake_case (`my_method_name`)
- **Costanti:** UPPERCASE (`MY_CONSTANT`)
- **Variabili:** snake_case (`$my_variable`)
- **Hooks:** snake_case con prefix (`fp_newspaper_my_hook`)

## 🧪 Testing

Prima di submitare PR, testa:

```bash
# Sintassi PHP
php -l file.php

# Se hai PHPCS installato
phpcs --standard=WordPress file.php

# Test funzionale
# Crea articoli, testa shortcodes, verifica REST API
```

## 📝 Documentazione

Se aggiungi nuove features, aggiorna:

- README.md
- README-DEV.md (se riguarda sviluppatori)
- CHANGELOG.md
- PHPDoc nei file modificati
- src/Hooks.php (se aggiungi hooks/filters)

## 🎯 Priorità

Contributi particolarmente apprezzati in queste aree:

1. **Bug fixes** (alta priorità)
2. **Security improvements** (alta priorità)
3. **Performance optimizations**
4. **Accessibility improvements**
5. **Gutenberg blocks**
6. **Email notifications**
7. **Traduzioni**
8. **Unit tests** (PHPUnit)

## 🚫 Cosa NON Fare

- ❌ Non modificare `.gitignore` senza motivo
- ❌ Non commitare file vendor/
- ❌ Non modificare CHANGELOG.md (lo fa il maintainer)
- ❌ Non aggiungere dipendenze senza discussione
- ❌ Non modificare database schema senza migration
- ❌ Non rimuovere security checks

## 🔍 Code Review

Tutte le PR passeranno code review che verifica:

- ✅ Coding standards
- ✅ Security (nonce, sanitization, escape)
- ✅ Performance (no N+1 queries)
- ✅ Compatibility (WordPress 6.0+, PHP 7.4+)
- ✅ Documentation (PHPDoc, README updates)
- ✅ Testing (manuale o automatico)

## 📧 Domande?

Se hai domande, puoi:

- Aprire una [Discussion](https://github.com/franpass87/FP-Newspaper/discussions)
- Contattare via email: info@francescopasseri.com

## 📜 Licenza

Contribuendo, accetti che i tuoi contributi siano rilasciati sotto licenza GPL v2 or later.

---

**Grazie per contribuire a FP Newspaper!** 🙏

Insieme rendiamo questo plugin ancora migliore! 🚀







