<?php
/**
 * Sistema Export/Import per articoli
 *
 * @package FPNewspaper
 */

namespace FPNewspaper;

defined('ABSPATH') || exit;

/**
 * Gestisce export e import di articoli
 */
class ExportImport {
    
    /**
     * Costruttore
     */
    public function __construct() {
        add_action('admin_menu', [$this, 'add_export_import_page']);
        add_action('admin_init', [$this, 'handle_export']);
        add_action('admin_init', [$this, 'handle_import']);
    }
    
    /**
     * Aggiunge pagina Export/Import
     */
    public function add_export_import_page() {
        add_submenu_page(
            'fp-newspaper',
            __('Export/Import Articoli', 'fp-newspaper'),
            __('Export/Import', 'fp-newspaper'),
            'manage_options',
            'fp-newspaper-export-import',
            [$this, 'render_page']
        );
    }
    
    /**
     * Gestisce export articoli
     */
    public function handle_export() {
        if (!isset($_GET['action']) || $_GET['action'] !== 'export' || !isset($_GET['nonce'])) {
            return;
        }
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Non hai i permessi per esportare articoli.', 'fp-newspaper'));
        }
        
        if (!wp_verify_nonce($_GET['nonce'], 'fp_export_articles')) {
            wp_die(__('Nonce non valido.', 'fp-newspaper'));
        }
        
        // Parametri export
        $post_ids = isset($_GET['posts']) ? array_map('absint', explode(',', $_GET['posts'])) : [];
        $include_media = isset($_GET['include_media']) && '1' === $_GET['include_media'];
        
        // Query articoli
        $args = [
            'post_type' => 'post',
            'posts_per_page' => -1,
            'post_status' => 'any',
        ];
        
        if (!empty($post_ids)) {
            $args['post__in'] = $post_ids;
        }
        
        $query = new \WP_Query($args);
        
        // Prepara dati
        $export_data = [
            'version' => FP_NEWSPAPER_VERSION,
            'date' => current_time('mysql'),
            'site_url' => get_site_url(),
            'articles' => []
        ];
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();
                
                if (!$post_id) {
                    continue;
                }
                
                $article = [
                    'post' => [
                        'post_title' => get_the_title($post_id),
                        'post_content' => get_post_field('post_content', $post_id),
                        'post_excerpt' => get_the_excerpt($post_id),
                        'post_status' => get_post_status($post_id),
                        'post_date' => get_post_field('post_date', $post_id),
                        'post_author' => get_post_field('post_author', $post_id),
                    ],
                    'meta' => [],
                    'taxonomies' => [],
                ];
                
                // Meta fields
                $meta_keys = [
                    '_fp_featured',
                    '_fp_breaking_news',
                    '_fp_article_subtitle',
                    '_fp_article_author_name',
                    '_fp_article_credit',
                    '_fp_article_priority',
                    '_fp_article_address',
                    '_fp_article_latitude',
                    '_fp_article_longitude',
                    '_fp_show_on_map',
                ];
                
                foreach ($meta_keys as $key) {
                    $value = get_post_meta($post_id, $key, true);
                    if (!empty($value)) {
                        $article['meta'][$key] = $value;
                    }
                }
                
                // Taxonomies
                $article['taxonomies']['category'] = wp_get_post_terms($post_id, 'category', ['fields' => 'names']);
                $article['taxonomies']['post_tag'] = wp_get_post_terms($post_id, 'post_tag', ['fields' => 'names']);
                
                // Featured image
                if (has_post_thumbnail($post_id)) {
                    $thumb_id = get_post_thumbnail_id($post_id);
                    $article['featured_image_url'] = wp_get_attachment_image_url($thumb_id, 'full');
                    
                    if ($include_media) {
                        $file_path = get_attached_file($thumb_id);
                        if ($file_path && file_exists($file_path)) {
                            $article['featured_image_base64'] = base64_encode(file_get_contents($file_path));
                            $article['featured_image_filename'] = basename($file_path);
                        }
                    }
                }
                
