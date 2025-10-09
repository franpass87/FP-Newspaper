<?php
/**
 * Admin Dashboard Analytics
 *
 * Dashboard pubblica con statistiche trasparenza
 *
 * @package CdV
 * @subpackage Admin
 * @since 1.2.0
 */

namespace CdV\Admin;

/**
 * Class Dashboard
 */
class Dashboard {
	/**
	 * Initialize
	 */
	public static function init(): void {
		add_action( 'admin_menu', array( self::class, 'add_menu_page' ) );
		add_shortcode( 'cdv_dashboard', array( self::class, 'render_public_dashboard' ) );
	}

	/**
	 * Add menu page
	 */
	public static function add_menu_page(): void {
		add_menu_page(
			__( 'Dashboard CdV', 'cronaca-di-viterbo' ),
			__( 'Dashboard', 'cronaca-di-viterbo' ),
			'read',
			'cdv-dashboard',
			array( self::class, 'render_admin_page' ),
			'dashicons-chart-area',
			3
		);
	}

	/**
	 * Render admin page
	 */
	public static function render_admin_page(): void {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Dashboard Cronaca di Viterbo', 'cronaca-di-viterbo' ); ?></h1>
			<?php echo do_shortcode( '[cdv_dashboard]' ); ?>
		</div>
		<?php
	}

