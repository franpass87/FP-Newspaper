<?php
/**
 * Plugin Name: CV Dossier & Context
 * Description: Dossier tematici con scheda riassuntiva automatica, timeline, mappa e follow-up per Cronaca di Viterbo.
 * Version: 1.0.0
 * Author: Cronaca di Viterbo
 * License: GPLv2 or later
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class CV_Dossier_Context {
    const VERSION = '1.0.0';
    const NONCE   = 'cv_dossier_nonce';
    const TABLE   = 'cv_dossier_followers';
    private static $instance = null;

    public static function init() {
        if ( null === self::$instance ) self::$instance = new self;
        return self::$instance;
    }

    private function __construct() {
        register_activation_hook( __FILE__, [ $this, 'activate' ] );
        register_uninstall_hook( __FILE__, [ __CLASS__, 'uninstall' ] );
        add_action( 'init', [ $this, 'register_cpts' ] );
        add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ] );
        add_action( 'save_post', [ $this, 'save_meta' ] );
        add_filter( 'the_content', [ $this, 'auto_context_in_post' ] );

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

    public function register_cpts() {
        // CPT Dossier
        register_post_type( 'cv_dossier', [
            'label' => 'Dossier',
            'public' => true,
            'show_in_rest' => true,
            'menu_icon' => 'dashicons-portfolio',
            'supports' => [ 'title', 'editor', 'thumbnail', 'excerpt', 'author' ],
            'has_archive' => true,
            'rewrite' => [ 'slug' => 'dossier' ],
        ]);

        // CPT Eventi (timeline) - figli di Dossier
        register_post_type( 'cv_dossier_event', [
            'label' => 'Eventi Dossier',
            'public' => false,
            'show_ui' => true,
            'show_in_rest' => false,
            'menu_icon' => 'dashicons-clock',
            'supports' => [ 'title', 'editor' ],
        ]);
    }

    public function add_meta_boxes() {
        // Metabox Dossier
        add_meta_box( 'cv_dossier_meta', 'Dettagli Dossier', [ $this, 'mb_dossier' ], 'cv_dossier', 'normal', 'default' );
        // Metabox Evento
        add_meta_box( 'cv_event_meta', 'Dettagli Evento (Timeline)', [ $this, 'mb_event' ], 'cv_dossier_event', 'normal', 'default' );
        // Metabox su Post: aggancio a Dossier
        add_meta_box( 'cv_link_meta', 'Dossier collegato', [ $this, 'mb_link' ], 'post', 'side', 'default' );
    }

    public function mb_dossier( $post ) {
        wp_nonce_field( self::NONCE, self::NONCE );
        $status   = get_post_meta( $post->ID, '_cv_status', true );      // open|closed
        $score    = get_post_meta( $post->ID, '_cv_score', true );       // 0-100
        $facts    = get_post_meta( $post->ID, '_cv_facts', true );       // testo (bullet, uno per riga)
        $actors   = get_post_meta( $post->ID, '_cv_actors', true );      // elenco attori/enti
        echo '<p><label>Stato: </label>
              <select name="cv_status">
                <option value="open" '.selected($status,'open',false).'>Aperto</option>
                <option value="closed" '.selected($status,'closed',false).'>Chiuso</option>
              </select></p>';
        echo '<p><label>Promesse mantenute (%): </label>
              <input type="number" min="0" max="100" name="cv_score" value="'.esc_attr($score).'"/></p>';
        echo '<p><label>Punti chiave (uno per riga):</label><br/>
              <textarea name="cv_facts" rows="5" style="width:100%;">'.esc_textarea($facts).'</textarea></p>';
        echo '<p><label>Attori/Enti coinvolti (virgola-separati):</label><br/>
              <input type="text" name="cv_actors" style="width:100%;" value="'.esc_attr($actors).'"/></p>';
    }

    public function mb_event( $post ) {
        wp_nonce_field( self::NONCE, self::NONCE );
        $date  = get_post_meta( $post->ID, '_cv_date', true ); // YYYY-MM-DD
        $place = get_post_meta( $post->ID, '_cv_place', true );
        $lat   = get_post_meta( $post->ID, '_cv_lat', true );
        $lng   = get_post_meta( $post->ID, '_cv_lng', true );
        $parent= wp_get_post_parent_id( $post->ID );
        echo '<p><label>Data (YYYY-MM-DD): </label><input type="date" name="cv_date" value="'.esc_attr($date).'"/></p>';
        echo '<p><label>Luogo (nome): </label><input type="text" name="cv_place" style="width:100%;" value="'.esc_attr($place).'"/></p>';
        echo '<p><label>Latitudine: </label><input type="text" name="cv_lat" value="'.esc_attr($lat).'"/> ';
        echo '<label>Longitudine: </label><input type="text" name="cv_lng" value="'.esc_attr($lng).'"/></p>';
        // parent selector
        $dossiers = get_posts([ 'post_type'=>'cv_dossier', 'numberposts'=>-1, 'orderby'=>'title', 'order'=>'ASC' ]);
        echo '<p><label>Dossier: </label><select name="cv_parent">';
        echo '<option value="">— Seleziona —</option>';
        foreach( $dossiers as $d ) {
            echo '<option value="'.intval($d->ID).'" '.selected($parent,$d->ID,false).'>'.esc_html($d->post_title).'</option>';
        }
        echo '</select></p>';
    }

    public function mb_link( $post ) {
        wp_nonce_field( self::NONCE, self::NONCE );
        $linked = get_post_meta( $post->ID, '_cv_dossier_id', true );
        $dossiers = get_posts([ 'post_type'=>'cv_dossier', 'numberposts'=>-1, 'orderby'=>'title', 'order'=>'ASC' ]);
        echo '<p><label>Collega a Dossier: </label><select name="cv_link_dossier" style="width:100%;">';
        echo '<option value="">— Nessuno —</option>';
        foreach( $dossiers as $d ) {
            echo '<option value="'.intval($d->ID).'" '.selected($linked,$d->ID,false).'>'.esc_html($d->post_title).'</option>';
        }
        echo '</select></p>';
    }

    public function save_meta( $post_id ) {
        if ( ! isset($_POST[self::NONCE]) || ! wp_verify_nonce( $_POST[self::NONCE], self::NONCE ) ) return;
        if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;
        if ( isset($_POST['post_type']) && 'page' === $_POST['post_type'] ) {
            if ( ! current_user_can( 'edit_page', $post_id ) ) return;
        } else {
            if ( ! current_user_can( 'edit_post', $post_id ) ) return;
        }

        // Dossier fields
        if ( get_post_type($post_id) === 'cv_dossier' ) {
            $status = sanitize_text_field( $_POST['cv_status'] ?? 'open' );
            $status = in_array( $status, ['open', 'closed'] ) ? $status : 'open';
            
            $score = intval( $_POST['cv_score'] ?? 0 );
            $score = max( 0, min( 100, $score ) ); // Ensure score is between 0-100
            
            update_post_meta( $post_id, '_cv_status', $status );
            update_post_meta( $post_id, '_cv_score',  $score );
            update_post_meta( $post_id, '_cv_facts',  wp_kses_post( $_POST['cv_facts'] ?? '' ) );
            update_post_meta( $post_id, '_cv_actors', sanitize_text_field( $_POST['cv_actors'] ?? '' ) );
        }

        // Event fields
        if ( get_post_type($post_id) === 'cv_dossier_event' ) {
            $date = preg_replace('/[^0-9\-]/', '', $_POST['cv_date'] ?? '' );
            // Validate date format (basic check)
            if ( $date && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
                $date = '';
            }
            
            $lat = sanitize_text_field( $_POST['cv_lat'] ?? '' );
            $lng = sanitize_text_field( $_POST['cv_lng'] ?? '' );
            
            // Validate coordinates (basic check)
            if ( $lat && ! is_numeric( $lat ) ) $lat = '';
            if ( $lng && ! is_numeric( $lng ) ) $lng = '';
            if ( $lat && ( $lat < -90 || $lat > 90 ) ) $lat = '';
            if ( $lng && ( $lng < -180 || $lng > 180 ) ) $lng = '';
            
            update_post_meta( $post_id, '_cv_date',  $date );
            update_post_meta( $post_id, '_cv_place', sanitize_text_field( $_POST['cv_place'] ?? '' ) );
            update_post_meta( $post_id, '_cv_lat',   $lat );
            update_post_meta( $post_id, '_cv_lng',   $lng );
            // parent
            $parent = isset($_POST['cv_parent']) ? intval($_POST['cv_parent']) : 0;
            wp_update_post([ 'ID'=>$post_id, 'post_parent'=>$parent ]);
        }

        // Post link to dossier
        if ( get_post_type($post_id) === 'post' ) {
            $dossier_id = isset($_POST['cv_link_dossier']) ? intval($_POST['cv_link_dossier']) : 0;
            if ( $dossier_id > 0 ) update_post_meta( $post_id, '_cv_dossier_id', $dossier_id );
            else delete_post_meta( $post_id, '_cv_dossier_id' );
        }
    }

    /** FRONTEND **/

    public function enqueue_front() {
        // Base CSS
        wp_register_style( 'cv-dossier', plugins_url( 'css/cv-dossier.css', __FILE__ ), [], self::VERSION );
        wp_enqueue_style( 'cv-dossier' );

        // Leaflet (solo se necessario, ma la usiamo nel map shortcode)
        if ( ! wp_script_is( 'leaflet', 'registered' ) ) {
            wp_register_style( 'leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', [], '1.9.4' );
            wp_register_script( 'leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', [], '1.9.4', true );
        }

        // Front JS
        wp_register_script( 'cv-dossier', plugins_url( 'js/cv-dossier.js', __FILE__ ), [ 'jquery' ], self::VERSION, true );
        wp_enqueue_script( 'cv-dossier' );
    }

    public function localize_js(){
        wp_localize_script( 'cv-dossier', 'CVDossier', [
            'ajax'  => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( self::NONCE ),
        ]);
    }

    public function auto_context_in_post( $content ) {
        if ( is_singular('post') && in_the_loop() && is_main_query() ) {
            $dossier_id = intval( get_post_meta( get_the_ID(), '_cv_dossier_id', true ) );
            if ( $dossier_id ) {
                $card = $this->render_context_card( $dossier_id, true );
                if ( $card ) $content = $card . $content;
            }
        }
        return $content;
    }

    public function sc_context( $atts ) {
        $id = intval( $atts['id'] ?? 0 );
        return $this->render_context_card( $id, false );
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
                    <?php echo $status==='open'?'Dossier aperto':'Dossier chiuso'; ?>
                </span>
                <h3 class="cv-card__title">
                    <a href="<?php echo esc_url( get_permalink($dossier_id) ); ?>">
                        <?php echo esc_html( get_the_title($dossier_id) ); ?>
                    </a>
                </h3>
                <div class="cv-score" title="Promesse mantenute"><?php echo intval($score); ?>%</div>
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
                    <div class="cv-actors"><strong>Attori/Enti:</strong> <?php echo esc_html($actors); ?></div>
                <?php endif; ?>

                <?php if ( $last ) : ?>
                    <div class="cv-last"><strong>Ultimo evento:</strong> <?php echo esc_html( $last ); ?></div>
                <?php endif; ?>
            </div>

            <div class="cv-card__cta">
                <a class="cv-btn" href="<?php echo esc_url( get_permalink($dossier_id) ); ?>" data-ga4="open_dossier">Tutto il Dossier</a>
                <form class="cv-follow" method="post" data-ga4="follow_dossier">
                    <input type="email" name="email" placeholder="La tua email per gli aggiornamenti" required />
                    <input type="hidden" name="dossier_id" value="<?php echo esc_attr($dossier_id); ?>"/>
                    <button type="submit" class="cv-btn">Segui</button>
                </form>
            </div>
        </aside>
        <?php
        return ob_get_clean();
    }

    public function sc_timeline( $atts ) {
        $id = intval( $atts['id'] ?? 0 );
        if ( ! $id ) return '';
        $events = get_posts([
            'post_type' => 'cv_dossier_event',
            'numberposts' => -1,
            'post_parent' => $id,
            'meta_key' => '_cv_date',
            'orderby'  => 'meta_value',
            'order'    => 'ASC',
        ]);
        if ( ! $events ) return '<p>Nessun evento in timeline.</p>';

        $out = '<div class="cv-timeline" data-ga4="timeline">';
        foreach ( $events as $e ) {
            $date  = esc_html( get_post_meta($e->ID,'_cv_date',true) );
            $place = esc_html( get_post_meta($e->ID,'_cv_place',true) );
            $out  .= '<div class="cv-tl-item">';
            $out  .= '<div class="cv-tl-date">'. $date .'</div>';
            $out  .= '<div class="cv-tl-content"><h4>'. esc_html($e->post_title) .'</h4>';
            if ( $place ) $out .= '<div class="cv-tl-place">'. $place .'</div>';
            $out  .= wpautop( esc_html( wp_strip_all_tags($e->post_content) ) ) . '</div></div>';
        }
        $out .= '</div>';
        return $out;
    }

    public function sc_map( $atts ) {
        $id = intval( $atts['id'] ?? 0 );
        $height = preg_replace('/[^0-9]/','', $atts['height'] ?? '380' );
        if ( ! $id ) return '';
        wp_enqueue_style( 'leaflet' );
        wp_enqueue_script( 'leaflet' );

        $events = get_posts([
            'post_type' => 'cv_dossier_event',
            'numberposts' => -1,
            'post_parent' => $id,
        ]);
        $markers = [];
        foreach ( $events as $e ) {
            $lat = get_post_meta($e->ID,'_cv_lat',true);
            $lng = get_post_meta($e->ID,'_cv_lng',true);
            if ( $lat && $lng ) {
                $markers[] = [
                    'title' => $e->post_title,
                    'lat'   => floatval($lat),
                    'lng'   => floatval($lng),
                    'place' => get_post_meta($e->ID,'_cv_place',true),
                    'date'  => get_post_meta($e->ID,'_cv_date',true),
                ];
            }
        }
        $id_attr = 'cvmap_' . $id . '_' . wp_generate_password(6,false);
        ob_start(); ?>
        <div id="<?php echo esc_attr($id_attr); ?>" class="cv-map" style="height:<?php echo intval($height); ?>px;"></div>
        <script>
        (function(){
            var el = document.getElementById('<?php echo esc_js($id_attr); ?>');
            if(!el || !window.L) return;
            var map = L.map(el).setView([42.416,12.105], 10); // Viterbo approx
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OSM' }).addTo(map);
            var markers = <?php echo wp_json_encode($markers); ?>;
            var bounds = [];
            markers.forEach(function(m){
                var mk = L.marker([m.lat,m.lng]).addTo(map)
                    .bindPopup('<strong>'+m.title+'</strong><br/>'+ (m.place||'') +'<br/>'+ (m.date||'') );
                bounds.push([m.lat,m.lng]);
            });
            if (bounds.length) map.fitBounds(bounds, { padding: [20,20] });
        })();
        </script>
        <?php
        return ob_get_clean();
    }

    public function ajax_follow() {
        check_ajax_referer( self::NONCE, 'nonce' );
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $dossier_id = isset($_POST['dossier_id']) ? intval($_POST['dossier_id']) : 0;
        if ( ! $email || ! is_email($email) || ! $dossier_id ) {
            wp_send_json_error([ 'message'=>'Dati non validi' ], 400 );
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
            wp_send_json_error([ 'message'=>'Errore di sistema' ], 500 );
        } else {
            wp_send_json_success([ 'message'=>'Ti avviseremo sugli aggiornamenti del dossier.' ]);
        }
    }
}

CV_Dossier_Context::init();