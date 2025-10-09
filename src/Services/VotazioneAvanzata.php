<?php
/**
 * Service: Votazione Avanzata
 *
 * Gestisce il sistema di votazione ponderata con peso variabile
 *
 * @package CdV
 * @subpackage Services
 * @since 1.4.0
 */

namespace CdV\Services;

/**
 * Class VotazioneAvanzata
 */
class VotazioneAvanzata {
	/**
	 * Moltiplicatori peso voto
	 */
	const PESO_BASE = 1.0;
	const PESO_RESIDENTE_QUARTIERE = 2.0;
	const PESO_UTENTE_VERIFICATO = 1.5;
	const PESO_ANZIANITA_1_ANNO = 1.2;
	const PESO_ANZIANITA_2_ANNI = 1.5;

	/**
	 * Initialize
	 */
	public static function init(): void {
		// Override default vote handler
		add_filter( 'cdv_vote_weight', array( self::class, 'calculate_vote_weight' ), 10, 3 );
		add_action( 'cdv_after_vote', array( self::class, 'save_vote_details' ), 10, 3 );
		
		// Admin meta box per visualizzare dettagli voti
		add_action( 'add_meta_boxes', array( self::class, 'add_votes_details_meta_box' ) );
	}

	/**
	 * Calculate vote weight based on user
	 *
	 * @param float $weight     Current weight.
	 * @param int   $user_id    User ID.
	 * @param int   $proposta_id Proposta ID.
	 * @return float
	 */
	public static function calculate_vote_weight( float $weight, int $user_id, int $proposta_id ): float {
		if ( ! $user_id ) {
			return self::PESO_BASE; // Utente non loggato = peso base
		}

		$total_weight = self::PESO_BASE;

		// Check residenza quartiere
		if ( self::is_resident_in_quartiere( $user_id, $proposta_id ) ) {
			$total_weight *= self::PESO_RESIDENTE_QUARTIERE;
		}

		// Check utente verificato
		if ( self::is_verified_user( $user_id ) ) {
			$total_weight *= self::PESO_UTENTE_VERIFICATO;
		}

		// Check anzianità account
		$account_age = self::get_account_age_months( $user_id );
		if ( $account_age >= 24 ) {
			$total_weight *= self::PESO_ANZIANITA_2_ANNI;
		} elseif ( $account_age >= 12 ) {
			$total_weight *= self::PESO_ANZIANITA_1_ANNO;
		}

		return apply_filters( 'cdv_final_vote_weight', $total_weight, $user_id, $proposta_id );
	}

	/**
	 * Check if user is resident in proposta quartiere
	 *
	 * @param int $user_id    User ID.
	 * @param int $proposta_id Proposta ID.
	 * @return bool
	 */
	private static function is_resident_in_quartiere( int $user_id, int $proposta_id ): bool {
		$user_quartiere_id = intval( get_user_meta( $user_id, 'cdv_quartiere_residenza', true ) );
		
		if ( ! $user_quartiere_id ) {
			return false;
		}

		$proposta_quartieri = wp_get_post_terms( $proposta_id, 'cdv_quartiere', array( 'fields' => 'ids' ) );
		
		return in_array( $user_quartiere_id, $proposta_quartieri, true );
	}

	/**
	 * Check if user is verified
	 *
	 * @param int $user_id User ID.
	 * @return bool
	 */
	private static function is_verified_user( int $user_id ): bool {
		return (bool) get_user_meta( $user_id, 'cdv_verified', true );
	}

	/**
	 * Get account age in months
	 *
	 * @param int $user_id User ID.
	 * @return int
	 */
	private static function get_account_age_months( int $user_id ): int {
		$user = get_userdata( $user_id );
		
		if ( ! $user ) {
			return 0;
		}

		$registered = strtotime( $user->user_registered );
		$now = time();
		
		return intval( ( $now - $registered ) / ( 30 * 24 * 60 * 60 ) );
	}

