/**
 * Voting System Module
 * Gestisce il sistema di votazione delle proposte
 * 
 * @package CdV
 */

(function($) {
	'use strict';

	const VotingSystem = {
		/**
		 * Inizializza il modulo
		 */
		init() {
			this.bindEvents();
		},

		/**
		 * Collega gli eventi
		 */
		bindEvents() {
			$(document).on('click', '.cdv-vote-btn', this.handleVote.bind(this));
		},

		/**
		 * Gestisce il click sul pulsante voto
		 * @param {Event} e - Evento click
		 */
		handleVote(e) {
			e.preventDefault();

			const $btn = $(e.currentTarget);
			const propostaId = $btn.data('id');
			const $count = $btn.find('.cdv-vote-count');

			// Verifica se già votato
			if ($btn.hasClass('voted')) {
				return;
			}

			// Disabilita pulsante
			$btn.prop('disabled', true);

			// Invia voto
			this.sendVote(propostaId, $btn, $count);
		},

		/**
		 * Invia il voto via AJAX
		 * @param {number} propostaId - ID della proposta
		 * @param {jQuery} $btn - Pulsante di voto
		 * @param {jQuery} $count - Elemento contatore voti
		 */
		sendVote(propostaId, $btn, $count) {
			$.ajax({
				url: cdvData.ajaxUrl,
				type: 'POST',
				data: {
					action: 'cdv_vote_proposta',
					nonce: cdvData.nonce,
					id: propostaId
				},
				success: (response) => {
					this.handleSuccess(response, propostaId, $btn, $count);
				},
				error: () => {
					this.handleError($btn);
				}
			});
		},

		/**
		 * Gestisce la risposta di successo
		 * @param {Object} response - Risposta AJAX
		 * @param {number} propostaId - ID della proposta
		 * @param {jQuery} $btn - Pulsante di voto
		 * @param {jQuery} $count - Elemento contatore voti
		 */
		handleSuccess(response, propostaId, $btn, $count) {
			if (response.success) {
				$count.text(response.data.votes);
				$btn.addClass('voted');

				// GA4 tracking
				if (window.AnalyticsTracker) {
					window.AnalyticsTracker.trackPropostaVoted(propostaId);
				}
			} else {
				alert(response.data.message);
				$btn.prop('disabled', false);
			}
		},

		/**
		 * Gestisce gli errori
		 * @param {jQuery} $btn - Pulsante di voto
		 */
		handleError($btn) {
			alert('Errore di connessione.');
			$btn.prop('disabled', false);
		}
	};

	// Esporta il modulo
	window.CdV = window.CdV || {};
	window.CdV.VotingSystem = VotingSystem;

})(jQuery);
