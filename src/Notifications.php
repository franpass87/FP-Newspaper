<?php
/**
 * Sistema Email Notifications
 *
 * @package FPNewspaper
 */

namespace FPNewspaper;

defined('ABSPATH') || exit;

/**
 * Gestisce le notifiche email per articoli
 */
class Notifications {
    
    /**
     * Costruttore
     */
    public function __construct() {
        // Hook per notifiche
        add_action('publish_post', [$this, 'send_new_article_notification'], 10, 2);
        add_action('wp_insert_comment', [$this, 'send_comment_notification'], 10, 2);
        
        // Admin page
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
    }
    
    /**
     * Invia notifica quando un nuovo articolo viene pubblicato
     */
    public function send_new_article_notification($post_id, $post) {
        // Solo per articoli pubblicati per la prima volta
        if ($post->post_status !== 'publish') {
            return;
        }
        
        // Verifica se già notificato
        if (get_post_meta($post_id, '_fp_email_notification_sent', true)) {
            return;
        }
        
        $enable_notifications = get_option('fp_newspaper_enable_new_article_emails', false);
        
        if (!$enable_notifications) {
            return;
        }
        
        // Ottieni destinatari
        $recipients = get_option('fp_newspaper_email_recipients', '');
        
        if (empty($recipients)) {
            return;
        }
        
        // Parse destinatari (può essere singolo o multipli separati da virgola)
        $emails = array_map('trim', explode(',', $recipients));
        
        $subject = sprintf(
            __('[%s] Nuovo Articolo: %s', 'fp-newspaper'),
            get_bloginfo('name'),
            get_the_title($post_id)
        );
        
        $message = $this->get_new_article_email_template($post_id, $post);
        
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        ];
        
        foreach ($emails as $email) {
            if (is_email($email)) {
                wp_mail($email, $subject, $message, $headers);
            }
        }
        
