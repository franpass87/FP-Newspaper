<?php
/**
 * Shortcode: Profilo Utente Pubblico
 *
 * @package CdV
 * @subpackage Shortcodes
 * @since 1.3.0
 */

namespace CdV\Shortcodes;

use CdV\Services\Reputazione;

/**
 * Class UserProfile
 */
class UserProfile {
	/**
	 * Render shortcode
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public static function render( $atts ): string {
		$atts = shortcode_atts(
			array(
				'user_id' => get_current_user_id(),
			),
			$atts,
			'cdv_user_profile'
		);

		$user_id = intval( $atts['user_id'] );

		if ( ! $user_id ) {
			return '<p>' . __( 'Devi essere loggato per vedere il tuo profilo.', 'cronaca-di-viterbo' ) . '</p>';
		}

		$user = get_userdata( $user_id );

		if ( ! $user ) {
			return '<p>' . __( 'Utente non trovato.', 'cronaca-di-viterbo' ) . '</p>';
		}

		$points = intval( get_user_meta( $user_id, 'cdv_points', true ) );
		$level_label = Reputazione::get_user_level_label( $user_id );
		$badges = get_user_meta( $user_id, 'cdv_badges', true ) ?: array();
		$all_badges = Reputazione::get_all_badges();

		// Get user stats
		$proposte_count = count_user_posts( $user_id, 'cdv_proposta', true );
		$total_votes = self::get_total_votes_received( $user_id );

		ob_start();
		?>
		<div class="cdv-user-profile">
			<div class="cdv-profile-header">
				<div class="cdv-profile-avatar">
					<?php echo get_avatar( $user_id, 120 ); ?>
				</div>
				<div class="cdv-profile-info">
					<h2 class="cdv-profile-name"><?php echo esc_html( $user->display_name ); ?></h2>
					<p class="cdv-profile-level">
						<span class="cdv-level-badge"><?php echo esc_html( $level_label ); ?></span>
					</p>
					<p class="cdv-profile-points">
						<strong><?php echo esc_html( number_format_i18n( $points ) ); ?></strong> <?php esc_html_e( 'punti', 'cronaca-di-viterbo' ); ?>
					</p>
				</div>
			</div>

			<div class="cdv-profile-stats">
				<div class="cdv-stat">
					<span class="cdv-stat-value"><?php echo esc_html( number_format_i18n( $proposte_count ) ); ?></span>
					<span class="cdv-stat-label"><?php esc_html_e( 'Proposte', 'cronaca-di-viterbo' ); ?></span>
				</div>
				<div class="cdv-stat">
					<span class="cdv-stat-value"><?php echo esc_html( number_format_i18n( $total_votes ) ); ?></span>
					<span class="cdv-stat-label"><?php esc_html_e( 'Voti ricevuti', 'cronaca-di-viterbo' ); ?></span>
				</div>
				<div class="cdv-stat">
					<span class="cdv-stat-value"><?php echo esc_html( count( $badges ) ); ?></span>
					<span class="cdv-stat-label"><?php esc_html_e( 'Badge', 'cronaca-di-viterbo' ); ?></span>
				</div>
			</div>

			<?php if ( ! empty( $badges ) ) : ?>
				<div class="cdv-profile-badges">
					<h3><?php esc_html_e( 'Badge Ottenuti', 'cronaca-di-viterbo' ); ?></h3>
					<div class="cdv-badges-grid">
						<?php foreach ( $badges as $badge_slug ) : ?>
							<?php if ( isset( $all_badges[ $badge_slug ] ) ) : ?>
								<?php $badge = $all_badges[ $badge_slug ]; ?>
								<div class="cdv-badge-item cdv-badge-earned">
									<span class="cdv-badge-icon"><?php echo $badge['icon']; ?></span>
									<h4><?php echo esc_html( $badge['name'] ); ?></h4>
									<p><?php echo esc_html( $badge['description'] ); ?></p>
									<small>+<?php echo esc_html( $badge['points'] ); ?> <?php esc_html_e( 'punti', 'cronaca-di-viterbo' ); ?></small>
								</div>
							<?php endif; ?>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>

			<div class="cdv-profile-badges-locked">
				<h3><?php esc_html_e( 'Badge da Sbloccare', 'cronaca-di-viterbo' ); ?></h3>
				<div class="cdv-badges-grid">
					<?php foreach ( $all_badges as $badge_slug => $badge ) : ?>
						<?php if ( ! in_array( $badge_slug, $badges, true ) ) : ?>
							<div class="cdv-badge-item cdv-badge-locked">
								<span class="cdv-badge-icon cdv-locked-icon">🔒</span>
								<h4><?php echo esc_html( $badge['name'] ); ?></h4>
								<p><?php echo esc_html( $badge['description'] ); ?></p>
								<small>+<?php echo esc_html( $badge['points'] ); ?> <?php esc_html_e( 'punti', 'cronaca-di-viterbo' ); ?></small>
							</div>
						<?php endif; ?>
					<?php endforeach; ?>
				</div>
			</div>

			<?php if ( $proposte_count > 0 ) : ?>
				<div class="cdv-profile-proposte">
					<h3><?php esc_html_e( 'Proposte Recenti', 'cronaca-di-viterbo' ); ?></h3>
					<?php
					$proposte = get_posts( array(
						'post_type'      => 'cdv_proposta',
						'author'         => $user_id,
						'post_status'    => 'publish',
						'posts_per_page' => 5,
					) );
					?>
					<?php if ( ! empty( $proposte ) ) : ?>
						<ul class="cdv-proposte-list">
							<?php foreach ( $proposte as $proposta ) : ?>
								<li>
									<a href="<?php echo esc_url( get_permalink( $proposta->ID ) ); ?>">
										<?php echo esc_html( $proposta->post_title ); ?>
									</a>
									<span class="cdv-proposta-votes">
										<?php echo esc_html( number_format_i18n( intval( get_post_meta( $proposta->ID, '_cdv_votes', true ) ) ) ); ?> voti
									</span>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get total votes received
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
			'post_status'    => 'publish',
		) );

		$total = 0;
		foreach ( $proposte as $proposta_id ) {
			$total += intval( get_post_meta( $proposta_id, '_cdv_votes', true ) );
		}

		return $total;
	}
}
