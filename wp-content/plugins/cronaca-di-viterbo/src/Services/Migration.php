<?php
/**
 * Service per migrazioni da versioni precedenti.
 *
 * @package CdV\Services
 */

namespace CdV\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Classe per gestire le migrazioni.
 */
class Migration {

	const DB_VERSION_KEY = 'cdv_db_version';
	const CURRENT_VERSION = '1.0.0';

	/**
	 * Esegue le migrazioni necessarie.
	 */
	public static function run() {
		$current_version = get_option( self::DB_VERSION_KEY, '0.0.0' );

		if ( version_compare( $current_version, self::CURRENT_VERSION, '<' ) ) {
			self::migrate_from_old_plugin();
			update_option( self::DB_VERSION_KEY, self::CURRENT_VERSION );
		}
	}

	/**
	 * Migra dati dal vecchio plugin.
	 */
	private static function migrate_from_old_plugin() {
		global $wpdb;

		// Migra meta chiavi da _cv_ a _cdv_
		$wpdb->query(
			"UPDATE {$wpdb->postmeta} 
			SET meta_key = REPLACE(meta_key, '_cv_', '_cdv_') 
			WHERE meta_key LIKE '_cv_%'"
		);

		// Migra opzioni da cv_ a cdv_
		$wpdb->query(
			"UPDATE {$wpdb->options} 
			SET option_name = REPLACE(option_name, 'cv_', 'cdv_') 
			WHERE option_name LIKE 'cv_%'"
		);

		// Migra CPT cv_dossier a cdv_dossier (se esiste)
		$wpdb->query(
			"UPDATE {$wpdb->posts} 
			SET post_type = 'cdv_dossier' 
			WHERE post_type = 'cv_dossier'"
		);

		// Migra CPT cv_dossier_event a cdv_evento (se esiste)
		$wpdb->query(
			"UPDATE {$wpdb->posts} 
			SET post_type = 'cdv_evento' 
			WHERE post_type = 'cv_dossier_event'"
		);

		// Nota: le tassonomie custom dovranno essere rimappate manualmente se necessario
		// oppure con uno script WP-CLI dedicato

		// Pulisci cache
		wp_cache_flush();
	}

	/**
	 * Ottiene la versione corrente del DB.
	 *
	 * @return string Version.
	 */
	public static function get_db_version() {
		return get_option( self::DB_VERSION_KEY, '0.0.0' );
	}
}
