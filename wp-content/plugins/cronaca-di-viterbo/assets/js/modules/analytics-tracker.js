/**
 * Analytics Tracker Module
 * Gestisce il tracking GA4 degli eventi
 * 
 * @package CdV
 */

(function() {
	'use strict';

	const AnalyticsTracker = {
		/**
		 * Verifica se GA4 è disponibile
		 * @returns {boolean}
		 */
		isAvailable() {
			return typeof dataLayer !== 'undefined';
		},

		/**
		 * Invia un evento a GA4
		 * @param {string} eventName - Nome dell'evento
		 * @param {Object} eventData - Dati dell'evento
		 */
		track(eventName, eventData = {}) {
			if (!this.isAvailable()) {
				return;
			}

			dataLayer.push({
				event: eventName,
				...eventData
			});
		},

		/**
		 * Traccia invio proposta
		 * @param {number} propostaId - ID della proposta
		 */
		trackPropostaSubmitted(propostaId) {
			this.track('proposta_submitted', {
				proposta_id: propostaId
			});
		},

		/**
		 * Traccia voto proposta
		 * @param {number} propostaId - ID della proposta
		 */
		trackPropostaVoted(propostaId) {
			this.track('proposta_voted', {
				proposta_id: propostaId
			});
		},

		/**
		 * Traccia firma petizione
		 * @param {number} petizioneId - ID della petizione
		 */
		trackPetizioneSigned(petizioneId) {
			this.track('petizione_signed', {
				petizione_id: petizioneId
			});
		},

		/**
		 * Traccia voto sondaggio
		 * @param {number} sondaggioId - ID del sondaggio
		 * @param {string} opzione - Opzione votata
		 */
		trackSondaggioVoted(sondaggioId, opzione) {
			this.track('sondaggio_voted', {
				sondaggio_id: sondaggioId,
				opzione: opzione
			});
		},

		/**
		 * Traccia evento personalizzato
		 * @param {string} category - Categoria evento
		 * @param {string} action - Azione
		 * @param {string} label - Etichetta
		 * @param {number} value - Valore
		 */
		trackCustomEvent(category, action, label = null, value = null) {
			const eventData = {
				event_category: category,
				event_action: action
			};

			if (label) {
				eventData.event_label = label;
			}

			if (value !== null) {
				eventData.event_value = value;
			}

			this.track('custom_event', eventData);
		}
	};

	// Esporta il modulo
	window.AnalyticsTracker = AnalyticsTracker;

})();
