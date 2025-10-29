<?php
/**
 * Gestione colonne personalizzate admin
 *
 * @package FPNewspaper
 */

namespace FPNewspaper\Admin;

defined('ABSPATH') || exit;

/**
 * Aggiunge colonne personalizzate alla lista articoli
 */
class Columns {
    
    /**
     * Costruttore
     */
    public function __construct() {
        // Colonne lista articoli
        add_filter('manage_fp_article_posts_columns', [$this, 'add_columns']);
        add_action('manage_fp_article_posts_custom_column', [$this, 'render_column'], 10, 2);
        add_filter('manage_edit-fp_article_sortable_columns', [$this, 'sortable_columns']);
        
        // Ordinamento
        add_action('pre_get_posts', [$this, 'handle_sorting']);
        
        // Filtri rapidi
        add_action('restrict_manage_posts', [$this, 'add_filters']);
        add_filter('parse_query', [$this, 'filter_query']);
    }
    
    /**
     * Aggiunge colonne personalizzate
     *
     * @param array $columns
     * @return array
     */
    public function add_columns($columns) {
        // Rimuovi colonna data default
        $date = $columns['date'];
        unset($columns['date']);
        
        // Aggiungi colonne custom
        $columns['thumbnail'] = __('Immagine', 'fp-newspaper');
        $columns['fp_featured'] = '<span class="dashicons dashicons-star-filled" title="' . esc_attr__('In Evidenza', 'fp-newspaper') . '"></span>';
        $columns['fp_breaking'] = '<span class="dashicons dashicons-megaphone" title="' . esc_attr__('Breaking News', 'fp-newspaper') . '"></span>';
        $columns['fp_views'] = __('Visualizzazioni', 'fp-newspaper');
        $columns['fp_categories'] = __('Categorie', 'fp-newspaper');
        $columns['date'] = $date; // Rimetti data alla fine
        
        return $columns;
    }
    
    /**
     * Renderizza contenuto colonne
     *
     * @param string $column
     * @param int $post_id
     */
    public function render_column($column, $post_id) {
        switch ($column) {
            case 'thumbnail':
                if (has_post_thumbnail($post_id)) {
                    echo get_the_post_thumbnail($post_id, [50, 50]);
                } else {
                    echo '<span class="dashicons dashicons-format-image" style="font-size: 50px; opacity: 0.3;"></span>';
                }
                break;
                
            case 'fp_featured':
                $featured = get_post_meta($post_id, '_fp_featured', true);
                if ('1' === $featured) {
                    echo '<span class="dashicons dashicons-star-filled" style="color: #dba617; font-size: 20px;" title="' . esc_attr__('In Evidenza', 'fp-newspaper') . '"></span>';
                } else {
                    echo '<span style="opacity: 0.2;">—</span>';
                }
                break;
                
            case 'fp_breaking':
                $breaking = get_post_meta($post_id, '_fp_breaking_news', true);
                if ('1' === $breaking) {
                    echo '<span class="dashicons dashicons-megaphone" style="color: #d63638; font-size: 20px;" title="' . esc_attr__('Breaking News', 'fp-newspaper') . '"></span>';
                } else {
                    echo '<span style="opacity: 0.2;">—</span>';
                }
                break;
                
            case 'fp_views':
                global $wpdb;
                $table_name = $wpdb->prefix . 'fp_newspaper_stats';
                
                $views = $wpdb->get_var($wpdb->prepare(
                    "SELECT views FROM $table_name WHERE post_id = %d",
                    $post_id
                ));
                
                if ($views) {
                    echo '<strong>' . number_format_i18n($views) . '</strong>';
                    echo '<br><small style="color: #646970;">👁️ views</small>';
                } else {
                    echo '<span style="opacity: 0.3;">0</span>';
                }
                break;
                
            case 'fp_categories':
                $terms = get_the_terms($post_id, 'fp_article_category');
                if ($terms && !is_wp_error($terms)) {
                    $output = [];
                    foreach ($terms as $term) {
                        $output[] = sprintf(
                            '<a href="%s">%s</a>',
                            esc_url(add_query_arg(['fp_article_category' => $term->slug], 'edit.php?post_type=fp_article')),
                            esc_html($term->name)
                        );
                    }
                    echo implode(', ', $output);
                } else {
                    echo '<span style="opacity: 0.3;">—</span>';
                }
                break;
        }
    }
    
