<?php
/**
 * Gestione bulk actions personalizzate
 *
 * @package FPNewspaper
 */

namespace FPNewspaper\Admin;

defined('ABSPATH') || exit;

/**
 * Aggiunge bulk actions personalizzate
 */
class BulkActions {
    
    /**
     * Costruttore
     */
    public function __construct() {
        // Aggiungi bulk actions
        add_filter('bulk_actions-edit-fp_article', [$this, 'add_bulk_actions']);
        add_filter('handle_bulk_actions-edit-fp_article', [$this, 'handle_bulk_actions'], 10, 3);
        add_action('admin_notices', [$this, 'bulk_action_notices']);
    }
    
    /**
     * Aggiunge azioni bulk personalizzate
     *
     * @param array $actions
     * @return array
     */
    public function add_bulk_actions($actions) {
        $actions['fp_set_featured'] = __('Imposta come in evidenza', 'fp-newspaper');
        $actions['fp_remove_featured'] = __('Rimuovi da in evidenza', 'fp-newspaper');
        $actions['fp_set_breaking'] = __('Imposta come breaking news', 'fp-newspaper');
        $actions['fp_remove_breaking'] = __('Rimuovi da breaking news', 'fp-newspaper');
        
        return $actions;
    }
    
    /**
     * Gestisce esecuzione bulk actions
     *
     * @param string $redirect_to
     * @param string $action
     * @param array $post_ids
     * @return string
     */
    public function handle_bulk_actions($redirect_to, $action, $post_ids) {
        // Verifica nonce (già verificato da WordPress, ma double check)
        if (!isset($_REQUEST['_wpnonce'])) {
            return $redirect_to;
        }
        
        $count = 0;
        
        switch ($action) {
            case 'fp_set_featured':
                foreach ($post_ids as $post_id) {
                    if (current_user_can('edit_post', $post_id)) {
                        update_post_meta($post_id, '_fp_featured', '1');
                        $count++;
                    }
                }
                
                // Invalida cache
                delete_transient('fp_featured_articles_cache');
                
                $redirect_to = add_query_arg('fp_featured_set', $count, $redirect_to);
                break;
                
            case 'fp_remove_featured':
                foreach ($post_ids as $post_id) {
                    if (current_user_can('edit_post', $post_id)) {
                        delete_post_meta($post_id, '_fp_featured');
                        $count++;
                    }
                }
                
                // Invalida cache
                delete_transient('fp_featured_articles_cache');
                
                $redirect_to = add_query_arg('fp_featured_removed', $count, $redirect_to);
                break;
                
            case 'fp_set_breaking':
                foreach ($post_ids as $post_id) {
                    if (current_user_can('edit_post', $post_id)) {
                        update_post_meta($post_id, '_fp_breaking_news', '1');
                        $count++;
                    }
                }
                
                $redirect_to = add_query_arg('fp_breaking_set', $count, $redirect_to);
                break;
                
            case 'fp_remove_breaking':
                foreach ($post_ids as $post_id) {
                    if (current_user_can('edit_post', $post_id)) {
                        delete_post_meta($post_id, '_fp_breaking_news');
                        $count++;
                    }
                }
                
                $redirect_to = add_query_arg('fp_breaking_removed', $count, $redirect_to);
                break;
        }
        
        return $redirect_to;
    }
    
    /**
     * Mostra notice dopo bulk actions
     */
    public function bulk_action_notices() {
        if (!isset($_REQUEST['post_type']) || 'fp_article' !== $_REQUEST['post_type']) {
            return;
        }
        
        // Featured set
        if (isset($_REQUEST['fp_featured_set'])) {
            $count = absint($_REQUEST['fp_featured_set']);
            printf(
                '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
                sprintf(
                    /* translators: %d: numero articoli */
                    esc_html(_n(
                        '%d articolo impostato come in evidenza.',
                        '%d articoli impostati come in evidenza.',
                        $count,
                        'fp-newspaper'
                    )),
                    $count
                )
            );
        }
        
        // Featured removed
        if (isset($_REQUEST['fp_featured_removed'])) {
            $count = absint($_REQUEST['fp_featured_removed']);
            printf(
                '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
                sprintf(
                    /* translators: %d: numero articoli */
                    esc_html(_n(
                        '%d articolo rimosso da in evidenza.',
                        '%d articoli rimossi da in evidenza.',
                        $count,
                        'fp-newspaper'
                    )),
                    $count
                )
            );
        }
        
        // Breaking set
        if (isset($_REQUEST['fp_breaking_set'])) {
            $count = absint($_REQUEST['fp_breaking_set']);
            printf(
                '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
                sprintf(
                    /* translators: %d: numero articoli */
                    esc_html(_n(
                        '%d articolo impostato come breaking news.',
                        '%d articoli impostati come breaking news.',
                        $count,
                        'fp-newspaper'
                    )),
                    $count
                )
            );
        }
        
        // Breaking removed
        if (isset($_REQUEST['fp_breaking_removed'])) {
            $count = absint($_REQUEST['fp_breaking_removed']);
            printf(
                '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
                sprintf(
                    /* translators: %d: numero articoli */
                    esc_html(_n(
                        '%d articolo rimosso da breaking news.',
                        '%d articoli rimossi da breaking news.',
                        $count,
                        'fp-newspaper'
                    )),
                    $count
                )
            );
        }
    }
}