	/**
	 * Render public dashboard shortcode
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public static function render_public_dashboard( $atts ): string {
		$atts = shortcode_atts(
			array(
				'periodo' => '30', // giorni
			),
			$atts
		);

		$periodo = intval( $atts['periodo'] );
		$stats = self::get_stats( $periodo );

		ob_start();
		?>
		<div class="cdv-dashboard">
			<div class="cdv-dashboard-header">
				<h2><?php esc_html_e( 'Statistiche Trasparenza', 'cronaca-di-viterbo' ); ?></h2>
				<p class="cdv-dashboard-subtitle">
					<?php echo esc_html( sprintf( __( 'Ultimi %d giorni', 'cronaca-di-viterbo' ), $periodo ) ); ?>
				</p>
			</div>

			<div class="cdv-stats-grid">
				<div class="cdv-stat-card">
					<div class="cdv-stat-icon">📝</div>
					<div class="cdv-stat-content">
						<div class="cdv-stat-value"><?php echo esc_html( number_format_i18n( $stats['proposte_totali'] ) ); ?></div>
						<div class="cdv-stat-label"><?php esc_html_e( 'Proposte Totali', 'cronaca-di-viterbo' ); ?></div>
						<div class="cdv-stat-change">
							+<?php echo esc_html( number_format_i18n( $stats['proposte_periodo'] ) ); ?> <?php esc_html_e( 'nel periodo', 'cronaca-di-viterbo' ); ?>
						</div>
					</div>
				</div>

				<div class="cdv-stat-card">
					<div class="cdv-stat-icon">✅</div>
					<div class="cdv-stat-content">
						<div class="cdv-stat-value"><?php echo esc_html( $stats['tasso_accettazione'] ); ?>%</div>
						<div class="cdv-stat-label"><?php esc_html_e( 'Tasso Accettazione', 'cronaca-di-viterbo' ); ?></div>
						<div class="cdv-stat-change">
							<?php echo esc_html( number_format_i18n( $stats['proposte_accettate'] ) ); ?> accettate
						</div>
					</div>
				</div>

				<div class="cdv-stat-card">
					<div class="cdv-stat-icon">✍️</div>
					<div class="cdv-stat-content">
						<div class="cdv-stat-value"><?php echo esc_html( number_format_i18n( $stats['firme_petizioni'] ) ); ?></div>
						<div class="cdv-stat-label"><?php esc_html_e( 'Firme Petizioni', 'cronaca-di-viterbo' ); ?></div>
						<div class="cdv-stat-change">
							<?php echo esc_html( number_format_i18n( $stats['petizioni_attive'] ) ); ?> attive
						</div>
					</div>
				</div>

				<div class="cdv-stat-card">
					<div class="cdv-stat-icon">👥</div>
					<div class="cdv-stat-content">
						<div class="cdv-stat-value"><?php echo esc_html( number_format_i18n( $stats['utenti_attivi'] ) ); ?></div>
						<div class="cdv-stat-label"><?php esc_html_e( 'Cittadini Attivi', 'cronaca-di-viterbo' ); ?></div>
						<div class="cdv-stat-change">
							+<?php echo esc_html( number_format_i18n( $stats['nuovi_utenti'] ) ); ?> nuovi
						</div>
					</div>
				</div>

				<div class="cdv-stat-card">
					<div class="cdv-stat-icon">🗳️</div>
					<div class="cdv-stat-content">
						<div class="cdv-stat-value"><?php echo esc_html( number_format_i18n( $stats['voti_totali'] ) ); ?></div>
						<div class="cdv-stat-label"><?php esc_html_e( 'Voti Totali', 'cronaca-di-viterbo' ); ?></div>
					</div>
				</div>

				<div class="cdv-stat-card">
					<div class="cdv-stat-icon">📊</div>
					<div class="cdv-stat-content">
						<div class="cdv-stat-value"><?php echo esc_html( $stats['tempo_medio_risposta'] ); ?></div>
						<div class="cdv-stat-label"><?php esc_html_e( 'Tempo Medio Risposta', 'cronaca-di-viterbo' ); ?></div>
					</div>
				</div>
			</div>

			<div class="cdv-dashboard-section">
				<h3><?php esc_html_e( 'Top Quartieri per Partecipazione', 'cronaca-di-viterbo' ); ?></h3>
				<div class="cdv-chart-container">
					<?php self::render_quartieri_chart( $stats['top_quartieri'] ); ?>
				</div>
			</div>

			<div class="cdv-dashboard-section">
				<h3><?php esc_html_e( 'Tematiche Più Discusse', 'cronaca-di-viterbo' ); ?></h3>
				<div class="cdv-chart-container">
					<?php self::render_tematiche_chart( $stats['top_tematiche'] ); ?>
				</div>
			</div>

			<div class="cdv-dashboard-section">
				<h3><?php esc_html_e( 'Proposte in Evidenza', 'cronaca-di-viterbo' ); ?></h3>
				<?php self::render_top_proposte( $stats['top_proposte'] ); ?>
			</div>

			<div class="cdv-dashboard-section">
				<h3><?php esc_html_e( 'Risposte Amministrazione Recenti', 'cronaca-di-viterbo' ); ?></h3>
				<?php self::render_risposte_recenti( $stats['risposte_recenti'] ); ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get statistics
	 *
	 * @param int $periodo Period in days.
	 * @return array
	 */
	private static function get_stats( int $periodo ): array {
		$date_query = array(
			'after' => $periodo . ' days ago',
		);

		// Proposte
		$proposte_totali = wp_count_posts( 'cdv_proposta' )->publish;
		$proposte_periodo = get_posts( array(
			'post_type'      => 'cdv_proposta',
			'post_status'    => 'publish',
			'date_query'     => $date_query,
			'posts_per_page' => -1,
			'fields'         => 'ids',
		) );

		// Proposte con risposta amministrazione
		$proposte_con_risposta = self::count_proposte_con_risposta();
		$proposte_accettate = self::count_proposte_accettate();
		$tasso_accettazione = $proposte_totali > 0 ? round( ( $proposte_accettate / $proposte_totali ) * 100, 1 ) : 0;

		// Petizioni
		$firme_petizioni = self::count_total_firme();
		$petizioni_attive = self::count_petizioni_attive();

		// Utenti
		$utenti_attivi = self::count_utenti_attivi( $periodo );
		$nuovi_utenti = count_users( array( 'who' => 'time' ) ); // Semplificato

		// Voti
		$voti_totali = self::count_total_voti();

		// Tempo medio risposta
		$tempo_medio = self::calcola_tempo_medio_risposta();

		// Top data
		$top_quartieri = self::get_top_quartieri( $periodo );
		$top_tematiche = self::get_top_tematiche( $periodo );
		$top_proposte = self::get_top_proposte( $periodo );
		$risposte_recenti = self::get_risposte_recenti( 5 );

		return array(
			'proposte_totali'        => $proposte_totali,
			'proposte_periodo'       => count( $proposte_periodo ),
			'proposte_accettate'     => $proposte_accettate,
			'tasso_accettazione'     => $tasso_accettazione,
			'firme_petizioni'        => $firme_petizioni,
			'petizioni_attive'       => $petizioni_attive,
			'utenti_attivi'          => $utenti_attivi,
			'nuovi_utenti'           => 0, // TODO
			'voti_totali'            => $voti_totali,
			'tempo_medio_risposta'   => $tempo_medio,
			'top_quartieri'          => $top_quartieri,
			'top_tematiche'          => $top_tematiche,
			'top_proposte'           => $top_proposte,
			'risposte_recenti'       => $risposte_recenti,
		);
	}

	/**
	 * Count proposte con risposta
	 *
	 * @return int
	 */
	private static function count_proposte_con_risposta(): int {
		global $wpdb;
		return intval( $wpdb->get_var(
			"SELECT COUNT(DISTINCT meta_value) 
			FROM {$wpdb->postmeta} 
			WHERE meta_key = '_cdv_proposta_id' 
			AND post_id IN (SELECT ID FROM {$wpdb->posts} WHERE post_type = 'cdv_risposta_amm' AND post_status = 'publish')"
		) );
	}

