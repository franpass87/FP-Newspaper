<?php
/**
 * Email Template: Weekly Digest
 *
 * Available variables:
 * - $proposte (array of WP_Post)
 * - $eventi (array of WP_Post)
 * - $dossier (array of WP_Post)
 * - $email (subscriber email)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Digest Settimanale</title>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; background-color: #f5f5f5;">
	<table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f5f5f5; padding: 40px 20px;">
		<tr>
			<td align="center">
				<table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,0.08);">
					<!-- Header -->
					<tr>
						<td style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); padding: 40px 30px; text-align: center;">
							<h1 style="margin: 0; color: #ffffff; font-size: 28px; font-weight: 700;">📰 Digest Settimanale</h1>
							<p style="margin: 10px 0 0 0; color: rgba(255,255,255,0.9); font-size: 16px;">
								<?php echo esc_html( date_i18n( 'D j F Y', current_time( 'timestamp' ) ) ); ?>
							</p>
						</td>
					</tr>

					<!-- Body -->
					<tr>
						<td style="padding: 40px 30px;">
							<p style="margin: 0 0 30px 0; font-size: 16px; color: #333; line-height: 1.6;">
								Ecco cosa è successo questa settimana su Cronaca di Viterbo:
							</p>

							<?php if ( ! empty( $proposte ) ) : ?>
								<!-- Proposte Section -->
								<div style="margin-bottom: 40px;">
									<h2 style="margin: 0 0 20px 0; color: #333; font-size: 22px; border-bottom: 2px solid #667eea; padding-bottom: 8px;">
										💡 Nuove Proposte (<?php echo count( $proposte ); ?>)
									</h2>
									<?php foreach ( $proposte as $proposta ) : ?>
										<?php $voti = get_post_meta( $proposta->ID, '_cdv_votes', true ); ?>
										<div style="background-color: #f8f9fa; padding: 20px; margin-bottom: 16px; border-radius: 8px; border-left: 4px solid #667eea;">
											<h3 style="margin: 0 0 8px 0; font-size: 18px;">
												<a href="<?php echo esc_url( get_permalink( $proposta->ID ) ); ?>" style="color: #333; text-decoration: none;">
													<?php echo esc_html( $proposta->post_title ); ?>
												</a>
											</h3>
											<p style="margin: 8px 0; font-size: 14px; color: #666; line-height: 1.5;">
												<?php echo esc_html( wp_trim_words( $proposta->post_excerpt, 20 ) ); ?>
											</p>
											<div style="font-size: 13px; color: #999;">
												👍 <?php echo esc_html( number_format_i18n( intval( $voti ) ) ); ?> voti
											</div>
										</div>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>

							<?php if ( ! empty( $eventi ) ) : ?>
								<!-- Eventi Section -->
								<div style="margin-bottom: 40px;">
									<h2 style="margin: 0 0 20px 0; color: #333; font-size: 22px; border-bottom: 2px solid #ff9800; padding-bottom: 8px;">
										📅 Eventi in Arrivo (<?php echo count( $eventi ); ?>)
									</h2>
									<?php foreach ( $eventi as $evento ) : ?>
										<?php $data = get_post_meta( $evento->ID, '_cdv_data_inizio', true ); ?>
										<div style="background-color: #fff3e0; padding: 20px; margin-bottom: 16px; border-radius: 8px; border-left: 4px solid #ff9800;">
											<h3 style="margin: 0 0 8px 0; font-size: 18px;">
												<a href="<?php echo esc_url( get_permalink( $evento->ID ) ); ?>" style="color: #333; text-decoration: none;">
													<?php echo esc_html( $evento->post_title ); ?>
												</a>
											</h3>
											<?php if ( $data ) : ?>
												<div style="font-size: 14px; color: #666; margin: 8px 0;">
													📍 <?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $data ) ) ); ?>
												</div>
											<?php endif; ?>
										</div>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>

							<?php if ( ! empty( $dossier ) ) : ?>
								<!-- Dossier Section -->
								<div style="margin-bottom: 40px;">
									<h2 style="margin: 0 0 20px 0; color: #333; font-size: 22px; border-bottom: 2px solid #f5576c; padding-bottom: 8px;">
										📰 Dossier Aggiornati (<?php echo count( $dossier ); ?>)
									</h2>
									<?php foreach ( $dossier as $d ) : ?>
										<div style="background-color: #ffebee; padding: 20px; margin-bottom: 16px; border-radius: 8px; border-left: 4px solid #f5576c;">
											<h3 style="margin: 0 0 8px 0; font-size: 18px;">
												<a href="<?php echo esc_url( get_permalink( $d->ID ) ); ?>" style="color: #333; text-decoration: none;">
													<?php echo esc_html( $d->post_title ); ?>
												</a>
											</h3>
											<p style="margin: 8px 0; font-size: 14px; color: #666; line-height: 1.5;">
												<?php echo esc_html( wp_trim_words( $d->post_excerpt, 15 ) ); ?>
											</p>
										</div>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>

							<!-- CTA -->
							<div style="text-align: center; margin: 40px 0 20px 0;">
								<a href="<?php echo esc_url( home_url( '/' ) ); ?>" style="display: inline-block; padding: 14px 32px; background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: #ffffff; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 16px;">
									Visita Cronaca di Viterbo
								</a>
							</div>

							<p style="margin: 30px 0 0 0; font-size: 14px; color: #666; text-align: center;">
								Grazie per essere parte della nostra community! 🙏
							</p>
						</td>
					</tr>

					<!-- Footer -->
					<tr>
						<td style="background-color: #f8f9fa; padding: 30px; text-align: center; border-top: 1px solid #e0e0e0;">
							<p style="margin: 0 0 8px 0; font-size: 12px; color: #999;">
								Ricevi questa email perché sei iscritto al digest settimanale
							</p>
							<p style="margin: 0 0 8px 0; font-size: 12px; color: #999;">
								<a href="<?php echo esc_url( home_url( '/disiscrizione?email=' . urlencode( $email ) ) ); ?>" style="color: #667eea; text-decoration: none;">
									Annulla iscrizione
								</a>
							</p>
							<p style="margin: 0; font-size: 12px; color: #999;">
								© <?php echo esc_html( date( 'Y' ) ); ?> Cronaca di Viterbo - Tutti i diritti riservati
							</p>
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
</body>
</html>
