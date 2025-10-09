<?php
/**
 * Service per sicurezza e validazione.
 *
 * @package CdV\Services
 */

namespace CdV\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Classe per gestire la sicurezza.
 */
class Security {

	/**
	 * Ottiene l'IP del client in modo sicuro.
	 *
	 * @return string IP address.
	 */
	public static function get_client_ip() {
		$ip = '';

		// Controlla vari header (proxy, cloudflare, etc)
		$headers = [
			'HTTP_CF_CONNECTING_IP', // Cloudflare
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_REAL_IP',
			'REMOTE_ADDR',
		];

		foreach ( $headers as $header ) {
			if ( ! empty( $_SERVER[ $header ] ) ) {
				$ip = sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) );
				// Se è una lista di IP, prendi il primo
				if ( strpos( $ip, ',' ) !== false ) {
					$ip_list = explode( ',', $ip );
					$ip = trim( $ip_list[0] );
				}
				if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
					break;
				}
			}
		}

		return $ip ?: '0.0.0.0';
	}

	/**
	 * Verifica se un IP è in rate limit.
	 *
	 * @param string $action Action name.
	 * @param int    $seconds Secondi di cooldown.
	 * @return bool True se in rate limit.
	 */
	public static function is_rate_limited( $action, $seconds = 60 ) {
		$ip = self::get_client_ip();
		$key = 'cdv_rate_limit_' . $action . '_' . md5( $ip );
		return (bool) get_transient( $key );
	}

	/**
	 * Verifica se il rate limit è OK (non superato).
	 *
	 * @param string $action Action name.
	 * @param int    $seconds Secondi di cooldown.
	 * @return bool True se il rate limit è OK (può procedere), False se è stato superato.
	 */
	public static function check_rate_limit( $action, $seconds = 60 ) {
		if ( self::is_rate_limited( $action, $seconds ) ) {
			return false; // Rate limit superato
		}
		
		// Imposta il rate limit
		self::set_rate_limit( $action, $seconds );
		return true; // OK, può procedere
	}

	/**
	 * Imposta un rate limit.
	 *
	 * @param string $action Action name.
	 * @param int    $seconds Secondi di cooldown.
	 */
	public static function set_rate_limit( $action, $seconds = 60 ) {
		$ip = self::get_client_ip();
		$key = 'cdv_rate_limit_' . $action . '_' . md5( $ip );
		set_transient( $key, true, $seconds );
	}
}