	/**
	 * Count proposte accettate
	 *
	 * @return int
	 */
	private static function count_proposte_accettate(): int {
		global $wpdb;
		return intval( $wpdb->get_var(
			"SELECT COUNT(DISTINCT pm1.meta_value) 
			FROM {$wpdb->postmeta} pm1
			INNER JOIN {$wpdb->postmeta} pm2 ON pm1.post_id = pm2.post_id
			WHERE pm1.meta_key = '_cdv_proposta_id'
			AND pm2.meta_key = '_cdv_status'
			AND pm2.meta_value IN ('accettata', 'in_corso', 'completata')"
		) );
	}

	/**
	 * Count total firme
	 *
	 * @return int
	 */
	private static function count_total_firme(): int {
		global $wpdb;
		$table = $wpdb->prefix . 'cdv_petizioni_firme';
		return intval( $wpdb->get_var( "SELECT COUNT(*) FROM $table" ) );
	}

	/**
	 * Count petizioni attive
	 *
	 * @return int
	 */
	private static function count_petizioni_attive(): int {
		return count( get_posts( array(
			'post_type'      => 'cdv_petizione',
			'post_status'    => 'publish',
			'meta_query'     => array(
				array(
					'key'     => '_cdv_aperta',
					'value'   => '0',
					'compare' => '!=',
				),
			),
			'posts_per_page' => -1,
			'fields'         => 'ids',
		) ) );
	}