	/**
	 * Save vote details with weight
	 *
	 * @param int   $proposta_id Proposta ID.
	 * @param int   $user_id     User ID.
	 * @param float $weight      Vote weight.
	 */
	public static function save_vote_details( int $proposta_id, int $user_id, float $weight ): void {
		global $wpdb;
		$table = $wpdb->prefix . 'cdv_voti_dettagli';

		// Create table if not exists
		$charset_collate = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE IF NOT EXISTS $table (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			proposta_id bigint(20) UNSIGNED NOT NULL,
			user_id bigint(20) UNSIGNED NOT NULL,
			weight float NOT NULL DEFAULT 1.0,
			is_resident tinyint(1) DEFAULT 0,
			is_verified tinyint(1) DEFAULT 0,
			account_age_months int(11) DEFAULT 0,
			motivazione text,
			ip_address varchar(100),
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY proposta_id (proposta_id),
			KEY user_id (user_id),
			UNIQUE KEY unique_vote (proposta_id, user_id)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		// Insert or update vote
		$wpdb->replace(
			$table,
			array(
				'proposta_id'       => $proposta_id,
				'user_id'           => $user_id,
				'weight'            => $weight,
				'is_resident'       => self::is_resident_in_quartiere( $user_id, $proposta_id ) ? 1 : 0,
				'is_verified'       => self::is_verified_user( $user_id ) ? 1 : 0,
				'account_age_months' => self::get_account_age_months( $user_id ),
				'ip_address'        => Security::get_client_ip(),
			),
			array( '%d', '%d', '%f', '%d', '%d', '%d', '%s' )
		);
	}

	/**
	 * Get vote weight for proposta
	 *
	 * @param int $proposta_id Proposta ID.
	 * @return float
	 */
	public static function get_proposta_weighted_votes( int $proposta_id ): float {
		global $wpdb;
		$table = $wpdb->prefix . 'cdv_voti_dettagli';

		$total = $wpdb->get_var( $wpdb->prepare(
			"SELECT SUM(weight) FROM $table WHERE proposta_id = %d",
			$proposta_id
		) );

		return $total ? floatval( $total ) : 0.0;
	}

	/**
	 * Get votes breakdown for proposta
	 *
	 * @param int $proposta_id Proposta ID.
	 * @return array
	 */
	public static function get_votes_breakdown( int $proposta_id ): array {
		global $wpdb;
		$table = $wpdb->prefix . 'cdv_voti_dettagli';

		$stats = $wpdb->get_row( $wpdb->prepare(
			"SELECT 
				COUNT(*) as total_voters,
				SUM(weight) as total_weight,
				SUM(is_resident) as residents,
				SUM(is_verified) as verified,
				AVG(weight) as avg_weight
			FROM $table 
			WHERE proposta_id = %d",
			$proposta_id
		), ARRAY_A );

		return $stats ?: array(
			'total_voters' => 0,
			'total_weight' => 0,
			'residents'    => 0,
			'verified'     => 0,
			'avg_weight'   => 0,
		);
	}

	/**
	 * Add meta box for votes details
	 */
	public static function add_votes_details_meta_box(): void {
		add_meta_box(
			'cdv_votes_details',
			__( 'Dettagli Voti Ponderati', 'cronaca-di-viterbo' ),
			array( self::class, 'render_votes_details_meta_box' ),
			'cdv_proposta',
			'side',
			'default'
		);
	}

	/**
	 * Render votes details meta box
	 *
	 * @param \WP_Post $post Post object.
	 */
	public static function render_votes_details_meta_box( $post ): void {
		$breakdown = self::get_votes_breakdown( $post->ID );
		$weighted_total = self::get_proposta_weighted_votes( $post->ID );
		$simple_votes = intval( get_post_meta( $post->ID, '_cdv_votes', true ) );

		?>
		<div class="cdv-votes-stats">
			<p>
				<strong><?php esc_html_e( 'Voti Semplici', 'cronaca-di-viterbo' ); ?>:</strong><br>
				<?php echo esc_html( number_format_i18n( $simple_votes ) ); ?>
			</p>
			<p>
				<strong><?php esc_html_e( 'Voti Ponderati', 'cronaca-di-viterbo' ); ?>:</strong><br>
				<?php echo esc_html( number_format_i18n( $weighted_total, 2 ) ); ?>
			</p>
			<p>
				<strong><?php esc_html_e( 'Peso Medio', 'cronaca-di-viterbo' ); ?>:</strong><br>
				<?php echo esc_html( number_format_i18n( floatval( $breakdown['avg_weight'] ), 2 ) ); ?>x
			</p>
			<hr>
			<p>
				<strong><?php esc_html_e( 'Breakdown', 'cronaca-di-viterbo' ); ?>:</strong>
			</p>
			<ul style="margin-left: 20px;">
				<li>👥 <?php echo esc_html( $breakdown['total_voters'] ); ?> votanti</li>
				<li>🏠 <?php echo esc_html( $breakdown['residents'] ); ?> residenti quartiere</li>
				<li>✅ <?php echo esc_html( $breakdown['verified'] ); ?> verificati</li>
			</ul>
		</div>
		<?php
	}

	/**
	 * Update vote handler to use weighted system
	 *
	 * @param int $proposta_id Proposta ID.
	 * @param int $user_id     User ID.
	 */
	public static function cast_weighted_vote( int $proposta_id, int $user_id ): void {
		$weight = apply_filters( 'cdv_vote_weight', 1.0, $user_id, $proposta_id );
		
		// Update simple count
		$current_votes = intval( get_post_meta( $proposta_id, '_cdv_votes', true ) );
		update_post_meta( $proposta_id, '_cdv_votes', $current_votes + 1 );

		// Update weighted count
		$current_weighted = floatval( get_post_meta( $proposta_id, '_cdv_weighted_votes', true ) );
		update_post_meta( $proposta_id, '_cdv_weighted_votes', $current_weighted + $weight );

		// Save details
		do_action( 'cdv_after_vote', $proposta_id, $user_id, $weight );
	}

	/**
	 * Get leaderboard by weighted votes
	 *
	 * @param array $args Query args.
	 * @return array
	 */
	public static function get_leaderboard( array $args = array() ): array {
		$defaults = array(
			'limit'     => 10,
			'quartiere' => '',
			'tematica'  => '',
		);

		$args = wp_parse_args( $args, $defaults );

		$query_args = array(
			'post_type'      => 'cdv_proposta',
			'post_status'    => 'publish',
			'posts_per_page' => intval( $args['limit'] ),
			'meta_key'       => '_cdv_weighted_votes',
			'orderby'        => 'meta_value_num',
			'order'          => 'DESC',
		);

		if ( ! empty( $args['quartiere'] ) ) {
			$query_args['tax_query'][] = array(
				'taxonomy' => 'cdv_quartiere',
				'field'    => 'slug',
				'terms'    => $args['quartiere'],
			);
		}

		if ( ! empty( $args['tematica'] ) ) {
			$query_args['tax_query'][] = array(
				'taxonomy' => 'cdv_tematica',
				'field'    => 'slug',
				'terms'    => $args['tematica'],
			);
		}

		return get_posts( $query_args );
	}
}