                $export_data['articles'][] = $article;
            }
            wp_reset_postdata();
        }
        
        // Output JSON
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="fp-newspaper-export-' . date('Y-m-d') . '.json"');
        
        echo wp_json_encode($export_data, JSON_PRETTY_PRINT);
        exit;
    }
    
    /**
     * Gestisce import articoli
     */
    public function handle_import() {
        if (!isset($_POST['fp_import_action']) || !wp_verify_nonce($_POST['_wpnonce'], 'fp_import_articles')) {
            return;
        }
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Non hai i permessi per importare articoli.', 'fp-newspaper'));
        }
        
        // Verifica upload
        if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>' . esc_html__('Errore nel caricamento del file.', 'fp-newspaper') . '</p></div>';
            });
            return;
        }
        
        $file_content = file_get_contents($_FILES['import_file']['tmp_name']);
        $import_data = json_decode($file_content, true);
        
        if (!$import_data || !isset($import_data['articles'])) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>' . esc_html__('File non valido.', 'fp-newspaper') . '</p></div>';
            });
            return;
        }
        
        $imported = 0;
        $skipped = 0;
        
        foreach ($import_data['articles'] as $article_data) {
            // Validazione dati
            if (!isset($article_data['post']['post_title']) || empty($article_data['post']['post_title'])) {
                $skipped++;
                continue;
            }
            
            // Controlla se esiste già
            if (isset($_POST['skip_existing']) && '1' === $_POST['skip_existing']) {
                $exists = post_exists($article_data['post']['post_title']);
                if ($exists) {
                    $skipped++;
                    continue;
                }
            }
            
            // Crea articolo
            if (!isset($article_data['post']) || !is_array($article_data['post'])) {
                $skipped++;
                continue;
            }
            
            $post_data = $article_data['post'];
            $post_data['post_type'] = 'post';
            $post_data['post_status'] = isset($_POST['import_status']) ? sanitize_text_field($_POST['import_status']) : 'draft';
            
            // Sanitizza post data
            if (!isset($post_data['post_title'])) {
                $post_data['post_title'] = '';
            }
            if (!isset($post_data['post_content'])) {
                $post_data['post_content'] = '';
            }
            if (!isset($post_data['post_excerpt'])) {
                $post_data['post_excerpt'] = '';
            }
            
            $post_data['post_title'] = sanitize_text_field($post_data['post_title']);
            $post_data['post_content'] = wp_kses_post($post_data['post_content']);
            $post_data['post_excerpt'] = sanitize_textarea_field($post_data['post_excerpt']);
            
            $new_post_id = wp_insert_post($post_data, true);
            
            if (is_wp_error($new_post_id)) {
                continue;
            }
            
            // Importa meta
            if (isset($article_data['meta']) && is_array($article_data['meta'])) {
                foreach ($article_data['meta'] as $key => $value) {
                    // Sanitizza chiave meta
                    $safe_key = sanitize_key($key);
                    if (!empty($safe_key)) {
                        // Sanitizza valore (mantieni tipo se possibile)
                        $safe_value = is_numeric($value) ? $value : sanitize_text_field($value);
                        update_post_meta($new_post_id, $safe_key, $safe_value);
                    }
                }
            }
            
            // Importa taxonomies
            if (isset($article_data['taxonomies']) && is_array($article_data['taxonomies'])) {
                foreach ($article_data['taxonomies'] as $taxonomy => $terms) {
                    if (is_array($terms) && !empty($terms)) {
                        // Sanitizza termini
                        $sanitized_terms = array_map('sanitize_text_field', $terms);
                        wp_set_post_terms($new_post_id, $sanitized_terms, sanitize_key($taxonomy));
                    }
                }
            }
            
            // Importa featured image
            if (isset($article_data['featured_image_url'])) {
                $this->import_featured_image($new_post_id, $article_data);
            }
            
            $imported++;
        }
        
        add_action('admin_notices', function() use ($imported, $skipped) {
            echo '<div class="notice notice-success"><p>';
            printf(
                esc_html__('Import completato! %d articoli importati, %d saltati.', 'fp-newspaper'),
                $imported,
                $skipped
            );
            echo '</p></div>';
        });
    }
    
    /**
     * Importa featured image
     */
    private function import_featured_image($post_id, $article_data) {
        // Prima prova con base64
        if (isset($article_data['featured_image_base64']) && !empty($article_data['featured_image_base64'])) {
            $filename = isset($article_data['featured_image_filename']) 
                ? sanitize_file_name($article_data['featured_image_filename'])
                : 'image.jpg';
            
            $decoded = base64_decode($article_data['featured_image_base64'], true);
            if ($decoded === false) {
                return; // Base64 invalido
            }
            
            $upload = wp_upload_bits($filename, null, $decoded);
            
            if (!$upload['error']) {
                $filetype = wp_check_filetype($filename);
                $attachment_id = wp_insert_attachment([
                    'post_mime_type' => $filetype['type'],
                    'post_title' => sanitize_file_name($filename),
                    'post_content' => '',
                    'post_status' => 'inherit'
                ], $upload['file']);
                
                if (!is_wp_error($attachment_id) && is_numeric($attachment_id)) {
                    require_once(ABSPATH . 'wp-admin/includes/image.php');
                    $attach_data = wp_generate_attachment_metadata($attachment_id, $upload['file']);
                    wp_update_attachment_metadata($attachment_id, $attach_data);
                    
                    set_post_thumbnail($post_id, $attachment_id);
                }
                return;
            }
        }
        
        // Fallback: scarica da URL
        if (isset($article_data['featured_image_url']) && !empty($article_data['featured_image_url'])) {
            // Includi file necessari
            if (!function_exists('media_sideload_image')) {
                require_once(ABSPATH . 'wp-admin/includes/media.php');
                require_once(ABSPATH . 'wp-admin/includes/file.php');
                require_once(ABSPATH . 'wp-admin/includes/image.php');
            }
            
            $attachment_id = media_sideload_image($article_data['featured_image_url'], $post_id, null, 'id');
            
            if (!is_wp_error($attachment_id) && is_numeric($attachment_id)) {
                set_post_thumbnail($post_id, $attachment_id);
            }
        }
    }
    
    /**
     * Renderizza pagina Export/Import
     */
    public function render_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Non hai i permessi per accedere a questa pagina.', 'fp-newspaper'));
        }
        
        // Conta articoli
        $total_articles = wp_count_posts('post');
        ?>
        <div class="wrap fp-export-import-page">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="fp-ei-container">
                <!-- Export Section -->
                <div class="fp-ei-section fp-ei-export">
                    <div class="fp-ei-header">
                        <h2>📤 <?php _e('Esporta Articoli', 'fp-newspaper'); ?></h2>
                        <p class="description"><?php _e('Esporta articoli in formato JSON per backup o migrazione.', 'fp-newspaper'); ?></p>
                    </div>
                    
                    <div class="fp-ei-form">
                        <h3><?php _e('Opzioni Esportazione', 'fp-newspaper'); ?></h3>
                        
                        <p>
                            <strong><?php _e('Articoli totali:', 'fp-newspaper'); ?></strong> 
                            <?php echo number_format_i18n($total_articles->publish); ?> pubblicati
                        </p>
                        
                        <form method="get" action="<?php echo admin_url('admin.php'); ?>">
                            <input type="hidden" name="page" value="fp-newspaper-export-import">
                            <input type="hidden" name="action" value="export">
                            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('fp_export_articles'); ?>">
                            
                            <p>
                                <label>
                                    <input type="checkbox" name="include_media" value="1" checked>
                                    <?php _e('Includi file media (immagini) nell\'export', 'fp-newspaper'); ?>
                                </label>
                                <span class="description"><?php _e('Attenzione: file più grandi', 'fp-newspaper'); ?></span>
                            </p>
                            
                            <?php submit_button(__('Esporta Articoli', 'fp-newspaper'), 'primary', 'submit', false); ?>
                        </form>
                    </div>
                </div>
                
                <!-- Import Section -->
                <div class="fp-ei-section fp-ei-import">
                    <div class="fp-ei-header">
                        <h2>📥 <?php _e('Importa Articoli', 'fp-newspaper'); ?></h2>
                        <p class="description"><?php _e('Importa articoli da un file JSON di export.', 'fp-newspaper'); ?></p>
                    </div>
                    
                    <div class="fp-ei-form">
                        <form method="post" enctype="multipart/form-data">
                            <?php wp_nonce_field('fp_import_articles'); ?>
                            <input type="hidden" name="fp_import_action" value="import">
                            
                            <p>
                                <label for="import_file">
                                    <strong><?php _e('File JSON:', 'fp-newspaper'); ?></strong>
                                </label>
                                <input type="file" name="import_file" accept=".json,application/json" required>
                            </p>
                            
                            <p>
                                <label>
                                    <input type="checkbox" name="skip_existing" value="1" checked>
                                    <?php _e('Salta articoli esistenti', 'fp-newspaper'); ?>
                                </label>
                            </p>
                            
                            <p>
                                <label for="import_status">
                                    <strong><?php _e('Stato importazione:', 'fp-newspaper'); ?></strong>
                                </label>
                                <select name="import_status" id="import_status">
                                    <option value="draft"><?php _e('Bozza', 'fp-newspaper'); ?></option>
                                    <option value="publish"><?php _e('Pubblicato', 'fp-newspaper'); ?></option>
                                    <option value="private"><?php _e('Privato', 'fp-newspaper'); ?></option>
                                </select>
                            </p>
                            
                            <?php submit_button(__('Importa Articoli', 'fp-newspaper'), 'primary', 'submit', false); ?>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .fp-export-import-page {
            max-width: 1200px;
        }
        
        .fp-ei-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-top: 30px;
        }
        
        .fp-ei-section {
            background: white;
            border: 1px solid #c3c4c7;
            border-radius: 8px;
            padding: 25px;
        }
        
        .fp-ei-header h2 {
            margin-top: 0;
            font-size: 20px;
            color: #1d2327;
        }
        
        .fp-ei-form h3 {
            margin-top: 0;
            font-size: 16px;
        }
        
        .fp-ei-form p {
            margin-bottom: 20px;
        }
        
        .fp-ei-form label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }
        
        .fp-ei-form input[type="file"] {
            width: 100%;
            padding: 8px;
        }
        
        .fp-ei-form select {
            width: 100%;
            padding: 8px;
        }
        
        @media (max-width: 782px) {
            .fp-ei-container {
                grid-template-columns: 1fr;
            }
        }
        </style>
        <?php
    }
}

