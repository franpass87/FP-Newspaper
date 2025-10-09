<?php
/**
 * Plugin Name: Cronaca di Viterbo
 * Plugin URI: https://francescopasseri.com
 * Description: Plugin modulare per il giornale "Cronaca di Viterbo": gestione dossier, proposte cittadini, eventi, ambasciatori civici, con integrazione WPBakery, AJAX, GA4, SEO e ruoli personalizzati.
 * Version: 1.5.0
 * Author: Francesco Passeri
 * Author URI: https://francescopasseri.com
 * License: GPLv2 or later
 * Text Domain: cronaca-di-viterbo
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.0
 */

namespace CdV;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Definizione costanti plugin
define( 'CDV_VERSION', '1.0.0' );
define( 'CDV_PLUGIN_FILE', __FILE__ );
define( 'CDV_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CDV_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'CDV_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Autoload Composer
$autoload = __DIR__ . '/vendor/autoload.php';
if ( is_readable( $autoload ) ) {
	require_once $autoload;
}

// Carica la classe Bootstrap
require_once CDV_PLUGIN_DIR . 'src/Bootstrap.php';

// Inizializza il plugin
Bootstrap::init();
