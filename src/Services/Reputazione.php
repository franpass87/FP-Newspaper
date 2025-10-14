<?php
/**
 * Service: Reputazione & Gamification
 *
 * Gestisce il sistema di reputazione, badge e punti
 *
 * @package CdV
 * @subpackage Services
 * @since 1.3.0
 */

namespace CdV\Services;

/**
 * Class Reputazione
 */
class Reputazione {
	/**
	 * Livelli utente
	 */
	const LIVELLO_CITTADINO = 1;
	const LIVELLO_ATTIVISTA = 2;
	const LIVELLO_LEADER = 3;
	const LIVELLO_AMBASCIATORE = 4;

	/**
	 * Badge disponibili
	 */
	private static $badges = array(
		'primo_cittadino'        => array(
			'name'        => 'Primo Cittadino',
			'description' => 'Prima proposta pubblicata',
			'icon'        => '🎯',
			'points'      => 10,
		),
		'guardiano_quartiere'    => array(
			'name'        => 'Guardiano del Quartiere',
			'description' => '10+ proposte pubblicate',
			'icon'        => '🏘️',
			'points'      => 50,
		),
		'voce_popolare'          => array(
			'name'        => 'Voce Popolare',
			'description' => '100+ voti ricevuti',
			'icon'        => '📢',
			'points'      => 100,
		),
		'attivista'              => array(
			'name'        => 'Attivista',
			'description' => 'Partecipato a 5+ eventi',
			'icon'        => '✊',
			'points'      => 75,
		),
		'firmatario'             => array(
			'name'        => 'Firmatario Seriale',
			'description' => 'Firmato 10+ petizioni',
			'icon'        => '✍️',
			'points'      => 40,
		),
		'democratico'            => array(
			'name'        => 'Democratico',
			'description' => 'Votato in 20+ sondaggi',
			'icon'        => '🗳️',
			'points'      => 60,
		),
		'influencer'             => array(
			'name'        => 'Influencer Civico',
			'description' => 'Proposta con 500+ voti',
			'icon'        => '⭐',
			'points'      => 200,
		),
		'pioniere'               => array(
			'name'        => 'Pioniere',
			'description' => 'Tra i primi 100 utenti',
			'icon'        => '🚀',
			'points'      => 25,
		),
	);

	/**
	 * Initialize hooks
	 */
	public static function init(): void {
		// Proposta pubblicata
		add_action( 'pending_to_publish', array( self::class, 'on_proposta_pubblicata' ), 10, 1 );

		// Voto ricevuto
		add_action( 'cdv_proposta_voted', array( self::class, 'on_voto_ricevuto' ), 10, 2 );

		// Petizione firmata
		add_action( 'cdv_petizione_firmata', array( self::class, 'on_petizione_firmata' ), 10, 3 );

		// Sondaggio votato
		add_action( 'cdv_sondaggio_votato', array( self::class, 'on_sondaggio_votato' ), 10, 3 );

		// Evento partecipato (futuro: check-in QR)
		add_action( 'cdv_evento_partecipato', array( self::class, 'on_evento_partecipato' ), 10, 2 );

		// Show badges in profile
		add_action( 'show_user_profile', array( self::class, 'show_user_badges' ) );
		add_action( 'edit_user_profile', array( self::class, 'show_user_badges' ) );
	}

	/**
	 * On proposta pubblicata
	 *
	 * @param \WP_Post $post Post object.
	 */
	public static function on_proposta_pubblicata( $post ): void {
		if ( $post->post_type !== 'cdv_proposta' ) {
			return;
		}

		$user_id = $post->post_author;

		// Add points
		self::add_points( $user_id, 50, 'Proposta pubblicata: ' . $post->post_title );

		// Check badge "Primo Cittadino"
		$proposte_count = count_user_posts( $user_id, 'cdv_proposta', true );
		if ( $proposte_count === 1 ) {
			self::award_badge( $user_id, 'primo_cittadino' );
		}

		// Check badge "Guardiano del Quartiere"
		if ( $proposte_count >= 10 ) {
			self::award_badge( $user_id, 'guardiano_quartiere' );
		}

		// Update livello
		self::update_user_level( $user_id );
	}

	/**
	 * On voto ricevuto
	 *
	 * @param int $post_id    Post ID.
	 * @param int $new_votes  New votes count.
	 */
	public static function on_voto_ricevuto( int $post_id, int $new_votes ): void {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return;
		}

		$user_id = $post->post_author;

		// Add points
		self::add_points( $user_id, 5, 'Voto ricevuto sulla proposta: ' . $post->post_title );

		// Check badge "Voce Popolare"
		$total_votes = self::get_total_votes_received( $user_id );
		if ( $total_votes >= 100 ) {
			self::award_badge( $user_id, 'voce_popolare' );
		}

		// Check badge "Influencer"
		if ( $new_votes >= 500 ) {
			self::award_badge( $user_id, 'influencer' );
		}

