<?php
/**
 * Desk/Sezioni Redazionali
 *
 * @package FPNewspaper\Editorial
 */

namespace FPNewspaper\Editorial;

use FPNewspaper\Logger;

defined('ABSPATH') || exit;

/**
 * Gestisce desk/sezioni redazionali del giornale
 */
class Desks {
    
    /**
     * Taxonomy slug
     */
    const TAXONOMY = 'fp_desk';
    
    /**
     * Meta key editor desk
     */
    const META_DESK_EDITOR = 'fp_desk_editor';
    
    /**
     * Costruttore
     */
    public function __construct() {
        add_action('init', [$this, 'register_taxonomy']);
        add_action('fp_desk_add_form_fields', [$this, 'add_desk_fields']);
        add_action('fp_desk_edit_form_fields', [$this, 'edit_desk_fields']);
        add_action('created_fp_desk', [$this, 'save_desk_meta']);
        add_action('edited_fp_desk', [$this, 'save_desk_meta']);
        add_action('save_post', [$this, 'save_post_desk'], 10, 2);
    }
    
    /**
     * Registra tassonomia Desk
     */
    public function register_taxonomy() {
        $labels = [
            'name' => __('Desk/Sezioni', 'fp-newspaper'),
            'singular_name' => __('Desk', 'fp-newspaper'),
            'menu_name' => __('Desk Redazionali', 'fp-newspaper'),
            'all_items' => __('Tutti i Desk', 'fp-newspaper'),
            'edit_item' => __('Modifica Desk', 'fp-newspaper'),
            'view_item' => __('Visualizza Desk', 'fp-newspaper'),
            'update_item' => __('Aggiorna Desk', 'fp-newspaper'),
            'add_new_item' => __('Aggiungi Nuovo Desk', 'fp-newspaper'),
            'new_item_name' => __('Nome Nuovo Desk', 'fp-newspaper'),
            'search_items' => __('Cerca Desk', 'fp-newspaper'),
        ];
        
        register_taxonomy(self::TAXONOMY, ['post'], [
            'hierarchical' => true,
            'labels' => $labels,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => ['slug' => 'desk'],
            'show_in_rest' => true,
            'meta_box_cb' => [$this, 'custom_meta_box'],
        ]);
    }
    
    /**
     * Meta box custom per desk (con stats)
     */
    public function custom_meta_box($post) {
        wp_nonce_field('fp_desk_nonce', 'fp_desk_nonce_field');
        
        $terms = get_terms([
            'taxonomy' => self::TAXONOMY,
            'hide_empty' => false,
        ]);
        
        $current_desk = wp_get_object_terms($post->ID, self::TAXONOMY, ['fields' => 'ids']);
        $current_desk = !empty($current_desk) ? $current_desk[0] : 0;
        
        ?>
        <div id="fp-desk-selector">
            <select name="fp_desk" id="fp_desk" class="widefat">
                <option value=""><?php _e('Nessun Desk', 'fp-newspaper'); ?></option>
                <?php foreach ($terms as $term): ?>
                    <?php
                    $editor_id = get_term_meta($term->term_id, self::META_DESK_EDITOR, true);
                    $editor = $editor_id ? get_userdata($editor_id) : null;
                    ?>
                    <option value="<?php echo esc_attr($term->term_id); ?>" <?php selected($current_desk, $term->term_id); ?>>
                        <?php echo esc_html($term->name); ?>
                        <?php if ($editor): ?>
                            (<?php echo esc_html($editor->display_name); ?>)
                        <?php endif; ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <p class="description"><?php _e('Assegna l\'articolo a un desk redazionale', 'fp-newspaper'); ?></p>
        </div>
        <?php
    }
    
