/**
 * Cronaca di Viterbo - Admin JavaScript Entry Point
 * Carica e inizializza tutti i moduli admin
 * 
 * @package CdV
 */

(function($) {
	'use strict';

	/**
	 * Inizializza applicazione admin
	 */
	const CdVAdminApp = {
		/**
		 * Inizializza tutti i moduli
		 */
		init() {
			$(document).ready(() => {
				this.initModules();
			});
		},

		/**
		 * Inizializza i moduli disponibili
		 */
		initModules() {
			// Verifica namespace globale
			if (typeof window.CdVAdmin === 'undefined') {
				console.warn('CdVAdmin namespace non disponibile');
				return;
			}

			// Inizializza Dashboard (solo su pagina dashboard)
			if ($('.cdv-dashboard-widget').length && window.CdVAdmin.Dashboard) {
				window.CdVAdmin.Dashboard.init();
			}

			// Inizializza Moderation (solo su pagine moderazione)
			if ($('.cdv-moderation-table').length && window.CdVAdmin.Moderation) {
				window.CdVAdmin.Moderation.init();
			}

			// Inizializza Settings (solo su pagine impostazioni)
			if ($('.cdv-settings-section').length && window.CdVAdmin.Settings) {
				window.CdVAdmin.Settings.init();
			}

		// Log inizializzazione completata (development only)
		if (window.console && window.cdvDebug) {
			console.log('CdV Admin: Moduli inizializzati correttamente');
		}
		}
	};

	// Avvia l'applicazione admin
	CdVAdminApp.init();

})(jQuery);