	/**
	 * Count utenti attivi
	 *
	 * @param int $periodo Period.
	 * @return int
	 */
	private static function count_utenti_attivi( int $periodo ): int {
		// Utenti che hanno fatto almeno un'azione nel periodo
		global $wpdb;
		$date = date( 'Y-m-d H:i:s', strtotime( "-$periodo days" ) );
		
		return intval( $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(DISTINCT post_author) 
			FROM {$wpdb->posts} 
			WHERE post_type IN ('cdv_proposta', 'cdv_petizione', 'cdv_sondaggio')
			AND post_date >= %s",
			$date
		) ) );
	}

	/**
	 * Count total voti
	 *
	 * @return int
	 */
	private static function count_total_voti(): int {
		global $wpdb;
		return intval( $wpdb->get_var(
			"SELECT SUM(CAST(meta_value AS UNSIGNED)) 
			FROM {$wpdb->postmeta} 
			WHERE meta_key = '_cdv_votes'"
		) );
	}

	/**
	 * Calcola tempo medio risposta
	 *
	 * @return string
	 */
	private static function calcola_tempo_medio_risposta(): string {
		global $wpdb;
		
		$avg_days = $wpdb->get_var(
			"SELECT AVG(DATEDIFF(r.post_date, p.post_date))
			FROM {$wpdb->posts} r
			INNER JOIN {$wpdb->postmeta} pm ON r.ID = pm.post_id
			INNER JOIN {$wpdb->posts} p ON pm.meta_value = p.ID
			WHERE r.post_type = 'cdv_risposta_amm'
			AND pm.meta_key = '_cdv_proposta_id'
			AND r.post_status = 'publish'
			AND p.post_status = 'publish'"
		);

		if ( ! $avg_days ) {
			return __( 'N/D', 'cronaca-di-viterbo' );
		}

		$days = intval( $avg_days );
		return sprintf( _n( '%d giorno', '%d giorni', $days, 'cronaca-di-viterbo' ), $days );
	}

	/**
	 * Get top quartieri
	 *
	 * @param int $periodo Period.
	 * @return array
	 */
	private static function get_top_quartieri( int $periodo ): array {
		$terms = get_terms( array(
			'taxonomy'   => 'cdv_quartiere',
			'hide_empty' => true,
			'number'     => 5,
		) );

		$data = array();
		foreach ( $terms as $term ) {
			$count = $term->count;
			$data[] = array(
				'name'  => $term->name,
				'count' => $count,
			);
		}

		return $data;
	}

	/**
	 * Get top tematiche
	 *
	 * @param int $periodo Period.
	 * @return array
	 */
	private static function get_top_tematiche( int $periodo ): array {
		$terms = get_terms( array(
			'taxonomy'   => 'cdv_tematica',
			'hide_empty' => true,
			'number'     => 5,
		) );

		$data = array();
		foreach ( $terms as $term ) {
			$data[] = array(
				'name'  => $term->name,
				'count' => $term->count,
			);
		}

		return $data;
	}

	/**
	 * Get top proposte
	 *
	 * @param int $periodo Period.
	 * @return array
	 */
	private static function get_top_proposte( int $periodo ): array {
		return get_posts( array(
			'post_type'      => 'cdv_proposta',
			'post_status'    => 'publish',
			'posts_per_page' => 5,
			'meta_key'       => '_cdv_votes',
			'orderby'        => 'meta_value_num',
			'order'          => 'DESC',
		) );
	}

	/**
	 * Get risposte recenti
	 *
	 * @param int $limit Limit.
	 * @return array
	 */
	private static function get_risposte_recenti( int $limit ): array {
		return get_posts( array(
			'post_type'      => 'cdv_risposta_amm',
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'orderby'        => 'date',
			'order'          => 'DESC',
		) );
	}

	/**
	 * Render quartieri chart
	 *
	 * @param array $data Data.
	 */
	private static function render_quartieri_chart( array $data ): void {
		if ( empty( $data ) ) {
			echo '<p>' . esc_html__( 'Nessun dato disponibile', 'cronaca-di-viterbo' ) . '</p>';
			return;
		}

		$max = max( array_column( $data, 'count' ) );
		?>
		<div class="cdv-bar-chart">
			<?php foreach ( $data as $item ) : ?>
				<?php $percentage = $max > 0 ? ( $item['count'] / $max ) * 100 : 0; ?>
				<div class="cdv-bar-row">
					<span class="cdv-bar-label"><?php echo esc_html( $item['name'] ); ?></span>
					<div class="cdv-bar">
						<div class="cdv-bar-fill" style="width: <?php echo esc_attr( $percentage ); ?>%"></div>
					</div>
					<span class="cdv-bar-value"><?php echo esc_html( number_format_i18n( $item['count'] ) ); ?></span>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * Render tematiche chart
	 *
	 * @param array $data Data.
	 */
	private static function render_tematiche_chart( array $data ): void {
		self::render_quartieri_chart( $data ); // Same format
	}

	/**
	 * Render top proposte
	 *
	 * @param array $proposte Proposte.
	 */
	private static function render_top_proposte( array $proposte ): void {
		if ( empty( $proposte ) ) {
			echo '<p>' . esc_html__( 'Nessuna proposta ancora', 'cronaca-di-viterbo' ) . '</p>';
			return;
		}

		?>
		<div class="cdv-proposte-table">
			<?php foreach ( $proposte as $proposta ) : ?>
				<?php $voti = intval( get_post_meta( $proposta->ID, '_cdv_votes', true ) ); ?>
				<div class="cdv-proposta-row">
					<div class="cdv-proposta-title">
						<a href="<?php echo esc_url( get_permalink( $proposta->ID ) ); ?>">
							<?php echo esc_html( $proposta->post_title ); ?>
						</a>
					</div>
					<div class="cdv-proposta-votes">
						<strong><?php echo esc_html( number_format_i18n( $voti ) ); ?></strong> voti
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * Render risposte recenti
	 *
	 * @param array $risposte Risposte.
	 */
	private static function render_risposte_recenti( array $risposte ): void {
		if ( empty( $risposte ) ) {
			echo '<p>' . esc_html__( 'Nessuna risposta ancora', 'cronaca-di-viterbo' ) . '</p>';
			return;
		}

		?>
		<div class="cdv-risposte-list">
			<?php foreach ( $risposte as $risposta ) : ?>
				<?php
				$proposta_id = get_post_meta( $risposta->ID, '_cdv_proposta_id', true );
				$status = get_post_meta( $risposta->ID, '_cdv_status', true );
				$proposta = get_post( $proposta_id );
				?>
				<div class="cdv-risposta-item">
					<div class="cdv-risposta-header">
						<h4>
							<a href="<?php echo esc_url( get_permalink( $risposta->ID ) ); ?>">
								<?php echo esc_html( $risposta->post_title ); ?>
							</a>
						</h4>
						<span class="cdv-badge cdv-status-<?php echo esc_attr( $status ); ?>">
							<?php echo esc_html( \CdV\PostTypes\RispostaAmministrazione::get_status_label( $status ) ); ?>
						</span>
					</div>
					<?php if ( $proposta ) : ?>
						<p class="cdv-risposta-meta">
							<?php esc_html_e( 'Risposta a:', 'cronaca-di-viterbo' ); ?>
							<a href="<?php echo esc_url( get_permalink( $proposta_id ) ); ?>">
								<?php echo esc_html( $proposta->post_title ); ?>
							</a>
						</p>
					<?php endif; ?>
					<p class="cdv-risposta-date">
						<?php echo esc_html( human_time_diff( strtotime( $risposta->post_date ), current_time( 'timestamp' ) ) ); ?> <?php esc_html_e( 'fa', 'cronaca-di-viterbo' ); ?>
					</p>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
	}
}