    /**
     * Campi aggiuntivi desk (create)
     */
    public function add_desk_fields($taxonomy) {
        $editors = get_users(['role__in' => ['fp_editor', 'fp_caporedattore', 'administrator']]);
        
        ?>
        <div class="form-field">
            <label for="fp_desk_editor"><?php _e('Editor Responsabile', 'fp-newspaper'); ?></label>
            <select name="fp_desk_editor" id="fp_desk_editor" class="postform">
                <option value=""><?php _e('Nessuno', 'fp-newspaper'); ?></option>
                <?php foreach ($editors as $editor): ?>
                    <option value="<?php echo esc_attr($editor->ID); ?>">
                        <?php echo esc_html($editor->display_name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <p><?php _e('Responsabile del desk che gestisce gli articoli di questa sezione', 'fp-newspaper'); ?></p>
        </div>
        <?php
    }
    
    /**
     * Campi aggiuntivi desk (edit)
     */
    public function edit_desk_fields($term) {
        $editor_id = get_term_meta($term->term_id, self::META_DESK_EDITOR, true);
        $editors = get_users(['role__in' => ['fp_editor', 'fp_caporedattore', 'administrator']]);
        
        ?>
        <tr class="form-field">
            <th scope="row">
                <label for="fp_desk_editor"><?php _e('Editor Responsabile', 'fp-newspaper'); ?></label>
            </th>
            <td>
                <select name="fp_desk_editor" id="fp_desk_editor" class="postform">
                    <option value=""><?php _e('Nessuno', 'fp-newspaper'); ?></option>
                    <?php foreach ($editors as $editor): ?>
                        <option value="<?php echo esc_attr($editor->ID); ?>" <?php selected($editor_id, $editor->ID); ?>>
                            <?php echo esc_html($editor->display_name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="description"><?php _e('Responsabile del desk', 'fp-newspaper'); ?></p>
                
                <?php
                // Mostra statistiche desk
                $stats = $this->get_desk_stats($term->term_id);
                if ($stats):
                ?>
                    <div style="margin-top: 15px; padding: 10px; background: #f9f9f9; border-radius: 4px;">
                        <strong><?php _e('Statistiche Desk:', 'fp-newspaper'); ?></strong>
                        <ul style="margin: 10px 0 0 0;">
                            <li><?php printf(__('Articoli pubblicati: %d', 'fp-newspaper'), $stats['published']); ?></li>
                            <li><?php printf(__('In lavorazione: %d', 'fp-newspaper'), $stats['in_progress']); ?></li>
                            <li><?php printf(__('Views totali: %s', 'fp-newspaper'), number_format_i18n($stats['total_views'])); ?></li>
                        </ul>
                    </div>
                <?php endif; ?>
            </td>
        </tr>
        <?php
    }
    
    /**
     * Salva meta desk (taxonomy term)
     */
    public function save_desk_meta($term_id) {
        if (isset($_POST['fp_desk_editor'])) {
            $editor_id = absint($_POST['fp_desk_editor']);
            update_term_meta($term_id, self::META_DESK_EDITOR, $editor_id);
        }
    }
    
    /**
     * Salva desk assegnato all'articolo
     */
    public function save_post_desk($post_id, $post) {
        // Verifica nonce
        if (!isset($_POST['fp_desk_nonce_field']) || 
            !wp_verify_nonce($_POST['fp_desk_nonce_field'], 'fp_desk_nonce')) {
            return;
        }
        
        // Verifica post type
        if ('post' !== $post->post_type) {
            return;
        }
        
        // Verifica autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Verifica capability
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Salva desk
        if (isset($_POST['fp_desk'])) {
            $desk_id = absint($_POST['fp_desk']);
            
            if ($desk_id > 0) {
                wp_set_object_terms($post_id, $desk_id, self::TAXONOMY);
            } else {
                // Rimuovi desk
                wp_delete_object_term_relationships($post_id, self::TAXONOMY);
            }
        }
    }
    
    /**
     * Ottiene desk di un articolo
     */
    public function get_article_desk($post_id) {
        $terms = wp_get_object_terms($post_id, self::TAXONOMY);
        return !empty($terms) && !is_wp_error($terms) ? $terms[0] : null;
    }
    
    /**
     * Statistiche desk
     */
    public function get_desk_stats($desk_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'fp_newspaper_stats';
        
        // Conta articoli del desk
        $published = (int) $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(DISTINCT p.ID)
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
            WHERE p.post_type = 'post'
            AND p.post_status = 'publish'
            AND tr.term_taxonomy_id = %d
        ", $desk_id));
        
        $in_progress = (int) $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(DISTINCT p.ID)
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
            WHERE p.post_type = 'post'
            AND p.post_status IN ('draft', 'fp_in_review', 'fp_approved')
            AND tr.term_taxonomy_id = %d
        ", $desk_id));
        
        $total_views = (int) $wpdb->get_var($wpdb->prepare("
            SELECT COALESCE(SUM(s.views), 0)
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
            LEFT JOIN {$table_name} s ON p.ID = s.post_id
            WHERE p.post_type = 'post'
            AND p.post_status = 'publish'
            AND tr.term_taxonomy_id = %d
        ", $desk_id));
        
        return [
            'published' => $published,
            'in_progress' => $in_progress,
            'total_views' => $total_views,
        ];
    }
    
    /**
     * Ottiene articoli desk
     */
    public function get_desk_articles($desk_id, $args = []) {
        $defaults = [
            'post_type' => 'post',
            'posts_per_page' => 20,
            'tax_query' => [
                [
                    'taxonomy' => self::TAXONOMY,
                    'field' => 'term_id',
                    'terms' => $desk_id,
                ],
            ],
        ];
        
        $args = wp_parse_args($args, $defaults);
        return new \WP_Query($args);
    }
}

