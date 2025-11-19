<?php
/**
 * Shortcodes per visualizzazione articoli
 *
 * @package FPNewspaper
 */

namespace FPNewspaper\Shortcodes;

defined('ABSPATH') || exit;

/**
 * Gestisce tutti gli shortcodes del plugin
 */
class Articles {
    
    /**
     * Registra shortcodes
     */
    public static function register() {
        add_shortcode('fp_articles', [__CLASS__, 'articles_list']);
        add_shortcode('fp_featured_articles', [__CLASS__, 'featured_articles']);
        add_shortcode('fp_breaking_news', [__CLASS__, 'breaking_news']);
        add_shortcode('fp_latest_articles', [__CLASS__, 'latest_articles']);
        add_shortcode('fp_article_stats', [__CLASS__, 'article_stats']);
        add_shortcode('fp_newspaper_archive', [__CLASS__, 'newspaper_archive']);
        add_shortcode('fp_interactive_map', [__CLASS__, 'interactive_map']);
        add_shortcode('fp_article_locations_map', [__CLASS__, 'article_locations_map']);
    }
    
    /**
     * Shortcode: Lista articoli
     * 
     * [fp_articles count="10" category="news" orderby="date"]
     *
     * @param array $atts
     * @return string
     */
    public static function articles_list($atts) {
        $atts = shortcode_atts([
            'count'    => 10,
            'category' => '',
            'tag'      => '',
            'orderby'  => 'date',
            'order'    => 'DESC',
            'layout'   => 'grid', // grid or list
        ], $atts, 'fp_articles');
        
        // Sanitizza attributi
        $count = min(absint($atts['count']), 50); // Max 50
        $category = sanitize_text_field($atts['category']);
        $tag = sanitize_text_field($atts['tag']);
        $orderby = in_array($atts['orderby'], ['date', 'title', 'rand', 'comment_count']) 
                   ? $atts['orderby'] : 'date';
        $order = 'ASC' === strtoupper($atts['order']) ? 'ASC' : 'DESC';
        $layout = in_array($atts['layout'], ['grid', 'list']) ? $atts['layout'] : 'grid';
        
        $args = [
            'post_type'      => 'post',
            'posts_per_page' => $count,
            'post_status'    => 'publish',
            'orderby'        => $orderby,
            'order'          => $order,
        ];
        
        // Aggiungi filtro categoria
        if (!empty($category)) {
            $args['tax_query'] = [
                [
                    'taxonomy' => 'category',
                    'field'    => 'slug',
                    'terms'    => $category,
                ],
            ];
        }
        
        // Aggiungi filtro tag
        if (!empty($tag)) {
            if (!isset($args['tax_query'])) {
                $args['tax_query'] = [];
            }
            $args['tax_query'][] = [
                'taxonomy' => 'post_tag',
                'field'    => 'slug',
                'terms'    => $tag,
            ];
        }
        
        $query = new \WP_Query($args);
        
        ob_start();
        
        if ($query->have_posts()) {
            echo '<div class="fp-articles-shortcode fp-layout-' . esc_attr($layout) . '">';
            
            while ($query->have_posts()) {
                $query->the_post();
                self::render_article_card(get_the_ID(), $layout);
            }
            
            echo '</div>';
            wp_reset_postdata();
        } else {
            echo '<p class="fp-no-articles">' . esc_html__('Nessun articolo trovato.', 'fp-newspaper') . '</p>';
        }
        
        return ob_get_clean();
    }
    
    /**
     * Shortcode: Articoli in evidenza
     * 
     * [fp_featured_articles count="5"]
     */
    public static function featured_articles($atts) {
        $atts = shortcode_atts([
            'count'  => 5,
            'layout' => 'grid',
        ], $atts, 'fp_featured_articles');
        
        $count = min(absint($atts['count']), 20);
        $layout = in_array($atts['layout'], ['grid', 'list']) ? $atts['layout'] : 'grid';
        
        $args = [
            'post_type'      => 'post',
            'posts_per_page' => $count,
            'post_status'    => 'publish',
            'meta_query'     => [
                [
                    'key'     => '_fp_featured',
                    'value'   => '1',
                    'compare' => '='
                ]
            ],
            'orderby'        => 'date',
            'order'          => 'DESC',
        ];
        
        $query = new \WP_Query($args);
        
        ob_start();
        
        if ($query->have_posts()) {
            echo '<div class="fp-featured-articles-shortcode fp-layout-' . esc_attr($layout) . '">';
            echo '<h2 class="fp-shortcode-title">' . esc_html__('Articoli in Evidenza', 'fp-newspaper') . '</h2>';
            
            while ($query->have_posts()) {
                $query->the_post();
                self::render_article_card(get_the_ID(), $layout);
            }
            
            echo '</div>';
            wp_reset_postdata();
        }
        
        return ob_get_clean();
    }
    
