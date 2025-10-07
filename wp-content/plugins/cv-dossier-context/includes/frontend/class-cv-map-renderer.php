<?php
/**
 * Map Renderer
 *
 * Gestisce il rendering delle mappe Leaflet.
 *
 * @package CV_Dossier_Context
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Classe per rendering mappe.
 */
class CV_Map_Renderer {
    
    /**
     * Renderizza la mappa di un post con marker personalizzati.
     *
     * @param int   $post_id       ID del post.
     * @param array $marker_cache  Cache riferimento per marker.
     * @return string HTML della mappa o stringa vuota.
     */
    public function render_post_map( $post_id, &$marker_cache = [] ) {
        $markers = get_post_meta( $post_id, '_cv_map_markers', true );
        if ( ! is_array( $markers ) || empty( $markers ) ) {
            return '';
        }
        
        if ( ! $this->is_post_map_enabled( $post_id ) ) {
            return '';
        }
        
        $map_height_meta = get_post_meta( $post_id, '_cv_map_height', true );
        $map_height = CV_Sanitizer::sanitize_map_height( $map_height_meta );
        
        $prepared = [];
        foreach ( $markers as $marker ) {
            if ( ! isset( $marker['lat'], $marker['lng'] ) ) {
                continue;
            }
            
            $lat = floatval( $marker['lat'] );
            $lng = floatval( $marker['lng'] );
            
            $title = sanitize_text_field( $marker['title'] ?? '' );
            $description = wpautop( $marker['description'] ?? '' );
            $description = wp_kses_post( $description );
            $image_data = CV_Marker_Helper::prepare_marker_image( $marker );
            
            $prepared[] = [
                'title'       => $title,
                'lat'         => $lat,
                'lng'         => $lng,
                'description' => $description,
                'image'       => $image_data,
            ];
        }
        
        if ( empty( $prepared ) ) {
            return '';
        }
        
        return $this->render_leaflet_map( $prepared, $map_height, 'article' );
    }
    
    /**
     * Renderizza la mappa di un dossier con gli eventi.
     *
     * @param int    $dossier_id   ID del dossier.
     * @param int    $height       Altezza della mappa.
     * @param array  $marker_cache Cache riferimento.
     * @return string HTML della mappa.
     */
    public function render_dossier_map( $dossier_id, $height = 380, &$marker_cache = [] ) {
        $markers = CV_Marker_Helper::get_dossier_markers( $dossier_id, $marker_cache );
        
        if ( empty( $markers ) ) {
            return '';
        }
        
        $height = CV_Sanitizer::sanitize_map_height( $height, 380 );
        
        return $this->render_dossier_leaflet_map( $markers, $height );
    }
    