        // Marca come notificato
        update_post_meta($post_id, '_fp_email_notification_sent', true);
    }
    
    /**
     * Invia notifica per nuovi commenti
     */
    public function send_comment_notification($comment_id, $comment) {
        // Solo per articoli
        if ($comment->comment_post_ID && get_post_type($comment->comment_post_ID) !== 'post') {
            return;
        }
        
        $enable_comments = get_option('fp_newspaper_enable_comment_emails', false);
        
        if (!$enable_comments) {
            return;
        }
        
        $post_id = $comment->comment_post_ID;
        $post = get_post($post_id);
        
        if (!$post || is_wp_error($post)) {
            return;
        }
        
        // Notifica autore articolo
        $author_email = get_the_author_meta('user_email', $post->post_author);
        
        if ($author_email && $author_email !== $comment->comment_author_email) {
            $subject = sprintf(
                __('[%s] Nuovo Commento su: %s', 'fp-newspaper'),
                get_bloginfo('name'),
                get_the_title($post_id)
            );
            
            $message = $this->get_comment_email_template($comment, $post);
            
            $headers = [
                'Content-Type: text/html; charset=UTF-8',
                'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
            ];
            
            wp_mail($author_email, $subject, $message, $headers);
        }
    }
    
    /**
     * Template email nuovo articolo
     */
    private function get_new_article_email_template($post_id, $post) {
        $author = get_the_author_meta('display_name', $post->post_author);
        $date = get_the_date('', $post_id);
        $permalink = get_permalink($post_id);
        
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #2271b1; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 8px 8px; }
                .article-title { font-size: 24px; margin: 20px 0; color: #1d2327; }
                .meta { color: #646970; font-size: 14px; margin-bottom: 20px; }
                .excerpt { font-size: 16px; line-height: 1.8; margin: 20px 0; }
                .button { display: inline-block; padding: 12px 24px; background: #2271b1; color: white; text-decoration: none; border-radius: 4px; margin: 20px 0; }
                .footer { text-align: center; margin-top: 30px; font-size: 12px; color: #646970; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1><?php echo esc_html(get_bloginfo('name')); ?></h1>
                    <p style="margin: 0;">📰 <?php _e('Nuovo Articolo Pubblicato', 'fp-newspaper'); ?></p>
                </div>
                
                <div class="content">
                    <h2 class="article-title"><?php echo esc_html(get_the_title($post_id)); ?></h2>
                    
                    <div class="meta">
                        <strong><?php _e('Autore:', 'fp-newspaper'); ?></strong> <?php echo esc_html($author); ?><br>
                        <strong><?php _e('Data:', 'fp-newspaper'); ?></strong> <?php echo esc_html($date); ?>
                    </div>
                    
                    <?php if (has_post_thumbnail($post_id)): ?>
                    <p>
                        <img src="<?php echo esc_url(get_the_post_thumbnail_url($post_id, 'medium')); ?>" 
                             alt="<?php echo esc_attr(get_the_title($post_id)); ?>" 
                             style="width: 100%; height: auto; border-radius: 4px;">
                    </p>
                    <?php endif; ?>
                    
                    <div class="excerpt">
                        <?php echo wp_kses_post(get_the_excerpt($post_id)); ?>
                    </div>
                    
                    <p>
                        <a href="<?php echo esc_url($permalink); ?>" class="button">
                            <?php _e('Leggi Articolo Completo', 'fp-newspaper'); ?>
                        </a>
                    </p>
                </div>
                
                <div class="footer">
                    <p><?php echo esc_html(get_bloginfo('name')); ?> | <a href="<?php echo esc_url(home_url()); ?>"><?php _e('Visita il sito', 'fp-newspaper'); ?></a></p>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Template email nuovo commento
     */
    private function get_comment_email_template($comment, $post) {
        $comment_author = $comment->comment_author;
        $comment_content = $comment->comment_content;
        $post_title = get_the_title($post->ID);
        $post_permalink = get_permalink($post->ID);
        
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #2271b1; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 8px 8px; }
                .comment-box { background: white; padding: 20px; border-left: 4px solid #2271b1; margin: 20px 0; border-radius: 4px; }
                .comment-author { font-weight: bold; color: #1d2327; margin-bottom: 10px; }
                .comment-content { font-style: italic; color: #3c434a; }
                .button { display: inline-block; padding: 12px 24px; background: #2271b1; color: white; text-decoration: none; border-radius: 4px; margin: 20px 0; }
                .footer { text-align: center; margin-top: 30px; font-size: 12px; color: #646970; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1><?php echo esc_html(get_bloginfo('name')); ?></h1>
                    <p style="margin: 0;">💬 <?php _e('Nuovo Commento', 'fp-newspaper'); ?></p>
                </div>
                
                <div class="content">
                    <p>
                        <?php printf(
                            __('Il tuo articolo "%s" ha ricevuto un nuovo commento.', 'fp-newspaper'),
                            '<strong>' . esc_html($post_title) . '</strong>'
                        ); ?>
                    </p>
                    
                    <div class="comment-box">
                        <div class="comment-author">
                            <?php echo esc_html($comment_author); ?> <?php _e('ha scritto:', 'fp-newspaper'); ?>
                        </div>
                        <div class="comment-content">
                            <?php echo wp_kses_post($comment_content); ?>
                        </div>
                    </div>
                    
                    <p>
                        <a href="<?php echo esc_url($post_permalink); ?>#comment-<?php echo $comment->comment_ID; ?>" class="button">
                            <?php _e('Visualizza Commento', 'fp-newspaper'); ?>
                        </a>
                    </p>
                </div>
                
                <div class="footer">
                    <p><?php echo esc_html(get_bloginfo('name')); ?> | <a href="<?php echo esc_url(home_url()); ?>"><?php _e('Visita il sito', 'fp-newspaper'); ?></a></p>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Aggiunge pagina impostazioni
     */
    public function add_settings_page() {
        add_submenu_page(
            'fp-newspaper',
            __('Notifiche Email', 'fp-newspaper'),
            __('Notifiche Email', 'fp-newspaper'),
            'manage_options',
            'fp-newspaper-notifications',
            [$this, 'render_settings_page']
        );
    }
    
    /**
     * Registra impostazioni
     */
    public function register_settings() {
        register_setting('fp_newspaper_notifications', 'fp_newspaper_enable_new_article_emails', [
            'type' => 'boolean',
            'default' => false,
            'sanitize_callback' => 'rest_sanitize_boolean',
        ]);
        
        register_setting('fp_newspaper_notifications', 'fp_newspaper_enable_comment_emails', [
            'type' => 'boolean',
            'default' => false,
            'sanitize_callback' => 'rest_sanitize_boolean',
        ]);
        
        register_setting('fp_newspaper_notifications', 'fp_newspaper_email_recipients', [
            'type' => 'string',
            'default' => '',
            'sanitize_callback' => [$this, 'sanitize_email_list'],
        ]);
    }
    
    /**
     * Sanitizza lista email
     */
    public function sanitize_email_list($value) {
        if (empty($value)) {
            return '';
        }
        
        $emails = array_map('trim', explode(',', $value));
        $valid_emails = [];
        
        foreach ($emails as $email) {
            if (is_email($email)) {
                $valid_emails[] = $email;
            }
        }
        
        return implode(', ', $valid_emails);
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
            
            <form method="post" action="options.php">
                <?php settings_fields('fp_newspaper_notifications'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="fp_newspaper_enable_new_article_emails">
                                <?php _e('Notifiche Nuovi Articoli', 'fp-newspaper'); ?>
                            </label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" 
                                       id="fp_newspaper_enable_new_article_emails" 
                                       name="fp_newspaper_enable_new_article_emails" 
                                       value="1" 
                                       <?php checked(get_option('fp_newspaper_enable_new_article_emails'), 1); ?>>
                                <?php _e('Invia email quando un nuovo articolo viene pubblicato', 'fp-newspaper'); ?>
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="fp_newspaper_enable_comment_emails">
                                <?php _e('Notifiche Commenti', 'fp-newspaper'); ?>
                            </label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" 
                                       id="fp_newspaper_enable_comment_emails" 
                                       name="fp_newspaper_enable_comment_emails" 
                                       value="1" 
                                       <?php checked(get_option('fp_newspaper_enable_comment_emails'), 1); ?>>
                                <?php _e('Invia email all\'autore quando l\'articolo riceve un commento', 'fp-newspaper'); ?>
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="fp_newspaper_email_recipients">
                                <?php _e('Destinatari Email', 'fp-newspaper'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="fp_newspaper_email_recipients" 
                                   name="fp_newspaper_email_recipients" 
                                   value="<?php echo esc_attr(get_option('fp_newspaper_email_recipients')); ?>" 
                                   class="regular-text"
                                   placeholder="email1@esempio.com, email2@esempio.com">
                            <p class="description">
                                <?php _e('Indirizzi email separati da virgola che riceveranno le notifiche dei nuovi articoli.', 'fp-newspaper'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}

