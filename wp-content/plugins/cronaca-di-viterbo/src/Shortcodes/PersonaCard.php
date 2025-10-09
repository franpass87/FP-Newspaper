<?php
/**
 * Shortcode Card Persona.
 *
 * @package CdV\Shortcodes
 */

namespace CdV\Shortcodes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Classe per lo shortcode [cdv_persona_card].
 */
class PersonaCard {

	/**
	 * Costruttore.
	 */
	public function __construct() {
		add_shortcode( 'cdv_persona_card', [ $this, 'render' ] );
	}

	/**
	 * Render dello shortcode.
	 *
	 * @param array $atts Attributi shortcode.
	 * @return string HTML card persona.
	 */
	public function render( $atts ) {
		$atts = shortcode_atts(
			[
				'id' => 0,
			],
			$atts,
			'cdv_persona_card'
		);

		$post_id = (int) $atts['id'];
		if ( ! $post_id ) {
			return '';
		}

		$persona = get_post( $post_id );
		if ( ! $persona || 'cdv_persona' !== $persona->post_type ) {
			return '';
		}

		$ruolo    = get_post_meta( $post_id, '_cdv_persona_ruolo', true );
		$email    = get_post_meta( $post_id, '_cdv_persona_email', true );
		$telefono = get_post_meta( $post_id, '_cdv_persona_telefono', true );
		$social   = get_post_meta( $post_id, '_cdv_persona_social', true );

		ob_start();
		?>
		<div class="cdv-persona-card">
			<?php if ( has_post_thumbnail( $post_id ) ) : ?>
				<div class="cdv-persona-avatar">
					<?php echo get_the_post_thumbnail( $post_id, 'medium' ); ?>
				</div>
			<?php endif; ?>
			<div class="cdv-persona-content">
				<h3><?php echo esc_html( $persona->post_title ); ?></h3>
				<?php if ( $ruolo ) : ?>
					<p class="cdv-persona-ruolo"><?php echo esc_html( $ruolo ); ?></p>
				<?php endif; ?>
				<?php if ( $persona->post_excerpt ) : ?>
					<p class="cdv-persona-bio"><?php echo esc_html( $persona->post_excerpt ); ?></p>
				<?php endif; ?>
				<div class="cdv-persona-contatti">
					<?php if ( $email ) : ?>
						<a href="mailto:<?php echo esc_attr( $email ); ?>" class="cdv-contatto">
							📧 <?php echo esc_html( $email ); ?>
						</a>
					<?php endif; ?>
					<?php if ( $telefono ) : ?>
						<a href="tel:<?php echo esc_attr( $telefono ); ?>" class="cdv-contatto">
							📞 <?php echo esc_html( $telefono ); ?>
						</a>
					<?php endif; ?>
					<?php if ( $social ) : ?>
						<a href="<?php echo esc_url( $social ); ?>" target="_blank" rel="noopener" class="cdv-contatto">
							🔗 Social
						</a>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}
