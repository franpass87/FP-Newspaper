<?php
/**
 * Helper per rendering views/templates.
 *
 * @package CdV\Utils
 */

namespace CdV\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Classe helper per rendering.
 */
class View {

	/**
	 * Render di un template.
	 *
	 * @param string $template Nome template.
	 * @param array  $data Dati da passare al template.
	 * @return string Output HTML.
	 */
	public static function render( $template, $data = [] ) {
		$template_path = CDV_PLUGIN_DIR . 'templates/' . $template . '.php';

		if ( ! file_exists( $template_path ) ) {
			return '';
		}

		extract( $data ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract

		ob_start();
		include $template_path;
		return ob_get_clean();
	}

	/**
	 * Include un template.
	 *
	 * @param string $template Nome template.
	 * @param array  $data Dati da passare al template.
	 */
	public static function include_template( $template, $data = [] ) {
		echo self::render( $template, $data ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
