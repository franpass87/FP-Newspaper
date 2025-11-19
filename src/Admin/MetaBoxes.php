<?php
/**
 * Gestione Meta Boxes
 *
 * @package FPNewspaper
 */

namespace FPNewspaper\Admin;

defined('ABSPATH') || exit;

/**
 * Classe per gestire i meta box personalizzati
 */
class MetaBoxes {
    
    /**
     * Costruttore
     */
    public function __construct() {
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
        add_action('save_post', [$this, 'save_meta_boxes'], 8);
    }
    
    /**
     * Aggiunge meta boxes
     */
    public function add_meta_boxes() {
        add_meta_box(
            'fp_article_options',
            __('Opzioni Articolo', 'fp-newspaper'),
            [$this, 'render_article_options'],
            'post',  // ✅ Usa post nativo
            'side',
            'default'
        );
        
        add_meta_box(
            'fp_article_location',
            __('Localizzazione', 'fp-newspaper'),
            [$this, 'render_article_location'],
            'post',  // ✅ Usa post nativo
            'normal',
            'high'
        );
        
        add_meta_box(
            'fp_article_stats',
            __('Statistiche Articolo', 'fp-newspaper'),
            [$this, 'render_article_stats'],
            'post',  // ✅ Usa post nativo
            'side',
            'default'
        );
    }
    
