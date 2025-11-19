<?php
/**
 * Story Formats - Template Articoli per Tipologia
 *
 * @package FPNewspaper\Templates
 */

namespace FPNewspaper\Templates;

use FPNewspaper\Logger;

defined('ABSPATH') || exit;

/**
 * Gestisce template articoli per diverse tipologie giornalistiche
 */
class StoryFormats {
    
    /**
     * Formati disponibili
     */
    const FORMAT_NEWS = 'news';
    const FORMAT_INTERVIEW = 'interview';
    const FORMAT_REPORTAGE = 'reportage';
    const FORMAT_OPINION = 'opinion';
    const FORMAT_LIVEBLOG = 'liveblog';
    const FORMAT_PHOTOSTORY = 'photostory';
    
    /**
     * Meta key formato
     */
    const META_STORY_FORMAT = '_fp_story_format';
    
    /**
     * Costruttore
     */
    public function __construct() {
        add_action('add_meta_boxes', [$this, 'add_meta_box']);
        add_action('save_post', [$this, 'save_format'], 5, 2);
        add_filter('post_class', [$this, 'add_format_class']);
    }
    
    /**
     * Aggiunge meta box selezione formato
     */
    public function add_meta_box() {
        add_meta_box(
            'fp_story_format',
            __('📰 Formato Articolo', 'fp-newspaper'),
            [$this, 'render_meta_box'],
            'post',
            'side',
            'high'
        );
    }
    
