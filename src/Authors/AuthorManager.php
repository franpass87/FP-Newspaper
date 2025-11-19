<?php
/**
 * Gestione Autori Avanzata
 *
 * @package FPNewspaper\Authors
 */

namespace FPNewspaper\Authors;

use FPNewspaper\Logger;

defined('ABSPATH') || exit;

/**
 * Profili autori estesi con statistiche e social
 */
class AuthorManager {
    
    /**
     * Meta keys
     */
    const META_BIO_SHORT = 'fp_author_bio_short';
    const META_BIO_LONG = 'fp_author_bio_long';
    const META_EXPERTISE = 'fp_author_expertise';
    const META_SOCIAL_TWITTER = 'fp_author_twitter';
    const META_SOCIAL_LINKEDIN = 'fp_author_linkedin';
    const META_SOCIAL_FACEBOOK = 'fp_author_facebook';
    const META_BADGE = 'fp_author_badge';
    
    /**
     * Costruttore
     */
    public function __construct() {
        add_action('show_user_profile', [$this, 'add_profile_fields']);
        add_action('edit_user_profile', [$this, 'add_profile_fields']);
        add_action('personal_options_update', [$this, 'save_profile_fields']);
        add_action('edit_user_profile_update', [$this, 'save_profile_fields']);
        
        // Author box in articoli
        add_filter('the_content', [$this, 'add_author_box'], 20);
    }
    
