<?php
/**
 * Media Credits Manager
 *
 * @package FPNewspaper\Media
 */

namespace FPNewspaper\Media;

defined('ABSPATH') || exit;

/**
 * Gestione crediti foto/video per licensing e copyright
 */
class CreditsManager {
    
    /**
     * Meta keys
     */
    const META_PHOTOGRAPHER = '_fp_media_photographer';
    const META_AGENCY = '_fp_media_agency';
    const META_LICENSE = '_fp_media_license';
    const META_COPYRIGHT = '_fp_media_copyright';
    
    /**
     * Costruttore
     */
    public function __construct() {
        add_filter('attachment_fields_to_edit', [$this, 'add_credit_fields'], 10, 2);
        add_filter('attachment_fields_to_save', [$this, 'save_credit_fields'], 10, 2);
        add_filter('wp_get_attachment_image_attributes', [$this, 'add_credit_to_caption'], 10, 2);
    }
    
    /**
     * Aggiunge campi crediti in media library
     */
    public function add_credit_fields($form_fields, $post) {
        $photographer = get_post_meta($post->ID, self::META_PHOTOGRAPHER, true);
        $agency = get_post_meta($post->ID, self::META_AGENCY, true);
        $license = get_post_meta($post->ID, self::META_LICENSE, true);
        $copyright = get_post_meta($post->ID, self::META_COPYRIGHT, true);
        
        $form_fields['fp_photographer'] = [
            'label' => __('Fotografo/Autore', 'fp-newspaper'),
            'input' => 'text',
            'value' => $photographer,
        ];
        
        $form_fields['fp_agency'] = [
            'label' => __('Agenzia', 'fp-newspaper'),
            'input' => 'text',
            'value' => $agency,
            'helps' => __('Es: Getty Images, Reuters, AFP', 'fp-newspaper'),
        ];
        
        $form_fields['fp_license'] = [
            'label' => __('Licenza', 'fp-newspaper'),
            'input' => 'html',
            'html' => '<select name="attachments[' . $post->ID . '][fp_license]">
                <option value="">' . __('Non specificata', 'fp-newspaper') . '</option>
                <option value="all_rights_reserved"' . selected($license, 'all_rights_reserved', false) . '>' . __('Tutti i diritti riservati', 'fp-newspaper') . '</option>
                <option value="cc_by"' . selected($license, 'cc_by', false) . '>CC BY</option>
                <option value="cc_by_sa"' . selected($license, 'cc_by_sa', false) . '>CC BY-SA</option>
                <option value="cc_by_nd"' . selected($license, 'cc_by_nd', false) . '>CC BY-ND</option>
                <option value="public_domain"' . selected($license, 'public_domain', false) . '>' . __('Pubblico Dominio', 'fp-newspaper') . '</option>
            </select>',
        ];
        
        $form_fields['fp_copyright'] = [
            'label' => __('Copyright', 'fp-newspaper'),
            'input' => 'text',
            'value' => $copyright,
            'helps' => __('Es: © 2025 Nome Fotografo', 'fp-newspaper'),
        ];
        
        return $form_fields;
    }
    
    /**
     * Salva crediti
     */
    public function save_credit_fields($post, $attachment) {
        if (isset($attachment['fp_photographer'])) {
            update_post_meta($post['ID'], self::META_PHOTOGRAPHER, sanitize_text_field($attachment['fp_photographer']));
        }
        
        if (isset($attachment['fp_agency'])) {
            update_post_meta($post['ID'], self::META_AGENCY, sanitize_text_field($attachment['fp_agency']));
        }
        
        if (isset($attachment['fp_license'])) {
            update_post_meta($post['ID'], self::META_LICENSE, sanitize_text_field($attachment['fp_license']));
        }
        
        if (isset($attachment['fp_copyright'])) {
            update_post_meta($post['ID'], self::META_COPYRIGHT, sanitize_text_field($attachment['fp_copyright']));
        }
        
        return $post;
    }
    
    /**
     * Aggiunge crediti alla caption
     */
    public function add_credit_to_caption($attr, $attachment) {
        $photographer = get_post_meta($attachment->ID, self::META_PHOTOGRAPHER, true);
        $agency = get_post_meta($attachment->ID, self::META_AGENCY, true);
        
        if ($photographer || $agency) {
            $credit = [];
            if ($photographer) {
                $credit[] = $photographer;
            }
            if ($agency) {
                $credit[] = $agency;
            }
            
            $credit_text = __('Foto:', 'fp-newspaper') . ' ' . implode(' / ', $credit);
            
            if (empty($attr['title'])) {
                $attr['title'] = $credit_text;
            } else {
                $attr['title'] .= ' | ' . $credit_text;
            }
        }
        
        return $attr;
    }
    
    /**
     * Ottiene crediti media
     */
    public function get_media_credits($attachment_id) {
        return [
            'photographer' => get_post_meta($attachment_id, self::META_PHOTOGRAPHER, true),
            'agency' => get_post_meta($attachment_id, self::META_AGENCY, true),
            'license' => get_post_meta($attachment_id, self::META_LICENSE, true),
            'copyright' => get_post_meta($attachment_id, self::META_COPYRIGHT, true),
        ];
    }
}