    /**
     * Renderizza meta box
     */
    public function render_meta_box($post) {
        wp_nonce_field('fp_story_format_nonce', 'fp_story_format_nonce');
        
        $current_format = get_post_meta($post->ID, self::META_STORY_FORMAT, true) ?: self::FORMAT_NEWS;
        $formats = $this->get_formats();
        
        ?>
        <div class="fp-format-selector">
            <select name="fp_story_format" id="fp_story_format" class="widefat">
                <?php foreach ($formats as $key => $format): ?>
                    <option value="<?php echo esc_attr($key); ?>" <?php selected($current_format, $key); ?>>
                        <?php echo esc_html($format['icon'] . ' ' . $format['label']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <div class="fp-format-description" style="margin-top: 10px; padding: 10px; background: #f9f9f9; border-radius: 4px; font-size: 12px;">
                <?php
                $desc = $formats[$current_format]['description'] ?? '';
                echo esc_html($desc);
                ?>
            </div>
            
            <?php
            // Meta boxes specifici per formato
            $this->render_format_specific_fields($post, $current_format);
            ?>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#fp_story_format').on('change', function() {
                // Reload per mostrare campi specifici
                // In futuro: carica via AJAX senza reload
                var notice = '<?php echo esc_js(__('Salva la bozza per vedere i campi specifici del formato', 'fp-newspaper')); ?>';
                if (confirm(notice + '\n\nSalvare ora?')) {
                    $('#save-post').click();
                }
            });
        });
        </script>
        <?php
    }
    
    /**
     * Campi specifici per formato
     */
    private function render_format_specific_fields($post, $format) {
        switch ($format) {
            case self::FORMAT_INTERVIEW:
                $this->render_interview_fields($post);
                break;
                
            case self::FORMAT_REPORTAGE:
                $this->render_reportage_fields($post);
                break;
                
            case self::FORMAT_LIVEBLOG:
                $this->render_liveblog_fields($post);
                break;
                
            case self::FORMAT_PHOTOSTORY:
                $this->render_photostory_fields($post);
                break;
        }
    }
    
    /**
     * Campi intervista
     */
    private function render_interview_fields($post) {
        $interviewee = get_post_meta($post->ID, '_fp_interview_interviewee', true);
        $role = get_post_meta($post->ID, '_fp_interview_role', true);
        
        ?>
        <div class="fp-format-fields" style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd;">
            <h4><?php _e('Campi Intervista', 'fp-newspaper'); ?></h4>
            
            <p>
                <label><?php _e('Intervistato:', 'fp-newspaper'); ?></label>
                <input type="text" name="fp_interview_interviewee" value="<?php echo esc_attr($interviewee); ?>" class="widefat" placeholder="<?php esc_attr_e('Nome intervistato', 'fp-newspaper'); ?>">
            </p>
            
            <p>
                <label><?php _e('Ruolo/Carica:', 'fp-newspaper'); ?></label>
                <input type="text" name="fp_interview_role" value="<?php echo esc_attr($role); ?>" class="widefat" placeholder="<?php esc_attr_e('Es: CEO, Sindaco, etc.', 'fp-newspaper'); ?>">
            </p>
        </div>
        <?php
    }
    
    /**
     * Campi reportage
     */
    private function render_reportage_fields($post) {
        $location = get_post_meta($post->ID, '_fp_reportage_location', true);
        $duration = get_post_meta($post->ID, '_fp_reportage_duration', true);
        
        ?>
        <div class="fp-format-fields" style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd;">
            <h4><?php _e('Campi Reportage', 'fp-newspaper'); ?></h4>
            
            <p>
                <label><?php _e('Luogo:', 'fp-newspaper'); ?></label>
                <input type="text" name="fp_reportage_location" value="<?php echo esc_attr($location); ?>" class="widefat" placeholder="<?php esc_attr_e('Luogo del reportage', 'fp-newspaper'); ?>">
            </p>
            
            <p>
                <label><?php _e('Durata Inchiesta:', 'fp-newspaper'); ?></label>
                <input type="text" name="fp_reportage_duration" value="<?php echo esc_attr($duration); ?>" class="widefat" placeholder="<?php esc_attr_e('Es: 3 mesi, 2 settimane', 'fp-newspaper'); ?>">
            </p>
        </div>
        <?php
    }
    
    /**
     * Campi live blog
     */
    private function render_liveblog_fields($post) {
        $event_start = get_post_meta($post->ID, '_fp_liveblog_event_start', true);
        $is_active = get_post_meta($post->ID, '_fp_liveblog_active', true);
        
        ?>
        <div class="fp-format-fields" style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd;">
            <h4><?php _e('Campi Live Blog', 'fp-newspaper'); ?></h4>
            
            <p>
                <label><?php _e('Inizio Evento:', 'fp-newspaper'); ?></label>
                <input type="datetime-local" name="fp_liveblog_event_start" value="<?php echo esc_attr($event_start); ?>" class="widefat">
            </p>
            
            <p>
                <label>
                    <input type="checkbox" name="fp_liveblog_active" value="1" <?php checked($is_active, '1'); ?>>
                    <?php _e('Live Blog Attivo (aggiornamenti in tempo reale)', 'fp-newspaper'); ?>
                </label>
            </p>
        </div>
        <?php
    }
    
    /**
     * Campi photo story
     */
    private function render_photostory_fields($post) {
        $photographer = get_post_meta($post->ID, '_fp_photostory_photographer', true);
        $photo_count = get_post_meta($post->ID, '_fp_photostory_count', true);
        
        ?>
        <div class="fp-format-fields" style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd;">
            <h4><?php _e('Campi Foto-Reportage', 'fp-newspaper'); ?></h4>
            
            <p>
                <label><?php _e('Fotografo:', 'fp-newspaper'); ?></label>
                <input type="text" name="fp_photostory_photographer" value="<?php echo esc_attr($photographer); ?>" class="widefat">
            </p>
            
            <p>
                <label><?php _e('Numero Foto:', 'fp-newspaper'); ?></label>
                <input type="number" name="fp_photostory_count" value="<?php echo esc_attr($photo_count); ?>" class="widefat" min="1">
            </p>
        </div>
        <?php
    }
    
    /**
     * Salva formato
     */
    public function save_format($post_id, $post) {
        if (!isset($_POST['fp_story_format_nonce']) || 
            !wp_verify_nonce($_POST['fp_story_format_nonce'], 'fp_story_format_nonce')) {
            return;
        }
        
        if ('post' !== $post->post_type) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Salva formato
        if (isset($_POST['fp_story_format'])) {
            $format = sanitize_text_field($_POST['fp_story_format']);
            update_post_meta($post_id, self::META_STORY_FORMAT, $format);
            
            // Salva campi specifici
            $this->save_format_specific_fields($post_id, $format);
        }
    }
    
    /**
     * Salva campi specifici formato
     */
    private function save_format_specific_fields($post_id, $format) {
        switch ($format) {
            case self::FORMAT_INTERVIEW:
                if (isset($_POST['fp_interview_interviewee'])) {
                    update_post_meta($post_id, '_fp_interview_interviewee', sanitize_text_field($_POST['fp_interview_interviewee']));
                }
                if (isset($_POST['fp_interview_role'])) {
                    update_post_meta($post_id, '_fp_interview_role', sanitize_text_field($_POST['fp_interview_role']));
                }
                break;
                
            case self::FORMAT_REPORTAGE:
                if (isset($_POST['fp_reportage_location'])) {
                    update_post_meta($post_id, '_fp_reportage_location', sanitize_text_field($_POST['fp_reportage_location']));
                }
                if (isset($_POST['fp_reportage_duration'])) {
                    update_post_meta($post_id, '_fp_reportage_duration', sanitize_text_field($_POST['fp_reportage_duration']));
                }
                break;
                
            case self::FORMAT_LIVEBLOG:
                if (isset($_POST['fp_liveblog_event_start'])) {
                    update_post_meta($post_id, '_fp_liveblog_event_start', sanitize_text_field($_POST['fp_liveblog_event_start']));
                }
                $is_active = isset($_POST['fp_liveblog_active']) && '1' === $_POST['fp_liveblog_active'] ? '1' : '0';
                update_post_meta($post_id, '_fp_liveblog_active', $is_active);
                break;
                
            case self::FORMAT_PHOTOSTORY:
                if (isset($_POST['fp_photostory_photographer'])) {
                    update_post_meta($post_id, '_fp_photostory_photographer', sanitize_text_field($_POST['fp_photostory_photographer']));
                }
                if (isset($_POST['fp_photostory_count'])) {
                    update_post_meta($post_id, '_fp_photostory_count', absint($_POST['fp_photostory_count']));
                }
                break;
        }
    }
    
    /**
     * Aggiunge classe CSS per formato
     */
    public function add_format_class($classes) {
        if (is_singular('post')) {
            $format = get_post_meta(get_the_ID(), self::META_STORY_FORMAT, true);
            if ($format) {
                $classes[] = 'story-format-' . $format;
            }
        }
        return $classes;
    }
    
    /**
     * Ottiene formati disponibili
     */
    public function get_formats() {
        return [
            self::FORMAT_NEWS => [
                'label' => __('News Standard', 'fp-newspaper'),
                'icon' => '📰',
                'description' => __('Articolo news standard: chi, cosa, dove, quando, perché', 'fp-newspaper'),
            ],
            self::FORMAT_INTERVIEW => [
                'label' => __('Intervista', 'fp-newspaper'),
                'icon' => '🎤',
                'description' => __('Formato domanda-risposta con intervistato', 'fp-newspaper'),
            ],
            self::FORMAT_REPORTAGE => [
                'label' => __('Reportage', 'fp-newspaper'),
                'icon' => '📸',
                'description' => __('Inchiesta approfondita, long-form journalism', 'fp-newspaper'),
            ],
            self::FORMAT_OPINION => [
                'label' => __('Opinione/Editoriale', 'fp-newspaper'),
                'icon' => '✍️',
                'description' => __('Articolo di opinione, commento, editoriale', 'fp-newspaper'),
            ],
            self::FORMAT_LIVEBLOG => [
                'label' => __('Live Blog', 'fp-newspaper'),
                'icon' => '🔴',
                'description' => __('Copertura live evento con aggiornamenti continui', 'fp-newspaper'),
            ],
            self::FORMAT_PHOTOSTORY => [
                'label' => __('Foto-Reportage', 'fp-newspaper'),
                'icon' => '📷',
                'description' => __('Storia raccontata principalmente con foto', 'fp-newspaper'),
            ],
        ];
    }
    
    /**
     * Ottiene formato articolo
     */
    public function get_article_format($post_id) {
        return get_post_meta($post_id, self::META_STORY_FORMAT, true) ?: self::FORMAT_NEWS;
    }
    
    /**
     * Statistiche formati
     */
    public function get_format_stats() {
        global $wpdb;
        
        $stats = [];
        $formats = array_keys($this->get_formats());
        
        foreach ($formats as $format) {
            $count = (int) $wpdb->get_var($wpdb->prepare("
                SELECT COUNT(*)
                FROM {$wpdb->posts} p
                INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                WHERE p.post_type = 'post'
                AND p.post_status = 'publish'
                AND pm.meta_key = %s
                AND pm.meta_value = %s
            ", self::META_STORY_FORMAT, $format));
            
            $stats[$format] = $count;
        }
        
        return $stats;
    }
}

