<?php
/**
 * Shortcode: Form Firma Petizione
 *
 * @package CdV
 * @subpackage Shortcodes
 * @since 1.3.0
 */

namespace CdV\Shortcodes;

use CdV\Utils\View;

/**
 * Class PetizioneForm
 */
class PetizioneForm {
	/**
	 * Render shortcode
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public static function render( $atts ): string {
		$atts = shortcode_atts(
			array(
				'id' => get_the_ID(),
			),
			$atts,
			'cdv_petizione_form'
		);

		$petizione_id = intval( $atts['id'] );

		if ( ! $petizione_id || get_post_type( $petizione_id ) !== 'cdv_petizione' ) {
			return '';
		}

		$petizione = get_post( $petizione_id );
		$aperta = get_post_meta( $petizione_id, '_cdv_aperta', true ) !== '0';
		$soglia = intval( get_post_meta( $petizione_id, '_cdv_soglia_firme', true ) );
		$firme = intval( get_post_meta( $petizione_id, '_cdv_firme_count', true ) );
		$deadline = get_post_meta( $petizione_id, '_cdv_deadline', true );
		$percentuale = $soglia > 0 ? round( ( $firme / $soglia ) * 100, 1 ) : 0;

		$is_scaduta = $deadline && strtotime( $deadline ) < time();
		$is_obiettivo_raggiunto = $firme >= $soglia;

		ob_start();
		?>
		<div class="cdv-petizione-form-wrapper" data-petizione-id="<?php echo esc_attr( $petizione_id ); ?>">
			<div class="cdv-petizione-progress">
				<div class="cdv-progress-bar">
					<div class="cdv-progress-fill" style="width: <?php echo esc_attr( min( $percentuale, 100 ) ); ?>%"></div>
				</div>
				<div class="cdv-progress-stats">
					<span class="cdv-firme-count"><strong><?php echo esc_html( number_format_i18n( $firme ) ); ?></strong> <?php esc_html_e( 'firme', 'cronaca-di-viterbo' ); ?></span>
					<span class="cdv-obiettivo"><?php esc_html_e( 'Obiettivo:', 'cronaca-di-viterbo' ); ?> <?php echo esc_html( number_format_i18n( $soglia ) ); ?></span>
				</div>
				<?php if ( $deadline ) : ?>
					<p class="cdv-deadline">
						<?php if ( $is_scaduta ) : ?>
							<span class="cdv-badge cdv-badge-error"><?php esc_html_e( 'Scaduta', 'cronaca-di-viterbo' ); ?></span>
						<?php else : ?>
							<?php esc_html_e( 'Scadenza:', 'cronaca-di-viterbo' ); ?> <?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $deadline ) ) ); ?>
						<?php endif; ?>
					</p>
				<?php endif; ?>
				<?php if ( $is_obiettivo_raggiunto ) : ?>
					<p class="cdv-obiettivo-raggiunto">
						<span class="cdv-badge cdv-badge-success">🎉 <?php esc_html_e( 'Obiettivo raggiunto!', 'cronaca-di-viterbo' ); ?></span>
					</p>
				<?php endif; ?>
			</div>

			<?php if ( $aperta && ! $is_scaduta ) : ?>
				<form class="cdv-petizione-form" data-nonce="<?php echo esc_attr( wp_create_nonce( 'cdv_ajax_nonce' ) ); ?>">
					<h3><?php esc_html_e( 'Firma questa petizione', 'cronaca-di-viterbo' ); ?></h3>
					
					<div class="cdv-form-row cdv-form-row-half">
						<div class="cdv-form-field">
							<label for="cdv_firma_nome"><?php esc_html_e( 'Nome *', 'cronaca-di-viterbo' ); ?></label>
							<input type="text" id="cdv_firma_nome" name="nome" required>
						</div>
						<div class="cdv-form-field">
							<label for="cdv_firma_cognome"><?php esc_html_e( 'Cognome *', 'cronaca-di-viterbo' ); ?></label>
							<input type="text" id="cdv_firma_cognome" name="cognome" required>
						</div>
					</div>

					<div class="cdv-form-row cdv-form-row-half">
						<div class="cdv-form-field">
							<label for="cdv_firma_email"><?php esc_html_e( 'Email *', 'cronaca-di-viterbo' ); ?></label>
							<input type="email" id="cdv_firma_email" name="email" required>
						</div>
						<div class="cdv-form-field">
							<label for="cdv_firma_comune"><?php esc_html_e( 'Comune', 'cronaca-di-viterbo' ); ?></label>
							<input type="text" id="cdv_firma_comune" name="comune">
						</div>
					</div>

					<div class="cdv-form-field">
						<label for="cdv_firma_motivazione"><?php esc_html_e( 'Motivazione (opzionale)', 'cronaca-di-viterbo' ); ?></label>
						<textarea id="cdv_firma_motivazione" name="motivazione" rows="3" maxlength="500"></textarea>
						<small><?php esc_html_e( 'Max 500 caratteri', 'cronaca-di-viterbo' ); ?></small>
					</div>

					<div class="cdv-form-field">
						<label class="cdv-checkbox">
							<input type="checkbox" name="privacy" required>
							<?php esc_html_e( 'Accetto la privacy policy *', 'cronaca-di-viterbo' ); ?>
						</label>
					</div>

					<div class="cdv-form-actions">
						<button type="submit" class="cdv-btn cdv-btn-primary">
							<?php esc_html_e( 'Firma Petizione', 'cronaca-di-viterbo' ); ?>
						</button>
					</div>

					<div class="cdv-form-message" style="display:none;"></div>
				</form>
			<?php else : ?>
				<p class="cdv-petizione-closed">
					<?php if ( $is_scaduta ) : ?>
						<?php esc_html_e( 'Questa petizione è scaduta.', 'cronaca-di-viterbo' ); ?>
					<?php else : ?>
						<?php esc_html_e( 'Questa petizione è chiusa.', 'cronaca-di-viterbo' ); ?>
					<?php endif; ?>
				</p>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}
}
