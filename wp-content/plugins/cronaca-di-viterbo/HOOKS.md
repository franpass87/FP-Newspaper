# Cronaca di Viterbo - Hooks & Filters

Documentazione completa di tutti gli hooks (actions e filters) disponibili nel plugin.

## Actions

### `cdv_proposta_submitted`

Eseguito dopo l'invio con successo di una proposta.

**Parametri:**
- `int $post_id` - ID della proposta creata
- `int $quartiere_id` - ID termine quartiere
- `int $tematica_id` - ID termine tematica

**Esempio:**
```php
add_action('cdv_proposta_submitted', function($post_id, $quartiere_id, $tematica_id) {
    // Invia email al moderatore
    wp_mail(
        get_option('admin_email'),
        'Nuova proposta da moderare',
        "Proposta #{$post_id} in attesa di moderazione"
    );
}, 10, 3);
```

---

### `cdv_proposta_voted`

Eseguito dopo una votazione con successo.

**Parametri:**
- `int $post_id` - ID della proposta votata
- `int $new_votes` - Nuovo conteggio voti

**Esempio:**
```php
add_action('cdv_proposta_voted', function($post_id, $new_votes) {
    // Notifica autore se raggiunge 10 voti
    if ($new_votes === 10) {
        $author_id = get_post_field('post_author', $post_id);
        // Invia notifica...
    }
}, 10, 2);
```

---

## Filters

### `cdv_proposta_form_fields`

Filtra i campi del form proposte prima del render.

**Parametri:**
- `array $fields` - Array campi form

**Esempio:**
```php
add_filter('cdv_proposta_form_fields', function($fields) {
    // Aggiungi campo email
    $fields['email'] = [
        'type' => 'email',
        'label' => 'Email',
        'required' => true
    ];
    return $fields;
});
```

---

### `cdv_vote_cooldown_seconds`

Filtra la durata del cooldown votazione (default: 3600s = 1h).

**Parametri:**
- `int $seconds` - Secondi di cooldown

**Esempio:**
```php
add_filter('cdv_vote_cooldown_seconds', function($seconds) {
    // Riduci a 30 minuti
    return 1800;
});
```

---

### `cdv_rate_limit_seconds`

Filtra la durata del rate limit invio proposte (default: 60s).

**Parametri:**
- `int $seconds` - Secondi di rate limit

**Esempio:**
```php
add_filter('cdv_rate_limit_seconds', function($seconds) {
    // Aumenta a 2 minuti
    return 120;
});
```

---

### `cdv_schema_dossier`

Filtra lo schema JSON-LD per dossier.

**Parametri:**
- `array $schema` - Array schema.org
- `WP_Post $post` - Post dossier

**Esempio:**
```php
add_filter('cdv_schema_dossier', function($schema, $post) {
    // Aggiungi organizzazione publisher
    $schema['publisher'] = [
        '@type' => 'Organization',
        'name' => 'Cronaca di Viterbo',
        'logo' => get_site_icon_url()
    ];
    return $schema;
}, 10, 2);
```

---

### `cdv_ga4_events_enabled`

Filtra se gli eventi GA4 sono abilitati (default: true se opzione attiva).

**Parametri:**
- `bool $enabled` - Se abilitato

**Esempio:**
```php
add_filter('cdv_ga4_events_enabled', function($enabled) {
    // Disabilita in staging
    if (wp_get_environment_type() === 'staging') {
        return false;
    }
    return $enabled;
});
```

---

## Custom Query Filters

### `cdv_proposte_query_args`

Filtra gli argomenti WP_Query per lista proposte.

**Parametri:**
- `array $args` - Array argomenti WP_Query
- `array $atts` - Attributi shortcode

**Esempio:**
```php
add_filter('cdv_proposte_query_args', function($args, $atts) {
    // Escludi proposte dell'autore corrente
    if (is_user_logged_in()) {
        $args['author__not_in'] = [get_current_user_id()];
    }
    return $args;
}, 10, 2);
```

---

### `cdv_eventi_query_args`

Filtra gli argomenti WP_Query per lista eventi.

**Parametri:**
- `array $args` - Array argomenti WP_Query
- `array $atts` - Attributi shortcode