    /**
     * Shortcode: Breaking news
     * 
     * [fp_breaking_news count="3"]
     */
    public static function breaking_news($atts) {
        $atts = shortcode_atts([
            'count' => 3,
        ], $atts, 'fp_breaking_news');
        
        $count = min(absint($atts['count']), 10);
        
        $args = [
            'post_type'      => 'post',
            'posts_per_page' => $count,
            'post_status'    => 'publish',
            'meta_query'     => [
                [
                    'key'     => '_fp_breaking_news',
                    'value'   => '1',
                    'compare' => '='
                ]
            ],
            'orderby'        => 'date',
            'order'          => 'DESC',
        ];
        
        $query = new \WP_Query($args);
        
        ob_start();
        
        if ($query->have_posts()) {
            echo '<div class="fp-breaking-news-shortcode">';
            echo '<div class="fp-breaking-header">';
            echo '<span class="fp-breaking-icon">🔥</span>';
            echo '<h2>' . esc_html__('Breaking News', 'fp-newspaper') . '</h2>';
            echo '</div>';
            
            echo '<ul class="fp-breaking-list">';
            while ($query->have_posts()) {
                $query->the_post();
                echo '<li>';
                echo '<a href="' . esc_url(get_permalink()) . '">';
                echo '<strong>' . esc_html(get_the_title()) . '</strong>';
                echo '</a>';
                echo '<span class="fp-time">' . esc_html(human_time_diff(get_the_time('U'), current_time('timestamp'))) . ' ' . esc_html__('fa', 'fp-newspaper') . '</span>';
                echo '</li>';
            }
            echo '</ul>';
            
            echo '</div>';
            wp_reset_postdata();
        }
        
        return ob_get_clean();
    }
    
    /**
     * Shortcode: Ultimi articoli
     * 
     * [fp_latest_articles count="5"]
     */
    public static function latest_articles($atts) {
        $atts = shortcode_atts([
            'count' => 5,
            'show_date' => 'yes',
            'show_excerpt' => 'no',
        ], $atts, 'fp_latest_articles');
        
        $count = min(absint($atts['count']), 20);
        $show_date = 'yes' === $atts['show_date'];
        $show_excerpt = 'yes' === $atts['show_excerpt'];
        
        $args = [
            'post_type'      => 'post',
            'posts_per_page' => $count,
            'post_status'    => 'publish',
            'orderby'        => 'date',
            'order'          => 'DESC',
        ];
        
        $query = new \WP_Query($args);
        
        ob_start();
        
        if ($query->have_posts()) {
            echo '<div class="fp-latest-articles-shortcode">';
            
            while ($query->have_posts()) {
                $query->the_post();
                echo '<article class="fp-latest-item">';
                
                if (has_post_thumbnail()) {
                    echo '<div class="fp-latest-thumb">';
                    echo '<a href="' . esc_url(get_permalink()) . '">';
                    the_post_thumbnail('thumbnail');
                    echo '</a>';
                    echo '</div>';
                }
                
                echo '<div class="fp-latest-content">';
                echo '<h3 class="fp-latest-title">';
                echo '<a href="' . esc_url(get_permalink()) . '">' . esc_html(get_the_title()) . '</a>';
                echo '</h3>';
                
                if ($show_date) {
                    echo '<span class="fp-latest-date">' . esc_html(get_the_date()) . '</span>';
                }
                
                if ($show_excerpt) {
                    echo '<div class="fp-latest-excerpt">' . wp_kses_post(get_the_excerpt()) . '</div>';
                }
                
                echo '</div>';
                echo '</article>';
            }
            
            echo '</div>';
            wp_reset_postdata();
        }
        
        return ob_get_clean();
    }
    
    /**
     * Shortcode: Statistiche articolo
     * 
     * [fp_article_stats id="123"]
     */
    public static function article_stats($atts) {
        $atts = shortcode_atts([
            'id' => get_the_ID(),
        ], $atts, 'fp_article_stats');
        
        $post_id = absint($atts['id']);
        
        if (!$post_id || 'post' !== get_post_type($post_id)) {
            return '<p class="fp-error">' . esc_html__('Articolo non valido.', 'fp-newspaper') . '</p>';
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'fp_newspaper_stats';
        
        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT views, shares FROM $table_name WHERE post_id = %d",
            $post_id
        ));
        
        $views = $stats ? absint($stats->views) : 0;
        $shares = $stats ? absint($stats->shares) : 0;
        
