<?php
/**
 * Sanitizer Helper Class
 *
 * Gestisce la sanitizzazione centralizzata dei dati.
 *
 * @package CV_Dossier_Context
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Classe per sanitizzazione centralizzata.
 */
class CV_Sanitizer {
    
    /**
     * Sanitizza l'altezza della mappa con validazioni avanzate.
     *
     * @param mixed $value Valore da sanitizzare.
     * @param int   $default Valore di default.
     * @return int Altezza sanitizzata.
     */
    public static function sanitize_map_height( $value, $default = 360 ) {
        if ( is_array( $value ) ) {
            $value = '';
        }
        
        $default = intval( $default );
        if ( $default <= 0 ) {
            $default = 360;
        }
        
        $default = max( 240, min( 800, $default ) );
        
        if ( is_scalar( $value ) ) {
            $normalized = (string) $value;
            $normalized = preg_replace( '/[\x{00A0}\s]+/u', '', $normalized );
            if ( null === $normalized ) {
                $normalized = '';
            }
            
            $has_comma = strpos( $normalized, ',' ) !== false;
            $has_dot   = strpos( $normalized, '.' ) !== false;
            
            if ( $has_comma && $has_dot ) {
                if ( strrpos( $normalized, ',' ) > strrpos( $normalized, '.' ) ) {
                    $normalized = str_replace( '.', '', $normalized );
                    $normalized = str_replace( ',', '.', $normalized );
                } else {
                    $normalized = str_replace( ',', '', $normalized );
                }
            } elseif ( $has_comma ) {
                $parts = explode( ',', $normalized );
                if ( count( $parts ) === 2 && strlen( $parts[1] ) <= 2 ) {
                    $normalized = str_replace( ',', '.', $normalized );
                } else {
                    $normalized = str_replace( ',', '', $normalized );
                }
            } elseif ( $has_dot ) {
                $parts = explode( '.', $normalized );
                if ( count( $parts ) === 2 && strlen( $parts[1] ) <= 2 ) {
                    // Leave decimal notation untouched.
                } else {
                    $normalized = str_replace( '.', '', $normalized );
                }
            }
            
            $normalized = preg_replace( '/[^0-9\.-]/', '', $normalized );
            if ( null === $normalized ) {
                $normalized = '';
            }
            
            if ( '' !== $normalized ) {
                $value = (int) floor( floatval( $normalized ) );
            } else {
                $value = 0;
            }
        } else {
            $value = 0;
        }
        
        if ( $value <= 0 ) {
            $value = $default;
        }
        
        return max( 240, min( 800, $value ) );
    }
    
    /**
     * Sanitizza un array di marker per la mappa.
     *
     * @param array $raw_markers Array grezzo di marker.
     * @return array Array sanitizzato di marker.
     */
    public static function sanitize_map_markers( $raw_markers ) {
        if ( ! is_array( $raw_markers ) ) {
            return [];
        }
        
        $clean_markers = [];
        $allowed_tags = [
            'a' => [ 'href' => [], 'title' => [], 'target' => [], 'rel' => [] ],
            'br' => [],
            'em' => [],
            'strong' => [],
            'p' => [],
            'ul' => [],
            'ol' => [],
            'li' => [],
        ];
        
        foreach ( $raw_markers as $marker ) {
            if ( ! is_array( $marker ) ) {
                continue;
            }
            
            $lat = isset( $marker['lat'] ) ? str_replace( ',', '.', trim( $marker['lat'] ) ) : '';
            $lng = isset( $marker['lng'] ) ? str_replace( ',', '.', trim( $marker['lng'] ) ) : '';
            
            if ( $lat === '' || $lng === '' ) {
                continue;
            }
            
            if ( ! is_numeric( $lat ) || ! is_numeric( $lng ) ) {
                continue;
            }
            
            $lat = floatval( $lat );
            $lng = floatval( $lng );
            
            if ( $lat < -90 || $lat > 90 || $lng < -180 || $lng > 180 ) {
                continue;
            }
            
            $title = isset( $marker['title'] ) ? sanitize_text_field( $marker['title'] ) : '';
            $description = isset( $marker['description'] ) ? wp_kses( $marker['description'], $allowed_tags ) : '';
            $image_id = isset( $marker['image_id'] ) ? intval( $marker['image_id'] ) : 0;
            $image_alt = isset( $marker['image_alt'] ) ? sanitize_text_field( $marker['image_alt'] ) : '';
            $image_url = '';
            
            if ( $image_id > 0 && ! wp_attachment_is_image( $image_id ) ) {
                $image_id = 0;
                $image_url = '';
            }
            
            if ( $image_id > 0 ) {
                $image_url = wp_get_attachment_url( $image_id ) ?: '';
            } else {
                $image_url = isset( $marker['image_url'] ) ? CV_Validator::validate_image_url( $marker['image_url'] ) : '';
            }
            
            $clean_markers[] = [
                'title'       => $title,
                'lat'         => $lat,
                'lng'         => $lng,
                'description' => $description,
                'image_id'    => $image_id,
                'image_url'   => $image_url,
                'image_alt'   => $image_alt,
            ];
        }
        
        return $clean_markers;
    }
    
    /**
     * Normalizza l'attributo alt delle immagini.
     *
     * @param mixed $alt Testo alt.
     * @return string Alt normalizzato.
     */
    public static function normalize_image_alt( $alt ) {
        $alt = is_scalar( $alt ) ? (string) $alt : '';
        $alt = sanitize_text_field( $alt );
        $alt = preg_replace( '/[\r\n]+/', ' ', $alt );
        return str_replace( '"', '', $alt );
    }
}