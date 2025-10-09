<?php
/**
 * Shortcode Form Proposta.
 *
 * @package CdV\Shortcodes
 */

namespace CdV\Shortcodes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Classe per lo shortcode [cdv_proposta_form].
 */
class PropostaForm {

	/**
	 * Costruttore.
	 */
	public function __construct() {
		add_shortcode( 'cdv_proposta_form', [ $this, 'render' ] );
	}

	/**
	 * Render dello shortcode.
	 *
	 * @param array $atts Attributi shortcode.
	 * @return string HTML del form.
	 */
	public function render( $atts ) {
		$atts = shortcode_atts(
			[
				'title' => __( 'Invia una Proposta', 'cronaca-di-viterbo' ),
			],
			$atts,
			'cdv_proposta_form'
		);

		$quartieri = get_terms(
			[
				'taxonomy'   => 'cdv_quartiere',
				'hide_empty' => false,
			]
		);

		$tematiche = get_terms(
			[
				'taxonomy'   => 'cdv_tematica',
				'hide_empty' => false,
			]
		);

		ob_start();
		?>
		<div class="cdv-proposta-form-wrap">
			<h3><?php echo esc_html( $atts['title'] ); ?></h3>
			<form id="cdv-proposta-form" class="cdv-form">
				<div class="cdv-form-group">
					<label for="cdv-proposta-title"><?php esc_html_e( 'Titolo (max 140 caratteri):', 'cronaca-di-viterbo' ); ?></label>
					<input type="text" id="cdv-proposta-title" name="title" maxlength="140" required>
				</div>

				<div class="cdv-form-group">
					<label for="cdv-proposta-content"><?php esc_html_e( 'Descrizione:', 'cronaca-di-viterbo' ); ?></label>
					<textarea id="cdv-proposta-content" name="content" rows="6" required></textarea>
				</div>

				<div class="cdv-form-group">
					<label for="cdv-proposta-quartiere"><?php esc_html_e( 'Quartiere:', 'cronaca-di-viterbo' ); ?></label>
					<select id="cdv-proposta-quartiere" name="quartiere" required>
						<option value=""><?php esc_html_e( '-- Seleziona --', 'cronaca-di-viterbo' ); ?></option>
						<?php foreach ( $quartieri as $quartiere ) : ?>
							<option value="<?php echo esc_attr( $quartiere->term_id ); ?>">
								<?php echo esc_html( $quartiere->name ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>

				<div class="cdv-form-group">
					<label for="cdv-proposta-tematica"><?php esc_html_e( 'Tematica:', 'cronaca-di-viterbo' ); ?></label>
					<select id="cdv-proposta-tematica" name="tematica" required>
						<option value=""><?php esc_html_e( '-- Seleziona --', 'cronaca-di-viterbo' ); ?></option>
						<?php foreach ( $tematiche as $tematica ) : ?>
							<option value="<?php echo esc_attr( $tematica->term_id ); ?>">
								<?php echo esc_html( $tematica->name ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>

				<div class="cdv-form-group cdv-checkbox">
					<label>
						<input type="checkbox" id="cdv-proposta-privacy" name="privacy" required>
						<?php esc_html_e( 'Accetto l\'informativa sulla privacy', 'cronaca-di-viterbo' ); ?>
					</label>
				</div>

			<input type="hidden" name="action" value="cdv_submit_proposta">
			<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'cdv_ajax_nonce' ) ); ?>">

				<div class="cdv-form-group">
					<button type="submit" class="cdv-btn cdv-btn-primary">
						<?php esc_html_e( 'Invia Proposta', 'cronaca-di-viterbo' ); ?>
					</button>
				</div>

				<div id="cdv-proposta-response" class="cdv-response"></div>
			</form>
		</div>
		<?php
		return ob_get_clean();
	}
}