    /**
     * Renderizza una mappa Leaflet per post/articoli.
     *
     * @param array  $markers  Array di marker preparati.
     * @param int    $height   Altezza in px.
     * @param string $type     Tipo mappa (article/dossier).
     * @return string HTML mappa.
     */
    private function render_leaflet_map( $markers, $height, $type = 'article' ) {
        $map_id           = 'cv_post_map_' . wp_generate_password( 12, false );
        $instructions_id  = $map_id . '_instructions';
        $instructions_txt = __( 'Trascina la mappa per esplorare i punti di interesse. Lo zoom è disabilitato per mantenere la panoramica.', 'cv-dossier' );
        $prepared_json    = wp_json_encode( $markers );
        $json_failed      = ( false === $prepared_json );
        
        if ( $json_failed ) {
            $prepared_json = '';
        }
        
        ob_start();
        ?>
        <div class="cv-map cv-map--<?php echo esc_attr( $type ); ?>" id="<?php echo esc_attr( $map_id ); ?>" role="region" aria-label="<?php echo esc_attr__( 'Mappa degli approfondimenti', 'cv-dossier' ); ?>" aria-describedby="<?php echo esc_attr( $instructions_id ); ?>" style="height:<?php echo esc_attr( $height ); ?>px;min-height:<?php echo esc_attr( $height ); ?>px;"<?php if ( $json_failed ) : ?> data-map-error="<?php echo esc_attr( 'data-invalid' ); ?>"<?php endif; ?> tabindex="0"></div>
        <span id="<?php echo esc_attr( $instructions_id ); ?>" class="cv-sr-only"><?php echo esc_html( $instructions_txt ); ?></span>
        <?php if ( $json_failed ) :
            return ob_get_clean();
        endif; ?>
        <script>
        (function(){
            var markers = <?php echo $prepared_json; ?>;
            var el = document.getElementById('<?php echo esc_js( $map_id ); ?>');
            if (!el) { return; }
            var attempts = 0;
            var maxAttempts = 30;
            var instructionId = el.getAttribute('aria-describedby') || '';
            function init(){
                if (typeof L === 'undefined') {
                    attempts++;
                    if (attempts > maxAttempts) {
                        el.setAttribute('data-map-error', 'leaflet-unavailable');
                        return;
                    }
                    setTimeout(init, 200);
                    return;
                }
                var map = L.map(el, {
                    scrollWheelZoom: false,
                    tap: false,
                    touchZoom: false,
                    doubleClickZoom: false,
                    boxZoom: false,
                    keyboard: false,
                    zoomControl: false
                });
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a> contributors'
                }).addTo(map);
                var bounds = [];
                markers.forEach(function(marker){
                    var popupHtml = '<div class="cv-map-popup">';
                    if (marker.title) {
                        popupHtml += '<h4>' + marker.title + '</h4>';
                    }
                    if (marker.image && marker.image.full) {
                        var alt = marker.image.alt ? marker.image.alt : marker.title;
                        var thumb = marker.image.thumb ? marker.image.thumb : marker.image.full;
                        popupHtml += '<a class="cv-map-popup-image" href="' + marker.image.full + '" data-full="' + marker.image.full + '" data-alt="' + (alt || '') + '"><img src="' + thumb + '" alt="' + (alt || '') + '" loading="lazy" /></a>';
                    }
                    if (marker.description) {
                        popupHtml += '<div class="cv-map-popup__text">' + marker.description + '</div>';
                    }
                    popupHtml += '</div>';
                    L.marker([marker.lat, marker.lng]).addTo(map).bindPopup(popupHtml);
                    bounds.push([marker.lat, marker.lng]);
                });
                if (bounds.length === 1) {
                    map.setView(bounds[0], 13);
                } else if (bounds.length) {
                    map.fitBounds(bounds, { padding: [20, 20] });
                } else {
                    map.setView([42.416, 12.105], 11);
                }

                if (instructionId) {
                    var instructionEl = document.getElementById(instructionId);
                    if (instructionEl) {
                        instructionEl.setAttribute('aria-hidden', 'false');
                    }
                }

                window.addEventListener('resize', function(){
                    setTimeout(function(){ map.invalidateSize(); }, 200);
                });
            }
            init();
        })();
        </script>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Renderizza una mappa Leaflet per dossier.
     *
     * @param array $markers Array di marker.
     * @param int   $height  Altezza in px.
     * @return string HTML mappa.
     */
    private function render_dossier_leaflet_map( $markers, $height ) {
        $id_attr          = 'cvmap_' . wp_generate_password( 12, false );
        $instructions_id  = $id_attr . '_instructions';
        $instructions_txt = __( 'Trascina la mappa per esplorare i punti di interesse. Lo zoom è disabilitato per mantenere la panoramica.', 'cv-dossier' );
        $markers_json     = wp_json_encode( $markers );
        $json_failed      = ( false === $markers_json );
        
        if ( $json_failed ) {
            $markers_json = '';
        }
        
        ob_start(); ?>
        <div id="<?php echo esc_attr( $id_attr ); ?>" class="cv-map" role="region" aria-label="<?php echo esc_attr__( 'Mappa del dossier', 'cv-dossier' ); ?>" aria-describedby="<?php echo esc_attr( $instructions_id ); ?>"<?php if ( $json_failed ) : ?> data-map-error="<?php echo esc_attr( 'data-invalid' ); ?>"<?php endif; ?> style="height:<?php echo intval( $height ); ?>px;" tabindex="0"></div>
        <span id="<?php echo esc_attr( $instructions_id ); ?>" class="cv-sr-only"><?php echo esc_html( $instructions_txt ); ?></span>
        <?php if ( $json_failed ) :
            return ob_get_clean();
        endif; ?>
        <script>
        (function(){
            var el = document.getElementById('<?php echo esc_js( $id_attr ); ?>');
            if (!el) { return; }
            var markers = <?php echo $markers_json; ?>;
            var attempts = 0;
            var maxAttempts = 30;
            var instructionId = el.getAttribute('aria-describedby') || '';
            function init(){
                if (typeof L === 'undefined') {
                    attempts++;
                    if (attempts > maxAttempts) {
                        el.setAttribute('data-map-error', 'leaflet-unavailable');
                        return;
                    }
                    setTimeout(init, 200);
                    return;
                }
                var map = L.map(el, {
                    scrollWheelZoom: false,
                    tap: false,
                    touchZoom: false,
                    doubleClickZoom: false,
                    boxZoom: false,
                    keyboard: false,
                    zoomControl: false
                });
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a> contributors'
                }).addTo(map);
                var bounds = [];
                markers.forEach(function(marker){
                    var popup = '<div class="cv-map-popup">';
                    if (marker.title) {
                        popup += '<h4>' + marker.title + '</h4>';
                    }
                    if (marker.place || marker.date) {
                        popup += '<p class="cv-map-popup__meta">';
                        if (marker.place) {
                            popup += '<span class="cv-map-popup__place">' + marker.place + '</span>';
                        }
                        if (marker.date) {
                            popup += '<span class="cv-map-popup__date">' + marker.date + '</span>';
                        }
                        popup += '</p>';
                    }
                    if (marker.description) {
                        popup += '<p class="cv-map-popup__excerpt">' + marker.description + '</p>';
                    }
                    popup += '</div>';
                    L.marker([marker.lat, marker.lng]).addTo(map).bindPopup(popup);
                    bounds.push([marker.lat, marker.lng]);
                });
                if (bounds.length === 1) {
                    map.setView(bounds[0], 13);
                } else if (bounds.length) {
                    map.fitBounds(bounds, { padding: [20, 20] });
                } else {
                    map.setView([42.416, 12.105], 11);
                }

                if (instructionId) {
                    var instructionEl = document.getElementById(instructionId);
                    if (instructionEl) {
                        instructionEl.setAttribute('aria-hidden', 'false');
                    }
                }

                window.addEventListener('resize', function(){
                    setTimeout(function(){ map.invalidateSize(); }, 200);
                });
            }
            init();
        })();
        </script>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Verifica se la mappa del post è abilitata.
     *
     * @param int $post_id ID del post.
     * @return bool True se abilitata.
     */
    private function is_post_map_enabled( $post_id ) {
        $raw_value = get_post_meta( $post_id, '_cv_map_enabled', true );
        $has_meta  = metadata_exists( 'post', $post_id, '_cv_map_enabled' );
        
        if ( $has_meta ) {
            if ( is_array( $raw_value ) ) {
                $has_meta = false;
            } elseif ( is_string( $raw_value ) ) {
                $trimmed = trim( $raw_value );
                if ( '' === $trimmed ) {
                    $has_meta = false;
                }
            }
        }
        
        if ( ! $has_meta ) {
            return true;
        }
        
        return $this->interpret_boolean_meta( $raw_value );
    }
    
    /**
     * Interpreta valore meta come booleano.
     *
     * @param mixed $value Valore.
     * @return bool Booleano.
     */
    private function interpret_boolean_meta( $value ) {
        if ( is_array( $value ) ) {
            return false;
        }
        
        if ( is_bool( $value ) ) {
            return $value;
        }
        
        if ( is_int( $value ) ) {
            return ( 0 !== $value );
        }
        
        if ( is_string( $value ) ) {
            $normalized = strtolower( trim( $value ) );
            
            if ( '' === $normalized ) {
                return false;
            }
            
            if ( is_numeric( $normalized ) ) {
                return intval( $normalized ) !== 0;
            }
            
            if ( in_array( $normalized, [ 'true', 'yes', 'on', 'enabled' ], true ) ) {
                return true;
            }
            
            if ( in_array( $normalized, [ 'false', 'no', 'off', 'disabled' ], true ) ) {
                return false;
            }
        }
        
        if ( is_numeric( $value ) ) {
            return intval( $value ) !== 0;
        }
        
        return false;
    }
}