<?php
/**
 * Email Template: Milestone Petizione
 *
 * Available variables:
 * - $petizione_title
 * - $petizione_link
 * - $firme_count
 * - $soglia
 * - $percentuale
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
	<title>Milestone Raggiunta!</title>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; background-color: #f5f5f5;">
	<table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f5f5f5; padding: 40px 20px;">
		<tr>
			<td align="center">
				<table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,0.08);">
					<!-- Header -->
					<tr>
						<td style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); padding: 40px 30px; text-align: center;">
							<div style="font-size: 64px; margin-bottom: 12px;">🎉</div>
							<h1 style="margin: 0; color: #ffffff; font-size: 28px; font-weight: 700;">Milestone Raggiunta!</h1>
							<p style="margin: 10px 0 0 0; color: rgba(255,255,255,0.9); font-size: 16px;">La tua petizione sta crescendo!</p>
						</td>
					</tr>

					<!-- Body -->
					<tr>
						<td style="padding: 40px 30px;">
							<p style="margin: 0 0 20px 0; font-size: 16px; color: #333; line-height: 1.6;">
								Complimenti! La tua petizione ha raggiunto un traguardo importante.
							</p>

							<!-- Petizione Title -->
							<div style="background-color: #f8f9fa; padding: 20px; margin: 20px 0; border-radius: 8px; text-align: center;">
								<h3 style="margin: 0 0 12px 0; color: #333; font-size: 20px;"><?php echo esc_html( $petizione_title ); ?></h3>
							</div>

							<!-- Stats -->
							<table width="100%" cellpadding="0" cellspacing="0" style="margin: 30px 0;">
								<tr>
									<td style="text-align: center; padding: 20px;">
										<div style="font-size: 48px; font-weight: 700; color: #f5576c; line-height: 1;">
											<?php echo esc_html( $firme_count ); ?>
										</div>
										<div style="font-size: 14px; color: #666; margin-top: 8px; text-transform: uppercase; letter-spacing: 1px;">
											Firme Raccolte
										</div>
									</td>
									<td style="text-align: center; padding: 20px;">
										<div style="font-size: 48px; font-weight: 700; color: #667eea; line-height: 1;">
											<?php echo esc_html( $percentuale ); ?>%
										</div>
										<div style="font-size: 14px; color: #666; margin-top: 8px; text-transform: uppercase; letter-spacing: 1px;">
											Obiettivo (<?php echo esc_html( $soglia ); ?>)
										</div>
									</td>
								</tr>
							</table>

							<!-- Progress Bar -->
							<div style="background-color: #e0e0e0; height: 24px; border-radius: 12px; overflow: hidden; margin: 30px 0;">
								<div style="background: linear-gradient(90deg, #f093fb 0%, #f5576c 100%); height: 100%; width: <?php echo esc_attr( min( $percentuale, 100 ) ); ?>%; transition: width 0.5s;"></div>
							</div>

							<p style="margin: 30px 0 20px 0; font-size: 16px; color: #333; line-height: 1.6;">
								Continua a condividere la tua petizione per raggiungere l'obiettivo di <strong><?php echo esc_html( $soglia ); ?> firme</strong>!
							</p>

							<!-- CTA Button -->
							<div style="text-align: center; margin: 30px 0;">
								<a href="<?php echo esc_url( $petizione_link ); ?>" style="display: inline-block; padding: 14px 32px; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: #ffffff; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 16px;">
									Visualizza Petizione
								</a>
							</div>

							<!-- Share Buttons -->
							<div style="text-align: center; margin: 30px 0;">
								<p style="margin: 0 0 16px 0; font-size: 14px; color: #666;">Condividi con i tuoi amici:</p>
								<a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode( $petizione_link ); ?>" style="display: inline-block; margin: 0 8px; padding: 10px 20px; background-color: #1877f2; color: #ffffff; text-decoration: none; border-radius: 6px; font-size: 14px; font-weight: 600;">
									Facebook
								</a>
								<a href="https://twitter.com/intent/tweet?url=<?php echo urlencode( $petizione_link ); ?>&text=<?php echo urlencode( $petizione_title ); ?>" style="display: inline-block; margin: 0 8px; padding: 10px 20px; background-color: #1da1f2; color: #ffffff; text-decoration: none; border-radius: 6px; font-size: 14px; font-weight: 600;">
									Twitter
								</a>
								<a href="https://wa.me/?text=<?php echo urlencode( $petizione_title . ' ' . $petizione_link ); ?>" style="display: inline-block; margin: 0 8px; padding: 10px 20px; background-color: #25d366; color: #ffffff; text-decoration: none; border-radius: 6px; font-size: 14px; font-weight: 600;">
									WhatsApp
								</a>
							</div>
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