        ob_start();
        ?>
        <div class="fp-article-stats-shortcode">
            <div class="fp-stat-item">
                <span class="fp-stat-icon">👁️</span>
                <span class="fp-stat-number"><?php echo number_format_i18n($views); ?></span>
                <span class="fp-stat-label"><?php esc_html_e('Visualizzazioni', 'fp-newspaper'); ?></span>
            </div>
            <div class="fp-stat-item">
                <span class="fp-stat-icon">🔗</span>
                <span class="fp-stat-number"><?php echo number_format_i18n($shares); ?></span>
                <span class="fp-stat-label"><?php esc_html_e('Condivisioni', 'fp-newspaper'); ?></span>
            </div>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Shortcode: Archivio Newspaper
     * 
     * [fp_newspaper_archive]
     */
    public static function newspaper_archive($atts) {
        $atts = shortcode_atts([
            'per_page' => 12,
            'layout' => 'grid',
            'show_filters' => 'yes',
        ], $atts, 'fp_newspaper_archive');
        
        $per_page = min(absint($atts['per_page']), 50);
        $layout = in_array($atts['layout'], ['grid', 'list']) ? $atts['layout'] : 'grid';
        $show_filters = 'yes' === $atts['show_filters'];
        
        // Query per articoli
        // Per page support (non solo per pagine archive, ma anche per page)
        global $wp_query;
        $paged = 1;
        if (get_query_var('paged')) {
            $paged = get_query_var('paged');
        } elseif (get_query_var('page')) {
            $paged = get_query_var('page');
        } elseif (isset($wp_query->query['paged'])) {
            $paged = $wp_query->query['paged'];
        }
        
        $args = [
            'post_type'      => 'post',
            'posts_per_page' => $per_page,
            'post_status'    => 'publish',
            'orderby'        => 'date',
            'order'          => 'DESC',
            'paged'          => $paged,
        ];
        
        // Filtro per categoria se presente in URL
        if (isset($_GET['category']) && !empty($_GET['category'])) {
            $category = sanitize_text_field($_GET['category']);
            $args['tax_query'] = [
                [
                    'taxonomy' => 'category',
                    'field'    => 'slug',
                    'terms'    => $category,
                ]
            ];
        }
        
        // Filtro per tag se presente in URL
        if (isset($_GET['tag']) && !empty($_GET['tag'])) {
            $tag = sanitize_text_field($_GET['tag']);
            if (!isset($args['tax_query'])) {
                $args['tax_query'] = [];
            }
            $args['tax_query'][] = [
                'taxonomy' => 'post_tag',
                'field'    => 'slug',
                'terms'    => $tag,
            ];
        }
        
        $query = new \WP_Query($args);
        
        ob_start();
        ?>
        <div class="fp-newspaper-archive">
            <?php if ($show_filters): ?>
            <div class="fp-archive-filters">
                <h3><?php esc_html_e('Filtra per:', 'fp-newspaper'); ?></h3>
                
                <!-- Filtro Categorie -->
                <div class="fp-filter fp-filter-categories">
                    <?php
                    $categories = get_terms([
                        'taxonomy' => 'category',
                        'hide_empty' => true,
                    ]);
                    
                    if ($categories && !is_wp_error($categories)):
                    ?>
                    <select name="category" class="fp-filter-select">
                        <option value=""><?php esc_html_e('Tutte le categorie', 'fp-newspaper'); ?></option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo esc_attr($cat->slug); ?>" 
                                <?php selected(isset($_GET['category']) ? sanitize_text_field($_GET['category']) : '', $cat->slug); ?>>
                            <?php echo esc_html($cat->name); ?> (<?php echo $cat->count; ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <?php endif; ?>
                </div>
                
                <!-- Filtro Tag -->
                <div class="fp-filter fp-filter-tags">
                    <?php
                    $tags = get_terms([
                        'taxonomy' => 'post_tag',
                        'hide_empty' => true,
                    ]);
                    
                    if ($tags && !is_wp_error($tags)):
                    ?>
                    <select name="tag" class="fp-filter-select">
                        <option value=""><?php esc_html_e('Tutti i tag', 'fp-newspaper'); ?></option>
                        <?php foreach ($tags as $tag): ?>
                        <option value="<?php echo esc_attr($tag->slug); ?>"
                                <?php selected(isset($_GET['tag']) ? sanitize_text_field($_GET['tag']) : '', $tag->slug); ?>>
                            <?php echo esc_html($tag->name); ?> (<?php echo $tag->count; ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <?php endif; ?>
                </div>
                
                <button type="button" class="fp-filter-button"><?php esc_html_e('Applica Filtri', 'fp-newspaper'); ?></button>
                <a href="<?php echo esc_url(remove_query_arg(['category', 'tag'])); ?>" 
                   class="fp-clear-filters"><?php esc_html_e('Rimuovi filtri', 'fp-newspaper'); ?></a>
            </div>
            <?php endif; ?>
            
            <!-- Risultati -->
            <div class="fp-archive-results">
                <div class="fp-archive-count">
                    <?php 
                    printf(
                        esc_html(_n('%d articolo trovato', '%d articoli trovati', $query->found_posts, 'fp-newspaper')),
                        number_format_i18n($query->found_posts)
                    );
                    ?>
                </div>
                
                <div class="fp-articles-shortcode fp-layout-<?php echo esc_attr($layout); ?>">
                <?php if ($query->have_posts()): ?>
                    <?php while ($query->have_posts()): $query->the_post(); ?>
                        <?php self::render_article_card(get_the_ID(), $layout); ?>
                    <?php endwhile; ?>
                    
                    <!-- Paginazione -->
                    <div class="fp-pagination">
                        <?php
                        echo paginate_links([
                            'total' => $query->max_num_pages,
                            'current' => max(1, $paged),
                            'prev_text' => '« ' . esc_html__('Precedente', 'fp-newspaper'),
                            'next_text' => esc_html__('Successiva', 'fp-newspaper') . ' »',
                        ]);
                        ?>
                    </div>
                <?php else: ?>
                    <p class="fp-no-articles"><?php esc_html_e('Nessun articolo trovato.', 'fp-newspaper'); ?></p>
                <?php endif; ?>
                </div>
            </div>
        </div>
        
        <style>
        .fp-newspaper-archive {
            max-width: 1200px;
            margin: 40px auto;
        }
        
        .fp-archive-filters {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        
        .fp-archive-filters h3 {
            margin-top: 0;
            margin-bottom: 15px;
            font-size: 18px;
        }
        
        .fp-filter {
            display: inline-block;
            margin-right: 15px;
            margin-bottom: 10px;
        }
        
        .fp-filter-select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            min-width: 200px;
        }
        
        .fp-filter-button {
            padding: 8px 20px;
            background: #2271b1;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .fp-filter-button:hover {
            background: #135e96;
        }
        
        .fp-clear-filters {
            margin-left: 15px;
            color: #d63638;
            text-decoration: none;
        }
        
        .fp-archive-count {
            margin-bottom: 20px;
            font-size: 14px;
            color: #646970;
        }
        
        .fp-pagination {
            margin-top: 40px;
            text-align: center;
        }
        
        .fp-pagination a,
        .fp-pagination span {
            display: inline-block;
            padding: 8px 12px;
            margin: 0 4px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-decoration: none;
        }
        
        .fp-pagination .current {
            background: #2271b1;
            color: white;
            border-color: #2271b1;
        }
        
        .fp-pagination a:hover {
            background: #f0f0f1;
        }
        </style>
        
        <script>
        (function() {
            const filterButton = document.querySelector('.fp-filter-button');
            if (filterButton) {
                filterButton.addEventListener('click', function() {
                    const categorySelect = document.querySelector('select[name="category"]');
                    const tagSelect = document.querySelector('select[name="tag"]');
                    
                    let url = window.location.href.split('?')[0];
                    const params = new URLSearchParams();
                    
                    if (categorySelect && categorySelect.value) {
                        params.append('category', categorySelect.value);
                    }
                    
                    if (tagSelect && tagSelect.value) {
                        params.append('tag', tagSelect.value);
                    }
                    
                    if (params.toString()) {
                        url += '?' + params.toString();
                    }
                    
                    window.location.href = url;
                });
            }
        })();
        </script>
        <?php
        wp_reset_postdata();
        
        return ob_get_clean();
    }
    
    /**
     * Renderizza card articolo
     *
     * @param int $post_id
     * @param string $layout
     */
    private static function render_article_card($post_id, $layout = 'grid') {
        if (!$post_id || !is_numeric($post_id)) {
            return;
        }
        
        $is_featured = '1' === get_post_meta($post_id, '_fp_featured', true);
        $is_breaking = '1' === get_post_meta($post_id, '_fp_breaking_news', true);
        
        $classes = ['fp-article-card'];
        if ($is_featured) {
            $classes[] = 'fp-is-featured';
        }
        if ($is_breaking) {
            $classes[] = 'fp-is-breaking';
        }
        
        echo '<article class="' . esc_attr(implode(' ', $classes)) . '">';
        
        // Thumbnail
        if (has_post_thumbnail($post_id)) {
            echo '<div class="fp-card-thumbnail">';
            echo '<a href="' . esc_url(get_permalink($post_id)) . '">';
            echo get_the_post_thumbnail($post_id, 'medium');
            echo '</a>';
            
            // Badge
            if ($is_breaking) {
                echo '<span class="fp-badge fp-badge-breaking">' . esc_html__('BREAKING', 'fp-newspaper') . '</span>';
            } elseif ($is_featured) {
                echo '<span class="fp-badge fp-badge-featured">⭐ ' . esc_html__('IN EVIDENZA', 'fp-newspaper') . '</span>';
            }
            
            echo '</div>';
        }
        
        echo '<div class="fp-card-content">';
        
        // Categorie
        $categories = get_the_terms($post_id, 'category');
        if ($categories && !is_wp_error($categories) && is_array($categories)) {
            echo '<div class="fp-card-categories">';
            foreach (array_slice($categories, 0, 2) as $cat) {
                if (!is_object($cat) || !isset($cat->name) || !isset($cat->term_id)) {
                    continue;
                }
                $term_link = get_term_link($cat);
                if (is_wp_error($term_link)) {
                    continue;
                }
                echo '<a href="' . esc_url($term_link) . '" class="fp-category-badge">';
                echo esc_html($cat->name);
                echo '</a>';
            }
            echo '</div>';
        }
        
        // Titolo
        echo '<h3 class="fp-card-title">';
        echo '<a href="' . esc_url(get_permalink($post_id)) . '">';
        echo esc_html(get_the_title($post_id));
        echo '</a>';
        echo '</h3>';
        
        // Sottotitolo
        $subtitle = get_post_meta($post_id, '_fp_article_subtitle', true);
        if (!empty($subtitle)) {
            echo '<p class="fp-card-subtitle">' . esc_html($subtitle) . '</p>';
        }
        
        // Meta
        echo '<div class="fp-card-meta">';
        echo '<span class="fp-card-date">' . esc_html(get_the_date('', $post_id)) . '</span>';
        echo '<span class="fp-card-author">' . esc_html__('di', 'fp-newspaper') . ' ' . esc_html(get_the_author_meta('display_name', get_post_field('post_author', $post_id))) . '</span>';
        echo '</div>';
        
        // Excerpt
        echo '<div class="fp-card-excerpt">';
        echo wp_kses_post(get_the_excerpt($post_id));
        echo '</div>';
        
        // Read more
        echo '<a href="' . esc_url(get_permalink($post_id)) . '" class="fp-card-readmore">';
        echo esc_html__('Leggi tutto', 'fp-newspaper') . ' →';
        echo '</a>';
        
        echo '</div>'; // .fp-card-content
        
        echo '</article>';
    }
    
    /**
     * Shortcode: Mappa Interattiva
     * 
     * [fp_interactive_map height="600" zoom="10"]
     */
    public static function interactive_map($atts) {
        $atts = shortcode_atts([
            'height' => 600,
            'zoom' => 10,
            'center_lat' => 41.9028, // Roma default
            'center_lng' => 12.4964,
        ], $atts, 'fp_interactive_map');

        self::enqueue_leaflet_assets();

        $height = min(absint($atts['height']), 1200);
        $zoom = min(absint($atts['zoom']), 18);
        $center_lat = floatval($atts['center_lat']);
        $center_lng = floatval($atts['center_lng']);
        $map_id = wp_unique_id('fp-interactive-map-');

        // Query articoli con localizzazione
        $args = [
            'post_type'      => 'post',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'meta_query'     => [
                [
                    'key'     => '_fp_show_on_map',
                    'value'   => '1',
                    'compare' => '='
                ],
                [
                    'key'     => '_fp_article_latitude',
                    'compare' => 'EXISTS'
                ],
                [
                    'key'     => '_fp_article_longitude',
                    'compare' => 'EXISTS'
                ]
            ]
        ];
        
        $query = new \WP_Query($args);
        
        ob_start();
        ?>
        <div class="fp-interactive-map-container" style="position: relative; width: 100%; height: <?php echo $height; ?>px;">
            <div id="<?php echo esc_attr($map_id); ?>" style="width: 100%; height: 100%; border-radius: 8px;"></div>
            
            <div class="fp-map-legend" style="position: absolute; top: 10px; right: 10px; background: white; padding: 15px; border-radius: 4px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); z-index: 1000; max-width: 250px;">
                <h4 style="margin: 0 0 10px 0; font-size: 14px;"><?php esc_html_e('Legenda', 'fp-newspaper'); ?></h4>
                <div style="font-size: 12px;">
                    <div><strong><?php esc_html_e('Articoli sulla mappa:', 'fp-newspaper'); ?></strong> <?php echo $query->found_posts; ?></div>
                    <p style="margin: 8px 0 0 0; color: #666;">
                        <?php esc_html_e('Clicca sui marker per visualizzare i dettagli degli articoli.', 'fp-newspaper'); ?>
                    </p>
                </div>
            </div>
        </div>
        
        <script>
        (function() {
            // Lazy loading map - carica solo quando visibile
            var mapElementId = <?php echo wp_json_encode($map_id); ?>;
            var mapElement = document.getElementById(mapElementId);
            var mapInitialized = false;
            
            function initMap() {
                if (mapInitialized || typeof L === 'undefined') {
                    return;
                }
                
                mapInitialized = true;
                
                try {
                    // Inizializza mappa
                    var map = L.map(mapElementId).setView([<?php echo $center_lat; ?>, <?php echo $center_lng; ?>], <?php echo $zoom; ?>);
                    
                    // Aggiungi tile layer OpenStreetMap
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                        maxZoom: 19
                    }).addTo(map);
                    
                    // Dati articoli per JavaScript
                    var articles = [
                <?php 
                if ($query->have_posts()) {
                    $first = true;
                    while ($query->have_posts()) {
                        $query->the_post();
                        $article_id = get_the_ID();
                        $lat = floatval(get_post_meta($article_id, '_fp_article_latitude', true));
                        $lng = floatval(get_post_meta($article_id, '_fp_article_longitude', true));
                        $address = get_post_meta($article_id, '_fp_article_address', true);
                        
                        if ($lat && $lng) {
                            if (!$first) echo ',';
                            $first = false;
                            ?>
                            {
                            id: <?php echo absint($article_id); ?>,
                            title: <?php echo wp_json_encode(get_the_title($article_id)); ?>,
                            lat: <?php echo floatval($lat); ?>,
                            lng: <?php echo floatval($lng); ?>,
                            url: <?php echo wp_json_encode(get_permalink($article_id)); ?>,
                            address: <?php echo wp_json_encode($address); ?>,
                            excerpt: <?php echo wp_json_encode(get_the_excerpt($article_id)); ?>,
                            date: <?php echo wp_json_encode(get_the_date('', $article_id)); ?>
                            }
                            <?php
                        }
                    }
                    wp_reset_postdata();
                }
                ?>
                    ];
                    
                    // Aggiungi marker per ogni articolo
                    var markers = L.markerClusterGroup({
                        chunkedLoading: true,
                        spiderfyOnMaxZoom: true,
                        showCoverageOnHover: false
                    });
                    
                    articles.forEach(function(article) {
                        var marker = L.marker([article.lat, article.lng]);
                        
                        var popupContent = '<div class="fp-map-popup">' +
                            '<h3><a href="' + article.url + '">' + article.title + '</a></h3>' +
                            (article.address ? '<p class="fp-map-address">📍 ' + article.address + '</p>' : '') +
                            '<p class="fp-map-date">📅 ' + article.date + '</p>' +
                            '<p class="fp-map-excerpt">' + article.excerpt.substring(0, 150) + '...</p>' +
                            '<a href="' + article.url + '" class="fp-map-readmore"><?php esc_html_e('Leggi articolo →', 'fp-newspaper'); ?></a>' +
                        '</div>';
                        
                        marker.bindPopup(popupContent);
                        markers.addLayer(marker);
                    });
                    
                    map.addLayer(markers);
                } catch(error) {
                    console.error('Errore inizializzazione mappa:', error);
                }
            }
            
            // Intersection Observer per lazy loading
            if ('IntersectionObserver' in window) {
                var observer = new IntersectionObserver(function(entries) {
                    entries.forEach(function(entry) {
                        if (entry.isIntersecting) {
                            initMap();
                            observer.unobserve(entry.target);
                        }
                    });
                }, {
                    rootMargin: '100px' // Carica 100px prima che diventi visibile
                });
                
                if (mapElement) {
                    observer.observe(mapElement);
                }
            } else {
                // Fallback per browser senza IntersectionObserver
                initMap();
            }
            
            // Aggiungi stili personalizzati
            var style = document.createElement('style');
            style.innerHTML = `
                .fp-map-popup {
                    min-width: 250px;
                    max-width: 350px;
                }
                .fp-map-popup h3 {
                    margin: 0 0 8px 0;
                    font-size: 16px;
                }
                .fp-map-popup h3 a {
                    color: #2271b1;
                    text-decoration: none;
                }
                .fp-map-popup h3 a:hover {
                    color: #135e96;
                }
                .fp-map-address, .fp-map-date {
                    margin: 5px 0;
                    font-size: 13px;
                    color: #666;
                }
                .fp-map-excerpt {
                    font-size: 13px;
                    color: #333;
                    margin: 10px 0;
                    line-height: 1.5;
                }
                .fp-map-readmore {
                    display: inline-block;
                    margin-top: 8px;
                    padding: 6px 12px;
                    background: #2271b1;
                    color: white;
                    text-decoration: none;
                    border-radius: 4px;
                    font-size: 13px;
                }
                .fp-map-readmore:hover {
                    background: #135e96;
                    color: white;
                }
                .fp-interactive-map-container .leaflet-container {
                    border-radius: 8px;
                }
            `;
            document.head.appendChild(style);
        })();
        </script>
        <?php
        
        return ob_get_clean();
    }

