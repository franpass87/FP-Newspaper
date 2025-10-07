<?php
/**
 * Validator Helper Class
 *
 * Gestisce le validazioni dei dati del plugin.
 *
 * @package CV_Dossier_Context
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Classe per validazioni centralizzate.
 */
class CV_Validator {
    
    /**
     * Valida una data in formato YYYY-MM-DD.
     *
     * @param string $date Data da validare.
     * @return string Data valida o stringa vuota.
     */
    public static function validate_date( $date ) {
        if ( ! is_string( $date ) ) {
            return '';
        }
        
        $date = preg_replace( '/[^0-9\-]/', '', $date );
        
        if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
            return '';
        }
        
        return $date;
    }
    
    /**
     * Valida coordinate geografiche (latitudine).
     *
     * @param mixed $lat Latitudine.
     * @return string Latitudine valida o stringa vuota.
     */
    public static function validate_latitude( $lat ) {
        if ( $lat === '' || $lat === null ) {
            return '';
        }
        
        $lat = self::normalize_coordinate( $lat );
        
        if ( ! is_numeric( $lat ) ) {
            return '';
        }
        
        $lat_float = floatval( $lat );
        
        if ( $lat_float < -90 || $lat_float > 90 ) {
            return '';
        }
        
        return (string) $lat_float;
    }
    
    /**
     * Valida coordinate geografiche (longitudine).
     *
     * @param mixed $lng Longitudine.
     * @return string Longitudine valida o stringa vuota.
     */
    public static function validate_longitude( $lng ) {
        if ( $lng === '' || $lng === null ) {
            return '';
        }
        
        $lng = self::normalize_coordinate( $lng );
        
        if ( ! is_numeric( $lng ) ) {
            return '';
        }
        
        $lng_float = floatval( $lng );
        
        if ( $lng_float < -180 || $lng_float > 180 ) {
            return '';
        }
        
        return (string) $lng_float;
    }
    
    /**
     * Normalizza una coordinata sostituendo virgole con punti.
     *
     * @param mixed $coordinate Coordinata da normalizzare.
     * @return string Coordinata normalizzata.
     */
    private static function normalize_coordinate( $coordinate ) {
        if ( ! is_string( $coordinate ) && ! is_numeric( $coordinate ) ) {
            return '';
        }
        
        $coordinate = (string) $coordinate;
        return str_replace( ',', '.', $coordinate );
    }
    
    /**
     * Valida un valore di stato (open/closed).
     *
     * @param string $status Stato da validare.
     * @return string Stato valido (open o closed).
     */
    public static function validate_status( $status ) {
        if ( ! is_string( $status ) ) {
            return 'open';
        }
        
        $status = sanitize_text_field( $status );
        
        return in_array( $status, [ 'open', 'closed' ], true ) ? $status : 'open';
    }
    
    /**
     * Valida e clamp un punteggio tra 0 e 100.
     *
     * @param mixed $score Punteggio da validare.
     * @return int Punteggio validato tra 0 e 100.
     */
    public static function validate_score( $score ) {
        $score = intval( $score );
        return max( 0, min( 100, $score ) );
    }
    
    /**
     * Valida un URL di immagine.
     *
     * @param string $url URL da validare.
     * @return string URL valido o stringa vuota.
     */
    public static function validate_image_url( $url ) {
        if ( ! is_string( $url ) || $url === '' ) {
            return '';
        }
        
        $url = esc_url_raw( $url );
        if ( ! $url ) {
            return '';
        }
        
        $path = wp_parse_url( $url, PHP_URL_PATH );
        if ( ! $path ) {
            return '';
        }
        
        $filename = wp_basename( $path );
        if ( ! $filename ) {
            return '';
        }
        
        $filetype = wp_check_filetype( $filename );
        if ( empty( $filetype['type'] ) || strpos( $filetype['type'], 'image/' ) !== 0 ) {
            return '';
        }
        
        return $url;
    }
    
    /**
     * Valida un ID attachment come immagine.
     *
     * @param mixed $attachment_id ID attachment.
     * @return int ID valido o 0.
     */
    public static function validate_image_attachment( $attachment_id ) {
        $attachment_id = intval( $attachment_id );
        
        if ( $attachment_id <= 0 ) {
            return 0;
        }
        
        if ( ! wp_attachment_is_image( $attachment_id ) ) {
            return 0;
        }
        
        return $attachment_id;
    }
}