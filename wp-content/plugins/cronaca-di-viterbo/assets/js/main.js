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

			// Inizializza Form Handler (se caricato)
			if (window.CdV.FormHandler) {
				window.CdV.FormHandler.init();
			}

			// Inizializza Voting System (se caricato)
			if (window.CdV.VotingSystem) {
				window.CdV.VotingSystem.init();
			}

			// Inizializza Petition Handler (se caricato)
			if (window.CdV.PetitionHandler) {
				window.CdV.PetitionHandler.init();
			}

		// Inizializza Poll Handler (se caricato)
		if (window.CdV.PollHandler) {
			window.CdV.PollHandler.init();
		}

		// Log inizializzazione completata (development only)
		if (window.console && window.cdvDebug) {
			const loadedModules = Object.keys(window.CdV).filter(key => 
				typeof window.CdV[key] === 'object' && window.CdV[key].init
			);
			console.log('CdV: Moduli inizializzati:', loadedModules.join(', '));
		}
	}
	};

	// Avvia l'applicazione
	CdVApp.init();

})(jQuery);