**Esempio:**
```php
add_filter('cdv_eventi_query_args', function($args, $atts) {
    // Mostra solo eventi pubblici
    $args['meta_query'][] = [
        'key' => '_cdv_evento_pubblico',
        'value' => '1'
    ];
    return $args;
}, 10, 2);
```

---

## Security & Validation Filters

### `cdv_allowed_content_tags`

Filtra i tag HTML permessi nel contenuto proposte.

**Parametri:**
- `array $allowed_tags` - Array tag permessi (wp_kses)

**Esempio:**
```php
add_filter('cdv_allowed_content_tags', function($allowed_tags) {
    // Permetti iframe per embed
    $allowed_tags['iframe'] = [
        'src' => true,
        'width' => true,
        'height' => true
    ];
    return $allowed_tags;
});
```

---

### `cdv_proposta_max_title_length`

Filtra lunghezza massima titolo proposta (default: 140).

**Parametri:**
- `int $length` - Lunghezza massima

**Esempio:**
```php
add_filter('cdv_proposta_max_title_length', function($length) {
    return 200; // Aumenta a 200 caratteri
});
```

---

## WPBakery Filters

### `cdv_wpbakery_category`

Filtra il nome categoria elementi WPBakery.

**Parametri:**
- `string $category` - Nome categoria (default: "Cronaca di Viterbo")

**Esempio:**
```php
add_filter('cdv_wpbakery_category', function($category) {
    return 'Giornalismo Locale';
});
```

---

## Template Filters

### `cdv_template_path`

Filtra il percorso base per i template.

**Parametri:**
- `string $path` - Percorso directory template

**Esempio:**
```php
add_filter('cdv_template_path', function($path) {
    // Usa template da tema child
    return get_stylesheet_directory() . '/cdv-templates/';
});
```

---

## Migration Hooks

### `cdv_before_migration`

Eseguito prima della migrazione dati.

**Esempio:**
```php
add_action('cdv_before_migration', function() {
    // Backup dati
    update_option('cdv_migration_backup', date('Y-m-d H:i:s'));
});
```

---

### `cdv_after_migration`

Eseguito dopo la migrazione dati.

**Parametri:**
- `string $old_version` - Versione precedente
- `string $new_version` - Nuova versione

**Esempio:**
```php
add_action('cdv_after_migration', function($old_version, $new_version) {
    error_log("Migrato da {$old_version} a {$new_version}");
}, 10, 2);
```

---

## Utilizzo Avanzato

### Esempio: Custom Moderazione
```php
// Approva automaticamente proposte da utenti fidati
add_action('cdv_proposta_submitted', function($post_id) {
    $author_id = get_post_field('post_author', $post_id);
    
    if (user_can($author_id, 'cdv_trusted_contributor')) {
        wp_update_post([
            'ID' => $post_id,
            'post_status' => 'publish'
        ]);
    }
});
```

### Esempio: Notifiche Custom
```php
// Notifica Slack per nuove proposte
add_action('cdv_proposta_submitted', function($post_id, $quartiere_id, $tematica_id) {
    $quartiere = get_term($quartiere_id)->name;
    $tematica = get_term($tematica_id)->name;
    
    wp_remote_post('https://hooks.slack.com/services/YOUR/WEBHOOK', [
        'body' => json_encode([
            'text' => "📣 Nuova proposta: Quartiere {$quartiere}, Tema {$tematica}"
        ])
    ]);
}, 10, 3);
```

### Esempio: Schema Personalizzato
```php
// Aggiungi breadcrumb al JSON-LD
add_filter('cdv_schema_dossier', function($schema, $post) {
    $terms = get_the_terms($post->ID, 'cdv_quartiere');
    
    if ($terms) {
        $schema['breadcrumb'] = [
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                [
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => $terms[0]->name
                ]
            ]
        ];
    }
    
    return $schema;
}, 10, 2);
```

---

## Note Sviluppatori

- Tutti gli hooks sono prefissati con `cdv_`
- Gli action hooks non ritornano valori
- I filter hooks devono sempre ritornare il valore filtrato
- Usa priorità < 10 per eseguire prima del core
- Usa priorità > 10 per eseguire dopo il core
- Documenta sempre i tuoi custom hooks
