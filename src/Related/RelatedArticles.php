<?php
/**
 * Related Articles - Articoli Correlati Intelligenti
 *
 * @package FPNewspaper\Related
 */

namespace FPNewspaper\Related;

use FPNewspaper\Logger;
use FPNewspaper\Cache\Manager as CacheManager;

defined('ABSPATH') || exit;

/**
 * Sistema articoli correlati con algoritmi di similarità
 */
class RelatedArticles {
    
    /**
     * Meta key override manuale
     */
    const META_RELATED_OVERRIDE = '_fp_related_override';
    
    /**
     * Costruttore
     */
    public function __construct() {
        add_filter('the_content', [$this, 'add_related_articles'], 30);
        add_action('add_meta_boxes', [$this, 'add_meta_box']);
        add_action('save_post', [$this, 'save_related_override'], 15);
    }
    
    /**
     * Aggiunge box articoli correlati
     */
    public function add_related_articles($content) {
        if (!is_singular('post') || !in_the_loop()) {
            return $content;
        }
        
        $related = $this->get_related(get_the_ID(), 'smart', 4);
        
        if (empty($related)) {
            return $content;
        }
        
        ob_start();
        ?>
        <section class="fp-related-articles" aria-labelledby="fp-related-title">
            <h3 id="fp-related-title" class="fp-related-title">
                <span class="fp-related-icon" aria-hidden="true">📚</span>
                <?php _e('Articoli Correlati', 'fp-newspaper'); ?>
            </h3>
            <div class="fp-related-grid">
                <?php foreach ($related as $post): ?>
                    <article class="fp-related-item">
                        <?php if (has_post_thumbnail($post->ID)): ?>
                            <div class="fp-related-thumb">
                                <a href="<?php echo esc_url(get_permalink($post->ID)); ?>" 
                                   aria-label="<?php echo esc_attr($post->post_title); ?>">
                                    <?php echo get_the_post_thumbnail($post->ID, 'medium', ['loading' => 'lazy']); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                        <div class="fp-related-content">
                            <h4 class="fp-related-post-title">
                                <a href="<?php echo esc_url(get_permalink($post->ID)); ?>" class="fp-focus-visible">
                                    <?php echo esc_html($post->post_title); ?>
                                </a>
                            </h4>
                            <time class="fp-related-meta" datetime="<?php echo esc_attr(get_the_date('c', $post->ID)); ?>">
                                <?php echo esc_html(get_the_date('', $post->ID)); ?>
                            </time>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
        <?php
        $related_html = ob_get_clean();
        
        return $content . $related_html;
    }
    
    /**
     * Meta box per override manuale
     */
    public function add_meta_box() {
        add_meta_box(
            'fp_related_articles',
            __('🔗 Articoli Correlati', 'fp-newspaper'),
            [$this, 'render_meta_box'],
            'post',
            'side',
            'low'
        );
    }
    
    /**
     * Renderizza meta box
     */
    public function render_meta_box($post) {
        wp_nonce_field('fp_related_nonce', 'fp_related_nonce');
        
        $override = get_post_meta($post->ID, self::META_RELATED_OVERRIDE, true);
        $override_ids = $override ? explode(',', $override) : [];
        
        ?>
        <p class="description">
            <?php _e('Override manuale (opzionale). Lascia vuoto per automatico.', 'fp-newspaper'); ?>
        </p>
        
        <input type="text" name="fp_related_override" value="<?php echo esc_attr($override); ?>" class="widefat" placeholder="<?php esc_attr_e('ID articoli separati da virgola (es: 123,456)', 'fp-newspaper'); ?>">
        
        <?php if (!empty($override_ids)): ?>
            <div style="margin-top: 10px;">
                <strong><?php _e('Articoli selezionati:', 'fp-newspaper'); ?></strong>
                <ul style="margin: 5px 0;">
                    <?php foreach ($override_ids as $id): ?>
                        <?php $related_post = get_post(absint($id)); ?>
                        <?php if ($related_post): ?>
                            <li><?php echo esc_html($related_post->post_title); ?></li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <?php
    }
    
