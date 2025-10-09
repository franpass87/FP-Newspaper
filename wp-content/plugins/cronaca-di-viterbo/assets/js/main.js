/**
 * Cronaca di Viterbo - Frontend JavaScript Entry Point
 * Carica e inizializza tutti i moduli
 * 
 * @package CdV
 */

(function($) {
	'use strict';

	/**
	 * Inizializza l'applicazione
	 */
	const CdVApp = {
		/**
		 * Inizializza tutti i moduli
		 */
		init() {
			// Inizializza moduli quando il DOM è pronto
			$(document).ready(() => {
				this.initModules();
			});
		},

		/**
		 * Inizializza i moduli disponibili
		 */
		initModules() {
			// Verifica namespace globale
			if (typeof window.CdV === 'undefined') {
				console.warn('CdV namespace non disponibile');
				return;
			}

			// Inizializza Form Handler
			if (window.CdV.FormHandler) {
				window.CdV.FormHandler.init();
			}

			// Inizializza Voting System
			if (window.CdV.VotingSystem) {
				window.CdV.VotingSystem.init();
			}

			// Log inizializzazione completata
			if (window.console && window.console.log) {
				console.log('CdV: Moduli inizializzati correttamente');
			}
		}
	};

	// Avvia l'applicazione
	CdVApp.init();

})(jQuery);
