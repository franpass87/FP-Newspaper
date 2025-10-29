<?php
/**
 * Pagina impostazioni plugin
 *
 * @package FPNewspaper
 */

namespace FPNewspaper\Admin;

defined('ABSPATH') || exit;

/**
 * Gestisce la pagina impostazioni
 */
class Settings {
    
    /**
     * Costruttore
     */
    public function __construct() {
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
    }
    
    /**
     * Aggiunge pagina impostazioni
     */
    public function add_settings_page() {
        add_submenu_page(
            'fp-newspaper',
            __('Impostazioni FP Newspaper', 'fp-newspaper'),
            __('Impostazioni', 'fp-newspaper'),
            'manage_options',
            'fp-newspaper-settings',
            [$this, 'render_settings_page']
        );
    }
    
    /**
     * Registra impostazioni
     */
    public function register_settings() {
        // Sezione generale
        add_settings_section(
            'fp_newspaper_general',
            __('Impostazioni Generali', 'fp-newspaper'),
            [$this, 'general_section_callback'],
            'fp-newspaper-settings'
        );
        
        // Articoli per pagina
        register_setting('fp_newspaper_settings', 'fp_newspaper_articles_per_page', [
            'type' => 'integer',
            'default' => 10,
            'sanitize_callback' => function($value) {
                $value = absint($value);
                return max(1, min($value, 100)); // Min 1, max 100
            },
        ]);
        
        add_settings_field(
            'fp_newspaper_articles_per_page',
            __('Articoli per pagina', 'fp-newspaper'),
            [$this, 'articles_per_page_callback'],
            'fp-newspaper-settings',
            'fp_newspaper_general'
        );
        
        // Abilita commenti
        register_setting('fp_newspaper_settings', 'fp_newspaper_enable_comments', [
            'type' => 'boolean',
            'default' => true,
            'sanitize_callback' => 'rest_sanitize_boolean',
        ]);
        
        add_settings_field(
            'fp_newspaper_enable_comments',
            __('Abilita commenti', 'fp-newspaper'),
            [$this, 'enable_comments_callback'],
            'fp-newspaper-settings',
            'fp_newspaper_general'
        );
        
        // Abilita condivisione
        register_setting('fp_newspaper_settings', 'fp_newspaper_enable_sharing', [
            'type' => 'boolean',
            'default' => true,
            'sanitize_callback' => 'rest_sanitize_boolean',
        ]);
        
        add_settings_field(
            'fp_newspaper_enable_sharing',
            __('Abilita condivisione social', 'fp-newspaper'),
            [$this, 'enable_sharing_callback'],
            'fp-newspaper-settings',
            'fp_newspaper_general'
        );
        
        // Sezione disinstallazione
        add_settings_section(
            'fp_newspaper_uninstall',
            __('Opzioni Disinstallazione', 'fp-newspaper'),
            [$this, 'uninstall_section_callback'],
            'fp-newspaper-settings'
        );
        
        // Cancella dati alla disinstallazione
        register_setting('fp_newspaper_settings', 'fp_newspaper_delete_data_on_uninstall', [
            'type' => 'boolean',
            'default' => false,
            'sanitize_callback' => 'rest_sanitize_boolean',
        ]);
        
        add_settings_field(
            'fp_newspaper_delete_data_on_uninstall',
            __('Cancella statistiche alla disinstallazione', 'fp-newspaper'),
            [$this, 'delete_data_callback'],
            'fp-newspaper-settings',
            'fp_newspaper_uninstall'
        );
        
        // Cancella articoli alla disinstallazione
        register_setting('fp_newspaper_settings', 'fp_newspaper_delete_posts_on_uninstall', [
            'type' => 'boolean',
            'default' => false,
            'sanitize_callback' => 'rest_sanitize_boolean',
        ]);
        
        add_settings_field(
            'fp_newspaper_delete_posts_on_uninstall',
            __('Cancella articoli alla disinstallazione', 'fp-newspaper'),
            [$this, 'delete_posts_callback'],
            'fp-newspaper-settings',
            'fp_newspaper_uninstall'
        );
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
            
            <?php settings_errors('fp_newspaper_settings'); ?>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('fp_newspaper_settings');
                do_settings_sections('fp-newspaper-settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Callback sezione generale
     */
    public function general_section_callback() {
        echo '<p>' . esc_html__('Configura le impostazioni generali del plugin.', 'fp-newspaper') . '</p>';
    }
    
    /**
     * Callback articoli per pagina
     */
    public function articles_per_page_callback() {
        $value = get_option('fp_newspaper_articles_per_page', 10);
        ?>
        <input type="number" 
               name="fp_newspaper_articles_per_page" 
               value="<?php echo esc_attr($value); ?>" 
               min="1" 
               max="100" 
               class="small-text">
        <p class="description">
            <?php esc_html_e('Numero di articoli da mostrare per pagina negli archivi.', 'fp-newspaper'); ?>
        </p>
        <?php
    }
    
    /**
     * Callback abilita commenti
     */
    public function enable_comments_callback() {
        $value = get_option('fp_newspaper_enable_comments', true);
        ?>
        <label>
            <input type="checkbox" 
                   name="fp_newspaper_enable_comments" 
                   value="1" 
                   <?php checked($value, true); ?>>
            <?php esc_html_e('Abilita i commenti sugli articoli', 'fp-newspaper'); ?>
        </label>
        <?php
    }
    
    /**
     * Callback abilita condivisione
     */
    public function enable_sharing_callback() {
        $value = get_option('fp_newspaper_enable_sharing', true);
        ?>
        <label>
            <input type="checkbox" 
                   name="fp_newspaper_enable_sharing" 
                   value="1" 
                   <?php checked($value, true); ?>>
            <?php esc_html_e('Mostra pulsanti di condivisione social', 'fp-newspaper'); ?>
        </label>
        <?php
    }
    
    /**
     * Callback sezione disinstallazione
     */
    public function uninstall_section_callback() {
        ?>
        <p><?php esc_html_e('Configura cosa fare quando il plugin viene disinstallato.', 'fp-newspaper'); ?></p>
        <div class="notice notice-warning inline">
            <p>
                <strong><?php esc_html_e('Attenzione:', 'fp-newspaper'); ?></strong>
                <?php esc_html_e('Queste azioni sono IRREVERSIBILI. I dati cancellati non possono essere recuperati.', 'fp-newspaper'); ?>
            </p>
        </div>
        <?php
    }
    
    /**
     * Callback cancella dati
     */
    public function delete_data_callback() {
        $value = get_option('fp_newspaper_delete_data_on_uninstall', false);
        ?>
        <label>
            <input type="checkbox" 
                   name="fp_newspaper_delete_data_on_uninstall" 
                   value="1" 
                   <?php checked($value, true); ?>>
            <?php esc_html_e('Cancella tabella statistiche quando disinstalli il plugin', 'fp-newspaper'); ?>
        </label>
        <p class="description" style="color: #d63638;">
            <?php esc_html_e('ATTENZIONE: Cancellerà permanentemente tutte le visualizzazioni e condivisioni registrate.', 'fp-newspaper'); ?>
        </p>
        <?php
    }
    
    /**
     * Callback cancella articoli
     */
    public function delete_posts_callback() {
        $value = get_option('fp_newspaper_delete_posts_on_uninstall', false);
        ?>
        <label>
            <input type="checkbox" 
                   name="fp_newspaper_delete_posts_on_uninstall" 
                   value="1" 
                   <?php checked($value, true); ?>>
            <?php esc_html_e('Cancella TUTTI gli articoli quando disinstalli il plugin', 'fp-newspaper'); ?>
        </label>
        <p class="description" style="color: #d63638;">
            <?php esc_html_e('ATTENZIONE: Cancellerà PERMANENTEMENTE tutti gli articoli, categorie e tag. IRREVERSIBILE!', 'fp-newspaper'); ?>
        </p>
        <?php
    }
}


