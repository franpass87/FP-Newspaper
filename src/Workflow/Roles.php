<?php
/**
 * Gestione Ruoli Editoriali Custom
 *
 * @package FPNewspaper\Workflow
 */

namespace FPNewspaper\Workflow;

use FPNewspaper\Logger;

defined('ABSPATH') || exit;

/**
 * Crea e gestisce ruoli personalizzati per workflow editoriale
 */
class Roles {
    
    /**
     * Slug ruoli
     */
    const ROLE_REDATTORE = 'fp_redattore';
    const ROLE_EDITOR = 'fp_editor';
    const ROLE_CAPOREDATTORE = 'fp_caporedattore';
    
    /**
     * Registra ruoli custom
     */
    public static function register_roles() {
        self::add_redattore_role();
        self::add_editor_role();
        self::add_caporedattore_role();
        
        Logger::info('Editorial roles registered');
    }
    
    /**
     * Rimuove ruoli custom (per uninstall)
     */
    public static function remove_roles() {
        remove_role(self::ROLE_REDATTORE);
        remove_role(self::ROLE_EDITOR);
        remove_role(self::ROLE_CAPOREDATTORE);
        
        Logger::info('Editorial roles removed');
    }
    
    /**
     * Ruolo: Redattore
     * 
     * Può: Scrivere articoli, salvare bozze
     * NON può: Pubblicare, approvare
     */
    private static function add_redattore_role() {
        add_role(self::ROLE_REDATTORE, __('Redattore', 'fp-newspaper'), [
            // Lettura
            'read' => true,
            
            // Post
            'edit_posts' => true,
            'delete_posts' => false,
            'publish_posts' => false,  // NON può pubblicare
            
            // Propri post
            'edit_published_posts' => false,
            'delete_published_posts' => false,
            
            // Media
            'upload_files' => true,
            
            // Categorie/Tag
            'manage_categories' => false,
            
            // Commenti
            'moderate_comments' => false,
        ]);
    }
    
    /**
     * Ruolo: Editor
     * 
     * Può: Tutto del redattore + approvare/rifiutare + modificare articoli altrui
     * NON può: Pubblicare (solo approva)
     */
    private static function add_editor_role() {
        add_role(self::ROLE_EDITOR, __('Editor', 'fp-newspaper'), [
            // Lettura
            'read' => true,
            
            // Post propri
            'edit_posts' => true,
            'delete_posts' => true,
            'publish_posts' => false,  // NON può pubblicare direttamente
            
            // Post altri
            'edit_others_posts' => true,
            'edit_published_posts' => true,
            'delete_others_posts' => false,
            'delete_published_posts' => false,
            
            // Privati
            'read_private_posts' => true,
            'edit_private_posts' => true,
            
            // Media
            'upload_files' => true,
            
            // Categorie/Tag
            'manage_categories' => true,
            
            // Commenti
            'moderate_comments' => true,
            'edit_comments' => true,
            
            // Custom capabilities per workflow
            'approve_posts' => true,  // Custom capability
            'reject_posts' => true,   // Custom capability
        ]);
    }
    
    /**
     * Ruolo: Caporedattore
     * 
     * Può: Tutto + pubblicare + gestire team
     */
    private static function add_caporedattore_role() {
        add_role(self::ROLE_CAPOREDATTORE, __('Caporedattore', 'fp-newspaper'), [
            // Lettura
            'read' => true,
            
            // Post propri
            'edit_posts' => true,
            'delete_posts' => true,
            'publish_posts' => true,  // Può pubblicare
            
            // Post altri
            'edit_others_posts' => true,
            'edit_published_posts' => true,
            'delete_others_posts' => true,
            'delete_published_posts' => true,
            
            // Privati
            'read_private_posts' => true,
            'edit_private_posts' => true,
            'delete_private_posts' => true,
            
            // Media
            'upload_files' => true,
            
            // Categorie/Tag
            'manage_categories' => true,
            
            // Commenti
            'moderate_comments' => true,
            'edit_comments' => true,
            'delete_comments' => true,
            
            // Custom capabilities
            'approve_posts' => true,
            'reject_posts' => true,
            'assign_posts' => true,    // Può assegnare articoli
            'manage_workflow' => true, // Gestisce workflow
        ]);
    }
    
    /**
     * Aggiunge capabilities custom agli admin
     */
    public static function add_admin_capabilities() {
        $role = get_role('administrator');
        
        if ($role) {
            $role->add_cap('approve_posts');
            $role->add_cap('reject_posts');
            $role->add_cap('assign_posts');
            $role->add_cap('manage_workflow');
            
            Logger::debug('Admin capabilities added');
        }
    }
    
    /**
     * Verifica se utente può approvare
     *
     * @param int $user_id
     * @return bool
     */
    public static function can_approve($user_id = null) {
        $user_id = $user_id ?? get_current_user_id();
        
        return user_can($user_id, 'approve_posts') || 
               user_can($user_id, 'publish_posts') ||
               user_can($user_id, 'manage_options');
    }
    
    /**
     * Verifica se utente può pubblicare
     *
     * @param int $user_id
     * @return bool
     */
    public static function can_publish($user_id = null) {
        $user_id = $user_id ?? get_current_user_id();
        
        return user_can($user_id, 'publish_posts') || 
               user_can($user_id, 'manage_options');
    }
    
    /**
     * Ottiene lista editor disponibili per assegnazione
     *
     * @return array
     */
    public static function get_available_editors() {
        $users = get_users([
            'role__in' => [
                self::ROLE_EDITOR,
                self::ROLE_CAPOREDATTORE,
                'administrator',
            ],
            'orderby' => 'display_name',
            'order' => 'ASC',
        ]);
        
        return $users;
    }
    
    /**
     * Ottiene statistiche per ruolo
     *
     * @return array
     */
    public static function get_role_stats() {
        return [
            'redattori' => count(get_users(['role' => self::ROLE_REDATTORE])),
            'editor' => count(get_users(['role' => self::ROLE_EDITOR])),
            'caporedattori' => count(get_users(['role' => self::ROLE_CAPOREDATTORE])),
        ];
    }
}


