<?php
/**
 * Admin: Analytics Avanzati
 *
 * @package CdV
 * @subpackage Admin
 * @since 1.6.0
 */

namespace CdV\Admin;

/**
 * Class Analytics
 */
class Analytics {
	/**
	 * Initialize
	 */
	public static function init(): void {
		add_action( 'admin_menu', array( self::class, 'add_menu_page' ) );
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue_scripts' ) );
	}

	/**
	 * Add menu page
	 */
	public static function add_menu_page(): void {
		add_submenu_page(
			'cdv-dashboard',
			__( 'Analytics Avanzati', 'cronaca-di-viterbo' ),
			__( 'Analytics', 'cronaca-di-viterbo' ),
			'manage_options',
			'cdv-analytics',
			array( self::class, 'render_page' )
		);
	}

	/**
	 * Enqueue scripts
	 *
	 * @param string $hook Hook suffix.
	 */
	public static function enqueue_scripts( $hook ): void {
		if ( $hook !== 'dashboard-cdv_page_cdv-analytics' ) {
			return;
		}

		wp_enqueue_script( 'chart-js', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js', array(), '4.4.0', true );
	}

	/**
	 * Render page
	 */
	public static function render_page(): void {
		$stats = self::get_advanced_stats();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Analytics Avanzati', 'cronaca-di-viterbo' ); ?></h1>

			<div class="cdv-analytics-grid">
				<!-- Engagement Timeline -->
				<div class="cdv-analytics-card cdv-card-full">
					<h2><?php esc_html_e( 'Engagement nel Tempo', 'cronaca-di-viterbo' ); ?></h2>
					<canvas id="cdv-engagement-chart" height="80"></canvas>
				</div>

				<!-- Top Contributors -->
				<div class="cdv-analytics-card">
					<h2><?php esc_html_e( 'Top Contributors', 'cronaca-di-viterbo' ); ?></h2>
					<table class="widefat">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Utente', 'cronaca-di-viterbo' ); ?></th>
								<th><?php esc_html_e( 'Punti', 'cronaca-di-viterbo' ); ?></th>
								<th><?php esc_html_e( 'Livello', 'cronaca-di-viterbo' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $stats['top_users'] as $user_data ) : ?>
								<tr>
									<td>
										<?php echo get_avatar( $user_data['id'], 32 ); ?>
										<strong><?php echo esc_html( $user_data['name'] ); ?></strong>
									</td>
									<td><?php echo esc_html( number_format_i18n( $user_data['points'] ) ); ?></td>
									<td><?php echo esc_html( $user_data['level'] ); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>

				<!-- Proposte per Status -->
				<div class="cdv-analytics-card">
					<h2><?php esc_html_e( 'Proposte per Status', 'cronaca-di-viterbo' ); ?></h2>
					<canvas id="cdv-status-chart"></canvas>
				</div>

				<!-- Heatmap Quartieri -->
				<div class="cdv-analytics-card cdv-card-full">
					<h2><?php esc_html_e( 'Heatmap Partecipazione Quartieri', 'cronaca-di-viterbo' ); ?></h2>
					<div id="cdv-heatmap">
						<?php foreach ( $stats['quartieri_heatmap'] as $q ) : ?>
							<div class="cdv-heatmap-item" style="flex: <?php echo esc_attr( $q['count'] ); ?>;">
								<div class="cdv-heatmap-label"><?php echo esc_html( $q['name'] ); ?></div>
								<div class="cdv-heatmap-value"><?php echo esc_html( number_format_i18n( $q['count'] ) ); ?></div>
							</div>
						<?php endforeach; ?>
					</div>
				</div>

				<!-- Conversion Funnel -->
				<div class="cdv-analytics-card">
					<h2><?php esc_html_e( 'Conversion Funnel', 'cronaca-di-viterbo' ); ?></h2>
					<div class="cdv-funnel">
						<div class="cdv-funnel-step" style="width: 100%;">
							<span><?php esc_html_e( 'Proposte Inviate', 'cronaca-di-viterbo' ); ?></span>
							<strong><?php echo esc_html( number_format_i18n( $stats['funnel']['inviate'] ) ); ?></strong>
						</div>
						<div class="cdv-funnel-step" style="width: <?php echo esc_attr( $stats['funnel']['pubblicate_perc'] ); ?>%;">
							<span><?php esc_html_e( 'Pubblicate', 'cronaca-di-viterbo' ); ?></span>
							<strong><?php echo esc_html( number_format_i18n( $stats['funnel']['pubblicate'] ) ); ?></strong>
						</div>
						<div class="cdv-funnel-step" style="width: <?php echo esc_attr( $stats['funnel']['con_risposta_perc'] ); ?>%;">
							<span><?php esc_html_e( 'Con Risposta', 'cronaca-di-viterbo' ); ?></span>
							<strong><?php echo esc_html( number_format_i18n( $stats['funnel']['con_risposta'] ) ); ?></strong>
						</div>
						<div class="cdv-funnel-step" style="width: <?php echo esc_attr( $stats['funnel']['accettate_perc'] ); ?>%;">
							<span><?php esc_html_e( 'Accettate', 'cronaca-di-viterbo' ); ?></span>
							<strong><?php echo esc_html( number_format_i18n( $stats['funnel']['accettate'] ) ); ?></strong>
						</div>
					</div>
				</div>
			</div>

			<script>
			// Engagement Chart
			const ctxEngagement = document.getElementById('cdv-engagement-chart');
			if (ctxEngagement) {
				new Chart(ctxEngagement, {
					type: 'line',
					data: {
						labels: <?php echo json_encode( array_column( $stats['engagement_timeline'], 'date' ) ); ?>,
						datasets: [
							{
								label: 'Proposte',
								data: <?php echo json_encode( array_column( $stats['engagement_timeline'], 'proposte' ) ); ?>,
								borderColor: '#667eea',
								tension: 0.4,
							},
							{
								label: 'Firme Petizioni',
								data: <?php echo json_encode( array_column( $stats['engagement_timeline'], 'firme' ) ); ?>,
								borderColor: '#f5576c',
								tension: 0.4,
							},
						],
					},
					options: {
						responsive: true,
						maintainAspectRatio: true,
					},
				});
			}

			// Status Chart
			const ctxStatus = document.getElementById('cdv-status-chart');
			if (ctxStatus) {
				new Chart(ctxStatus, {
					type: 'doughnut',
					data: {
						labels: ['Pending', 'Pubblicate', 'Con Risposta', 'Accettate'],
						datasets: [{
							data: [
								<?php echo intval( $stats['status']['pending'] ); ?>,
								<?php echo intval( $stats['status']['publish'] ); ?>,
								<?php echo intval( $stats['status']['con_risposta'] ); ?>,
								<?php echo intval( $stats['status']['accettate'] ); ?>,
							],
							backgroundColor: ['#ffc107', '#2196f3', '#ff9800', '#4caf50'],
						}],
					},
				});
			}
			</script>

			<style>
			.cdv-analytics-grid {
				display: grid;
				grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
				gap: 24px;
				margin-top: 30px;
			}
			.cdv-analytics-card {
				background: #fff;
				padding: 24px;
				border-radius: 8px;
				box-shadow: 0 2px 8px rgba(0,0,0,0.05);
			}
			.cdv-card-full {
				grid-column: 1 / -1;
			}
			.cdv-analytics-card h2 {
				margin-top: 0;
				font-size: 18px;
			}
			.cdv-heatmap {
				display: flex;
				gap: 8px;
				margin-top: 20px;
			}
			.cdv-heatmap-item {
				background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
				color: #fff;
				padding: 20px;
				border-radius: 8px;
				text-align: center;
			}
			.cdv-funnel {
				margin-top: 20px;
			}
			.cdv-funnel-step {
				background: linear-gradient(90deg, #667eea, #764ba2);
				color: #fff;
				padding: 20px;
				margin-bottom: 12px;
				border-radius: 8px;
				display: flex;
				justify-content: space-between;
				align-items: center;
				transition: all 0.3s;
			}
			</style>
		</div>
		<?php
	}

	/**
	 * Get advanced stats
	 *
	 * @return array
	 */
	private static function get_advanced_stats(): array {
		global $wpdb;

		// Top users
		$top_users = $wpdb->get_results(
			"SELECT user_id, meta_value as points 
			FROM {$wpdb->usermeta} 
			WHERE meta_key = 'cdv_points' 
			ORDER BY CAST(meta_value AS UNSIGNED) DESC 
			LIMIT 10",
			ARRAY_A
		);

		$top_users_data = array();
		foreach ( $top_users as $user ) {
			$user_obj = get_userdata( $user['user_id'] );
			if ( $user_obj ) {
				$top_users_data[] = array(
					'id'     => $user['user_id'],
					'name'   => $user_obj->display_name,
					'points' => intval( $user['points'] ),
					'level'  => \CdV\Services\Reputazione::get_user_level_label( $user['user_id'] ),
				);
			}
		}

		// Status breakdown
		$status_counts = array(
			'pending'      => 0,
			'publish'      => 0,
			'con_risposta' => 0,
			'accettate'    => 0,
		);

		$all_proposte = get_posts( array(
			'post_type'      => 'cdv_proposta',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		) );

		foreach ( $all_proposte as $proposta_id ) {
			$status = get_post_status( $proposta_id );
			if ( $status === 'pending' ) {
				$status_counts['pending']++;
			} elseif ( $status === 'publish' ) {
				$status_counts['publish']++;
				
				// Check risposta
				$has_risposta = $wpdb->get_var( $wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_cdv_proposta_id' AND meta_value = %d",
					$proposta_id
				) );
				
				if ( $has_risposta ) {
					$status_counts['con_risposta']++;
					
					// Check accettata
					$risposta_status = $wpdb->get_var( $wpdb->prepare(
						"SELECT pm2.meta_value FROM {$wpdb->postmeta} pm1
						INNER JOIN {$wpdb->postmeta} pm2 ON pm1.post_id = pm2.post_id
						WHERE pm1.meta_key = '_cdv_proposta_id' AND pm1.meta_value = %d
						AND pm2.meta_key = '_cdv_status'
						LIMIT 1",
						$proposta_id
					) );
					
					if ( in_array( $risposta_status, array( 'accettata', 'in_corso', 'completata' ), true ) ) {
						$status_counts['accettate']++;
					}
				}
			}
		}

		// Engagement timeline (ultimi 30 giorni)
		$timeline = array();
		for ( $i = 29; $i >= 0; $i-- ) {
			$date = date( 'Y-m-d', strtotime( "-$i days" ) );
			
			$proposte_count = $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'cdv_proposta' AND DATE(post_date) = %s",
				$date
			) );
			
			$firme_count = $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}cdv_petizioni_firme WHERE DATE(created_at) = %s",
				$date
			) );

			$timeline[] = array(
				'date'     => date_i18n( 'j M', strtotime( $date ) ),
				'proposte' => intval( $proposte_count ),
				'firme'    => intval( $firme_count ),
			);
		}

		// Quartieri heatmap
		$quartieri = get_terms( array( 'taxonomy' => 'cdv_quartiere' ) );
		$quartieri_data = array();

		foreach ( $quartieri as $term ) {
			$quartieri_data[] = array(
				'name'  => $term->name,
				'count' => $term->count,
			);
		}

		// Funnel
		$total_proposte = count( $all_proposte );
		$funnel = array(
			'inviate'           => $total_proposte,
			'pubblicate'        => $status_counts['publish'],
			'pubblicate_perc'   => $total_proposte > 0 ? round( ( $status_counts['publish'] / $total_proposte ) * 100, 1 ) : 0,
			'con_risposta'      => $status_counts['con_risposta'],
			'con_risposta_perc' => $status_counts['publish'] > 0 ? round( ( $status_counts['con_risposta'] / $status_counts['publish'] ) * 100, 1 ) : 0,
			'accettate'         => $status_counts['accettate'],
			'accettate_perc'    => $status_counts['con_risposta'] > 0 ? round( ( $status_counts['accettate'] / $status_counts['con_risposta'] ) * 100, 1 ) : 0,
		);

		return array(
			'top_users'           => $top_users_data,
			'status'              => $status_counts,
			'engagement_timeline' => $timeline,
			'quartieri_heatmap'   => $quartieri_data,
			'funnel'              => $funnel,
		);
	}
}
