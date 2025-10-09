/**
 * Poll Handler Module
 * Gestisce i sondaggi interattivi
 * 
 * @package CdV
 */

(function($) {
	'use strict';

	const PollHandler = {
		/**
		 * Inizializza il modulo
		 */
		init() {
			this.bindEvents();
			this.initCharts();
		},

		/**
		 * Collega gli eventi
		 */
		bindEvents() {
			$(document).on('submit', '.cdv-poll-form', this.handleSubmit.bind(this));
			$(document).on('click', '.cdv-poll-option', this.handleQuickVote.bind(this));
			$(document).on('click', '.cdv-view-results', this.showResults.bind(this));
		},

		/**
		 * Gestisce invio voto
		 * @param {Event} e - Evento submit
		 */
		handleSubmit(e) {
			e.preventDefault();

			const $form = $(e.target);
			const $submitBtn = $form.find('button[type="submit"]');
			const sondaggioId = $form.find('[name="sondaggio_id"]').val();
			const opzione = $form.find('input[name="opzione"]:checked').val();

			if (!opzione) {
				alert('Seleziona un\'opzione');
				return;
			}

			// Disabilita pulsante
			$submitBtn.prop('disabled', true).text('Invio voto...');

			// Invio AJAX
			this.sendVote(sondaggioId, opzione, $form, $submitBtn);
		},

		/**
		 * Voto rapido (click su opzione)
		 * @param {Event} e - Evento click
		 */
		handleQuickVote(e) {
			e.preventDefault();

			const $option = $(e.currentTarget);
			const sondaggioId = $option.data('poll-id');
			const opzione = $option.data('option');

			if ($option.hasClass('voted')) {
				return; // Già votato
			}

			$option.addClass('voting');
			this.sendVote(sondaggioId, opzione, null, null, $option);
		},

		/**
		 * Invia voto
		 * @param {number} sondaggioId - ID sondaggio
		 * @param {string} opzione - Opzione scelta
		 * @param {jQuery} $form - Form (opzionale)
		 * @param {jQuery} $submitBtn - Pulsante submit (opzionale)
		 * @param {jQuery} $option - Opzione clickata (opzionale)
		 */
		sendVote(sondaggioId, opzione, $form, $submitBtn, $option) {
			$.ajax({
				url: cdvData.ajaxUrl,
				type: 'POST',
				data: {
					action: 'cdv_vota_sondaggio',
					nonce: cdvData.nonce,
					sondaggio_id: sondaggioId,
					opzione: opzione
				},
				success: (response) => {
					if (response.success) {
						this.handleSuccess(response, sondaggioId, opzione);
					} else {
						this.handleError(response.data.message);
					}
				},
				error: () => {
					this.handleError('Errore di connessione');
				},
				complete: () => {
					if ($submitBtn) {
						$submitBtn.prop('disabled', false).text('Vota');
					}
					if ($option) {
						$option.removeClass('voting');
					}
				}
			});
		},

		/**
		 * Gestisce successo
		 * @param {Object} response - Risposta AJAX
		 * @param {number} sondaggioId - ID sondaggio
		 * @param {string} opzione - Opzione votata
		 */
		handleSuccess(response, sondaggioId, opzione) {
			// Nascondi form
			$(`.cdv-poll-${sondaggioId} .cdv-poll-form`).hide();

			// Mostra risultati
			this.displayResults(sondaggioId, response.data.risultati);

			// Marca come votato
			$(`.cdv-poll-${sondaggioId}`).addClass('voted');

			// Analytics
			if (window.AnalyticsTracker) {
				window.AnalyticsTracker.trackSondaggioVoted(sondaggioId, opzione);
			}

			// Messaggio
			if (window.CdV && window.CdV.Utils) {
				window.CdV.Utils.showNotification('Voto registrato!', 'success');
			}
		},

		/**
		 * Gestisce errore
		 * @param {string} message - Messaggio errore
		 */
		handleError(message) {
			alert(message);
		},

		/**
		 * Mostra risultati
		 * @param {Event} e - Evento click
		 */
		showResults(e) {
			e.preventDefault();

			const sondaggioId = $(e.currentTarget).data('poll-id');
			$(`.cdv-poll-${sondaggioId} .cdv-poll-results`).slideDown();
		},

		/**
		 * Visualizza risultati
		 * @param {number} sondaggioId - ID sondaggio
		 * @param {Object} risultati - Risultati sondaggio
		 */
		displayResults(sondaggioId, risultati) {
			const $results = $(`.cdv-poll-${sondaggioId} .cdv-poll-results`);
			
			if (!$results.length) {
				// Crea contenitore risultati
				const $resultsContainer = $('<div>')
					.addClass('cdv-poll-results')
					.insertAfter($(`.cdv-poll-${sondaggioId} .cdv-poll-form`));
				
				this.renderResults($resultsContainer, risultati);
			} else {
				this.updateResults($results, risultati);
			}

			$results.slideDown();
		},

		/**
		 * Renderizza risultati
		 * @param {jQuery} $container - Contenitore
		 * @param {Object} risultati - Risultati
		 */
		renderResults($container, risultati) {
			const totaleVoti = Object.values(risultati).reduce((a, b) => a + b, 0);

			$container.empty();

			Object.keys(risultati).forEach(opzione => {
				const voti = risultati[opzione];
				const percentuale = totaleVoti > 0 ? Math.round((voti / totaleVoti) * 100) : 0;

				const $option = $('<div>')
					.addClass('cdv-poll-result-item')
					.html(`
						<div class="cdv-poll-result-label">
							<span>${opzione}</span>
							<span>${percentuale}% (${voti} voti)</span>
						</div>
						<div class="cdv-poll-result-bar">
							<div class="cdv-poll-result-fill" style="width: ${percentuale}%"></div>
						</div>
					`);

				$container.append($option);
			});

			// Totale voti
			$('<div>')
				.addClass('cdv-poll-total')
				.text(`Totale voti: ${totaleVoti}`)
				.appendTo($container);
		},

		/**
		 * Aggiorna risultati esistenti
		 * @param {jQuery} $results - Contenitore risultati
		 * @param {Object} risultati - Nuovi risultati
		 */
		updateResults($results, risultati) {
			const totaleVoti = Object.values(risultati).reduce((a, b) => a + b, 0);

			Object.keys(risultati).forEach(opzione => {
				const voti = risultati[opzione];
				const percentuale = totaleVoti > 0 ? Math.round((voti / totaleVoti) * 100) : 0;

				const $item = $results.find(`.cdv-poll-result-item:contains("${opzione}")`);
				$item.find('.cdv-poll-result-label span:last-child').text(`${percentuale}% (${voti} voti)`);
				$item.find('.cdv-poll-result-fill').animate({ width: percentuale + '%' }, 500);
			});

			$results.find('.cdv-poll-total').text(`Totale voti: ${totaleVoti}`);
		},

		/**
		 * Inizializza grafici
		 */
		initCharts() {
			// Placeholder per eventuali grafici Chart.js
			$('.cdv-poll-results').each(function() {
				// Inizializza grafico se necessario
			});
		}
	};

	// Esporta il modulo
	window.CdV = window.CdV || {};
	window.CdV.PollHandler = PollHandler;

})(jQuery);
