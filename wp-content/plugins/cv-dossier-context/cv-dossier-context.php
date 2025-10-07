<?php
/**
 * Plugin Name: CV Dossier & Context
 * Plugin URI: https://francescopasseri.com
 * Description: Gestisce dossier tematici con schede riassuntive automatiche, timeline eventi e mappe interattive. Include follow-up email per redazioni WordPress.
 * Version: 1.0.2
 * Author: Francesco Passeri
 * Author URI: https://francescopasseri.com
 * License: GPLv2 or later
 * Text Domain: cv-dossier
 */

/**
 * Main plugin bootstrap for CV Dossier & Context.
 *
 * Gestisce dossier tematici con schede riassuntive automatiche, timeline eventi e mappe interattive. Include follow-up email per
 * redazioni WordPress.
 *
 * @package CV_Dossier_Context
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Autoload Composer se disponibile
$autoload = __DIR__ . '/vendor/autoload.php';
if ( is_readable( $autoload ) ) {
    require $autoload;
}

// Carica la classe principale del plugin
require_once __DIR__ . '/includes/class-cv-plugin.php';

// Inizializza il plugin
CV_Plugin::init( __FILE__ );