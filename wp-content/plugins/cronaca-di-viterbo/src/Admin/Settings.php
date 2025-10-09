<?php
/**
 * Pagina Impostazioni.
 *
 * @package CdV\Admin
 */

namespace CdV\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Classe per le impostazioni del plugin.
 */
class Settings {

	/**
	 * Costruttore.
	 */
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'add_settings_page' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
	}

	/**
	 * Aggiunge la pagina impostazioni.
	 */
	public function add_settings_page() {
		add_submenu_page(
			'cdv-moderation',
			__( 'Impostazioni', 'cronaca-di-viterbo' ),
			__( 'Impostazioni', 'cronaca-di-viterbo' ),
			'manage_options',
			'cdv-settings',
			[ $this, 'render_settings_page' ]
		);
	}

	/**
	 * Registra le impostazioni.
	 */
	public function register_settings() {
		register_setting( 'cdv_settings', 'cdv_enable_ga4' );
		register_setting( 'cdv_settings', 'cdv_enable_schema' );
		register_setting( 'cdv_settings', 'cdv_turnstile_key' );
		register_setting( 'cdv_settings', 'cdv_recaptcha_key' );

		add_settings_section(
			'cdv_general',
			__( 'Impostazioni Generali', 'cronaca-di-viterbo' ),
			null,
			'cdv-settings'
		);

		add_settings_field(
			'cdv_enable_ga4',
			__( 'Abilita tracking GA4', 'cronaca-di-viterbo' ),
			[ $this, 'render_checkbox_field' ],
			'cdv-settings',
			'cdv_general',
			[ 'name' => 'cdv_enable_ga4' ]
		);

		add_settings_field(
			'cdv_enable_schema',
			__( 'Abilita JSON-LD Schema.org', 'cronaca-di-viterbo' ),
			[ $this, 'render_checkbox_field' ],
			'cdv-settings',
			'cdv_general',
			[ 'name' => 'cdv_enable_schema' ]
		);

		add_settings_section(
			'cdv_security',
			__( 'Sicurezza (Roadmap 1.1)', 'cronaca-di-viterbo' ),
			function() {
				echo '<p>' . esc_html__( 'Le seguenti opzioni saranno disponibili nella versione 1.1', 'cronaca-di-viterbo' ) . '</p>';
			},
			'cdv-settings'
		);

		add_settings_field(
			'cdv_turnstile_key',
			__( 'Cloudflare Turnstile Key', 'cronaca-di-viterbo' ),
			[ $this, 'render_text_field' ],
			'cdv-settings',
			'cdv_security',
			[ 'name' => 'cdv_turnstile_key', 'disabled' => true ]
		);

		add_settings_field(
			'cdv_recaptcha_key',
			__( 'Google reCAPTCHA Key', 'cronaca-di-viterbo' ),
			[ $this, 'render_text_field' ],
			'cdv-settings',
			'cdv_security',
			[ 'name' => 'cdv_recaptcha_key', 'disabled' => true ]
		);
	}

	/**
	 * Render campo checkbox.
	 *
	 * @param array $args Argomenti.
	 */
	public function render_checkbox_field( $args ) {
		$value = get_option( $args['name'], 1 );
		?>
		<label>
			<input type="checkbox" name="<?php echo esc_attr( $args['name'] ); ?>" value="1" <?php checked( $value, 1 ); ?>>
			<?php esc_html_e( 'Attivo', 'cronaca-di-viterbo' ); ?>
		</label>
		<?php
	}

	/**
	 * Render campo testo.
	 *
	 * @param array $args Argomenti.
	 */
	public function render_text_field( $args ) {
		$value = get_option( $args['name'], '' );
		$disabled = ! empty( $args['disabled'] ) ? 'disabled' : '';
		?>
		<input type="text" name="<?php echo esc_attr( $args['name'] ); ?>" value="<?php echo esc_attr( $value ); ?>" class="regular-text" <?php echo esc_attr( $disabled ); ?>>
		<?php
	}

	/**
	 * Render pagina impostazioni.
	 */
	public function render_settings_page() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Impostazioni Cronaca di Viterbo', 'cronaca-di-viterbo' ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'cdv_settings' );
				do_settings_sections( 'cdv-settings' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}
}
