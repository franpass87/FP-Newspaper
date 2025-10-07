<?php
/**
 * Timeline Renderer
 *
 * Gestisce il rendering delle timeline degli eventi.
 *
 * @package CV_Dossier_Context
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Classe per rendering timeline.
 */
class CV_Timeline {
    
    /**
     * Renderizza la timeline degli eventi di un dossier.
     *
     * @param int $dossier_id ID del dossier.
     * @return string HTML della timeline.
     */
    public function render( $dossier_id ) {
        $events = get_posts([
            'post_type'   => 'cv_dossier_event',
            'numberposts' => -1,
            'post_parent' => $dossier_id,
            'meta_key'    => '_cv_date',
            'orderby'     => 'meta_value',
            'order'       => 'ASC',
        ]);
        
        if ( ! $events ) {
            return '<p>' . esc_html__( 'Nessun evento in timeline.', 'cv-dossier' ) . '</p>';
        }
        
        $out = '<div class="cv-timeline" data-ga4="timeline">';
        foreach ( $events as $e ) {
            $date    = esc_html( get_post_meta( $e->ID, '_cv_date', true ) );
            $place   = esc_html( get_post_meta( $e->ID, '_cv_place', true ) );
            $content = apply_filters( 'cv_dossier_timeline_item_content', $e->post_content, $e );
            $content = is_string( $content ) ? trim( $content ) : '';
            $content = $content !== '' ? wp_kses_post( wpautop( $content ) ) : '';
            
            $out .= '<div class="cv-tl-item">';
            $out .= '<div class="cv-tl-date">' . $date . '</div>';
            $out .= '<div class="cv-tl-content"><h4>' . esc_html( get_the_title( $e ) ) . '</h4>';
            
            if ( $place ) {
                $out .= '<div class="cv-tl-place">' . $place . '</div>';
            }
            
            if ( $content ) {
                $out .= $content;
            }
            
            $out .= '</div></div>';
        }
        $out .= '</div>';
        
        return $out;
    }
}