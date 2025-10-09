<?php
/**
 * Email Template: Risposta Amministrazione
 *
 * Available variables:
 * - $proposta_title
 * - $proposta_link
 * - $risposta_title
 * - $risposta_link
 * - $status
 * - $status_label
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
	<title>Risposta Amministrazione</title>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; background-color: #f5f5f5;">
	<table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f5f5f5; padding: 40px 20px;">
		<tr>
			<td align="center">
				<table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,0.08);">
					<!-- Header -->
					<tr>
						<td style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 40px 30px; text-align: center;">
							<h1 style="margin: 0; color: #ffffff; font-size: 28px; font-weight: 700;">🏛️ Risposta Amministrazione</h1>
							<p style="margin: 10px 0 0 0; color: rgba(255,255,255,0.9); font-size: 16px;">Cronaca di Viterbo</p>
						</td>
					</tr>

					<!-- Body -->
					<tr>
						<td style="padding: 40px 30px;">
							<p style="margin: 0 0 20px 0; font-size: 16px; color: #333; line-height: 1.6;">
								Ciao,
							</p>

							<p style="margin: 0 0 20px 0; font-size: 16px; color: #333; line-height: 1.6;">
				L'Amministrazione comunale ha risposto alla tua proposta <strong>"<?php echo esc_html( $proposta_title ); ?>"</strong>.
							</p>

							<!-- Status Badge -->
							<div style="text-align: center; margin: 30px 0;">
								<?php
								$status_colors = array(
									'in_valutazione' => '#ffc107',
									'accettata'      => '#28a745',
									'respinta'       => '#dc3545',
									'in_corso'       => '#17a2b8',
									'completata'     => '#28a745',
								);
								$color = isset( $status_colors[ $status ] ) ? $status_colors[ $status ] : '#6c757d';
								?>
								<span style="display: inline-block; padding: 12px 24px; background-color: <?php echo esc_attr( $color ); ?>; color: #ffffff; border-radius: 24px; font-weight: 600; font-size: 14px; text-transform: uppercase; letter-spacing: 1px;">
									<?php echo esc_html( $status_label ); ?>
								</span>
							</div>

							<!-- Risposta Details -->
							<div style="background-color: #f8f9fa; border-left: 4px solid <?php echo esc_attr( $color ); ?>; padding: 20px; margin: 30px 0; border-radius: 8px;">
								<h3 style="margin: 0 0 12px 0; color: #333; font-size: 18px;"><?php echo esc_html( $risposta_title ); ?></h3>
								<p style="margin: 0; font-size: 14px; color: #666;">
									<a href="<?php echo esc_url( $risposta_link ); ?>" style="color: #667eea; text-decoration: none; font-weight: 600;">Leggi la risposta completa →</a>
								</p>
							</div>

							<!-- CTA Button -->
							<div style="text-align: center; margin: 30px 0;">
								<a href="<?php echo esc_url( $proposta_link ); ?>" style="display: inline-block; padding: 14px 32px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #ffffff; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 16px;">
									Visualizza Proposta
								</a>
							</div>

							<p style="margin: 30px 0 0 0; font-size: 14px; color: #666; line-height: 1.6;">
								Grazie per la tua partecipazione attiva alla vita della comunità!
							</p>
						</td>
					</tr>

					<!-- Footer -->
					<tr>
						<td style="background-color: #f8f9fa; padding: 30px; text-align: center; border-top: 1px solid #e0e0e0;">
							<p style="margin: 0 0 8px 0; font-size: 12px; color: #999;">
								Questa è un'email automatica da Cronaca di Viterbo
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
