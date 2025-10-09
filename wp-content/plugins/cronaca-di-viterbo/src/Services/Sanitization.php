<?php
/**
 * Service per sanitizzazione dati.
 *
 * @package CdV\Services
 */

namespace CdV\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Classe per gestire la sanitizzazione.
 */
class Sanitization {

	/**
	 * Sanitizza un titolo.
	 *
	 * @param string $title Titolo da sanitizzare.
	 * @return string Titolo sanitizzato.
	 */
	public static function sanitize_title( $title ) {
		return sanitize_text_field( trim( $title ) );
	}

	/**
	 * Sanitizza un contenuto (permette alcuni tag HTML).
	 *
	 * @param string $content Contenuto da sanitizzare.
	 * @return string Contenuto sanitizzato.
	 */
	public static function sanitize_content( $content ) {
		$allowed_tags = [
			'p'      => [],
			'br'     => [],
			'strong' => [],
			'em'     => [],
			'b'      => [],
			'i'      => [],
			'u'      => [],
			'a'      => [
				'href'   => [],
				'title'  => [],
				'target' => [],
			],
		];

		return wp_kses( trim( $content ), $allowed_tags );
	}

	/**
	 * Sanitizza un URL.
	 *
	 * @param string $url URL da sanitizzare.
	 * @return string URL sanitizzato.
	 */
	public static function sanitize_url( $url ) {
		return esc_url_raw( trim( $url ) );
	}

	/**
	 * Sanitizza un email.
	 *
	 * @param string $email Email da sanitizzare.
	 * @return string Email sanitizzato.
	 */
	public static function sanitize_email( $email ) {
		return sanitize_email( trim( $email ) );
	}
}
