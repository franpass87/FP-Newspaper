<?php
/**
 * Integrazione Google Analytics 4
 *
 * @package FPNewspaper
 */

namespace FPNewspaper;

defined('ABSPATH') || exit;

/**
 * Gestisce l'integrazione con Google Analytics 4
 */
class Analytics {
    
    /**
     * Costruttore
     */
    public function __construct() {
        // Inietta tracking code
        add_action('wp_head', [$this, 'add_ga4_code']);
        
        // Tracking eventi personalizzati
        add_action('wp_footer', [$this, 'add_custom_tracking']);
        
        // Admin page
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
    }
    
    /**
     * Aggiunge Google Analytics 4 tracking code
     */
    public function add_ga4_code() {
        $ga4_id = get_option('fp_newspaper_ga4_id', '');
        
        if (empty($ga4_id)) {
            return;
        }
        
        // Controlla se admin deve essere tracciato
        $track_logged_in = get_option('fp_newspaper_ga4_track_logged_in', false);
        if (!$track_logged_in && current_user_can('manage_options')) {
            return; // Admin non tracciare
        }
        
        ?>
        <!-- Google Analytics 4 -->
        <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_js($ga4_id); ?>"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            
            gtag('config', '<?php echo esc_js($ga4_id); ?>', {
                'page_title': document.title,
                'page_location': window.location.href
            });
            
            // Tracking articoli
            <?php if (is_singular('post')): 
                global $post, $wpdb;
                $article_id = get_the_ID();
                
                // Ottieni views dalla tabella stats
                $table_name = $wpdb->prefix . 'fp_newspaper_stats';
                $views = 0;
                if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name) {
                    $stats = $wpdb->get_var($wpdb->prepare(
                        "SELECT views FROM $table_name WHERE post_id = %d",
                        $article_id
                    ));
                    $views = $stats ? (int) $stats : 0;
                }
                
                // Custom dimensions per articoli
                $author = get_the_author();
                $categories = wp_get_post_terms($article_id, 'category', ['fields' => 'names']);
                $is_featured = get_post_meta($article_id, '_fp_featured', true) === '1';
                $is_breaking = get_post_meta($article_id, '_fp_breaking_news', true) === '1';
            ?>
            gtag('event', 'article_view', {
                'article_id': '<?php echo absint($article_id); ?>',
                'article_title': <?php echo wp_json_encode(get_the_title($article_id)); ?>,
                'article_author': <?php echo wp_json_encode($author); ?>,
                'article_category': <?php echo wp_json_encode($categories[0] ?? ''); ?>,
                'is_featured': <?php echo $is_featured ? 'true' : 'false'; ?>,
                'is_breaking': <?php echo $is_breaking ? 'true' : 'false'; ?>,
                'current_views': <?php echo absint($views); ?>
            });
            
