<?php
/**
 * Shortcode: Form Vota Sondaggio
 *
 * @package CdV
 * @subpackage Shortcodes
 * @since 1.4.0
 */

namespace CdV\Shortcodes;

/**
 * Class SondaggioForm
 */
class SondaggioForm {
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
			'cdv_sondaggio_form'
		);

		$sondaggio_id = intval( $atts['id'] );

		if ( ! $sondaggio_id || get_post_type( $sondaggio_id ) !== 'cdv_sondaggio' ) {
			return '';
		}

		$sondaggio = get_post( $sondaggio_id );
		$options = get_post_meta( $sondaggio_id, '_cdv_options', true ) ?: array();
		$aperto = get_post_meta( $sondaggio_id, '_cdv_aperto', true ) !== '0';
		$multiplo = get_post_meta( $sondaggio_id, '_cdv_multiplo', true );
		$mostra_risultati = get_post_meta( $sondaggio_id, '_cdv_mostra_risultati', true ) !== '0';
		$scadenza = get_post_meta( $sondaggio_id, '_cdv_scadenza', true );

		$is_scaduto = $scadenza && strtotime( $scadenza ) < time();

		// Get results
		global $wpdb;
		$table = $wpdb->prefix . 'cdv_sondaggi_voti';
		$results = array();
		$total_partecipanti = 0;

		if ( $mostra_risultati ) {
			$raw_results = $wpdb->get_results( $wpdb->prepare(
				"SELECT option_index, COUNT(*) as votes FROM `{$table}` WHERE sondaggio_id = %d GROUP BY option_index",
				$sondaggio_id
			) );

			$total_partecipanti = $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(DISTINCT user_identifier) FROM `{$table}` WHERE sondaggio_id = %d",
				$sondaggio_id
			) );

			foreach ( $raw_results as $result ) {
				$results[ $result->option_index ] = array(
					'votes'      => intval( $result->votes ),
					'percentage' => $total_partecipanti > 0 ? round( ( $result->votes / $total_partecipanti ) * 100, 1 ) : 0,
				);
			}
		}

		ob_start();
		?>
		<div class="cdv-sondaggio-wrapper" data-sondaggio-id="<?php echo esc_attr( $sondaggio_id ); ?>">
			<?php if ( $scadenza ) : ?>
				<p class="cdv-sondaggio-scadenza">
					<?php if ( $is_scaduto ) : ?>
						<span class="cdv-badge cdv-badge-error"><?php esc_html_e( 'Sondaggio chiuso', 'cronaca-di-viterbo' ); ?></span>
					<?php else : ?>
						<?php esc_html_e( 'Scadenza:', 'cronaca-di-viterbo' ); ?> <?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $scadenza ) ) ); ?>
					<?php endif; ?>
				</p>
			<?php endif; ?>

		<?php if ( $aperto && ! $is_scaduto ) : ?>
			<form class="cdv-sondaggio-form" data-nonce="<?php echo esc_attr( wp_create_nonce( 'cdv_ajax_nonce' ) ); ?>" data-multiplo="<?php echo esc_attr( $multiplo ? '1' : '0' ); ?>" data-sondaggio-id="<?php echo esc_attr( $sondaggio_id ); ?>">
				<div class="cdv-sondaggio-options">
						<?php foreach ( $options as $index => $option ) : ?>
							<label class="cdv-sondaggio-option">
								<input type="<?php echo $multiplo ? 'checkbox' : 'radio'; ?>" name="options<?php echo $multiplo ? '[]' : ''; ?>" value="<?php echo esc_attr( $index ); ?>">
								<span class="cdv-option-text"><?php echo esc_html( $option ); ?></span>
								<?php if ( $mostra_risultati && isset( $results[ $index ] ) ) : ?>
									<span class="cdv-option-stats">
										<span class="cdv-option-percentage"><?php echo esc_html( $results[ $index ]['percentage'] ); ?>%</span>
										<span class="cdv-option-votes">(<?php echo esc_html( number_format_i18n( $results[ $index ]['votes'] ) ); ?> voti)</span>
									</span>
								<?php endif; ?>
							</label>
						<?php endforeach; ?>
					</div>

					<?php if ( $multiplo ) : ?>
						<p class="cdv-sondaggio-hint"><small><?php esc_html_e( 'Puoi selezionare più opzioni', 'cronaca-di-viterbo' ); ?></small></p>
					<?php endif; ?>

					<div class="cdv-form-actions">
						<button type="submit" class="cdv-btn cdv-btn-primary">
							<?php esc_html_e( 'Vota', 'cronaca-di-viterbo' ); ?>
						</button>
					</div>

					<?php if ( $mostra_risultati && $total_partecipanti > 0 ) : ?>
						<p class="cdv-sondaggio-partecipanti">
							<small><?php echo esc_html( sprintf( _n( '%s partecipante', '%s partecipanti', $total_partecipanti, 'cronaca-di-viterbo' ), number_format_i18n( $total_partecipanti ) ) ); ?></small>
						</p>
					<?php endif; ?>

					<div class="cdv-form-message" style="display:none;"></div>
				</form>
			<?php else : ?>
				<p class="cdv-sondaggio-closed">
					<?php esc_html_e( 'Questo sondaggio è chiuso.', 'cronaca-di-viterbo' ); ?>
				</p>

				<?php if ( $mostra_risultati && ! empty( $results ) ) : ?>
					<div class="cdv-sondaggio-results">
						<h4><?php esc_html_e( 'Risultati', 'cronaca-di-viterbo' ); ?></h4>
						<?php foreach ( $options as $index => $option ) : ?>
							<?php if ( isset( $results[ $index ] ) ) : ?>
								<div class="cdv-result-row">
									<span class="cdv-result-option"><?php echo esc_html( $option ); ?></span>
									<div class="cdv-result-bar">
										<div class="cdv-result-fill" style="width: <?php echo esc_attr( $results[ $index ]['percentage'] ); ?>%"></div>
									</div>
									<span class="cdv-result-percentage"><?php echo esc_html( $results[ $index ]['percentage'] ); ?>%</span>
								</div>
							<?php endif; ?>
						<?php endforeach; ?>
						<p class="cdv-sondaggio-partecipanti">
							<small><?php echo esc_html( sprintf( _n( '%s partecipante', '%s partecipanti', $total_partecipanti, 'cronaca-di-viterbo' ), number_format_i18n( $total_partecipanti ) ) ); ?></small>
						</p>
					</div>
				<?php endif; ?>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}
}