    /**
     * Renderizza meta box opzioni articolo
     *
     * @param \WP_Post $post
     */
    public function render_article_options($post) {
        wp_nonce_field('fp_article_options_nonce', 'fp_article_options_nonce');
        
        $featured = get_post_meta($post->ID, '_fp_featured', true);
        $breaking = get_post_meta($post->ID, '_fp_breaking_news', true);
        $subtitle = get_post_meta($post->ID, '_fp_article_subtitle', true);
        $author_name = get_post_meta($post->ID, '_fp_article_author_name', true);
        $credit = get_post_meta($post->ID, '_fp_article_credit', true);
        $priority = get_post_meta($post->ID, '_fp_article_priority', true);
        ?>
        
        <!-- Header -->
        <div class="fp-side-section-header">
            <h4 class="fp-side-title">
                <span class="dashicons dashicons-admin-generic"></span>
                <?php _e('Impostazioni Articolo', 'fp-newspaper'); ?>
            </h4>
        </div>
        
        <!-- Featured Article -->
        <div class="fp-side-section fp-checkbox-section">
            <label class="fp-checkbox-label">
                <input type="checkbox" name="fp_featured" value="1" <?php checked($featured, '1'); ?>>
                <span class="fp-checkbox-icon dashicons dashicons-star-filled"></span>
                <div class="fp-checkbox-content">
                    <strong><?php _e('In Evidenza', 'fp-newspaper'); ?></strong>
                    <span class="fp-checkbox-desc"><?php _e('Mostra questo articolo in evidenza', 'fp-newspaper'); ?></span>
                </div>
            </label>
        </div>
        
        <!-- Breaking News -->
        <div class="fp-side-section fp-checkbox-section">
            <label class="fp-checkbox-label">
                <input type="checkbox" name="fp_breaking_news" value="1" <?php checked($breaking, '1'); ?>>
                <span class="fp-checkbox-icon dashicons dashicons-megaphone"></span>
                <div class="fp-checkbox-content">
                    <strong><?php _e('Breaking News', 'fp-newspaper'); ?></strong>
                    <span class="fp-checkbox-desc"><?php _e('Contrassegna come notizia urgente', 'fp-newspaper'); ?></span>
                </div>
            </label>
        </div>
        
        <div class="fp-side-divider"></div>
        
        <!-- Subtitle -->
        <div class="fp-side-section">
            <label for="fp_article_subtitle" class="fp-side-label">
                <?php _e('Sottotitolo', 'fp-newspaper'); ?>
            </label>
            <textarea 
                id="fp_article_subtitle" 
                name="fp_article_subtitle" 
                class="fp-side-field"
                rows="2"
                placeholder="<?php esc_attr_e('Sottotitolo breve dell\'articolo...', 'fp-newspaper'); ?>"><?php echo esc_textarea($subtitle); ?></textarea>
            <p class="fp-side-help"><?php _e('Sottotitolo che apparirà sotto il titolo principale', 'fp-newspaper'); ?></p>
        </div>
        
        <div class="fp-side-divider"></div>
        
        <!-- Author Name (override) -->
        <div class="fp-side-section">
            <label for="fp_article_author_name" class="fp-side-label">
                <?php _e('Nome Autore', 'fp-newspaper'); ?>
            </label>
            <input 
                type="text" 
                id="fp_article_author_name" 
                name="fp_article_author_name" 
                value="<?php echo esc_attr($author_name); ?>" 
                class="fp-side-field"
                placeholder="<?php esc_attr_e('Nome completo...', 'fp-newspaper'); ?>">
            <p class="fp-side-help"><?php _e('Override del nome autore (opzionale)', 'fp-newspaper'); ?></p>
        </div>
        
        <!-- Credit -->
        <div class="fp-side-section">
            <label for="fp_article_credit" class="fp-side-label">
                <?php _e('Crediti', 'fp-newspaper'); ?>
            </label>
            <input 
                type="text" 
                id="fp_article_credit" 
                name="fp_article_credit" 
                value="<?php echo esc_attr($credit); ?>" 
                class="fp-side-field"
                placeholder="<?php esc_attr_e('Es: Foto di...', 'fp-newspaper'); ?>">
            <p class="fp-side-help"><?php _e('Crediti foto/video/fonte', 'fp-newspaper'); ?></p>
        </div>
        
        <div class="fp-side-divider"></div>
        
        <!-- Priority -->
        <div class="fp-side-section">
            <label for="fp_article_priority" class="fp-side-label">
                <?php _e('Priorità', 'fp-newspaper'); ?>
            </label>
            <select id="fp_article_priority" name="fp_article_priority" class="fp-side-field">
                <option value=""><?php _e('Normale', 'fp-newspaper'); ?></option>
                <option value="high" <?php selected($priority, 'high'); ?>><?php _e('Alta', 'fp-newspaper'); ?></option>
                <option value="normal" <?php selected($priority, 'normal'); ?>><?php _e('Normale', 'fp-newspaper'); ?></option>
                <option value="low" <?php selected($priority, 'low'); ?>><?php _e('Bassa', 'fp-newspaper'); ?></option>
            </select>
            <p class="fp-side-help"><?php _e('Priorità dell\'articolo nell\'ordinamento', 'fp-newspaper'); ?></p>
        </div>
        
        <!-- Sidebar Styles -->
        <style>
        /* Sidebar Meta Box Styles */
        .fp-side-section-header {
            padding: 0 0 12px 0;
            border-bottom: 1px solid #e5e5e5;
            margin-bottom: 16px;
        }
        
        .fp-side-title {
            margin: 0;
            font-size: 13px;
            font-weight: 600;
            color: #1d2327;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .fp-side-title .dashicons {
            font-size: 18px;
            color: #646970;
        }
        
        .fp-side-section {
            margin-bottom: 16px;
        }
        
        .fp-side-divider {
            height: 1px;
            background: #e5e5e5;
            margin: 16px 0;
        }
        
        /* Checkbox Sections */
        .fp-checkbox-section {
            padding: 12px;
            background: #f6f7f7;
            border-radius: 6px;
            transition: all 0.2s;
        }
        
        .fp-checkbox-section:hover {
            background: #f0f0f1;
        }
        
        .fp-checkbox-label {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            cursor: pointer;
        }
        
        .fp-checkbox-label input[type="checkbox"] {
            margin-top: 2px;
            cursor: pointer;
        }
        
        .fp-checkbox-icon {
            font-size: 20px;
            line-height: 1.2;
            margin-top: 1px;
        }
        
        .fp-checkbox-label input[type="checkbox"]:checked ~ .fp-checkbox-icon {
            color: #2271b1;
        }
        
        .fp-checkbox-content {
            flex: 1;
        }
        
        .fp-checkbox-content strong {
            display: block;
            font-size: 13px;
            color: #1d2327;
            margin-bottom: 2px;
        }
        
        .fp-checkbox-desc {
            display: block;
            font-size: 12px;
            color: #646970;
            line-height: 1.4;
        }
        
        .fp-side-label {
            display: block;
            font-weight: 600;
            font-size: 12px;
            color: #1d2327;
            margin-bottom: 6px;
        }
        
        .fp-side-field {
            width: 100%;
            padding: 6px 8px;
            border: 1px solid #8c8f94;
            border-radius: 4px;
            font-size: 13px;
            transition: all 0.15s;
        }
        
        .fp-side-field:focus {
            outline: none;
            border-color: #2271b1;
            box-shadow: 0 0 0 1px #2271b1;
        }
        
        .fp-side-field textarea {
            resize: vertical;
            min-height: 60px;
        }
        
        .fp-side-help {
            margin: 6px 0 0 0;
            font-size: 11px;
            color: #646970;
            line-height: 1.4;
        }
        
        /* Specific icons */
        .fp-checkbox-label input[type="checkbox"]:checked ~ .fp-checkbox-icon.dashicons-star-filled {
            color: #dba617;
        }
        
        .fp-checkbox-label input[type="checkbox"]:checked ~ .fp-checkbox-icon.dashicons-megaphone {
            color: #d63638;
        }
        
        /* Unchecked state */
        .fp-checkbox-label input[type="checkbox"]:not(:checked) ~ .fp-checkbox-icon.dashicons-star-filled {
            color: #c3c4c7;
        }
        
        .fp-checkbox-label input[type="checkbox"]:not(:checked) ~ .fp-checkbox-icon.dashicons-megaphone {
            color: #c3c4c7;
        }
        </style>
        <?php
    }
    
