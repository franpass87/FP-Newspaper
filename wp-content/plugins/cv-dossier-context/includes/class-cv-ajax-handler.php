<?php
/**
 * AJAX Handler
 *
 * Gestisce le richieste AJAX del plugin.
 *
 * @package CV_Dossier_Context
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Classe per gestione richieste AJAX.
 */
class CV_AJAX_Handler {
    
    /**
     * Nome nonce.
     *
     * @var string
     */
    private $nonce;
    
    /**
     * Nome tabella database.
     *
     * @var string
     */
    private $table_name;
    
    /**
     * Costruttore.
     *
     * @param string $nonce      Nome nonce.
     * @param string $table_name Nome tabella database.
     */
    public function __construct( $nonce, $table_name ) {
        $this->nonce      = $nonce;
        $this->table_name = $table_name;
    }
    
    /**
     * Inizializza l'handler registrando gli hooks AJAX.
     */
    public function init() {
        add_action( 'wp_ajax_cv_follow_dossier', [ $this, 'handle_follow' ] );
        add_action( 'wp_ajax_nopriv_cv_follow_dossier', [ $this, 'handle_follow' ] );
    }
    
    /**
     * Gestisce la richiesta di follow-up di un dossier.
     */
    public function handle_follow() {
        check_ajax_referer( $this->nonce, 'nonce' );
        
        $email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
        $dossier_id = isset( $_POST['dossier_id'] ) ? intval( wp_unslash( $_POST['dossier_id'] ) ) : 0;
        
        if ( ! $email || ! is_email( $email ) || ! $dossier_id ) {
            wp_send_json_error( [ 'message' => __( 'Dati non validi', 'cv-dossier' ) ], 400 );
        }
        
        $dossier = get_post( $dossier_id );
        if ( ! $dossier || 'cv_dossier' !== $dossier->post_type || 'publish' !== $dossier->post_status ) {
            wp_send_json_error( [ 'message' => __( 'Dossier non trovato', 'cv-dossier' ) ], 404 );
        }
        
        global $wpdb;
        $table = $wpdb->prefix . $this->table_name;
        $inserted = $wpdb->query( $wpdb->prepare(
            "INSERT IGNORE INTO {$table} (dossier_id, email) VALUES (%d, %s)", $dossier_id, $email
        ));
        
        /**
         * Hook per integrazione esterna (es. Brevo).
         *
         * @param int    $dossier_id ID del dossier.
         * @param string $email      Email del follower.
         */
        do_action( 'cv_dossier_follow', $dossier_id, $email );
        
        if ( $inserted === false ) {
            wp_send_json_error( [ 'message' => __( 'Errore di sistema', 'cv-dossier' ) ], 500 );
        } else {
            wp_send_json_success( [ 'message' => __( 'Ti avviseremo sugli aggiornamenti del dossier.', 'cv-dossier' ) ] );
        }
    }
}