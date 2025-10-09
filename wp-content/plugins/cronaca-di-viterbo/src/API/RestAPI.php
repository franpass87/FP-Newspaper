<?php
/**
 * REST API Endpoints
 *
 * @package CdV
 * @subpackage API
 * @since 1.6.0
 */

namespace CdV\API;

/**
 * Class RestAPI
 */
class RestAPI {
	/**
	 * Namespace
	 */
	const NAMESPACE = 'cdv/v1';

	/**
	 * Initialize
	 */
	public static function init(): void {
		add_action( 'rest_api_init', array( self::class, 'register_routes' ) );
	}

	/**
	 * Register REST routes
	 */
	public static function register_routes(): void {
		// Get proposte
		register_rest_route(
			self::NAMESPACE,
			'/proposte',
			array(
				'methods'             => 'GET',
				'callback'            => array( self::class, 'get_proposte' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'limit'     => array(
						'default'           => 10,
						'sanitize_callback' => 'absint',
					),
					'quartiere' => array(
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'orderby'   => array(
						'default'           => 'votes',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		// Get petizioni
		register_rest_route(
			self::NAMESPACE,
			'/petizioni',
			array(
				'methods'             => 'GET',
				'callback'            => array( self::class, 'get_petizioni' ),
				'permission_callback' => '__return_true',
			)
		);

		// Get sondaggi
		register_rest_route(
			self::NAMESPACE,
			'/sondaggi',
			array(
				'methods'             => 'GET',
				'callback'            => array( self::class, 'get_sondaggi' ),
				'permission_callback' => '__return_true',
			)
		);

		// Get stats
		register_rest_route(
			self::NAMESPACE,
			'/stats',
			array(
				'methods'             => 'GET',
				'callback'            => array( self::class, 'get_stats' ),
				'permission_callback' => '__return_true',
			)
		);

		// Get user profile
		register_rest_route(
			self::NAMESPACE,
			'/user/(?P<id>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( self::class, 'get_user_profile' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'id' => array(
						'validate_callback' => function( $param ) {
							return is_numeric( $param );
						},
					),
				),
			)
		);

		// Submit firma petizione (POST)
		register_rest_route(
			self::NAMESPACE,
			'/petizioni/(?P<id>\d+)/firma',
			array(
				'methods'             => 'POST',
				'callback'            => array( self::class, 'firma_petizione' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'nome'    => array( 'required' => true ),
					'cognome' => array( 'required' => true ),
					'email'   => array( 'required' => true ),
				),
			)
		);
	}

	/**
	 * Get proposte
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public static function get_proposte( $request ) {
		$limit = $request->get_param( 'limit' );
		$quartiere = $request->get_param( 'quartiere' );
		$orderby = $request->get_param( 'orderby' );

		$args = array(
			'post_type'      => 'cdv_proposta',
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
		);

		if ( $orderby === 'votes' ) {
			$args['meta_key'] = '_cdv_votes';
			$args['orderby'] = 'meta_value_num';
			$args['order'] = 'DESC';
		}

		if ( ! empty( $quartiere ) ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'cdv_quartiere',
					'field'    => 'slug',
					'terms'    => $quartiere,
				),
			);
		}

		$proposte = get_posts( $args );
		$data = array();

		foreach ( $proposte as $proposta ) {
			$voti = intval( get_post_meta( $proposta->ID, '_cdv_votes', true ) );
			$quartieri = wp_get_post_terms( $proposta->ID, 'cdv_quartiere', array( 'fields' => 'names' ) );
			$tematiche = wp_get_post_terms( $proposta->ID, 'cdv_tematica', array( 'fields' => 'names' ) );

			$data[] = array(
				'id'        => $proposta->ID,
				'title'     => $proposta->post_title,
				'excerpt'   => get_the_excerpt( $proposta ),
				'content'   => apply_filters( 'the_content', $proposta->post_content ),
				'voti'      => $voti,
				'quartiere' => ! empty( $quartieri ) ? $quartieri[0] : '',
				'tematica'  => ! empty( $tematiche ) ? $tematiche[0] : '',
				'author'    => get_the_author_meta( 'display_name', $proposta->post_author ),
				'date'      => get_the_date( 'c', $proposta ),
				'link'      => get_permalink( $proposta ),
			);
		}

		return new \WP_REST_Response( $data, 200 );
	}

	/**
	 * Get petizioni
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public static function get_petizioni( $request ) {
		$petizioni = get_posts( array(
			'post_type'      => 'cdv_petizione',
			'post_status'    => 'publish',
			'posts_per_page' => 20,
		) );

		$data = array();

		foreach ( $petizioni as $petizione ) {
			$firme = intval( get_post_meta( $petizione->ID, '_cdv_firme_count', true ) );
			$soglia = intval( get_post_meta( $petizione->ID, '_cdv_soglia_firme', true ) );
			$aperta = get_post_meta( $petizione->ID, '_cdv_aperta', true ) !== '0';

			$data[] = array(
				'id'          => $petizione->ID,
				'title'       => $petizione->post_title,
				'excerpt'     => get_the_excerpt( $petizione ),
				'firme'       => $firme,
				'soglia'      => $soglia,
				'percentuale' => $soglia > 0 ? round( ( $firme / $soglia ) * 100, 1 ) : 0,
				'aperta'      => $aperta,
				'link'        => get_permalink( $petizione ),
			);
		}

		return new \WP_REST_Response( $data, 200 );
	}

	/**
	 * Get sondaggi
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public static function get_sondaggi( $request ) {
		$sondaggi = get_posts( array(
			'post_type'      => 'cdv_sondaggio',
			'post_status'    => 'publish',
			'posts_per_page' => 20,
		) );

		$data = array();

		foreach ( $sondaggi as $sondaggio ) {
			$options = get_post_meta( $sondaggio->ID, '_cdv_options', true ) ?: array();
			$aperto = get_post_meta( $sondaggio->ID, '_cdv_aperto', true ) !== '0';

			global $wpdb;
			$table = $wpdb->prefix . 'cdv_sondaggi_voti';
			$total = $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(DISTINCT user_identifier) FROM $table WHERE sondaggio_id = %d",
				$sondaggio->ID
			) );

			$data[] = array(
				'id'            => $sondaggio->ID,
				'title'         => $sondaggio->post_title,
				'options'       => $options,
				'aperto'        => $aperto,
				'partecipanti'  => intval( $total ),
				'link'          => get_permalink( $sondaggio ),
			);
		}

		return new \WP_REST_Response( $data, 200 );
	}

	/**
	 * Get stats
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public static function get_stats( $request ) {
		global $wpdb;

		$stats = array(
			'proposte'       => wp_count_posts( 'cdv_proposta' )->publish,
			'petizioni'      => wp_count_posts( 'cdv_petizione' )->publish,
			'sondaggi'       => wp_count_posts( 'cdv_sondaggio' )->publish,
			'eventi'         => wp_count_posts( 'cdv_evento' )->publish,
			'firme_totali'   => intval( $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}cdv_petizioni_firme" ) ),
			'voti_totali'    => intval( $wpdb->get_var( "SELECT SUM(CAST(meta_value AS UNSIGNED)) FROM {$wpdb->postmeta} WHERE meta_key = '_cdv_votes'" ) ),
		);

		return new \WP_REST_Response( $stats, 200 );
	}

	/**
	 * Get user profile
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public static function get_user_profile( $request ) {
		$user_id = intval( $request->get_param( 'id' ) );

		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return new \WP_Error( 'user_not_found', __( 'Utente non trovato', 'cronaca-di-viterbo' ), array( 'status' => 404 ) );
		}

		$points = intval( get_user_meta( $user_id, 'cdv_points', true ) );
		$level = intval( get_user_meta( $user_id, 'cdv_level', true ) );
		$badges = get_user_meta( $user_id, 'cdv_badges', true ) ?: array();

		$data = array(
			'id'            => $user_id,
			'name'          => $user->display_name,
			'avatar'        => get_avatar_url( $user_id, array( 'size' => 96 ) ),
			'points'        => $points,
			'level'         => $level,
			'level_label'   => \CdV\Services\Reputazione::get_user_level_label( $user_id ),
			'badges'        => $badges,
			'proposte_count' => count_user_posts( $user_id, 'cdv_proposta', true ),
		);

		return new \WP_REST_Response( $data, 200 );
	}

	/**
	 * Firma petizione via REST
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public static function firma_petizione( $request ) {
		// Delegate to AJAX handler
		$_POST['petizione_id'] = $request->get_param( 'id' );
		$_POST['nome'] = $request->get_param( 'nome' );
		$_POST['cognome'] = $request->get_param( 'cognome' );
		$_POST['email'] = $request->get_param( 'email' );
		$_POST['comune'] = $request->get_param( 'comune' );
		$_POST['motivazione'] = $request->get_param( 'motivazione' );
		$_POST['privacy'] = 'on';
		$_POST['nonce'] = wp_create_nonce( 'cdv_ajax_nonce' );

		ob_start();
		\CdV\Ajax\FirmaPetizione::handle();
		$response = ob_get_clean();

		$decoded = json_decode( $response, true );

		if ( isset( $decoded['success'] ) && $decoded['success'] ) {
			return new \WP_REST_Response( $decoded['data'], 200 );
		}

		return new \WP_Error( 'firma_failed', $decoded['data']['message'] ?? 'Errore', array( 'status' => 400 ) );
	}
}