    /**
     * Shortcode: Mappa personalizzata per articolo
     *
     * [fp_article_locations_map height="500" zoom="14" post_id="123"]
     */
    public static function article_locations_map($atts) {
        $atts = shortcode_atts([
            'post_id' => 0,
            'height'  => 500,
            'zoom'    => 14,
        ], $atts, 'fp_article_locations_map');

        $post_id = absint($atts['post_id']);
        if (!$post_id) {
            $post_id = get_the_ID();
        }

        if (!$post_id) {
            return '';
        }

        $raw_locations = get_post_meta($post_id, '_fp_map_locations', true);
        if (!is_array($raw_locations) || empty($raw_locations)) {
            return '<div class="fp-story-map-empty">' . esc_html__('Nessun luogo configurato per questa mappa.', 'fp-newspaper') . '</div>';
        }

        $locations = [];
        foreach ($raw_locations as $location) {
            if (!is_array($location)) {
                continue;
            }

            if (!isset($location['latitude'], $location['longitude'])) {
                continue;
            }

            $lat = floatval($location['latitude']);
            $lng = floatval($location['longitude']);

            if (!is_finite($lat) || !is_finite($lng)) {
                continue;
            }

            $title   = isset($location['title']) ? sanitize_text_field($location['title']) : '';
            $caption = isset($location['caption']) ? sanitize_textarea_field($location['caption']) : '';
            $image_id = isset($location['image_id']) ? absint($location['image_id']) : 0;
            $image_html = $image_id ? wp_get_attachment_image($image_id, 'medium', false, ['class' => 'fp-story-map-image']) : '';

            $locations[] = [
                'title'      => $title,
                'caption'    => wpautop($caption),
                'lat'        => $lat,
                'lng'        => $lng,
                'image_html' => $image_html,
            ];
        }

        if (empty($locations)) {
            return '<div class="fp-story-map-empty">' . esc_html__('Coordinate mancanti per i luoghi della mappa.', 'fp-newspaper') . '</div>';
        }

        self::enqueue_leaflet_assets();

        $height = min(absint($atts['height']), 1200);
        $zoom   = max(1, min(absint($atts['zoom']), 19));
        $map_id = wp_unique_id('fp-story-map-');
        $default_center = [
            'lat' => $locations[0]['lat'],
            'lng' => $locations[0]['lng'],
        ];

        ob_start();
        ?>
        <div class="fp-story-map-wrapper">
            <div id="<?php echo esc_attr($map_id); ?>" class="fp-story-map" style="height: <?php echo esc_attr($height); ?>px;"></div>
            <div class="fp-story-map-list">
                <h4><?php esc_html_e('Luoghi in evidenza', 'fp-newspaper'); ?></h4>
                <ol>
                    <?php foreach ($locations as $index => $location) : ?>
                        <li>
                            <?php if (!empty($location['title'])) : ?>
                                <strong><?php echo esc_html($location['title']); ?></strong>
                            <?php else : ?>
                                <strong><?php printf(esc_html__('Luogo %d', 'fp-newspaper'), $index + 1); ?></strong>
                            <?php endif; ?>
                            <div class="fp-story-map-list__meta">
                                <span>Lat: <?php echo esc_html(number_format($location['lat'], 6)); ?></span>
                                <span>Lng: <?php echo esc_html(number_format($location['lng'], 6)); ?></span>
                            </div>
                            <?php if (!empty($location['caption'])) : ?>
                                <div class="fp-story-map-list__caption"><?php echo wp_kses_post($location['caption']); ?></div>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ol>
            </div>
        </div>

        <script>
        (function() {
            var mapElementId = <?php echo wp_json_encode($map_id); ?>;
            var mapData = <?php echo wp_json_encode($locations); ?>;
            var defaultCenter = <?php echo wp_json_encode($default_center); ?>;
            var defaultZoom = <?php echo (int) $zoom; ?>;

            function initStoryMap() {
                if (typeof L === 'undefined') {
                    setTimeout(initStoryMap, 120);
                    return;
                }

                var mapElement = document.getElementById(mapElementId);
                if (!mapElement) {
                    return;
                }

                var map = L.map(mapElementId).setView([defaultCenter.lat, defaultCenter.lng], defaultZoom);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                    maxZoom: 19
                }).addTo(map);

                var markerGroup = [];

                mapData.forEach(function(location) {
                    var marker = L.marker([location.lat, location.lng]).addTo(map);
                    markerGroup.push(marker);

                    if (location.title || location.caption || location.image_html) {
                        var popupHtml = '<div class="fp-story-map-popup">';
                        if (location.image_html) {
                            popupHtml += '<div class="fp-story-map-popup__image">' + location.image_html + '</div>';
                        }
                        if (location.title) {
                            popupHtml += '<h3>' + location.title + '</h3>';
                        }
                        if (location.caption) {
                            popupHtml += '<div class="fp-story-map-popup__caption">' + location.caption + '</div>';
                        }
                        popupHtml += '<div class="fp-story-map-popup__coords">Lat: ' + location.lat.toFixed(6) + ' • Lng: ' + location.lng.toFixed(6) + '</div>';
                        popupHtml += '</div>';
                        marker.bindPopup(popupHtml);
                    }
                });

                if (markerGroup.length > 1) {
                    var group = L.featureGroup(markerGroup);
                    map.fitBounds(group.getBounds().pad(0.2));
                }
            }

            if (document.readyState !== 'loading') {
                initStoryMap();
            } else {
                document.addEventListener('DOMContentLoaded', initStoryMap);
            }
        })();
        </script>

        <style>
        .fp-story-map-wrapper {
            position: relative;
            margin: 24px 0;
            border: 1px solid #e5e5e5;
            border-radius: 10px;
            overflow: hidden;
            background: #fff;
        }

        .fp-story-map {
            width: 100%;
        }

        .fp-story-map-list {
            padding: 16px 20px;
            background: #f9fafb;
            border-top: 1px solid #e5e5e5;
        }

        .fp-story-map-list h4 {
            margin: 0 0 10px;
            font-size: 14px;
            font-weight: 600;
        }

        .fp-story-map-list ol {
            margin: 0;
            padding-left: 18px;
            display: grid;
            gap: 12px;
        }

        .fp-story-map-list__meta {
            display: flex;
            gap: 12px;
            font-size: 12px;
            color: #64748b;
            margin-top: 4px;
        }

        .fp-story-map-list__caption {
            font-size: 13px;
            color: #475569;
            margin-top: 6px;
        }

        .fp-story-map-popup {
            max-width: 320px;
        }

        .fp-story-map-popup h3 {
            margin: 0 0 6px;
            font-size: 16px;
            color: #0f172a;
        }

        .fp-story-map-popup__image {
            margin-bottom: 8px;
        }

        .fp-story-map-popup__image img {
            width: 100%;
            height: auto;
            display: block;
            border-radius: 6px;
        }

        .fp-story-map-popup__caption {
            font-size: 13px;
            color: #475569;
            line-height: 1.5;
        }

        .fp-story-map-popup__coords {
            margin-top: 8px;
            font-size: 12px;
            color: #64748b;
        }

        @media (min-width: 992px) {
            .fp-story-map-wrapper {
                display: grid;
                grid-template-columns: 2fr 1fr;
            }

            .fp-story-map-list {
                border-left: 1px solid #e5e5e5;
                border-top: 0;
            }
        }
        </style>
        <?php

        return ob_get_clean();
    }

    /**
     * Enqueue Leaflet assets una sola volta.
     */
    private static function enqueue_leaflet_assets() {
        if (!wp_style_is('leaflet', 'enqueued')) {
            wp_enqueue_style('leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', [], '1.9.4');
        }

        if (!wp_script_is('leaflet', 'enqueued')) {
            wp_enqueue_script('leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', [], '1.9.4', true);
        }

        if (!wp_style_is('leaflet-markercluster', 'enqueued')) {
            wp_enqueue_style('leaflet-markercluster', 'https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css', ['leaflet'], '1.5.3');
            wp_enqueue_style('leaflet-markercluster-default', 'https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css', ['leaflet-markercluster'], '1.5.3');
        }

        if (!wp_script_is('leaflet-markercluster', 'enqueued')) {
            wp_enqueue_script('leaflet-markercluster', 'https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js', ['leaflet'], '1.5.3', true);
        }
    }
}