    /**
     * Salva override
     */
    public function save_related_override($post_id) {
        if (!isset($_POST['fp_related_nonce']) || 
            !wp_verify_nonce($_POST['fp_related_nonce'], 'fp_related_nonce')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (isset($_POST['fp_related_override'])) {
            update_post_meta($post_id, self::META_RELATED_OVERRIDE, sanitize_text_field($_POST['fp_related_override']));
        }
    }
    
    /**
     * Ottiene articoli correlati
     *
     * @param int $post_id
     * @param string $algorithm 'simple' | 'smart'
     * @param int $limit
     * @return array
     */
    public function get_related($post_id, $algorithm = 'smart', $limit = 4) {
        // Check override manuale
        $override = get_post_meta($post_id, self::META_RELATED_OVERRIDE, true);
        if ($override) {
            return $this->get_by_ids(explode(',', $override));
        }
        
        // Cache
        return CacheManager::get("related_articles_{$post_id}_{$algorithm}_{$limit}", function() use ($post_id, $algorithm, $limit) {
            if ($algorithm === 'smart') {
                return $this->get_related_smart($post_id, $limit);
            }
            return $this->get_related_simple($post_id, $limit);
        }, 3600);
    }
    
    /**
     * Algoritmo semplice: base tag e categoria
     */
    private function get_related_simple($post_id, $limit) {
        $categories = wp_get_post_categories($post_id);
        $tags = wp_get_post_tags($post_id, ['fields' => 'ids']);
        
        // Edge case: articolo senza categorie/tag - return empty
        if (empty($categories) && empty($tags)) {
            return [];
        }
        
        $tax_query = ['relation' => 'OR'];
        
        if (!empty($categories)) {
            $tax_query[] = [
                'taxonomy' => 'category',
                'field' => 'term_id',
                'terms' => $categories,
            ];
        }
        
        if (!empty($tags)) {
            $tax_query[] = [
                'taxonomy' => 'post_tag',
                'field' => 'term_id',
                'terms' => $tags,
            ];
        }
        
        $args = [
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'post__not_in' => [$post_id],
            'tax_query' => $tax_query,
        ];
        
        $query = new \WP_Query($args);
        return $query->posts;
    }
    
    /**
     * Algoritmo smart: similarità ponderata
     */
    private function get_related_smart($post_id, $limit) {
        global $wpdb;
        
        $categories = wp_get_post_categories($post_id);
        $tags = wp_get_post_tags($post_id, ['fields' => 'ids']);
        
        if (empty($categories) && empty($tags)) {
            return $this->get_related_simple($post_id, $limit);
        }
        
        // Sanitizza IDs per IN clause (WordPress best practice)
        $cat_ids = !empty($categories) ? implode(',', array_map('absint', $categories)) : '0';
        $tag_ids = !empty($tags) ? implode(',', array_map('absint', $tags)) : '0';
        
        // NOTA: IN clause non supporta placeholder in wpdb->prepare, quindi sanitizziamo manualmente con absint()
        // Questo è sicuro perché absint() garantisce solo numeri interi
        $sql = $wpdb->prepare("
            SELECT 
                p.ID,
                p.post_title,
                p.post_date,
                (
                    (SELECT COUNT(*) FROM {$wpdb->term_relationships} tr
                     INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                     WHERE tr.object_id = p.ID 
                     AND tt.taxonomy = 'category'
                     AND tt.term_id IN ({$cat_ids})
                    ) * 2
                ) +
                (
                    (SELECT COUNT(*) FROM {$wpdb->term_relationships} tr
                     INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                     WHERE tr.object_id = p.ID 
                     AND tt.taxonomy = 'post_tag'
                     AND tt.term_id IN ({$tag_ids})
                    )
                ) as similarity_score
            FROM {$wpdb->posts} p
            WHERE p.post_type = 'post'
            AND p.post_status = 'publish'
            AND p.ID != %d
            HAVING similarity_score > 0
            ORDER BY similarity_score DESC, p.post_date DESC
            LIMIT %d
        ", $post_id, $limit);
        
        $results = $wpdb->get_results($sql);
        
        return $results ?: [];
    }
    
    /**
     * Ottiene articoli per ID
     */
    private function get_by_ids($ids) {
        $ids = array_map('absint', $ids);
        $ids = array_filter($ids);
        
        if (empty($ids)) {
            return [];
        }
        
        $query = new \WP_Query([
            'post_type' => 'post',
            'post_status' => 'publish',
            'post__in' => $ids,
            'orderby' => 'post__in',
            'posts_per_page' => count($ids),
        ]);
        
        return $query->posts;
    }
}

