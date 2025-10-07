<?php
/**
 * Meta Boxes Manager
 *
 * Gestisce le meta boxes per Dossier, Eventi e Post.
 *
 * @package CV_Dossier_Context
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Classe per gestione Meta Boxes.
 */
class CV_Meta_Boxes {
    
    /**
     * Nome nonce.
     *
     * @var string
     */
    private $nonce;
    
    /**
     * Costruttore.
     *
     * @param string $nonce Nome nonce.
     */
    public function __construct( $nonce ) {
        $this->nonce = $nonce;
    }
    
    /**
     * Inizializza il manager registrando gli hooks.
     */
    public function init() {
        add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ] );
        add_action( 'save_post', [ $this, 'save_meta' ] );
    }
    
    /**
     * Aggiunge le meta boxes.
     */
    public function add_meta_boxes() {
        add_meta_box( 'cv_dossier_meta', __( 'Dettagli Dossier', 'cv-dossier' ), [ $this, 'render_dossier_meta_box' ], 'cv_dossier', 'normal', 'default' );
        add_meta_box( 'cv_event_meta', __( 'Dettagli Evento (Timeline)', 'cv-dossier' ), [ $this, 'render_event_meta_box' ], 'cv_dossier_event', 'normal', 'default' );
        add_meta_box( 'cv_link_meta', __( 'Dossier collegato', 'cv-dossier' ), [ $this, 'render_post_link_meta_box' ], 'post', 'side', 'default' );
        add_meta_box( 'cv_post_map_meta', __( 'Mappa interattiva', 'cv-dossier' ), [ $this, 'render_post_map_meta_box' ], 'post', 'normal', 'high' );
    }
    
    /**
     * Renderizza la meta box del Dossier.
     *
     * @param WP_Post $post Post corrente.
     */
    public function render_dossier_meta_box( $post ) {
        wp_nonce_field( $this->nonce, $this->nonce );
        
        $status   = get_post_meta( $post->ID, '_cv_status', true );
        $score    = get_post_meta( $post->ID, '_cv_score', true );
        $facts    = get_post_meta( $post->ID, '_cv_facts', true );
        $actors   = get_post_meta( $post->ID, '_cv_actors', true );
        $show_context  = $this->is_feature_enabled( $post->ID, '_cv_show_context', true );
        $show_timeline = $this->is_feature_enabled( $post->ID, '_cv_show_timeline', true );
        $default_map   = CV_Marker_Helper::dossier_has_markers( $post->ID );
        $show_map      = $this->is_feature_enabled( $post->ID, '_cv_show_map', $default_map );
        $map_height_meta = get_post_meta( $post->ID, '_cv_dossier_map_height', true );
        $map_height      = CV_Sanitizer::sanitize_map_height( $map_height_meta, 380 );
        ?>
        <input type="hidden" name="cv_dossier_meta_present" value="1" />
        <p>
            <label for="cv_status"><?php esc_html_e( 'Stato', 'cv-dossier' ); ?></label>
            <select id="cv_status" name="cv_status">
                <option value="open" <?php selected( $status, 'open' ); ?>><?php esc_html_e( 'Aperto', 'cv-dossier' ); ?></option>
                <option value="closed" <?php selected( $status, 'closed' ); ?>><?php esc_html_e( 'Chiuso', 'cv-dossier' ); ?></option>
            </select>
        </p>
        <p>
            <label for="cv_score"><?php esc_html_e( 'Promesse mantenute (%)', 'cv-dossier' ); ?></label>
            <input type="number" min="0" max="100" id="cv_score" name="cv_score" value="<?php echo esc_attr( $score ); ?>" />
        </p>
        <p>
            <label for="cv_facts"><?php esc_html_e( 'Punti chiave (uno per riga)', 'cv-dossier' ); ?></label><br />
            <textarea id="cv_facts" name="cv_facts" rows="5" style="width:100%;"><?php echo esc_textarea( $facts ); ?></textarea>
        </p>
        <p>
            <label for="cv_actors"><?php esc_html_e( 'Attori/Enti coinvolti (separati da virgola)', 'cv-dossier' ); ?></label><br />
            <input type="text" id="cv_actors" name="cv_actors" style="width:100%;" value="<?php echo esc_attr( $actors ); ?>" />
        </p>
        <hr />
        <h4><?php esc_html_e( 'Componenti aggiuntivi del dossier', 'cv-dossier' ); ?></h4>
        <p><?php esc_html_e( 'Scegli quali elementi mostrare automaticamente nella pagina del dossier.', 'cv-dossier' ); ?></p>
        <p>
            <label>
                <input type="checkbox" name="cv_show_context" value="1" <?php checked( $show_context ); ?> />
                <?php esc_html_e( 'Mostra la scheda riassuntiva automaticamente nel dossier', 'cv-dossier' ); ?>
            </label>
        </p>
        <p>
            <label>
                <input type="checkbox" name="cv_show_timeline" value="1" <?php checked( $show_timeline ); ?> />
                <?php esc_html_e( 'Mostra la timeline degli eventi del dossier', 'cv-dossier' ); ?>
            </label>
        </p>
        <p>
            <label>
                <input type="checkbox" name="cv_show_map" value="1" <?php checked( $show_map ); ?> />
                <?php esc_html_e( 'Mostra la mappa degli eventi del dossier', 'cv-dossier' ); ?>
            </label>
        </p>
        <p>
            <label for="cv_dossier_map_height"><?php esc_html_e( 'Altezza della mappa del dossier (px)', 'cv-dossier' ); ?></label>
            <input type="number" id="cv_dossier_map_height" name="cv_dossier_map_height" min="240" max="800" step="10" value="<?php echo esc_attr( $map_height ); ?>" />
            <span class="description"><?php esc_html_e( 'Imposta un valore tra 240 e 800 pixel. Predefinito: 380.', 'cv-dossier' ); ?></span>
        </p>
        <?php
    }
    
    /**
     * Renderizza la meta box dell'Evento.
     *
     * @param WP_Post $post Post corrente.
     */
    public function render_event_meta_box( $post ) {
        wp_nonce_field( $this->nonce, $this->nonce );
        
        $date  = get_post_meta( $post->ID, '_cv_date', true );
        $place = get_post_meta( $post->ID, '_cv_place', true );
        $lat   = get_post_meta( $post->ID, '_cv_lat', true );
        $lng   = get_post_meta( $post->ID, '_cv_lng', true );
        $parent = wp_get_post_parent_id( $post->ID );
        $dossiers = get_posts([ 'post_type'=>'cv_dossier', 'numberposts'=>-1, 'orderby'=>'title', 'order'=>'ASC' ]);
        ?>
        <p>
            <label for="cv_date"><?php esc_html_e( 'Data (YYYY-MM-DD)', 'cv-dossier' ); ?></label>
            <input type="date" id="cv_date" name="cv_date" value="<?php echo esc_attr( $date ); ?>" />
        </p>
        <p>
            <label for="cv_place"><?php esc_html_e( 'Luogo (nome)', 'cv-dossier' ); ?></label>
            <input type="text" id="cv_place" name="cv_place" style="width:100%;" value="<?php echo esc_attr( $place ); ?>" />
        </p>
        <p>
            <label for="cv_lat"><?php esc_html_e( 'Latitudine', 'cv-dossier' ); ?></label>
            <input type="text" id="cv_lat" name="cv_lat" value="<?php echo esc_attr( $lat ); ?>" />
            <label for="cv_lng" style="margin-left:10px;">&nbsp;<?php esc_html_e( 'Longitudine', 'cv-dossier' ); ?></label>
            <input type="text" id="cv_lng" name="cv_lng" value="<?php echo esc_attr( $lng ); ?>" />
        </p>
        <p>
            <label for="cv_parent"><?php esc_html_e( 'Dossier', 'cv-dossier' ); ?></label>
            <select id="cv_parent" name="cv_parent">
                <option value=""><?php esc_html_e( '— Seleziona —', 'cv-dossier' ); ?></option>
                <?php foreach ( $dossiers as $d ) : ?>
                    <option value="<?php echo intval( $d->ID ); ?>" <?php selected( $parent, $d->ID ); ?>><?php echo esc_html( $d->post_title ); ?></option>
                <?php endforeach; ?>
            </select>
        </p>
        <?php
    }
    
    /**
     * Renderizza la meta box per collegare un post a un dossier.
     *
     * @param WP_Post $post Post corrente.
     */
    public function render_post_link_meta_box( $post ) {
        wp_nonce_field( $this->nonce, $this->nonce );
        
        $linked = get_post_meta( $post->ID, '_cv_dossier_id', true );
        $dossiers = get_posts([ 'post_type'=>'cv_dossier', 'numberposts'=>-1, 'orderby'=>'title', 'order'=>'ASC' ]);
        ?>
        <p>
            <label for="cv_link_dossier"><?php esc_html_e( 'Collega a Dossier', 'cv-dossier' ); ?></label>
            <select id="cv_link_dossier" name="cv_link_dossier" style="width:100%;">
                <option value=""><?php esc_html_e( '— Nessuno —', 'cv-dossier' ); ?></option>
                <?php foreach ( $dossiers as $d ) : ?>
                    <option value="<?php echo intval( $d->ID ); ?>" <?php selected( $linked, $d->ID ); ?>><?php echo esc_html( $d->post_title ); ?></option>
                <?php endforeach; ?>
            </select>
        </p>
        <?php
    }
    
    /**
     * Renderizza la meta box per la mappa del post.
     *
     * @param WP_Post $post Post corrente.
     */
    public function render_post_map_meta_box( $post ) {
        wp_nonce_field( $this->nonce, $this->nonce );
        
        $markers = get_post_meta( $post->ID, '_cv_map_markers', true );
        if ( ! is_array( $markers ) ) {
            $markers = [];
        }
        
        $is_map_enabled = $this->is_post_map_enabled( $post->ID );
        $map_height_meta = get_post_meta( $post->ID, '_cv_map_height', true );
        $map_height = CV_Sanitizer::sanitize_map_height( $map_height_meta );
        
        echo '<input type="hidden" name="cv_map_markers_present" value="1" />';
        echo '<p><label><input type="checkbox" name="cv_map_enabled" value="1"' . checked( true, $is_map_enabled, false ) . ' /> ' . esc_html__( 'Mostra la mappa nell\'articolo', 'cv-dossier' ) . '</label></p>';
        echo '<p><label for="cv_map_height">' . esc_html__( 'Altezza della mappa (px)', 'cv-dossier' ) . '</label> <input type="number" id="cv_map_height" name="cv_map_height" class="small-text" min="240" max="800" step="10" value="' . esc_attr( $map_height ) . '" /> <span class="description">' . esc_html__( 'Imposta un valore tra 240 e 800 pixel. Predefinito: 360.', 'cv-dossier' ) . '</span></p>';
        echo '<p class="description">' . esc_html__( 'Aggiungi dei punti sulla mappa indicando coordinate (latitudine e longitudine), contenuti descrittivi e, facoltativamente, un\'immagine. I lettori potranno aprire la foto a schermo intero.', 'cv-dossier' ) . '</p>';
        echo '<div id="cv-map-markers" class="cv-map-markers">';
        
        if ( ! empty( $markers ) ) {
            foreach ( $markers as $index => $marker ) {
                echo $this->get_marker_template( $index, $marker );
            }
        } else {
            echo $this->get_marker_template( 0, [] );
        }
        
        echo '</div>';
        echo '<p><button type="button" class="button button-secondary" id="cv-map-add-marker">' . esc_html__( 'Aggiungi punto', 'cv-dossier' ) . '</button></p>';
        echo '<p class="description">' . esc_html__( 'Suggerimento: puoi ottenere le coordinate cliccando con il tasto destro su Google Maps oppure usando servizi come openstreetmap.org.', 'cv-dossier' ) . '</p>';
    }
    
    /**
     * Ottiene il template HTML per un marker.
     *
     * @param int|string $index  Indice del marker.
     * @param array      $marker Dati del marker.
     * @return string HTML template.
     */
    public function get_marker_template( $index, $marker = [] ) {
        $defaults = [
            'title'       => '',
            'lat'         => '',
            'lng'         => '',
            'description' => '',
            'image_id'    => 0,
            'image_url'   => '',
            'image_alt'   => '',
        ];
        
        $is_placeholder = is_string( $index ) && false !== strpos( $index, '__INDEX__' );
        if ( $is_placeholder ) {
            $marker = array_merge( $defaults, [] );
        } else {
            $marker = array_merge( $defaults, is_array( $marker ) ? $marker : [] );
        }
        
        $index_attr = $is_placeholder ? $index : intval( $index );
        $display_number = $is_placeholder ? '' : intval( $index ) + 1;
        $image_id_value = $is_placeholder ? 0 : intval( $marker['image_id'] );
        $image_url_value = $is_placeholder ? '' : CV_Validator::validate_image_url( $marker['image_url'] ?? '' );
        $image_alt_value = $is_placeholder ? '' : ( $marker['image_alt'] ?? '' );
        $preview_url = '';
        
        if ( $image_id_value ) {
            if ( wp_attachment_is_image( $image_id_value ) ) {
                $thumb = wp_get_attachment_image_src( $image_id_value, 'medium' );
                if ( $thumb ) {
                    $preview_url = $thumb[0];
                } else {
                    $preview_url = wp_get_attachment_url( $image_id_value );
                }
            } else {
                $image_id_value = 0;
                $image_url_value = '';
            }
        } elseif ( $image_url_value ) {
            $preview_url = $image_url_value;
        }
        
        ob_start();
        ?>
        <div class="cv-map-marker" data-index="<?php echo esc_attr( $index_attr ); ?>">
            <div class="cv-map-marker__header">
                <strong><?php esc_html_e( 'Punto', 'cv-dossier' ); ?> <span class="cv-map-marker__number"><?php echo esc_html( $display_number ); ?></span></strong>
                <button type="button" class="button-link-delete cv-map-marker__remove"><?php echo esc_html__( 'Rimuovi punto', 'cv-dossier' ); ?></button>
            </div>
            <div class="cv-map-marker__fields">
                <p>
                    <label><?php esc_html_e( 'Titolo del punto', 'cv-dossier' ); ?></label>
                    <input type="text" name="cv_map_markers[<?php echo esc_attr( $index_attr ); ?>][title]" value="<?php echo esc_attr( $marker['title'] ?? '' ); ?>" class="widefat" />
                </p>
                <div class="cv-map-marker__coords">
                    <p>
                        <label><?php esc_html_e( 'Latitudine', 'cv-dossier' ); ?></label>
                        <input type="number" step="any" name="cv_map_markers[<?php echo esc_attr( $index_attr ); ?>][lat]" value="<?php echo esc_attr( $marker['lat'] ?? '' ); ?>" />
                    </p>
                    <p>
                        <label><?php esc_html_e( 'Longitudine', 'cv-dossier' ); ?></label>
                        <input type="number" step="any" name="cv_map_markers[<?php echo esc_attr( $index_attr ); ?>][lng]" value="<?php echo esc_attr( $marker['lng'] ?? '' ); ?>" />
                    </p>
                </div>
                <p>
                    <label><?php esc_html_e( 'Descrizione (HTML ammesso: link, grassetto, elenco)', 'cv-dossier' ); ?></label>
                    <textarea name="cv_map_markers[<?php echo esc_attr( $index_attr ); ?>][description]" class="widefat" rows="4"><?php echo esc_textarea( $marker['description'] ?? '' ); ?></textarea>
                </p>
                <div class="cv-map-marker__image">
                    <div class="cv-map-marker__image-preview">
                        <?php if ( $preview_url ) : ?>
                            <img src="<?php echo esc_url( $preview_url ); ?>" alt="" />
                        <?php else : ?>
                            <span class="cv-map-marker__image-placeholder"><?php esc_html_e( 'Nessuna immagine selezionata', 'cv-dossier' ); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="cv-map-marker__image-actions">
                        <input type="hidden" class="cv-map-marker__image-id" name="cv_map_markers[<?php echo esc_attr( $index_attr ); ?>][image_id]" value="<?php echo esc_attr( $image_id_value ); ?>" />
                        <input type="url" class="cv-map-marker__image-url" name="cv_map_markers[<?php echo esc_attr( $index_attr ); ?>][image_url]" value="<?php echo esc_attr( $image_url_value ); ?>" placeholder="https://" />
                        <input type="text" class="cv-map-marker__image-alt" name="cv_map_markers[<?php echo esc_attr( $index_attr ); ?>][image_alt]" value="<?php echo esc_attr( $image_alt_value ); ?>" placeholder="<?php esc_attr_e( 'Testo alternativo immagine', 'cv-dossier' ); ?>" />
                        <button type="button" class="button cv-map-marker__select-media"><?php echo esc_html__( 'Scegli dalla libreria', 'cv-dossier' ); ?></button>
                        <button type="button" class="button-link cv-map-marker__clear-media"><?php echo esc_html__( 'Rimuovi immagine', 'cv-dossier' ); ?></button>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Salva i meta dati.
     *
     * @param int $post_id ID del post.
     */
    public function save_meta( $post_id ) {
        if ( ! isset( $_POST[ $this->nonce ] ) ) {
            return;
        }
        
        $nonce = wp_unslash( $_POST[ $this->nonce ] );
        if ( ! is_string( $nonce ) || ! wp_verify_nonce( $nonce, $this->nonce ) ) {
            return;
        }
        
        if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) {
            return;
        }
        
        if ( isset($_POST['post_type']) && 'page' === $_POST['post_type'] ) {
            if ( ! current_user_can( 'edit_page', $post_id ) ) {
                return;
            }
        } else {
            if ( ! current_user_can( 'edit_post', $post_id ) ) {
                return;
            }
        }
        
        $post_type = get_post_type( $post_id );
        
        if ( 'cv_dossier' === $post_type ) {
            $this->save_dossier_meta( $post_id );
        } elseif ( 'cv_dossier_event' === $post_type ) {
            $this->save_event_meta( $post_id );
        } elseif ( 'post' === $post_type ) {
            $this->save_post_meta( $post_id );
        }
    }
    
    /**
     * Salva i meta del dossier.
     *
     * @param int $post_id ID del post.
     */
    private function save_dossier_meta( $post_id ) {
        $status = isset( $_POST['cv_status'] ) ? sanitize_text_field( wp_unslash( $_POST['cv_status'] ) ) : 'open';
        $status = CV_Validator::validate_status( $status );
        
        $score = isset( $_POST['cv_score'] ) ? intval( wp_unslash( $_POST['cv_score'] ) ) : 0;
        $score = CV_Validator::validate_score( $score );
        
        update_post_meta( $post_id, '_cv_status', $status );
        update_post_meta( $post_id, '_cv_score', $score );
        
        $facts = isset( $_POST['cv_facts'] ) ? wp_kses_post( wp_unslash( $_POST['cv_facts'] ) ) : '';
        $actors = isset( $_POST['cv_actors'] ) ? sanitize_text_field( wp_unslash( $_POST['cv_actors'] ) ) : '';
        update_post_meta( $post_id, '_cv_facts', $facts );
        update_post_meta( $post_id, '_cv_actors', $actors );
        
        if ( isset( $_POST['cv_dossier_meta_present'] ) ) {
            $show_context  = isset( $_POST['cv_show_context'] ) ? '1' : '0';
            $show_timeline = isset( $_POST['cv_show_timeline'] ) ? '1' : '0';
            $show_map      = isset( $_POST['cv_show_map'] ) ? '1' : '0';
            
            update_post_meta( $post_id, '_cv_show_context', $show_context );
            update_post_meta( $post_id, '_cv_show_timeline', $show_timeline );
            update_post_meta( $post_id, '_cv_show_map', $show_map );
            
            $map_height_input = array_key_exists( 'cv_dossier_map_height', $_POST ) ? wp_unslash( $_POST['cv_dossier_map_height'] ) : '';
            $map_height = CV_Sanitizer::sanitize_map_height( $map_height_input, 380 );
            update_post_meta( $post_id, '_cv_dossier_map_height', $map_height );
        }
    }
    
    /**
     * Salva i meta dell'evento.
     *
     * @param int $post_id ID del post.
     */
    private function save_event_meta( $post_id ) {
        $date_raw = isset( $_POST['cv_date'] ) ? wp_unslash( $_POST['cv_date'] ) : '';
        $date = CV_Validator::validate_date( $date_raw );
        
        $lat = isset( $_POST['cv_lat'] ) ? sanitize_text_field( wp_unslash( $_POST['cv_lat'] ) ) : '';
        $lng = isset( $_POST['cv_lng'] ) ? sanitize_text_field( wp_unslash( $_POST['cv_lng'] ) ) : '';
        
        $lat = CV_Validator::validate_latitude( $lat );
        $lng = CV_Validator::validate_longitude( $lng );
        
        update_post_meta( $post_id, '_cv_date', $date );
        
        $place = isset( $_POST['cv_place'] ) ? sanitize_text_field( wp_unslash( $_POST['cv_place'] ) ) : '';
        update_post_meta( $post_id, '_cv_place', $place );
        update_post_meta( $post_id, '_cv_lat', $lat );
        update_post_meta( $post_id, '_cv_lng', $lng );
        
        $parent = isset( $_POST['cv_parent'] ) ? intval( wp_unslash( $_POST['cv_parent'] ) ) : 0;
        $current_parent = (int) wp_get_post_parent_id( $post_id );
        
        if ( $parent !== $current_parent ) {
            remove_action( 'save_post', [ $this, 'save_meta' ] );
            wp_update_post([
                'ID'          => $post_id,
                'post_parent' => $parent,
            ]);
            add_action( 'save_post', [ $this, 'save_meta' ] );
        }
    }
    
    /**
     * Salva i meta del post standard.
     *
     * @param int $post_id ID del post.
     */
    private function save_post_meta( $post_id ) {
        $dossier_id = isset($_POST['cv_link_dossier']) ? intval( wp_unslash( $_POST['cv_link_dossier'] ) ) : 0;
        if ( $dossier_id > 0 ) {
            update_post_meta( $post_id, '_cv_dossier_id', $dossier_id );
        } else {
            delete_post_meta( $post_id, '_cv_dossier_id' );
        }
        
        if ( isset( $_POST['cv_map_markers_present'] ) ) {
            $map_enabled = isset( $_POST['cv_map_enabled'] ) ? '1' : '0';
            update_post_meta( $post_id, '_cv_map_enabled', $map_enabled );
            
            $map_height = isset( $_POST['cv_map_height'] ) ? wp_unslash( $_POST['cv_map_height'] ) : '';
            $map_height = CV_Sanitizer::sanitize_map_height( $map_height );
            update_post_meta( $post_id, '_cv_map_height', $map_height );
            
            $raw_markers = [];
            if ( isset( $_POST['cv_map_markers'] ) && is_array( $_POST['cv_map_markers'] ) ) {
                $raw_markers = wp_unslash( $_POST['cv_map_markers'] );
            }
            
            $clean_markers = CV_Sanitizer::sanitize_map_markers( $raw_markers );
            
            if ( ! empty( $clean_markers ) ) {
                update_post_meta( $post_id, '_cv_map_markers', $clean_markers );
            } else {
                delete_post_meta( $post_id, '_cv_map_markers' );
            }
        }
    }
    
    /**
     * Verifica se una feature è abilitata.
     *
     * @param int    $post_id ID post.
     * @param string $meta_key Meta key.
     * @param bool   $default Valore default.
     * @return bool True se abilitata.
     */
    private function is_feature_enabled( $post_id, $meta_key, $default = false ) {
        $post_id = intval( $post_id );
        if ( $post_id <= 0 ) {
            return (bool) $default;
        }
        
        if ( ! metadata_exists( 'post', $post_id, $meta_key ) ) {
            return (bool) $default;
        }
        
        $value = get_post_meta( $post_id, $meta_key, true );
        
        if ( '' === $value ) {
            return (bool) $default;
        }
        
        return $this->interpret_boolean_meta( $value );
    }
    
    /**
     * Verifica se la mappa di un post è abilitata.
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
     * Interpreta un valore meta come booleano.
     *
     * @param mixed $value Valore.
     * @return bool Valore booleano.
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