    /**
     * Colonne ordinabili
     *
     * @param array $columns
     * @return array
     */
    public function sortable_columns($columns) {
        $columns['fp_views'] = 'fp_views';
        $columns['fp_featured'] = 'fp_featured';
        $columns['fp_breaking'] = 'fp_breaking';
        
        return $columns;
    }
    
    /**
     * Gestisce ordinamento custom
     *
     * @param \WP_Query $query
     */
    public function handle_sorting($query) {
        if (!is_admin() || !$query->is_main_query()) {
            return;
        }
        
        if ('fp_article' !== $query->get('post_type')) {
            return;
        }
        
        $orderby = $query->get('orderby');
        
        switch ($orderby) {
            case 'fp_views':
                $query->set('meta_key', '_fp_views_cache');
                $query->set('orderby', 'meta_value_num');
                break;
                
            case 'fp_featured':
                $query->set('meta_key', '_fp_featured');
                $query->set('orderby', 'meta_value');
                break;
                
            case 'fp_breaking':
                $query->set('meta_key', '_fp_breaking_news');
                $query->set('orderby', 'meta_value');
                break;
        }
    }
    
    /**
     * Aggiunge filtri dropdown
     *
     * @param string $post_type
     */
    public function add_filters($post_type) {
        if ('fp_article' !== $post_type) {
            return;
        }
        
        // Filtro per featured
        $featured = isset($_GET['fp_featured_filter']) ? sanitize_text_field($_GET['fp_featured_filter']) : '';
        ?>
        <select name="fp_featured_filter">
            <option value=""><?php _e('Tutti gli articoli', 'fp-newspaper'); ?></option>
            <option value="1" <?php selected($featured, '1'); ?>><?php _e('Solo in evidenza', 'fp-newspaper'); ?></option>
            <option value="0" <?php selected($featured, '0'); ?>><?php _e('Solo normali', 'fp-newspaper'); ?></option>
        </select>
        
        <?php
        // Filtro per breaking news
        $breaking = isset($_GET['fp_breaking_filter']) ? sanitize_text_field($_GET['fp_breaking_filter']) : '';
        ?>
        <select name="fp_breaking_filter">
            <option value=""><?php _e('Tutte le notizie', 'fp-newspaper'); ?></option>
            <option value="1" <?php selected($breaking, '1'); ?>><?php _e('Solo breaking news', 'fp-newspaper'); ?></option>
            <option value="0" <?php selected($breaking, '0'); ?>><?php _e('Solo normali', 'fp-newspaper'); ?></option>
        </select>
        <?php
    }
    
    /**
     * Applica filtri alla query
     *
     * @param \WP_Query $query
     */
    public function filter_query($query) {
        global $pagenow, $typenow;
        
        if ('edit.php' !== $pagenow || 'fp_article' !== $typenow) {
            return;
        }
        
        $meta_query = [];
        
        // Filtro featured
        if (isset($_GET['fp_featured_filter']) && '' !== $_GET['fp_featured_filter']) {
            $meta_query[] = [
                'key' => '_fp_featured',
                'value' => sanitize_text_field($_GET['fp_featured_filter']),
                'compare' => '='
            ];
        }
        
        // Filtro breaking
        if (isset($_GET['fp_breaking_filter']) && '' !== $_GET['fp_breaking_filter']) {
            $meta_query[] = [
                'key' => '_fp_breaking_news',
                'value' => sanitize_text_field($_GET['fp_breaking_filter']),
                'compare' => '='
            ];
        }
        
        if (!empty($meta_query)) {
            $query->set('meta_query', $meta_query);
        }
    }
}

