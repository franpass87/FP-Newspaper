<?php
/**
 * Marker Helper Class
 *
 * Gestisce la preparazione e il rendering dei marker per le mappe.
 *
 * @package CV_Dossier_Context
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Classe helper per gestione marker.
 */
class CV_Marker_Helper {
    
    /**
     * Prepara i dati dell'immagine di un marker.
     *
     * @param array $marker Dati del marker.
     * @return array|null Dati immagine preparati o null.
     */
    public static function prepare_marker_image( $marker ) {
        $image_id  = isset( $marker['image_id'] ) ? intval( $marker['image_id'] ) : 0;
        $image_url = isset( $marker['image_url'] ) ? CV_Validator::validate_image_url( $marker['image_url'] ) : '';
        $image_alt = isset( $marker['image_alt'] ) ? sanitize_text_field( $marker['image_alt'] ) : '';
        
        if ( $image_id > 0 && ! wp_attachment_is_image( $image_id ) ) {
            $image_id = 0;
        }
        
        if ( $image_id > 0 ) {
            $full = wp_get_attachment_image_src( $image_id, 'full' );
            $thumb = wp_get_attachment_image_src( $image_id, 'large' );
            if ( ! $thumb ) {
                $thumb = wp_get_attachment_image_src( $image_id, 'medium_large' );
            }
            if ( ! $thumb ) {
                $thumb = wp_get_attachment_image_src( $image_id, 'medium' );
            }
            $alt = $image_alt ?: get_post_meta( $image_id, '_wp_attachment_image_alt', true );
            $alt = CV_Sanitizer::normalize_image_alt( $alt );
            
            if ( $full ) {
                return [
                    'full'  => esc_url( $full[0] ),
                    'thumb' => esc_url( $thumb ? $thumb[0] : $full[0] ),
                    'alt'   => $alt,
                ];
            }
        }
        
        if ( $image_url ) {
            $alt = CV_Sanitizer::normalize_image_alt( $image_alt );
            return [
                'full'  => esc_url( $image_url ),
                'thumb' => esc_url( $image_url ),
                'alt'   => $alt,
            ];
        }
        
        return null;
    }
    
    /**
     * Ottiene i marker di un dossier dai suoi eventi.
     *
     * @param int   $dossier_id ID del dossier.
     * @param array $cache      Cache per evitare query duplicate.
     * @return array Array di marker.
     */
    public static function get_dossier_markers( $dossier_id, &$cache = [] ) {
        $dossier_id = intval( $dossier_id );
        if ( $dossier_id <= 0 ) {
            return [];
        }
        
        if ( isset( $cache[ $dossier_id ] ) ) {
            return $cache[ $dossier_id ];
        }
        
        $events = get_posts([
            'post_type'   => 'cv_dossier_event',
            'numberposts' => -1,
            'post_parent' => $dossier_id,
        ]);
        
        $markers = [];
        
        foreach ( $events as $event ) {
            $lat_raw = get_post_meta( $event->ID, '_cv_lat', true );
            $lng_raw = get_post_meta( $event->ID, '_cv_lng', true );
            
            if ( '' === $lat_raw || null === $lat_raw || '' === $lng_raw || null === $lng_raw ) {
                continue;
            }
            
            if ( ! is_numeric( $lat_raw ) || ! is_numeric( $lng_raw ) ) {
                continue;
            }
            
            $raw_description = wp_strip_all_tags( $event->post_content );
            $raw_description = preg_replace( '/\s+/u', ' ', $raw_description );
            if ( null === $raw_description ) {
                $raw_description = '';
            }
            $raw_description = trim( $raw_description );
            $description     = '';
            
            if ( '' !== $raw_description ) {
                $description = sanitize_textarea_field( wp_trim_words( $raw_description, 50, '…' ) );
            }
            
            $markers[] = [
                'title'       => sanitize_text_field( $event->post_title ),
                'lat'         => floatval( $lat_raw ),
                'lng'         => floatval( $lng_raw ),
                'place'       => sanitize_text_field( get_post_meta( $event->ID, '_cv_place', true ) ),
                'date'        => sanitize_text_field( get_post_meta( $event->ID, '_cv_date', true ) ),
                'description' => $description,
            ];
        }
        
        wp_reset_postdata();
        
        $cache[ $dossier_id ] = $markers;
        
        return $markers;
    }
    
    /**
     * Verifica se un dossier ha marker disponibili.
     *
     * @param int   $dossier_id ID del dossier.
     * @param array $cache      Cache reference.
     * @return bool True se ha marker.
     */
    public static function dossier_has_markers( $dossier_id, &$cache = [] ) {
        return ! empty( self::get_dossier_markers( $dossier_id, $cache ) );
    }
}