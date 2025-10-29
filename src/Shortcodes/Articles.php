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
            'post_type'      => 'fp_article',
            'posts_per_page' => $count,
            'post_status'    => 'publish',
            'orderby'        => $orderby,
            'order'          => $order,
        ];
        
        // Aggiungi filtro categoria
        if (!empty($category)) {
            $args['tax_query'] = [
                [
                    'taxonomy' => 'fp_article_category',
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
                'taxonomy' => 'fp_article_tag',
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
            'post_type'      => 'fp_article',
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
            'post_type'      => 'fp_article',
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
            'post_type'      => 'fp_article',
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
        
        if (!$post_id || 'fp_article' !== get_post_type($post_id)) {
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
     * Renderizza card articolo
     *
     * @param int $post_id
     * @param string $layout
     */
    private static function render_article_card($post_id, $layout = 'grid') {
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
        $categories = get_the_terms($post_id, 'fp_article_category');
        if ($categories && !is_wp_error($categories)) {
            echo '<div class="fp-card-categories">';
            foreach (array_slice($categories, 0, 2) as $cat) {
                echo '<a href="' . esc_url(get_term_link($cat)) . '" class="fp-category-badge">';
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
}