    /**
     * Renderizza meta box localizzazione articolo
     *
     * @param \WP_Post $post
     */
    public function render_article_location($post) {
        $address = get_post_meta($post->ID, '_fp_article_address', true);
        $latitude = get_post_meta($post->ID, '_fp_article_latitude', true);
        $longitude = get_post_meta($post->ID, '_fp_article_longitude', true);
        $show_on_map = get_post_meta($post->ID, '_fp_show_on_map', true);
        $map_locations = get_post_meta($post->ID, '_fp_map_locations', true);

        if (!is_array($map_locations)) {
            $map_locations = [];
        }

        wp_enqueue_media();
        wp_enqueue_script('jquery-ui-sortable');
        
        ?>
        <!-- Header Section -->
        <div class="fp-meta-section fp-section-header">
            <div class="fp-section-icon">📍</div>
            <div class="fp-section-content">
                <h3 class="fp-section-title"><?php _e('Geolocalizzazione Articolo', 'fp-newspaper'); ?></h3>
                <p class="fp-section-description"><?php _e('Aggiungi informazioni di localizzazione per questo articolo. I dati saranno utilizzati per mostrare l\'articolo sulla mappa interattiva del sito.', 'fp-newspaper'); ?></p>
            </div>
        </div>
        
        <!-- Toggle Map Display -->
        <div class="fp-meta-section fp-section-toggle">
            <label class="fp-toggle-switch">
                <input type="checkbox" id="fp_show_on_map" name="fp_show_on_map" value="1" <?php checked($show_on_map, '1'); ?>>
                <span class="fp-toggle-slider"></span>
            </label>
            <div class="fp-toggle-content">
                <label for="fp_show_on_map" class="fp-toggle-label">
                    <strong><?php _e('Mostra sulla mappa interattiva', 'fp-newspaper'); ?></strong>
                    <span class="fp-toggle-description"><?php _e('Abilita la visualizzazione di questo articolo sulla mappa interattiva pubblica', 'fp-newspaper'); ?></span>
                </label>
            </div>
        </div>
        
        <!-- Address Input Section -->
        <div class="fp-meta-section fp-section-input">
            <label for="fp_article_address" class="fp-input-label">
                <?php _e('Indirizzo', 'fp-newspaper'); ?>
            </label>
            <div class="fp-input-group">
                <input type="text" 
                       id="fp_article_address" 
                       name="fp_article_address" 
                       value="<?php echo esc_attr($address); ?>" 
                       class="fp-input-field"
                       placeholder="<?php esc_attr_e('Es: Via Roma 1, 00100 Roma, Italia', 'fp-newspaper'); ?>">
                <button type="button" id="fp-geocode-address" class="fp-button fp-button-primary">
                    <span class="dashicons dashicons-search"></span>
                    <?php _e('Cerca Coordinate', 'fp-newspaper'); ?>
                </button>
            </div>
            <p class="fp-input-help"><?php _e('Inserisci un indirizzo completo e premi il pulsante per trovare automaticamente le coordinate geografiche tramite geocoding.', 'fp-newspaper'); ?></p>
        </div>
        
        <!-- Coordinates Section -->
        <div class="fp-meta-section fp-section-coordinates">
            <label class="fp-input-label">
                <?php _e('Coordinate Geografiche', 'fp-newspaper'); ?>
            </label>
            <div class="fp-coordinates-grid">
                <div class="fp-coordinate-field">
                    <label for="fp_article_latitude" class="fp-coordinate-label">
                        <?php _e('Latitudine', 'fp-newspaper'); ?>
                    </label>
                    <input type="text" 
                           id="fp_article_latitude" 
                           name="fp_article_latitude" 
                           value="<?php echo esc_attr($latitude); ?>" 
                           class="fp-input-field fp-coordinate-input"
                           placeholder="41.9028"
                           pattern="-?\d+\.?\d*">
                    <span class="fp-coordinate-unit">°N</span>
                </div>
                
                <div class="fp-coordinate-field">
                    <label for="fp_article_longitude" class="fp-coordinate-label">
                        <?php _e('Longitudine', 'fp-newspaper'); ?>
                    </label>
                    <input type="text" 
                           id="fp_article_longitude" 
                           name="fp_article_longitude" 
                           value="<?php echo esc_attr($longitude); ?>" 
                           class="fp-input-field fp-coordinate-input"
                           placeholder="12.4964"
                           pattern="-?\d+\.?\d*">
                    <span class="fp-coordinate-unit">°E</span>
                </div>
            </div>
            <p class="fp-input-help"><?php _e('Alternativamente, puoi inserire manualmente le coordinate in formato decimale (es: 41.9028, 12.4964).', 'fp-newspaper'); ?></p>
        </div>
        
        <!-- Preview Map Section -->
        <?php if (!empty($latitude) && !empty($longitude)): ?>
        <div class="fp-meta-section fp-section-preview">
            <label class="fp-input-label">
                <?php _e('Anteprima Posizione', 'fp-newspaper'); ?>
            </label>
            <div id="fp-map-container" class="fp-map-preview" style="height: 400px;"></div>
            <p class="fp-input-help" style="margin-top: 8px;">
                <?php _e('La mappa mostra la posizione selezionata. Puoi modificare le coordinate sopra per aggiornare la posizione.', 'fp-newspaper'); ?>
            </p>
        </div>
        <?php endif; ?>

        <div class="fp-meta-section fp-section-divider"></div>

        <div class="fp-meta-section fp-section-multi-map">
            <div class="fp-section-header" style="margin-bottom: 20px;">
                <div class="fp-section-icon">🗺️</div>
                <div class="fp-section-content">
                    <h3 class="fp-section-title"><?php _e('Mappa Approfondimento', 'fp-newspaper'); ?></h3>
                    <p class="fp-section-description"><?php _e('Aggiungi più punti di interesse per creare una mappa dedicata a questo articolo (es. luoghi storici, itinerari, monumenti).', 'fp-newspaper'); ?></p>
                </div>
            </div>

            <div id="fp-map-locations-list" class="fp-map-locations-list">
                <?php
                if (!empty($map_locations)) {
                    foreach ($map_locations as $index => $location) {
                        $this->render_map_location_item($index, $location);
                    }
                } else {
                    echo '<p class="fp-map-locations-empty">' . esc_html__('Nessun luogo aggiunto. Clicca su “Aggiungi luogo” per iniziare.', 'fp-newspaper') . '</p>';
                }
                ?>
            </div>

            <button type="button" class="button button-secondary" id="fp-add-map-location">
                <span class="dashicons dashicons-plus"></span>
                <?php esc_html_e('Aggiungi luogo', 'fp-newspaper'); ?>
            </button>

            <p class="fp-input-help" style="margin-top: 12px;">
                <?php esc_html_e('Inserisci il corto shortcode [fp_article_locations_map] dove vuoi mostrare la mappa (supportato anche da WPBakery).', 'fp-newspaper'); ?>
            </p>

            <p class="fp-input-help" style="margin-top: 4px;">
                <?php esc_html_e('Ogni luogo supporta titolo, descrizione, immagine e coordinate precise. Puoi riordinare i blocchi trascinandoli.', 'fp-newspaper'); ?>
            </p>
        </div>

        <script type="text/template" id="fp-map-location-template">
            <?php $this->render_map_location_item('__index__', []); ?>
        </script>

        <script>
        jQuery(function($) {
            // Inizializza mappa se coordinate esistono
            <?php if (!empty($latitude) && !empty($longitude)): ?>
            if (typeof L !== 'undefined') {
                var map = L.map('fp-map-container').setView([<?php echo esc_js($latitude); ?>, <?php echo esc_js($longitude); ?>], 13);
                
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap contributors'
                }).addTo(map);
                
                var marker = L.marker([<?php echo esc_js($latitude); ?>, <?php echo esc_js($longitude); ?>]).addTo(map);
                marker.bindPopup('<?php echo esc_js($post->post_title); ?>').openPopup();
            }
            <?php endif; ?>
            
            // Geocoding con Nominatim (OpenStreetMap)
            $('#fp-geocode-address').on('click', function(e) {
                e.preventDefault();
                
                var address = $('#fp_article_address').val();
                if (!address || address.trim() === '') {
                    alert('<?php echo esc_js(__('Inserisci un indirizzo prima di cercare.', 'fp-newspaper')); ?>');
                    $('#fp_article_address').focus();
                    return;
                }
                
                var button = $(this);
                var originalText = button.html();
                button.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> <?php echo esc_js(__('Ricerca...', 'fp-newspaper')); ?>');
                
                $.ajax({
                    url: 'https://nominatim.openstreetmap.org/search',
                    data: {
                        q: address,
                        format: 'json',
                        limit: 1,
                        countrycodes: 'it' // Limita all'Italia
                    },
                    dataType: 'json',
                    headers: {
                        'User-Agent': 'FP-Newspaper WordPress Plugin'
                    },
                    timeout: 10000,
                    success: function(data) {
                        if (data && data.length > 0) {
                            $('#fp_article_latitude').val(data[0].lat);
                            $('#fp_article_longitude').val(data[0].lon);
                            
                            // Show success message
                            var successMsg = $('<div class="fp-success-msg">✓ Coordinate trovate!</div>');
                            button.after(successMsg);
                            setTimeout(function() {
                                successMsg.fadeOut(function() { $(this).remove(); });
                            }, 3000);
                        } else {
                            alert('<?php echo esc_js(__('Indirizzo non trovato. Prova con un indirizzo più specifico o completo.', 'fp-newspaper')); ?>');
                        }
                    },
                    error: function() {
                        alert('<?php echo esc_js(__('Errore durante la ricerca. Controlla la tua connessione internet e riprova.', 'fp-newspaper')); ?>');
                    },
                    complete: function() {
                        button.prop('disabled', false).html(originalText);
                    }
                });
            });
            
            // Allow Enter key to trigger geocoding
            $('#fp_article_address').on('keypress', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    $('#fp-geocode-address').click();
                }
            });
        });
        
        // Add spinning icon CSS
        jQuery(function($) {
            if ($('#fp-article-location-meta-box').length) {
                $('<style>').prop('type', 'text/css').html(`
                    .fp-button-primary .dashicons {
                        margin: 0;
                    }
                    .spin {
                        animation: spin 1s linear infinite;
                    }
                    @keyframes spin {
                        from { transform: rotate(0deg); }
                        to { transform: rotate(360deg); }
                    }
                    .fp-success-msg {
                        display: inline-block;
                        margin-left: 10px;
                        padding: 4px 12px;
                        background: #00a32a;
                        color: white;
                        border-radius: 4px;
                        font-size: 13px;
                        font-weight: 600;
                    }
                `).appendTo('head');
            }
        });
        </script>
        
        <style>
        /* Meta Box Sections */
        .fp-meta-section {
            margin: 0 0 24px 0;
            padding: 0;
        }
        
        .fp-meta-section:last-child {
            margin-bottom: 0;
        }
        
        /* Header Section */
        .fp-section-header {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e5e5e5;
        }
        
        .fp-section-icon {
            font-size: 32px;
            line-height: 1;
        }
        
        .fp-section-content {
            flex: 1;
        }
        
        .fp-section-title {
            margin: 0 0 8px 0;
            font-size: 16px;
            font-weight: 600;
            color: #1d2327;
        }
        
        .fp-section-description {
            margin: 0;
            font-size: 13px;
            color: #646970;
            line-height: 1.6;
        }
        
        /* Toggle Switch */
        .fp-section-toggle {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 16px;
            background: #f6f7f7;
            border-radius: 8px;
        }
        
        .fp-toggle-switch {
            position: relative;
            display: inline-block;
            width: 52px;
            height: 28px;
            flex-shrink: 0;
        }
        
        .fp-toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .fp-toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #c3c4c7;
            transition: 0.3s;
            border-radius: 28px;
        }
        
        .fp-toggle-slider:before {
            position: absolute;
            content: "";
            height: 22px;
            width: 22px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: 0.3s;
            border-radius: 50%;
        }
        
        .fp-toggle-switch input:checked + .fp-toggle-slider {
            background-color: #2271b1;
        }
        
        .fp-toggle-switch input:checked + .fp-toggle-slider:before {
            transform: translateX(24px);
        }
        
        .fp-toggle-content {
            flex: 1;
        }
        
        .fp-toggle-label {
            display: block;
            cursor: pointer;
        }
        
        .fp-toggle-label strong {
            display: block;
            margin-bottom: 4px;
            font-size: 14px;
            color: #1d2327;
        }
        
        .fp-toggle-description {
            display: block;
            font-size: 13px;
            color: #646970;
            line-height: 1.5;
        }
        
        /* Input Sections */
        .fp-input-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 13px;
            color: #1d2327;
        }
        
        .fp-input-group {
            display: flex;
            gap: 8px;
        }
        
        .fp-input-field {
            flex: 1;
            min-width: 0;
            padding: 8px 12px;
            border: 1px solid #8c8f94;
            border-radius: 4px;
            font-size: 14px;
            transition: border-color 0.15s ease-in-out;
        }
        
        .fp-input-field:focus {
            outline: none;
            border-color: #2271b1;
            box-shadow: 0 0 0 1px #2271b1;
        }
        
        .fp-button {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border: 1px solid transparent;
            border-radius: 4px;
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.15s ease-in-out;
            white-space: nowrap;
        }
        
        .fp-button-primary {
            background: #2271b1;
            color: white;
            border-color: #2271b1;
        }
        
        .fp-button-primary:hover {
            background: #135e96;
            border-color: #135e96;
        }
        
        .fp-button-primary:focus {
            outline: none;
            box-shadow: 0 0 0 1px #fff, 0 0 0 3px #2271b1;
        }
        
        .fp-input-help {
            margin: 8px 0 0 0;
            font-size: 12px;
            color: #646970;
            line-height: 1.5;
        }
        
        /* Coordinates Grid */
        .fp-coordinates-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        
        .fp-coordinate-field {
            display: flex;
            flex-direction: column;
        }
        
        .fp-coordinate-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            font-size: 12px;
            color: #646970;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .fp-coordinate-input-wrapper {
            position: relative;
        }
        
        .fp-coordinate-input {
            padding-right: 40px;
        }
        
        .fp-coordinate-unit {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 12px;
            color: #646970;
            font-weight: 500;
            pointer-events: none;
        }
        
        /* Map Preview */
        .fp-section-preview {
            padding: 20px;
            background: #f6f7f7;
            border-radius: 8px;
        }
        
        .fp-map-preview {
            width: 100%;
            height: 400px;
            border: 1px solid #c3c4c7;
            border-radius: 8px;
            overflow: hidden;
        }
        
        #fp-map-container .leaflet-container {
            border-radius: 8px;
        }
        
        /* Responsive */
        @media (max-width: 782px) {
            .fp-coordinates-grid {
                grid-template-columns: 1fr;
            }
            
            .fp-input-group {
                flex-direction: column;
            }
            
            .fp-button {
                width: 100%;
                justify-content: center;
            }
        }

        .fp-section-divider {
            height: 1px;
            background: #e5e5e5;
            margin: 32px 0;
        }

        .fp-map-locations-list {
            display: flex;
            flex-direction: column;
            gap: 18px;
            margin-bottom: 16px;
        }

        .fp-map-locations-empty {
            margin: 0;
            padding: 24px;
            background: #f1f5f9;
            border: 1px dashed #cbd5f5;
            border-radius: 8px;
            color: #475569;
            font-size: 13px;
            text-align: center;
        }

        .fp-map-location-item {
            border: 1px solid #e5e5e5;
            border-radius: 8px;
            padding: 16px;
            background: #fff;
            position: relative;
        }

        .fp-map-location-item .fp-map-location-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }

        .fp-map-location-handle {
            cursor: move;
            color: #646970;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .fp-map-location-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        .fp-map-location-field {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .fp-map-location-grid textarea {
            min-height: 80px;
        }

        .fp-map-location-image {
            grid-column: span 2;
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .fp-location-image-preview {
            width: 96px;
            height: 96px;
            border-radius: 6px;
            overflow: hidden;
            background: #f6f7f7;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px dashed #dcdcde;
            flex-shrink: 0;
        }

        .fp-location-image-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .fp-location-image-placeholder {
            font-size: 12px;
            color: #646970;
        }

        .fp-location-image-actions {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .fp-map-location-footer {
            margin-top: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .fp-map-location-item .button-link-delete {
            color: #d63638;
        }

        @media (max-width: 1024px) {
            .fp-map-location-grid {
                grid-template-columns: 1fr;
            }

            .fp-map-location-image {
                flex-direction: column;
                align-items: flex-start;
            }
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            const locationsList = $('#fp-map-locations-list');
            let locationIndex = locationsList.children('.fp-map-location-item').length;

            function reindexLocations() {
                locationsList.children('.fp-map-location-item').each(function(index) {
                    const $item = $(this);
                    $item.attr('data-index', index);
                    $item.find('.fp-map-location-number').text(index + 1);

                    $item.find('[name^="fp_map_locations"]').each(function() {
                        const currentName = $(this).attr('name');
                        if (!currentName) {
                            return;
                        }
                        const newName = currentName.replace(/fp_map_locations\[\d+\]/, 'fp_map_locations[' + index + ']');
                        $(this).attr('name', newName);
                    });
                });

                locationIndex = locationsList.children('.fp-map-location-item').length;
            }

            function removeEmptyState() {
                locationsList.find('.fp-map-locations-empty').remove();
            }

            $('#fp-add-map-location').on('click', function(event) {
                event.preventDefault();
                const template = $('#fp-map-location-template').html().replace(/__index__/g, locationIndex);
                const $node = $(template);
                locationsList.append($node);
                removeEmptyState();
                reindexLocations();
            });

            locationsList.on('click', '.fp-remove-map-location', function(event) {
                event.preventDefault();
                $(this).closest('.fp-map-location-item').remove();
                reindexLocations();
                if (!locationsList.children('.fp-map-location-item').length) {
                    locationsList.append('<p class="fp-map-locations-empty"><?php echo esc_js(__('Nessun luogo aggiunto. Clicca su “Aggiungi luogo” per iniziare.', 'fp-newspaper')); ?></p>');
                }
            });

            locationsList.on('click', '.fp-upload-location-image', function(event) {
                event.preventDefault();
                const $button = $(this);
                const $item = $button.closest('.fp-map-location-item');

                const frame = wp.media({
                    title: '<?php echo esc_js(__('Scegli immagine del luogo', 'fp-newspaper')); ?>',
                    button: {
                        text: '<?php echo esc_js(__('Usa immagine', 'fp-newspaper')); ?>'
                    },
                    multiple: false
                });

                frame.on('select', function() {
                    const attachment = frame.state().get('selection').first().toJSON();
                    const thumbnail = attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;
                    $item.find('.fp-location-image-id').val(attachment.id);
                    $item.find('.fp-location-image-preview').html('<img src="' + thumbnail + '" alt="' + (attachment.alt || '') + '" />');
                    $item.addClass('fp-location-has-image');
                });

                frame.open();
            });

            locationsList.on('click', '.fp-remove-location-image', function(event) {
                event.preventDefault();
                const $item = $(this).closest('.fp-map-location-item');
                $item.find('.fp-location-image-id').val('');
                $item.find('.fp-location-image-preview').html('<span class="fp-location-image-placeholder"><?php echo esc_js(__('Nessuna immagine', 'fp-newspaper')); ?></span>');
                $item.removeClass('fp-location-has-image');
            });

            if (locationsList.length && typeof locationsList.sortable === 'function') {
                locationsList.sortable({
                    handle: '.fp-map-location-handle',
                    update: reindexLocations
                });
            }
        });
        </script>
        <?php
    }
    
    /**
     * Renderizza meta box statistiche articolo
     *
     * @param \WP_Post $post
     */
    public function render_article_stats($post) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'fp_newspaper_stats';
        
        // Verifica che la tabella esista
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        
        $views = 0;
        $shares = 0;
        $last_updated = '';
        
        if ($table_exists) {
            $stats = $wpdb->get_row($wpdb->prepare(
                "SELECT views, shares, last_updated FROM $table_name WHERE post_id = %d",
                $post->ID
            ));
            
            if ($stats && !is_wp_error($stats)) {
                $views = (int) $stats->views;
                $shares = (int) $stats->shares;
                $last_updated = $stats->last_updated;
            }
        }
        ?>
        
        <div class="fp-side-section-header">
            <h4 class="fp-side-title">
                <span class="dashicons dashicons-chart-line"></span>
                <?php _e('Statistiche', 'fp-newspaper'); ?>
            </h4>
        </div>
        
        <div class="fp-stats-container">
            <!-- Views -->
            <div class="fp-stat-card fp-stat-views">
                <div class="fp-stat-icon">👁️</div>
                <div class="fp-stat-data">
                    <div class="fp-stat-number"><?php echo number_format_i18n($views); ?></div>
                    <div class="fp-stat-label"><?php _e('Visualizzazioni', 'fp-newspaper'); ?></div>
                </div>
            </div>
            
            <!-- Shares -->
            <div class="fp-stat-card fp-stat-shares">
                <div class="fp-stat-icon">🔗</div>
                <div class="fp-stat-data">
                    <div class="fp-stat-number"><?php echo number_format_i18n($shares); ?></div>
                    <div class="fp-stat-label"><?php _e('Condivisioni', 'fp-newspaper'); ?></div>
                </div>
            </div>
        </div>
        
        <?php if ($last_updated): ?>
        <div class="fp-side-divider"></div>
        <div class="fp-last-update">
            <span class="dashicons dashicons-clock" style="font-size: 16px; color: #646970;"></span>
            <span class="fp-update-text">
                <strong><?php _e('Aggiornato:', 'fp-newspaper'); ?></strong><br>
                <?php echo human_time_diff(strtotime($last_updated), current_time('timestamp')); ?> fa
            </span>
        </div>
        <?php endif; ?>
        
        <?php if (!$table_exists): ?>
        <div class="fp-side-divider"></div>
        <div class="fp-stats-error">
            <span class="dashicons dashicons-warning" style="color: #d63638;"></span>
            <span><?php _e('Tabella statistiche non trovata.', 'fp-newspaper'); ?></span>
        </div>
        <?php endif; ?>
        
        <!-- Stats Styles -->
        <style>
        .fp-stats-container {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .fp-stat-card {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            background: #f6f7f7;
            border-radius: 6px;
            transition: all 0.2s;
        }
        
        .fp-stat-card:hover {
            background: #f0f0f1;
        }
        
        .fp-stat-icon {
            font-size: 24px;
            line-height: 1;
        }
        
        .fp-stat-data {
            flex: 1;
        }
        
        .fp-stat-number {
            font-size: 20px;
            font-weight: 700;
            color: #1d2327;
            line-height: 1.2;
        }
        
        .fp-stat-label {
            font-size: 11px;
            color: #646970;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 2px;
        }
        
        .fp-stat-views .fp-stat-number {
            color: #00a32a;
        }
        
        .fp-stat-shares .fp-stat-number {
            color: #2271b1;
        }
        
        .fp-last-update {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            padding: 12px;
            background: #f6f7f7;
            border-radius: 6px;
        }
        
        .fp-update-text {
            font-size: 12px;
            line-height: 1.5;
        }
        
        .fp-update-text strong {
            color: #1d2327;
        }
        
        .fp-stats-error {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px;
            background: #fcf0f1;
            border-left: 3px solid #d63638;
            border-radius: 4px;
            font-size: 12px;
            color: #d63638;
        }
        </style>
        <?php
    }
    
    /**
     * Salva meta boxes
     *
     * @param int $post_id
     */
    public function save_meta_boxes($post_id) {
        // Verifica nonce
        if (!isset($_POST['fp_article_options_nonce']) || 
            !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['fp_article_options_nonce'])), 'fp_article_options_nonce')) {
            return;
        }
        
        // Verifica post type
        if (!isset($_POST['post_type']) || 'post' !== $_POST['post_type']) {
            return;
        }
        
        // Verifica autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Verifica permessi
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Verifica che non sia una revisione
        if (wp_is_post_revision($post_id)) {
            return;
        }
        
        // Salva featured (sanitizzato)
        $featured = isset($_POST['fp_featured']) && '1' === $_POST['fp_featured'] ? '1' : '0';
        update_post_meta($post_id, '_fp_featured', sanitize_text_field($featured));
        
        // Salva breaking news (sanitizzato)
        $breaking = isset($_POST['fp_breaking_news']) && '1' === $_POST['fp_breaking_news'] ? '1' : '0';
        update_post_meta($post_id, '_fp_breaking_news', sanitize_text_field($breaking));
        
        // Salva localizzazione
        if (isset($_POST['fp_show_on_map'])) {
            $show_on_map = isset($_POST['fp_show_on_map']) && '1' === $_POST['fp_show_on_map'] ? '1' : '0';
            update_post_meta($post_id, '_fp_show_on_map', sanitize_text_field($show_on_map));
        }
        
        if (isset($_POST['fp_article_address'])) {
            $address = sanitize_text_field(wp_unslash($_POST['fp_article_address']));
            update_post_meta($post_id, '_fp_article_address', $address);
        }
        
        if (isset($_POST['fp_article_latitude'])) {
            $latitude = floatval(sanitize_text_field(wp_unslash($_POST['fp_article_latitude'])));
            update_post_meta($post_id, '_fp_article_latitude', $latitude);
        }
        
        if (isset($_POST['fp_article_longitude'])) {
            $longitude = floatval(sanitize_text_field(wp_unslash($_POST['fp_article_longitude'])));
            update_post_meta($post_id, '_fp_article_longitude', $longitude);
        }

        // Salva i punti personalizzati per la mappa approfondimento
        if (isset($_POST['fp_map_locations']) && is_array($_POST['fp_map_locations'])) {
            $raw_locations = wp_unslash($_POST['fp_map_locations']);
            $clean_locations = [];

            foreach ($raw_locations as $location) {
                if (!is_array($location)) {
                    continue;
                }

                $title = isset($location['title']) ? sanitize_text_field($location['title']) : '';
                $caption = isset($location['caption']) ? sanitize_textarea_field($location['caption']) : '';
                $lat_raw = isset($location['latitude']) ? trim((string) $location['latitude']) : '';
                $lng_raw = isset($location['longitude']) ? trim((string) $location['longitude']) : '';
                $image_id = isset($location['image_id']) ? absint($location['image_id']) : 0;

                if ($lat_raw === '' || $lng_raw === '') {
                    continue;
                }

                $latitude = floatval($lat_raw);
                $longitude = floatval($lng_raw);

                if (!is_finite($latitude) || !is_finite($longitude)) {
                    continue;
                }

                $clean_locations[] = [
                    'title'     => $title,
                    'caption'   => $caption,
                    'latitude'  => $latitude,
                    'longitude' => $longitude,
                    'image_id'  => $image_id,
                ];
            }

            if (!empty($clean_locations)) {
                update_post_meta($post_id, '_fp_map_locations', $clean_locations);
            } else {
                delete_post_meta($post_id, '_fp_map_locations');
            }
        } else {
            delete_post_meta($post_id, '_fp_map_locations');
        }
        
        // Salva campi articolo aggiuntivi
        if (isset($_POST['fp_article_subtitle'])) {
            $subtitle = sanitize_textarea_field(wp_unslash($_POST['fp_article_subtitle']));
            update_post_meta($post_id, '_fp_article_subtitle', $subtitle);
        }
        
        if (isset($_POST['fp_article_author_name'])) {
            $author_name = sanitize_text_field(wp_unslash($_POST['fp_article_author_name']));
            update_post_meta($post_id, '_fp_article_author_name', $author_name);
        }
        
        if (isset($_POST['fp_article_credit'])) {
            $credit = sanitize_text_field(wp_unslash($_POST['fp_article_credit']));
            update_post_meta($post_id, '_fp_article_credit', $credit);
        }
        
        if (isset($_POST['fp_article_priority'])) {
            $priority = sanitize_text_field(wp_unslash($_POST['fp_article_priority']));
            if (in_array($priority, ['high', 'normal', 'low'], true)) {
                update_post_meta($post_id, '_fp_article_priority', $priority);
            }
        }
    }

    /**
     * Renderizza un singolo blocco luogo per la mappa avanzata.
     *
     * @param int|string $index
     * @param array      $location
     *
     * @return void
     */
    private function render_map_location_item($index, array $location = []) {
        $title = isset($location['title']) ? $location['title'] : '';
        $caption = isset($location['caption']) ? $location['caption'] : '';
        $latitude = isset($location['latitude']) ? $location['latitude'] : '';
        $longitude = isset($location['longitude']) ? $location['longitude'] : '';
        $image_id = isset($location['image_id']) ? absint($location['image_id']) : 0;

        $image_html = $image_id ? wp_get_attachment_image($image_id, 'thumbnail', false, ['style' => 'width:100%;height:100%;object-fit:cover;']) : '<span class="fp-location-image-placeholder">' . esc_html__('Nessuna immagine', 'fp-newspaper') . '</span>';
        $display_index = is_numeric($index) ? ((int) $index + 1) : '';
        ?>
        <div class="fp-map-location-item" data-index="<?php echo esc_attr($index); ?>">
            <div class="fp-map-location-header">
                <div class="fp-map-location-handle">
                    <span class="dashicons dashicons-move"></span>
                    <strong><?php esc_html_e('Luogo', 'fp-newspaper'); ?> <span class="fp-map-location-number"><?php echo esc_html($display_index); ?></span></strong>
                </div>
                <button type="button" class="button-link-delete fp-remove-map-location">
                    <span class="dashicons dashicons-trash"></span>
                    <?php esc_html_e('Rimuovi', 'fp-newspaper'); ?>
                </button>
            </div>

            <div class="fp-map-location-grid">
                <div class="fp-map-location-field">
                    <label class="fp-input-label" for="fp_map_location_title_<?php echo esc_attr($index); ?>"><?php esc_html_e('Titolo', 'fp-newspaper'); ?></label>
                    <input type="text" id="fp_map_location_title_<?php echo esc_attr($index); ?>" name="fp_map_locations[<?php echo esc_attr($index); ?>][title]" value="<?php echo esc_attr($title); ?>" class="fp-input-field" placeholder="<?php esc_attr_e('Es: Torre del Paradosso', 'fp-newspaper'); ?>">
                </div>

                <div class="fp-map-location-field">
                    <label class="fp-input-label" for="fp_map_location_lat_<?php echo esc_attr($index); ?>"><?php esc_html_e('Latitudine', 'fp-newspaper'); ?></label>
                    <input type="text" id="fp_map_location_lat_<?php echo esc_attr($index); ?>" name="fp_map_locations[<?php echo esc_attr($index); ?>][latitude]" value="<?php echo esc_attr($latitude); ?>" class="fp-input-field" placeholder="41.7028">
                </div>

                <div class="fp-map-location-field">
                    <label class="fp-input-label" for="fp_map_location_caption_<?php echo esc_attr($index); ?>"><?php esc_html_e('Descrizione', 'fp-newspaper'); ?></label>
                    <textarea id="fp_map_location_caption_<?php echo esc_attr($index); ?>" name="fp_map_locations[<?php echo esc_attr($index); ?>][caption]" class="fp-input-field" rows="3" placeholder="<?php esc_attr_e('Breve descrizione del luogo', 'fp-newspaper'); ?>"><?php echo esc_textarea($caption); ?></textarea>
                </div>

                <div class="fp-map-location-field">
                    <label class="fp-input-label" for="fp_map_location_lng_<?php echo esc_attr($index); ?>"><?php esc_html_e('Longitudine', 'fp-newspaper'); ?></label>
                    <input type="text" id="fp_map_location_lng_<?php echo esc_attr($index); ?>" name="fp_map_locations[<?php echo esc_attr($index); ?>][longitude]" value="<?php echo esc_attr($longitude); ?>" class="fp-input-field" placeholder="12.3467">
                </div>

                <div class="fp-map-location-image">
                    <div class="fp-location-image-preview"><?php echo $image_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
                    <div class="fp-location-image-actions">
                        <button type="button" class="button fp-upload-location-image"><?php esc_html_e('Seleziona immagine', 'fp-newspaper'); ?></button>
                        <button type="button" class="button-link-delete fp-remove-location-image"><?php esc_html_e('Rimuovi immagine', 'fp-newspaper'); ?></button>
                    </div>
                    <input type="hidden" class="fp-location-image-id" name="fp_map_locations[<?php echo esc_attr($index); ?>][image_id]" value="<?php echo esc_attr($image_id); ?>">
                </div>
            </div>
        </div>
        <?php
    }
}