		self::update_user_level( $user_id );
	}

	/**
	 * On petizione firmata
	 *
	 * @param int    $petizione_id Petizione ID.
	 * @param string $email        Email.
	 * @param int    $user_id      User ID.
	 */
	public static function on_petizione_firmata( int $petizione_id, string $email, int $user_id ): void {
		if ( ! $user_id ) {
			return;
		}

		self::add_points( $user_id, 10, 'Firma petizione #' . $petizione_id );

		// Check badge
		$firme_count = self::get_petizioni_firmate_count( $user_id );
		if ( $firme_count >= 10 ) {
			self::award_badge( $user_id, 'firmatario' );
		}

		self::update_user_level( $user_id );
	}

	/**
	 * On sondaggio votato
	 *
	 * @param int   $sondaggio_id Sondaggio ID.
	 * @param array $options      Options.
	 * @param int   $user_id      User ID.
	 */
	public static function on_sondaggio_votato( int $sondaggio_id, array $options, int $user_id ): void {
		if ( ! $user_id ) {
			return;
		}

		self::add_points( $user_id, 5, 'Voto sondaggio #' . $sondaggio_id );

		// Check badge
		$sondaggi_count = self::get_sondaggi_votati_count( $user_id );
		if ( $sondaggi_count >= 20 ) {
			self::award_badge( $user_id, 'democratico' );
		}

		self::update_user_level( $user_id );
	}

	/**
	 * On evento partecipato
	 *
	 * @param int $evento_id Evento ID.
	 * @param int $user_id   User ID.
	 */
	public static function on_evento_partecipato( int $evento_id, int $user_id ): void {
		self::add_points( $user_id, 20, 'Partecipazione evento #' . $evento_id );

		// Check badge
		$eventi_count = self::get_eventi_partecipati_count( $user_id );
		if ( $eventi_count >= 5 ) {
			self::award_badge( $user_id, 'attivista' );
		}

		self::update_user_level( $user_id );
	}

	/**
	 * Add points to user
	 *
	 * @param int    $user_id User ID.
	 * @param int    $points  Points.
	 * @param string $reason  Reason.
	 */
	public static function add_points( int $user_id, int $points, string $reason = '' ): void {
		global $wpdb;
		
		// Usa un UPDATE atomico per evitare race conditions
		$meta_exists = $wpdb->get_var( $wpdb->prepare(
			"SELECT umeta_id FROM {$wpdb->usermeta} WHERE user_id = %d AND meta_key = 'cdv_points'",
			$user_id
		) );
		
		if ( ! $meta_exists ) {
			// Prima volta, crea il meta
			add_user_meta( $user_id, 'cdv_points', $points, true );
			$new_points = $points;
		} else {
			// UPDATE atomico
			$wpdb->query( $wpdb->prepare(
				"UPDATE {$wpdb->usermeta} SET meta_value = CAST(meta_value AS UNSIGNED) + %d WHERE user_id = %d AND meta_key = 'cdv_points'",
				$points,
				$user_id
			) );
			
			// Recupera il nuovo valore
			$new_points = intval( get_user_meta( $user_id, 'cdv_points', true ) );
		}

		// Log activity
		$log = get_user_meta( $user_id, 'cdv_points_log', true ) ?: array();
		$log[] = array(
			'points' => $points,
			'reason' => $reason,
			'date'   => current_time( 'mysql' ),
		);

		// Keep only last 100 entries
		if ( count( $log ) > 100 ) {
			$log = array_slice( $log, -100 );
		}

		update_user_meta( $user_id, 'cdv_points_log', $log );

		do_action( 'cdv_points_added', $user_id, $points, $new_points );
	}

	/**
	 * Award badge to user
	 *
	 * @param int    $user_id User ID.
	 * @param string $badge   Badge slug.
	 */
	public static function award_badge( int $user_id, string $badge ): void {
		$badges = get_user_meta( $user_id, 'cdv_badges', true ) ?: array();

		if ( in_array( $badge, $badges, true ) ) {
			return; // Already has it
		}

		$badges[] = $badge;
		update_user_meta( $user_id, 'cdv_badges', $badges );

		// Add badge points
		if ( isset( self::$badges[ $badge ] ) ) {
			self::add_points( $user_id, self::$badges[ $badge ]['points'], 'Badge ottenuto: ' . self::$badges[ $badge ]['name'] );
		}

		do_action( 'cdv_badge_awarded', $user_id, $badge );

		// TODO: Send notification
	}

	/**
	 * Update user level based on points
	 *
	 * @param int $user_id User ID.
	 */
	public static function update_user_level( int $user_id ): void {
		$points = intval( get_user_meta( $user_id, 'cdv_points', true ) );
		$current_level = intval( get_user_meta( $user_id, 'cdv_level', true ) );

		$new_level = self::LIVELLO_CITTADINO;

		if ( $points >= 2000 ) {
			$new_level = self::LIVELLO_AMBASCIATORE;
		} elseif ( $points >= 500 ) {
			$new_level = self::LIVELLO_LEADER;
		} elseif ( $points >= 100 ) {
			$new_level = self::LIVELLO_ATTIVISTA;
		}

		if ( $new_level !== $current_level ) {
			update_user_meta( $user_id, 'cdv_level', $new_level );
			do_action( 'cdv_level_up', $user_id, $new_level, $current_level );
		}
	}

	/**
	 * Get user level label
	 *
	 * @param int $user_id User ID.
	 * @return string
	 */
	public static function get_user_level_label( int $user_id ): string {
		$level = intval( get_user_meta( $user_id, 'cdv_level', true ) );

		$labels = array(
			self::LIVELLO_CITTADINO    => __( 'Cittadino', 'cronaca-di-viterbo' ),
			self::LIVELLO_ATTIVISTA    => __( 'Attivista', 'cronaca-di-viterbo' ),
			self::LIVELLO_LEADER       => __( 'Leader', 'cronaca-di-viterbo' ),
			self::LIVELLO_AMBASCIATORE => __( 'Ambasciatore', 'cronaca-di-viterbo' ),
		);

		return $labels[ $level ] ?? $labels[ self::LIVELLO_CITTADINO ];
	}

	/**
	 * Get total votes received by user
	 *
	 * @param int $user_id User ID.
	 * @return int
	 */
	private static function get_total_votes_received( int $user_id ): int {
		$proposte = get_posts( array(
			'post_type'      => 'cdv_proposta',
			'author'         => $user_id,
			'posts_per_page' => -1,
			'fields'         => 'ids',
		) );

		$total = 0;
		foreach ( $proposte as $proposta_id ) {
			$total += intval( get_post_meta( $proposta_id, '_cdv_votes', true ) );
		}

		return $total;
	}

	/**
	 * Get petizioni firmate count
	 *
	 * @param int $user_id User ID.
	 * @return int
	 */
	private static function get_petizioni_firmate_count( int $user_id ): int {
		global $wpdb;
		$table = $wpdb->prefix . 'cdv_petizioni_firme';

		return intval( $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM `{$table}` WHERE user_id = %d",
			$user_id
		) ) );
	}

	/**
	 * Get sondaggi votati count
	 *
	 * @param int $user_id User ID.
	 * @return int
	 */
	private static function get_sondaggi_votati_count( int $user_id ): int {
		global $wpdb;
		$table = $wpdb->prefix . 'cdv_sondaggi_voti';

		return intval( $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(DISTINCT sondaggio_id) FROM `{$table}` WHERE user_id = %d",
			$user_id
		) ) );
	}

	/**
	 * Get eventi partecipati count
	 *
	 * @param int $user_id User ID.
	 * @return int
	 */
	private static function get_eventi_partecipati_count( int $user_id ): int {
		// TODO: Implementare check-in eventi
		return 0;
	}

	/**
	 * Show user badges in profile
	 *
	 * @param \WP_User $user User object.
	 */
	public static function show_user_badges( $user ): void {
		$points = intval( get_user_meta( $user->ID, 'cdv_points', true ) );
		$level = self::get_user_level_label( $user->ID );
		$badges = get_user_meta( $user->ID, 'cdv_badges', true ) ?: array();

		?>
		<h2><?php esc_html_e( 'Reputazione Cronaca di Viterbo', 'cronaca-di-viterbo' ); ?></h2>
		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'Punti', 'cronaca-di-viterbo' ); ?></th>
				<td><strong style="font-size: 20px; color: #1e73be;"><?php echo esc_html( number_format_i18n( $points ) ); ?></strong></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Livello', 'cronaca-di-viterbo' ); ?></th>
				<td><strong><?php echo esc_html( $level ); ?></strong></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Badge Ottenuti', 'cronaca-di-viterbo' ); ?></th>
				<td>
					<?php if ( ! empty( $badges ) ) : ?>
						<div class="cdv-badges">
							<?php foreach ( $badges as $badge_slug ) : ?>
								<?php if ( isset( self::$badges[ $badge_slug ] ) ) : ?>
									<?php $badge = self::$badges[ $badge_slug ]; ?>
									<span class="cdv-badge" title="<?php echo esc_attr( $badge['description'] ); ?>" style="display: inline-block; margin: 5px; padding: 8px 12px; background: #f0f0f0; border-radius: 20px;">
										<span style="font-size: 18px;"><?php echo $badge['icon']; ?></span>
										<strong><?php echo esc_html( $badge['name'] ); ?></strong>
										<small>(+<?php echo esc_html( $badge['points'] ); ?> punti)</small>
									</span>
								<?php endif; ?>
							<?php endforeach; ?>
						</div>
					<?php else : ?>
						<em><?php esc_html_e( 'Nessun badge ancora', 'cronaca-di-viterbo' ); ?></em>
					<?php endif; ?>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Get all badges
	 *
	 * @return array
	 */
	public static function get_all_badges(): array {
		return self::$badges;
	}
}