    /**
     * Aggiunge campi profilo autore
     */
    public function add_profile_fields($user) {
        $bio_short = get_user_meta($user->ID, self::META_BIO_SHORT, true);
        $bio_long = get_user_meta($user->ID, self::META_BIO_LONG, true);
        $expertise = get_user_meta($user->ID, self::META_EXPERTISE, true);
        $twitter = get_user_meta($user->ID, self::META_SOCIAL_TWITTER, true);
        $linkedin = get_user_meta($user->ID, self::META_SOCIAL_LINKEDIN, true);
        $facebook = get_user_meta($user->ID, self::META_SOCIAL_FACEBOOK, true);
        $badge = get_user_meta($user->ID, self::META_BADGE, true);
        
        ?>
        <h2><?php _e('📰 Profilo Autore FP Newspaper', 'fp-newspaper'); ?></h2>
        <table class="form-table">
            <tr>
                <th><label for="fp_author_badge"><?php _e('Badge/Credenziale', 'fp-newspaper'); ?></label></th>
                <td>
                    <select name="fp_author_badge" id="fp_author_badge" class="regular-text">
                        <option value=""><?php _e('Nessun badge', 'fp-newspaper'); ?></option>
                        <option value="special_correspondent" <?php selected($badge, 'special_correspondent'); ?>><?php _e('Inviato Speciale', 'fp-newspaper'); ?></option>
                        <option value="foreign_correspondent" <?php selected($badge, 'foreign_correspondent'); ?>><?php _e('Corrispondente Estero', 'fp-newspaper'); ?></option>
                        <option value="columnist" <?php selected($badge, 'columnist'); ?>><?php _e('Opinionista', 'fp-newspaper'); ?></option>
                        <option value="investigative" <?php selected($badge, 'investigative'); ?>><?php _e('Giornalista Investigativo', 'fp-newspaper'); ?></option>
                        <option value="expert" <?php selected($badge, 'expert'); ?>><?php _e('Esperto di Settore', 'fp-newspaper'); ?></option>
                    </select>
                    <p class="description"><?php _e('Badge mostrato accanto al nome', 'fp-newspaper'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="fp_author_bio_short"><?php _e('Bio Breve', 'fp-newspaper'); ?></label></th>
                <td>
                    <input type="text" name="fp_author_bio_short" id="fp_author_bio_short" value="<?php echo esc_attr($bio_short); ?>" class="regular-text">
                    <p class="description"><?php _e('1 riga, mostrata negli articoli (max 160 caratteri)', 'fp-newspaper'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="fp_author_bio_long"><?php _e('Bio Completa', 'fp-newspaper'); ?></label></th>
                <td>
                    <textarea name="fp_author_bio_long" id="fp_author_bio_long" rows="5" class="large-text"><?php echo esc_textarea($bio_long); ?></textarea>
                    <p class="description"><?php _e('Biografia completa, mostrata nella pagina autore', 'fp-newspaper'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="fp_author_expertise"><?php _e('Aree di Competenza', 'fp-newspaper'); ?></label></th>
                <td>
                    <input type="text" name="fp_author_expertise" id="fp_author_expertise" value="<?php echo esc_attr($expertise); ?>" class="regular-text">
                    <p class="description"><?php _e('Es: Politica, Economia, Sport (separati da virgola)', 'fp-newspaper'); ?></p>
                </td>
            </tr>
        </table>
        
        <h3><?php _e('Social Media', 'fp-newspaper'); ?></h3>
        <table class="form-table">
            <tr>
                <th><label for="fp_author_twitter"><?php _e('Twitter/X', 'fp-newspaper'); ?></label></th>
                <td>
                    <input type="text" name="fp_author_twitter" id="fp_author_twitter" value="<?php echo esc_attr($twitter); ?>" class="regular-text" placeholder="@username">
                </td>
            </tr>
            <tr>
                <th><label for="fp_author_linkedin"><?php _e('LinkedIn', 'fp-newspaper'); ?></label></th>
                <td>
                    <input type="url" name="fp_author_linkedin" id="fp_author_linkedin" value="<?php echo esc_attr($linkedin); ?>" class="regular-text" placeholder="https://linkedin.com/in/username">
                </td>
            </tr>
            <tr>
                <th><label for="fp_author_facebook"><?php _e('Facebook', 'fp-newspaper'); ?></label></th>
                <td>
                    <input type="url" name="fp_author_facebook" id="fp_author_facebook" value="<?php echo esc_attr($facebook); ?>" class="regular-text" placeholder="https://facebook.com/username">
                </td>
            </tr>
        </table>
        
        <?php
        // Mostra statistiche autore
        $stats = $this->get_author_stats($user->ID);
        if ($stats): ?>
            <h3><?php _e('📊 Statistiche', 'fp-newspaper'); ?></h3>
            <table class="form-table">
                <tr>
                    <th><?php _e('Articoli Pubblicati:', 'fp-newspaper'); ?></th>
                    <td><strong><?php echo esc_html($stats['published']); ?></strong></td>
                </tr>
                <tr>
                    <th><?php _e('Views Totali:', 'fp-newspaper'); ?></th>
                    <td><strong><?php echo esc_html(number_format_i18n($stats['total_views'])); ?></strong></td>
                </tr>
                <tr>
                    <th><?php _e('Media Views/Articolo:', 'fp-newspaper'); ?></th>
                    <td><strong><?php echo esc_html(number_format_i18n($stats['avg_views'])); ?></strong></td>
                </tr>
                <tr>
                    <th><?php _e('Articolo Più Letto:', 'fp-newspaper'); ?></th>
                    <td>
                        <?php if ($stats['top_article']): ?>
                            <a href="<?php echo esc_url(get_edit_post_link($stats['top_article']->ID)); ?>">
                                <?php echo esc_html($stats['top_article']->post_title); ?>
                                (<?php echo esc_html(number_format_i18n($stats['top_article']->views)); ?> views)
                            </a>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
        <?php endif;
    }
    
    /**
     * Salva campi profilo
     */
    public function save_profile_fields($user_id) {
        if (!current_user_can('edit_user', $user_id)) {
            return;
        }
        
        if (isset($_POST['fp_author_bio_short'])) {
            update_user_meta($user_id, self::META_BIO_SHORT, sanitize_text_field($_POST['fp_author_bio_short']));
        }
        
        if (isset($_POST['fp_author_bio_long'])) {
            update_user_meta($user_id, self::META_BIO_LONG, sanitize_textarea_field($_POST['fp_author_bio_long']));
        }
        
        if (isset($_POST['fp_author_expertise'])) {
            update_user_meta($user_id, self::META_EXPERTISE, sanitize_text_field($_POST['fp_author_expertise']));
        }
        
        if (isset($_POST['fp_author_twitter'])) {
            update_user_meta($user_id, self::META_SOCIAL_TWITTER, sanitize_text_field($_POST['fp_author_twitter']));
        }
        
        if (isset($_POST['fp_author_linkedin'])) {
            update_user_meta($user_id, self::META_SOCIAL_LINKEDIN, esc_url_raw($_POST['fp_author_linkedin']));
        }
        
        if (isset($_POST['fp_author_facebook'])) {
            update_user_meta($user_id, self::META_SOCIAL_FACEBOOK, esc_url_raw($_POST['fp_author_facebook']));
        }
        
        if (isset($_POST['fp_author_badge'])) {
            update_user_meta($user_id, self::META_BADGE, sanitize_text_field($_POST['fp_author_badge']));
        }
    }
    
    /**
     * Aggiunge author box agli articoli
     */
    public function add_author_box($content) {
        if (!is_singular('post') || !in_the_loop()) {
            return $content;
        }
        
        $author_id = get_the_author_meta('ID');
        $author_box = $this->render_author_box($author_id);
        
        return $content . $author_box;
    }
    
    /**
     * Renderizza author box
     */
    public function render_author_box($author_id) {
        $author = get_userdata($author_id);
        if (!$author) {
            return '';
        }
        
        $bio_short = get_user_meta($author_id, self::META_BIO_SHORT, true);
        $badge = get_user_meta($author_id, self::META_BADGE, true);
        $twitter = get_user_meta($author_id, self::META_SOCIAL_TWITTER, true);
        $linkedin = get_user_meta($author_id, self::META_SOCIAL_LINKEDIN, true);
        $stats = $this->get_author_stats($author_id);
        
        ob_start();
        ?>
        <section class="fp-author-box" aria-labelledby="fp-author-<?php echo esc_attr($author_id); ?>-name">
            <div class="fp-author-avatar">
                <?php echo get_avatar($author_id, 80, '', esc_attr($author->display_name)); ?>
            </div>
            <div class="fp-author-info">
                <h4 id="fp-author-<?php echo esc_attr($author_id); ?>-name" class="fp-author-name">
                    <?php echo esc_html($author->display_name); ?>
                    <?php if ($badge): ?>
                        <span class="fp-author-badge" role="text"><?php echo esc_html($this->get_badge_label($badge)); ?></span>
                    <?php endif; ?>
                </h4>
                <?php if ($bio_short): ?>
                    <p class="fp-author-bio"><?php echo esc_html($bio_short); ?></p>
                <?php endif; ?>
                <div class="fp-author-meta">
                    <span><?php echo esc_html($stats['published']); ?> articoli</span>
                    <?php if ($twitter || $linkedin): ?>
                        <span class="fp-author-social" role="list">
                            <?php if ($twitter): ?>
                                <a href="https://twitter.com/<?php echo esc_attr(ltrim($twitter, '@')); ?>" 
                                   target="_blank" 
                                   rel="noopener noreferrer" 
                                   aria-label="<?php echo esc_attr(sprintf(__('Segui %s su Twitter', 'fp-newspaper'), $author->display_name)); ?>"
                                   class="fp-focus-visible">
                                    <span class="dashicons dashicons-twitter" aria-hidden="true"></span>
                                </a>
                            <?php endif; ?>
                            <?php if ($linkedin): ?>
                                <a href="<?php echo esc_url($linkedin); ?>" 
                                   target="_blank" 
                                   rel="noopener noreferrer" 
                                   aria-label="<?php echo esc_attr(sprintf(__('Connetti con %s su LinkedIn', 'fp-newspaper'), $author->display_name)); ?>"
                                   class="fp-focus-visible">
                                    <span class="dashicons dashicons-linkedin" aria-hidden="true"></span>
                                </a>
                            <?php endif; ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Ottiene statistiche autore
     */
    public function get_author_stats($author_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'fp_newspaper_stats';
        
        // Conta articoli pubblicati
        $published = (int) $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*)
            FROM {$wpdb->posts}
            WHERE post_type = 'post'
            AND post_status = 'publish'
            AND post_author = %d
        ", $author_id));
        
        // Views totali
        $total_views = (int) $wpdb->get_var($wpdb->prepare("
            SELECT COALESCE(SUM(s.views), 0)
            FROM {$wpdb->posts} p
            LEFT JOIN {$table_name} s ON p.ID = s.post_id
            WHERE p.post_type = 'post'
            AND p.post_status = 'publish'
            AND p.post_author = %d
        ", $author_id));
        
        // Media views
        $avg_views = $published > 0 ? round($total_views / $published) : 0;
        
        // Articolo più letto
        $top_article = $wpdb->get_row($wpdb->prepare("
            SELECT p.ID, p.post_title, s.views
            FROM {$wpdb->posts} p
            INNER JOIN {$table_name} s ON p.ID = s.post_id
            WHERE p.post_type = 'post'
            AND p.post_status = 'publish'
            AND p.post_author = %d
            ORDER BY s.views DESC
            LIMIT 1
        ", $author_id));
        
        return [
            'published' => $published,
            'total_views' => $total_views,
            'avg_views' => $avg_views,
            'top_article' => $top_article,
        ];
    }
    
    /**
     * Leaderboard autori
     */
    public function get_leaderboard($period = 'month', $limit = 10) {
        global $wpdb;
        
        $days = $period === 'week' ? 7 : 30;
        $start_date = date('Y-m-d', strtotime("-{$days} days"));
        $table_name = $wpdb->prefix . 'fp_newspaper_stats';
        
        $results = $wpdb->get_results($wpdb->prepare("
            SELECT 
                u.ID,
                u.display_name,
                COUNT(p.ID) as article_count,
                COALESCE(SUM(s.views), 0) as total_views,
                COALESCE(AVG(s.views), 0) as avg_views
            FROM {$wpdb->users} u
            INNER JOIN {$wpdb->posts} p ON u.ID = p.post_author
            LEFT JOIN {$table_name} s ON p.ID = s.post_id
            WHERE p.post_type = 'post'
            AND p.post_status = 'publish'
            AND DATE(p.post_date) >= %s
            GROUP BY u.ID
            ORDER BY total_views DESC
            LIMIT %d
        ", $start_date, $limit));
        
        return $results ?: [];
    }
    
    /**
     * Ottiene label badge
     */
    private function get_badge_label($badge) {
        $labels = [
            'special_correspondent' => __('Inviato Speciale', 'fp-newspaper'),
            'foreign_correspondent' => __('Corrispondente Estero', 'fp-newspaper'),
            'columnist' => __('Opinionista', 'fp-newspaper'),
            'investigative' => __('Giornalista Investigativo', 'fp-newspaper'),
            'expert' => __('Esperto di Settore', 'fp-newspaper'),
        ];
        
        return $labels[$badge] ?? '';
    }
}