            // Tracking time on page
            var articleStartTime = Date.now();
            window.addEventListener('beforeunload', function() {
                var timeOnPage = Math.round((Date.now() - articleStartTime) / 1000);
                if (timeOnPage > 5) { // Solo se più di 5 secondi
                    gtag('event', 'article_engagement', {
                        'article_id': '<?php echo absint($article_id); ?>',
                        'time_on_page': timeOnPage
                    });
                }
            });
            <?php endif; ?>
        </script>
        <?php
    }
    
    /**
     * Aggiunge tracking eventi personalizzati
     */
    public function add_custom_tracking() {
        $ga4_id = get_option('fp_newspaper_ga4_id', '');
        
        if (empty($ga4_id)) {
            return;
        }
        
        ?>
        <script>
        (function() {
            // Tracking clic su articoli in shortcode
            document.addEventListener('click', function(e) {
                var link = e.target.closest('.fp-card-title a, .fp-latest-title a, .fp-featured-articles-shortcode a');
                if (link) {
                    var articleId = link.getAttribute('data-article-id') || '';
                    var articleTitle = link.textContent.trim();
                    
                    if (typeof gtag !== 'undefined') {
                        gtag('event', 'article_click', {
                            'article_id': articleId,
                            'article_title': articleTitle,
                            'link_text': articleTitle
                        });
                    }
                }
            });
            
            // Tracking scorrimento articoli in archive
            var articleCards = document.querySelectorAll('.fp-article-card');
            var observerOptions = {
                threshold: 0.5,
                rootMargin: '0px 0px -100px 0px'
            };
            
            var articleObserver = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting && typeof gtag !== 'undefined') {
                        var articleId = entry.target.getAttribute('data-article-id');
                        if (articleId && !entry.target.dataset.tracked) {
                            entry.target.dataset.tracked = 'true';
                            gtag('event', 'article_impression', {
                                'article_id': articleId
                            });
                        }
                    }
                });
            }, observerOptions);
            
            articleCards.forEach(function(card) {
                articleObserver.observe(card);
            });
            
            // Tracking engagement mappa
            var mapContainer = document.querySelector('.fp-interactive-map-container');
            if (mapContainer && typeof gtag !== 'undefined') {
                var mapStartTime = Date.now();
                var markersOpened = 0;
                
                // Traccia apertura popup marker
                document.addEventListener('click', function(e) {
                    if (e.target.closest('.fp-map-readmore')) {
                        markersOpened++;
                    }
                });
                
                // Traccia utilizzo mappa
                window.addEventListener('beforeunload', function() {
                    var timeOnMap = Math.round((Date.now() - mapStartTime) / 1000);
                    if (timeOnMap > 10) { // Solo se più di 10 secondi sulla mappa
                        gtag('event', 'map_engagement', {
                            'time_on_map': timeOnMap,
                            'markers_opened': markersOpened
                        });
                    }
                });
            }
        })();
        </script>
        <?php
    }
    
    /**
     * Aggiunge pagina impostazioni
     */
    public function add_settings_page() {
        add_submenu_page(
            'fp-newspaper',
            __('Google Analytics 4', 'fp-newspaper'),
            __('Google Analytics', 'fp-newspaper'),
            'manage_options',
            'fp-newspaper-analytics',
            [$this, 'render_settings_page']
        );
    }
    
    /**
     * Registra impostazioni
     */
    public function register_settings() {
        register_setting('fp_newspaper_analytics', 'fp_newspaper_ga4_id', [
            'type' => 'string',
            'default' => '',
            'sanitize_callback' => [$this, 'sanitize_ga4_id'],
        ]);
        
        register_setting('fp_newspaper_analytics', 'fp_newspaper_ga4_anonymize_ip', [
            'type' => 'boolean',
            'default' => true,
            'sanitize_callback' => 'rest_sanitize_boolean',
        ]);
        
        register_setting('fp_newspaper_analytics', 'fp_newspaper_ga4_track_logged_in', [
            'type' => 'boolean',
            'default' => false,
            'sanitize_callback' => 'rest_sanitize_boolean',
        ]);
    }
    
    /**
     * Sanitizza GA4 ID
     */
    public function sanitize_ga4_id($value) {
        // Formato GA4: G-XXXXXXXXXX
        $value = trim($value);
        if (preg_match('/^G-[A-Z0-9]+$/', $value)) {
            return $value;
        }
        return '';
    }
    
    /**
     * Renderizza pagina impostazioni
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Non hai i permessi per accedere a questa pagina.', 'fp-newspaper'));
        }
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="notice notice-info">
                <p>
                    <?php _e('Inserisci il tuo Google Analytics 4 Measurement ID (formato: G-XXXXXXXXXX).', 'fp-newspaper'); ?><br>
                    <strong><?php _e('Come trovarlo:', 'fp-newspaper'); ?></strong>
                    <?php _e('Vai su Google Analytics > Admin > Data Streams > Web Stream > Measurement ID', 'fp-newspaper'); ?>
                </p>
            </div>
            
            <form method="post" action="options.php">
                <?php settings_fields('fp_newspaper_analytics'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="fp_newspaper_ga4_id">
                                <?php _e('Google Analytics 4 ID', 'fp-newspaper'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="fp_newspaper_ga4_id" 
                                   name="fp_newspaper_ga4_id" 
                                   value="<?php echo esc_attr(get_option('fp_newspaper_ga4_id')); ?>" 
                                   class="regular-text"
                                   placeholder="G-XXXXXXXXXX"
                                   pattern="G-[A-Z0-9]+">
                            <p class="description">
                                <?php _e('Measurement ID del tuo Google Analytics 4 property.', 'fp-newspaper'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="fp_newspaper_ga4_anonymize_ip">
                                <?php _e('Anonymize IP', 'fp-newspaper'); ?>
                            </label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" 
                                       id="fp_newspaper_ga4_anonymize_ip" 
                                       name="fp_newspaper_ga4_anonymize_ip" 
                                       value="1" 
                                       <?php checked(get_option('fp_newspaper_ga4_anonymize_ip'), 1); ?>>
                                <?php _e('Anonimizza gli indirizzi IP per conformità GDPR', 'fp-newspaper'); ?>
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="fp_newspaper_ga4_track_logged_in">
                                <?php _e('Traccia Utenti Loggati', 'fp-newspaper'); ?>
                            </label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" 
                                       id="fp_newspaper_ga4_track_logged_in" 
                                       name="fp_newspaper_ga4_track_logged_in" 
                                       value="1" 
                                       <?php checked(get_option('fp_newspaper_ga4_track_logged_in'), 1); ?>>
                                <?php _e('Includi gli admin/utenti loggati nel tracking (sconsigliato)', 'fp-newspaper'); ?>
                            </label>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
            
            <hr>
            
            <h2><?php _e('Eventi Tracciati', 'fp-newspaper'); ?></h2>
            <p><?php _e('Il plugin traccia automaticamente i seguenti eventi:', 'fp-newspaper'); ?></p>
            <ul>
                <li><code>article_view</code> - <?php _e('Visualizzazione articolo singolo', 'fp-newspaper'); ?></li>
                <li><code>article_click</code> - <?php _e('Clic su link articolo', 'fp-newspaper'); ?></li>
                <li><code>article_impression</code> - <?php _e('Visualizzazione articolo in lista', 'fp-newspaper'); ?></li>
                <li><code>article_engagement</code> - <?php _e('Tempo lettura articolo (min 5 sec)', 'fp-newspaper'); ?></li>
                <li><code>map_engagement</code> - <?php _e('Utilizzo mappa interattiva', 'fp-newspaper'); ?></li>
            </ul>
        </div>
        <?php
    }
}

