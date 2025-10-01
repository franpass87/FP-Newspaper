<?php
/**
 * Plugin Name: CV Dossier & Context
 * Description: Dossier tematici con scheda riassuntiva automatica, timeline, mappa e follow-up per Cronaca di Viterbo.
 * Version: 1.0.2
 * Author: Cronaca di Viterbo
 * License: GPLv2 or later
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$autoload = __DIR__ . '/vendor/autoload.php';
if ( is_readable( $autoload ) ) {
    require $autoload;
}

class CV_Dossier_Context {
    const VERSION = '1.0.2';
    const NONCE   = 'cv_dossier_nonce';
    const TABLE   = 'cv_dossier_followers';
    private static $instance = null;

    /**
     * Cache dossier map markers per request to avoid duplicate queries.
     *
     * @var array<int, array>
     */
    private $dossier_markers_cache = [];

    public static function init() {
        if ( null === self::$instance ) self::$instance = new self;
        return self::$instance;
    }

    private function __construct() {
        register_activation_hook( __FILE__, [ $this, 'activate' ] );
        register_uninstall_hook( __FILE__, [ __CLASS__, 'uninstall' ] );
        add_action( 'plugins_loaded', [ $this, 'load_textdomain' ] );
        add_action( 'init', [ $this, 'register_cpts' ] );
        add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ] );
        add_action( 'save_post', [ $this, 'save_meta' ] );
        add_filter( 'the_content', [ $this, 'auto_context_in_post' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin' ] );

        // Shortcodes
        add_shortcode( 'cv_dossier_context', [ $this, 'sc_context' ] );
        add_shortcode( 'cv_dossier_timeline', [ $this, 'sc_timeline' ] );
        add_shortcode( 'cv_dossier_map', [ $this, 'sc_map' ] );

        // Assets
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_front' ] );

        // AJAX follow
        add_action( 'wp_ajax_cv_follow_dossier', [ $this, 'ajax_follow' ] );
        add_action( 'wp_ajax_nopriv_cv_follow_dossier', [ $this, 'ajax_follow' ] );

        // Localize for JS
        add_action( 'wp_enqueue_scripts', [ $this, 'localize_js' ], 20 );
    }

    public function activate() {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE;
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            dossier_id BIGINT UNSIGNED NOT NULL,
            email VARCHAR(190) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY dossier_email (dossier_id, email),
            KEY dossier_id (dossier_id)
        ) {$charset_collate};";
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    public static function uninstall() {
        // Only remove data if user explicitly chooses to
        if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) return;

        global $wpdb;
        
        // Remove custom tables (optional - some plugins keep data)
        // Uncomment the line below if you want to remove the table on uninstall
        // $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}" . self::TABLE );
        
        // Remove all plugin options and post meta
        delete_option( 'cv_dossier_version' );
        
        // Remove all meta data for dossiers and events
        $wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_cv_%'" );
        
        // Clean up any remaining plugin data
        wp_cache_flush();
    }

    public function load_textdomain() {
        load_plugin_textdomain( 'cv-dossier', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
    }

    public function register_cpts() {
        // CPT Dossier
        register_post_type( 'cv_dossier', [
            'label' => __( 'Dossier', 'cv-dossier' ),
            'public' => true,
            'show_in_rest' => true,
            'menu_icon' => 'dashicons-portfolio',
            'supports' => [ 'title', 'editor', 'thumbnail', 'excerpt', 'author' ],
            'has_archive' => true,
            'rewrite' => [ 'slug' => 'dossier' ],
        ]);

        // CPT Eventi (timeline) - figli di Dossier
        register_post_type( 'cv_dossier_event', [
            'label' => __( 'Eventi Dossier', 'cv-dossier' ),
            'public' => false,
            'show_ui' => true,
            'show_in_rest' => false,
            'menu_icon' => 'dashicons-clock',
            'supports' => [ 'title', 'editor' ],
        ]);
    }

    public function add_meta_boxes() {
        // Metabox Dossier
        add_meta_box( 'cv_dossier_meta', __( 'Dettagli Dossier', 'cv-dossier' ), [ $this, 'mb_dossier' ], 'cv_dossier', 'normal', 'default' );
        // Metabox Evento
        add_meta_box( 'cv_event_meta', __( 'Dettagli Evento (Timeline)', 'cv-dossier' ), [ $this, 'mb_event' ], 'cv_dossier_event', 'normal', 'default' );
        // Metabox su Post: aggancio a Dossier
        add_meta_box( 'cv_link_meta', __( 'Dossier collegato', 'cv-dossier' ), [ $this, 'mb_link' ], 'post', 'side', 'default' );
        add_meta_box( 'cv_post_map_meta', __( 'Mappa interattiva', 'cv-dossier' ), [ $this, 'mb_post_map' ], 'post', 'normal', 'high' );
    }

    public function mb_dossier( $post ) {
        wp_nonce_field( self::NONCE, self::NONCE );
        $status   = get_post_meta( $post->ID, '_cv_status', true );      // open|closed
        $score    = get_post_meta( $post->ID, '_cv_score', true );       // 0-100
        $facts    = get_post_meta( $post->ID, '_cv_facts', true );       // testo (bullet, uno per riga)
        $actors   = get_post_meta( $post->ID, '_cv_actors', true );      // elenco attori/enti
        $show_context  = $this->is_dossier_feature_enabled( $post->ID, '_cv_show_context', true );
        $show_timeline = $this->is_dossier_feature_enabled( $post->ID, '_cv_show_timeline', true );
        $default_map   = $this->dossier_has_map_markers( $post->ID );
        $show_map      = $this->is_dossier_feature_enabled( $post->ID, '_cv_show_map', $default_map );
        $map_height_meta = get_post_meta( $post->ID, '_cv_dossier_map_height', true );
        $map_height      = $this->sanitize_map_height( $map_height_meta, 380 );
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

    public function mb_event( $post ) {
        wp_nonce_field( self::NONCE, self::NONCE );
        $date  = get_post_meta( $post->ID, '_cv_date', true ); // YYYY-MM-DD
        $place = get_post_meta( $post->ID, '_cv_place', true );
        $lat   = get_post_meta( $post->ID, '_cv_lat', true );
        $lng   = get_post_meta( $post->ID, '_cv_lng', true );
        $parent= wp_get_post_parent_id( $post->ID );
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
        <?php
        $dossiers = get_posts([ 'post_type'=>'cv_dossier', 'numberposts'=>-1, 'orderby'=>'title', 'order'=>'ASC' ]);
        ?>
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

    public function mb_link( $post ) {
        wp_nonce_field( self::NONCE, self::NONCE );
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

    public function save_meta( $post_id ) {
        if ( ! isset( $_POST[ self::NONCE ] ) ) {
            return;
        }

        $nonce = wp_unslash( $_POST[ self::NONCE ] );
        if ( ! is_string( $nonce ) || ! wp_verify_nonce( $nonce, self::NONCE ) ) {
            return;
        }
        if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;
        if ( isset($_POST['post_type']) && 'page' === $_POST['post_type'] ) {
            if ( ! current_user_can( 'edit_page', $post_id ) ) return;
        } else {
            if ( ! current_user_can( 'edit_post', $post_id ) ) return;
        }

        // Dossier fields
        if ( get_post_type($post_id) === 'cv_dossier' ) {
            $status = isset( $_POST['cv_status'] ) ? sanitize_text_field( wp_unslash( $_POST['cv_status'] ) ) : 'open';
            $status = in_array( $status, ['open', 'closed'] ) ? $status : 'open';

            $score = isset( $_POST['cv_score'] ) ? intval( wp_unslash( $_POST['cv_score'] ) ) : 0;
            $score = max( 0, min( 100, $score ) ); // Ensure score is between 0-100

            update_post_meta( $post_id, '_cv_status', $status );
            update_post_meta( $post_id, '_cv_score',  $score );
            $facts   = isset( $_POST['cv_facts'] ) ? wp_kses_post( wp_unslash( $_POST['cv_facts'] ) ) : '';
            $actors  = isset( $_POST['cv_actors'] ) ? sanitize_text_field( wp_unslash( $_POST['cv_actors'] ) ) : '';
            update_post_meta( $post_id, '_cv_facts',  $facts );
            update_post_meta( $post_id, '_cv_actors', $actors );

            if ( isset( $_POST['cv_dossier_meta_present'] ) ) {
                $show_context  = isset( $_POST['cv_show_context'] ) ? '1' : '0';
                $show_timeline = isset( $_POST['cv_show_timeline'] ) ? '1' : '0';
                $show_map      = isset( $_POST['cv_show_map'] ) ? '1' : '0';

                update_post_meta( $post_id, '_cv_show_context', $show_context );
                update_post_meta( $post_id, '_cv_show_timeline', $show_timeline );
                update_post_meta( $post_id, '_cv_show_map', $show_map );

                $map_height_input = array_key_exists( 'cv_dossier_map_height', $_POST ) ? wp_unslash( $_POST['cv_dossier_map_height'] ) : '';
                $map_height       = $this->sanitize_map_height( $map_height_input, 380 );
                update_post_meta( $post_id, '_cv_dossier_map_height', $map_height );
            }
        }

        // Event fields
        if ( get_post_type($post_id) === 'cv_dossier_event' ) {
            $date_raw = isset( $_POST['cv_date'] ) ? wp_unslash( $_POST['cv_date'] ) : '';
            $date = preg_replace('/[^0-9\-]/', '', $date_raw );
            // Validate date format (basic check)
            if ( $date && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
                $date = '';
            }

            $lat = isset( $_POST['cv_lat'] ) ? sanitize_text_field( wp_unslash( $_POST['cv_lat'] ) ) : '';
            $lng = isset( $_POST['cv_lng'] ) ? sanitize_text_field( wp_unslash( $_POST['cv_lng'] ) ) : '';

            if ( $lat !== '' ) {
                $lat = str_replace( ',', '.', $lat );
            }

            if ( $lng !== '' ) {
                $lng = str_replace( ',', '.', $lng );
            }

            // Validate coordinates (basic check)
            if ( $lat && ! is_numeric( $lat ) ) $lat = '';
            if ( $lng && ! is_numeric( $lng ) ) $lng = '';
            if ( $lat && ( $lat < -90 || $lat > 90 ) ) $lat = '';
            if ( $lng && ( $lng < -180 || $lng > 180 ) ) $lng = '';

            update_post_meta( $post_id, '_cv_date',  $date );
            $place = isset( $_POST['cv_place'] ) ? sanitize_text_field( wp_unslash( $_POST['cv_place'] ) ) : '';
            update_post_meta( $post_id, '_cv_place', $place );
            update_post_meta( $post_id, '_cv_lat',   $lat );
            update_post_meta( $post_id, '_cv_lng',   $lng );
            // parent
            $parent        = isset( $_POST['cv_parent'] ) ? intval( wp_unslash( $_POST['cv_parent'] ) ) : 0;
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

        // Post link to dossier
        if ( get_post_type($post_id) === 'post' ) {
            $dossier_id = isset($_POST['cv_link_dossier']) ? intval( wp_unslash( $_POST['cv_link_dossier'] ) ) : 0;
            if ( $dossier_id > 0 ) update_post_meta( $post_id, '_cv_dossier_id', $dossier_id );
            else delete_post_meta( $post_id, '_cv_dossier_id' );

            if ( isset( $_POST['cv_map_markers_present'] ) ) {
                $map_enabled = isset( $_POST['cv_map_enabled'] ) ? '1' : '0';
                update_post_meta( $post_id, '_cv_map_enabled', $map_enabled );

                $map_height = isset( $_POST['cv_map_height'] ) ? wp_unslash( $_POST['cv_map_height'] ) : '';
                $map_height = $this->sanitize_map_height( $map_height );
                update_post_meta( $post_id, '_cv_map_height', $map_height );

                $raw_markers = [];
                if ( isset( $_POST['cv_map_markers'] ) && is_array( $_POST['cv_map_markers'] ) ) {
                    $raw_markers = wp_unslash( $_POST['cv_map_markers'] );
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
                    if ( ! is_array( $marker ) ) continue;

                    $lat = isset($marker['lat']) ? str_replace(',', '.', trim($marker['lat'])) : '';
                    $lng = isset($marker['lng']) ? str_replace(',', '.', trim($marker['lng'])) : '';
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
                    $image_id = isset($marker['image_id']) ? intval($marker['image_id']) : 0;
                    $image_alt = isset( $marker['image_alt'] ) ? sanitize_text_field( $marker['image_alt'] ) : '';
                    $image_url = '';

                    if ( $image_id > 0 && ! wp_attachment_is_image( $image_id ) ) {
                        $image_id = 0;
                        $image_url = '';
                    }

                    if ( $image_id > 0 ) {
                        $image_url = wp_get_attachment_url( $image_id ) ?: '';
                    } else {
                        $image_url = isset( $marker['image_url'] ) ? $this->sanitize_marker_image_url( $marker['image_url'] ) : '';
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

                if ( ! empty( $clean_markers ) ) {
                    update_post_meta( $post_id, '_cv_map_markers', $clean_markers );
                } else {
                    delete_post_meta( $post_id, '_cv_map_markers' );
                }
            }
        }
    }

    /** FRONTEND **/

    public function enqueue_front() {
        wp_register_style( 'cv-dossier', plugins_url( 'css/cv-dossier.css', __FILE__ ), [], self::VERSION );

        if ( ! wp_style_is( 'leaflet', 'registered' ) ) {
            wp_register_style( 'leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', [], '1.9.4' );
        }

        if ( ! wp_script_is( 'leaflet', 'registered' ) ) {
            wp_register_script( 'leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', [], '1.9.4', true );
        }

        wp_register_script( 'cv-dossier', plugins_url( 'js/cv-dossier.js', __FILE__ ), [ 'jquery' ], self::VERSION, true );

        $needs_assets = false;
        $needs_leaflet = false;

        $post_id = get_queried_object_id();
        if ( $post_id && is_singular( 'post' ) ) {
            $linked_dossier = intval( get_post_meta( $post_id, '_cv_dossier_id', true ) );
            if ( $linked_dossier && $this->is_dossier_feature_enabled( $linked_dossier, '_cv_show_context', true ) ) {
                $needs_assets = true;
            }

            $markers           = get_post_meta( $post_id, '_cv_map_markers', true );
            $toggle_state      = $this->get_map_toggle_state( $post_id );
            $has_toggle_meta   = $toggle_state['has_meta'];
            $show_map          = $toggle_state['is_enabled'];

            if ( ! $has_toggle_meta ) {
                $show_map = true;
            }

            if ( $show_map && is_array( $markers ) && ! empty( $markers ) ) {
                $needs_assets  = true;
                $needs_leaflet = true;
            }
        }

        global $post;
        if ( $post instanceof WP_Post ) {
            if ( has_shortcode( $post->post_content, 'cv_dossier_context' ) || has_shortcode( $post->post_content, 'cv_dossier_timeline' ) ) {
                $needs_assets = true;
            }

            if ( has_shortcode( $post->post_content, 'cv_dossier_map' ) ) {
                $needs_assets  = true;
                $needs_leaflet = true;
            }
        }

        if ( $needs_assets ) {
            $this->ensure_front_assets( $needs_leaflet );
        }
    }

    public function enqueue_admin( $hook ) {
        if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) {
            return;
        }

        $screen = get_current_screen();
        if ( ! $screen || 'post' !== $screen->post_type ) {
            return;
        }

        wp_enqueue_media();
        wp_enqueue_style( 'cv-dossier-admin', plugins_url( 'css/cv-dossier-admin.css', __FILE__ ), [], self::VERSION );
        wp_enqueue_script( 'cv-dossier-admin', plugins_url( 'js/cv-dossier-admin.js', __FILE__ ), [ 'jquery' ], self::VERSION, true );
        wp_localize_script( 'cv-dossier-admin', 'CVDossierAdmin', [
            'markerTemplate' => $this->get_marker_template('__INDEX__'),
            'chooseImage'    => __( 'Scegli immagine', 'cv-dossier' ),
            'removeImage'    => __( 'Rimuovi immagine', 'cv-dossier' ),
            'removePoint'    => __( 'Rimuovi punto', 'cv-dossier' ),
            'noImage'        => __( 'Nessuna immagine selezionata', 'cv-dossier' ),
        ] );
    }

    public function localize_js(){
        if ( ! wp_script_is( 'cv-dossier', 'registered' ) ) {
            return;
        }

        $data = [
            'ajax'                 => admin_url( 'admin-ajax.php' ),
            'nonce'                => wp_create_nonce( self::NONCE ),
            'lightboxCloseLabel'   => __( 'Chiudi immagine', 'cv-dossier' ),
            'lightboxDialogLabel'  => __( 'Immagine ingrandita', 'cv-dossier' ),
            'submitLoadingText'    => __( 'Invio…', 'cv-dossier' ),
            'followSuccessHtml'    => wp_kses_post( sprintf( '<span class="cv-ok" role="status" aria-live="polite" tabindex="-1">%s</span>', esc_html__( 'Iscritto ✔', 'cv-dossier' ) ) ),
            'followGenericError'   => __( 'Errore durante l\'iscrizione. Riprova.', 'cv-dossier' ),
            'followNetworkError'   => __( 'Errore di connessione. Riprova.', 'cv-dossier' ),
            'mapLoadingLabel'      => __( 'Caricamento mappa...', 'cv-dossier' ),
            'mapErrorGeneric'      => __( 'Impossibile caricare la mappa.', 'cv-dossier' ),
            'mapErrorLeaflet'      => __( 'Impossibile caricare la mappa: il servizio cartografico non ha risposto.', 'cv-dossier' ),
            'mapErrorTimeout'      => __( 'Impossibile inizializzare la mappa entro il tempo previsto.', 'cv-dossier' ),
            'mapErrorRefresh'      => __( 'Ricarica la pagina e riprova', 'cv-dossier' ),
            'cardAriaLabel'        => __( 'Scheda dossier', 'cv-dossier' ),
            'followFormAriaLabel'  => __( 'Modulo per seguire il dossier', 'cv-dossier' ),
            'followEmailAriaLabel' => __( 'Inserisci la tua email per ricevere aggiornamenti', 'cv-dossier' ),
        ];

        wp_localize_script( 'cv-dossier', 'CVDossier', $data );
    }

    private function ensure_front_assets( $include_leaflet = false ) {
        if ( wp_style_is( 'cv-dossier', 'registered' ) && ! wp_style_is( 'cv-dossier', 'enqueued' ) ) {
            wp_enqueue_style( 'cv-dossier' );
        }

        if ( wp_script_is( 'cv-dossier', 'registered' ) && ! wp_script_is( 'cv-dossier', 'enqueued' ) ) {
            wp_enqueue_script( 'cv-dossier' );
        }

        if ( $include_leaflet ) {
            if ( wp_style_is( 'leaflet', 'registered' ) && ! wp_style_is( 'leaflet', 'enqueued' ) ) {
                wp_enqueue_style( 'leaflet' );
            }
            if ( wp_script_is( 'leaflet', 'registered' ) && ! wp_script_is( 'leaflet', 'enqueued' ) ) {
                wp_enqueue_script( 'leaflet' );
            }
        }
    }

    public function auto_context_in_post( $content ) {
        if ( ! in_the_loop() || ! is_main_query() ) {
            return $content;
        }

        if ( is_singular('post') ) {
            $prepend = '';
            $dossier_id = intval( get_post_meta( get_the_ID(), '_cv_dossier_id', true ) );
            if ( $dossier_id && $this->is_dossier_feature_enabled( $dossier_id, '_cv_show_context', true ) ) {
                $card = $this->render_context_card( $dossier_id, true );
                if ( $card ) {
                    $this->ensure_front_assets();
                    $prepend .= $card;
                }
            }

            $map = $this->render_post_map( get_the_ID() );
            if ( $map ) {
                $prepend .= $map;
            }

            if ( $prepend ) {
                $content = $prepend . $content;
            }
            return $content;
        }

        if ( is_singular( 'cv_dossier' ) ) {
            $post_id = get_the_ID();
            $append  = '';

            if ( ! has_shortcode( $content, 'cv_dossier_context' ) && $this->is_dossier_feature_enabled( $post_id, '_cv_show_context', true ) ) {
                $append .= $this->sc_context( [ 'id' => $post_id ] );
            }

            if ( ! has_shortcode( $content, 'cv_dossier_timeline' ) && $this->is_dossier_feature_enabled( $post_id, '_cv_show_timeline', true ) ) {
                $append .= $this->sc_timeline( [ 'id' => $post_id ] );
            }

            $has_markers = $this->dossier_has_map_markers( $post_id );

            if ( ! has_shortcode( $content, 'cv_dossier_map' ) && $this->is_dossier_feature_enabled( $post_id, '_cv_show_map', $has_markers ) ) {
                $map_height_meta = get_post_meta( $post_id, '_cv_dossier_map_height', true );
                $map_markup      = $this->sc_map( [
                    'id'     => $post_id,
                    'height' => $map_height_meta,
                ] );
                if ( $map_markup ) {
                    $append .= $map_markup;
                }
            }

            if ( $append ) {
                $content .= $append;
            }
        }

        return $content;
    }

    private function is_dossier_feature_enabled( $post_id, $meta_key, $default = false ) {
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

        return $this->interpret_map_enabled_meta( $value );
    }

    private function dossier_has_map_markers( $dossier_id ) {
        return ! empty( $this->get_dossier_map_markers( $dossier_id ) );
    }

    public function mb_post_map( $post ) {
        wp_nonce_field( self::NONCE, self::NONCE );
        $markers = get_post_meta( $post->ID, '_cv_map_markers', true );
        if ( ! is_array( $markers ) ) {
            $markers = [];
        }

        $toggle_state     = $this->get_map_toggle_state( $post->ID );
        $has_toggle_meta  = $toggle_state['has_meta'];
        $is_map_enabled   = $toggle_state['is_enabled'];

        if ( ! $has_toggle_meta ) {
            $is_map_enabled = true;
        }
        $map_height_meta  = get_post_meta( $post->ID, '_cv_map_height', true );
        $map_height       = $this->sanitize_map_height( $map_height_meta );

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

    private function get_marker_template( $index, $marker = [] ) {
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
        $image_url_value = $is_placeholder ? '' : $this->sanitize_marker_image_url( $marker['image_url'] ?? '' );
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

    private function render_post_map( $post_id ) {
        $markers = get_post_meta( $post_id, '_cv_map_markers', true );
        if ( ! is_array( $markers ) || empty( $markers ) ) {
            return '';
        }

        $toggle_state = $this->get_map_toggle_state( $post_id );
        $has_toggle   = $toggle_state['has_meta'];
        $show_map     = $toggle_state['is_enabled'];

        if ( ! $has_toggle ) {
            $show_map = true;
        }

        if ( ! $show_map ) {
            return '';
        }

        $map_height_meta = get_post_meta( $post_id, '_cv_map_height', true );
        $map_height      = $this->sanitize_map_height( $map_height_meta );

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
            $image_data = $this->prepare_marker_image( $marker );

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

        $this->ensure_front_assets( true );

        $map_id           = 'cv_post_map_' . $post_id . '_' . wp_generate_password( 6, false );
        $instructions_id  = $map_id . '_instructions';
        $instructions_txt = __( 'Trascina la mappa per esplorare i punti di interesse. Lo zoom è disabilitato per mantenere la panoramica.', 'cv-dossier' );
        $prepared_json    = wp_json_encode( $prepared );
        $json_failed      = ( false === $prepared_json );

        if ( $json_failed ) {
            $prepared_json = '';
        }

        ob_start();
        ?>
        <div class="cv-map cv-map--article" id="<?php echo esc_attr( $map_id ); ?>" role="region" aria-label="<?php echo esc_attr__( 'Mappa degli approfondimenti', 'cv-dossier' ); ?>" aria-describedby="<?php echo esc_attr( $instructions_id ); ?>" style="height:<?php echo esc_attr( $map_height ); ?>px;min-height:<?php echo esc_attr( $map_height ); ?>px;"<?php if ( $json_failed ) : ?> data-map-error="<?php echo esc_attr( 'data-invalid' ); ?>"<?php endif; ?> tabindex="0"></div>
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

    private function prepare_marker_image( $marker ) {
        $image_id  = isset( $marker['image_id'] ) ? intval( $marker['image_id'] ) : 0;
        $image_url = isset( $marker['image_url'] ) ? $this->sanitize_marker_image_url( $marker['image_url'] ) : '';
        $image_alt = isset( $marker['image_alt'] ) ? sanitize_text_field( $marker['image_alt'] ) : '';

        $normalize_alt = static function( $alt ) {
            $alt = is_scalar( $alt ) ? (string) $alt : '';
            $alt = sanitize_text_field( $alt );
            $alt = preg_replace( '/[\r\n]+/', ' ', $alt );
            return str_replace( "\"", '', $alt );
        };

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
            $alt = $normalize_alt( $alt );

            if ( $full ) {
                return [
                    'full'  => esc_url( $full[0] ),
                    'thumb' => esc_url( $thumb ? $thumb[0] : $full[0] ),
                    'alt'   => $alt,
                ];
            }
        }

        if ( $image_url ) {
            $alt = $normalize_alt( $image_alt );
            return [
                'full'  => esc_url( $image_url ),
                'thumb' => esc_url( $image_url ),
                'alt'   => $alt,
            ];
        }

        return null;
    }

    private function sanitize_marker_image_url( $url ) {
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

    private function get_map_toggle_state( $post_id ) {
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

        return [
            'has_meta'   => $has_meta,
            'is_enabled' => $this->interpret_map_enabled_meta( $raw_value ),
        ];
    }

    private function interpret_map_enabled_meta( $value ) {
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

    private function sanitize_map_height( $value, $default = 360 ) {
        if ( is_array( $value ) ) {
            $value = '';
        }

        $default = intval( $default );
        if ( $default <= 0 ) {
            $default = 360;
        }

        if ( $default < 240 ) {
            $default = 240;
        }

        if ( $default > 800 ) {
            $default = 800;
        }

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

        if ( $value < 240 ) {
            $value = 240;
        }

        if ( $value > 800 ) {
            $value = 800;
        }

        return $value;
    }

    public function sc_context( $atts ) {
        $id = $this->resolve_dossier_shortcode_id( $atts );
        if ( ! $id ) {
            return '';
        }

        if ( ! $this->is_dossier_feature_enabled( $id, '_cv_show_context', true ) ) {
            return '';
        }

        $card = $this->render_context_card( $id, false );
        if ( $card ) {
            $this->ensure_front_assets();
        }
        return $card;
    }

    private function render_context_card( $dossier_id, $compact = false ) {
        $post = get_post( $dossier_id );
        if ( ! $post || $post->post_type !== 'cv_dossier' ) return '';

        $status = get_post_meta( $dossier_id, '_cv_status', true ) ?: 'open';
        $score  = intval( get_post_meta( $dossier_id, '_cv_score', true ) );
        $facts  = get_post_meta( $dossier_id, '_cv_facts', true );
        $actors = get_post_meta( $dossier_id, '_cv_actors', true );

        $events = get_posts([
            'post_type' => 'cv_dossier_event',
            'numberposts' => 3,
            'post_parent' => $dossier_id,
            'meta_key' => '_cv_date',
            'orderby'  => 'meta_value',
            'order'    => 'DESC',
        ]);
        $last = $events ? get_post_meta( $events[0]->ID, '_cv_date', true ) : '';

        ob_start(); ?>
        <aside class="cv-card" data-ga4="dossier_context" data-dossier="<?php echo esc_attr($dossier_id); ?>">
            <div class="cv-card__head">
                <span class="cv-badge <?php echo $status==='open'?'open':'closed'; ?>">
                    <?php echo $status === 'open' ? esc_html__( 'Dossier aperto', 'cv-dossier' ) : esc_html__( 'Dossier chiuso', 'cv-dossier' ); ?>
                </span>
                <h3 class="cv-card__title">
                    <a href="<?php echo esc_url( get_permalink($dossier_id) ); ?>">
                        <?php echo esc_html( get_the_title($dossier_id) ); ?>
                    </a>
                </h3>
                <div class="cv-score" title="<?php echo esc_attr__( 'Promesse mantenute', 'cv-dossier' ); ?>"><?php echo intval($score); ?>%</div>
            </div>

            <div class="cv-card__body">
                <?php if ( $facts ) : ?>
                    <ul class="cv-facts">
                        <?php foreach ( preg_split("/\r\n|\n|\r/", $facts ) as $li ) {
                            $li = trim($li); if (!$li) continue;
                            echo '<li>'. esc_html($li) .'</li>';
                        } ?>
                    </ul>
                <?php endif; ?>

                <?php if ( $actors ) : ?>
                    <div class="cv-actors"><strong><?php esc_html_e( 'Attori/Enti:', 'cv-dossier' ); ?></strong> <?php echo esc_html($actors); ?></div>
                <?php endif; ?>

                <?php if ( $last ) : ?>
                    <div class="cv-last"><strong><?php esc_html_e( 'Ultimo evento:', 'cv-dossier' ); ?></strong> <?php echo esc_html( $last ); ?></div>
                <?php endif; ?>
            </div>

            <div class="cv-card__cta">
                <a class="cv-btn" href="<?php echo esc_url( get_permalink($dossier_id) ); ?>" data-ga4="open_dossier"><?php esc_html_e( 'Tutto il dossier', 'cv-dossier' ); ?></a>
                <form class="cv-follow" method="post" data-ga4="follow_dossier">
                    <input type="email" name="email" placeholder="<?php echo esc_attr__( 'La tua email per gli aggiornamenti', 'cv-dossier' ); ?>" required />
                    <input type="hidden" name="dossier_id" value="<?php echo esc_attr($dossier_id); ?>"/>
                    <button type="submit" class="cv-btn"><?php esc_html_e( 'Segui', 'cv-dossier' ); ?></button>
                </form>
            </div>
        </aside>
        <?php
        return ob_get_clean();
    }

    public function sc_timeline( $atts ) {
        $id = $this->resolve_dossier_shortcode_id( $atts );
        if ( ! $id ) {
            return '';
        }

        if ( ! $this->is_dossier_feature_enabled( $id, '_cv_show_timeline', true ) ) {
            return '';
        }
        $events = get_posts([
            'post_type' => 'cv_dossier_event',
            'numberposts' => -1,
            'post_parent' => $id,
            'meta_key' => '_cv_date',
            'orderby'  => 'meta_value',
            'order'    => 'ASC',
        ]);
        if ( ! $events ) {
            return '<p>' . esc_html__( 'Nessun evento in timeline.', 'cv-dossier' ) . '</p>';
        }

        $this->ensure_front_assets();

        $out = '<div class="cv-timeline" data-ga4="timeline">';
        foreach ( $events as $e ) {
            $date     = esc_html( get_post_meta( $e->ID, '_cv_date', true ) );
            $place    = esc_html( get_post_meta( $e->ID, '_cv_place', true ) );
            $content  = apply_filters( 'cv_dossier_timeline_item_content', $e->post_content, $e );
            $content  = is_string( $content ) ? trim( $content ) : '';
            $content  = $content !== '' ? wp_kses_post( wpautop( $content ) ) : '';

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

    public function sc_map( $atts ) {
        $id = $this->resolve_dossier_shortcode_id( $atts );
        if ( ! $id ) {
            return '';
        }

        $height_raw = $atts['height'] ?? '';
        $height     = $this->sanitize_map_height( $height_raw, 380 );

        $default_enabled = $this->dossier_has_map_markers( $id );
        if ( ! $this->is_dossier_feature_enabled( $id, '_cv_show_map', $default_enabled ) ) {
            return '';
        }

        $markers = $this->get_dossier_map_markers( $id );
        if ( empty( $markers ) ) {
            return '';
        }

        $this->ensure_front_assets( true );
        $id_attr          = 'cvmap_' . $id . '_' . wp_generate_password( 6, false );
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

    private function resolve_dossier_shortcode_id( $atts ) {
        $id = 0;

        if ( isset( $atts['id'] ) ) {
            $id = intval( $atts['id'] );
        }

        if ( $id > 0 ) {
            return $id;
        }

        global $post;

        if ( $post instanceof WP_Post && 'cv_dossier' === $post->post_type ) {
            return (int) $post->ID;
        }

        return 0;
    }

    private function get_dossier_map_markers( $dossier_id ) {
        $dossier_id = intval( $dossier_id );
        if ( $dossier_id <= 0 ) {
            return [];
        }

        if ( isset( $this->dossier_markers_cache[ $dossier_id ] ) ) {
            return $this->dossier_markers_cache[ $dossier_id ];
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

        $this->dossier_markers_cache[ $dossier_id ] = $markers;

        return $markers;
    }

    public function ajax_follow() {
        check_ajax_referer( self::NONCE, 'nonce' );
        $email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
        $dossier_id = isset( $_POST['dossier_id'] ) ? intval( wp_unslash( $_POST['dossier_id'] ) ) : 0;
        if ( ! $email || ! is_email($email) || ! $dossier_id ) {
            wp_send_json_error([ 'message' => __( 'Dati non validi', 'cv-dossier' ) ], 400 );
        }

        $dossier = get_post( $dossier_id );
        if ( ! $dossier || 'cv_dossier' !== $dossier->post_type || 'publish' !== $dossier->post_status ) {
            wp_send_json_error([ 'message' => __( 'Dossier non trovato', 'cv-dossier' ) ], 404 );
        }
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE;
        $inserted = $wpdb->query( $wpdb->prepare(
            "INSERT IGNORE INTO {$table} (dossier_id, email) VALUES (%d, %s)", $dossier_id, $email
        ));
        /**
         * Hook per integrazione esterna (es. Brevo).
         * add_action('cv_dossier_follow', function($dossier_id,$email){ ... });
         */
        do_action( 'cv_dossier_follow', $dossier_id, $email );

        if ( $inserted === false ) {
            wp_send_json_error([ 'message' => __( 'Errore di sistema', 'cv-dossier' ) ], 500 );
        } else {
            wp_send_json_success([ 'message' => __( 'Ti avviseremo sugli aggiornamenti del dossier.', 'cv-dossier' ) ]);
        }
    }
}

CV_Dossier_Context::init();
