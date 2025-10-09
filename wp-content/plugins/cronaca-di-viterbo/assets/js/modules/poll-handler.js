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
		const sondaggioId = $form.data('sondaggio-id') || $form.closest('.cdv-sondaggio-wrapper').data('sondaggio-id');
		const isMultiplo = $form.data('multiplo') === '1';
		
		// Raccoglie le opzioni selezionate
		let options = [];
		if (isMultiplo) {
			$form.find('input[name="options[]"]:checked').each(function() {
				options.push(parseInt($(this).val()));
			});
		} else {
			const selectedOption = $form.find('input[name="options"]:checked').val();
			if (selectedOption !== undefined) {
				options.push(parseInt(selectedOption));
			}
		}

		if (options.length === 0) {
			alert('Seleziona almeno un\'opzione');
			return;
		}

		// Disabilita pulsante
		$submitBtn.prop('disabled', true).text('Invio voto...');

		// Invio AJAX
		this.sendVote(sondaggioId, options, $form, $submitBtn);
	},

	/**
	 * Voto rapido (click su opzione)
	 * @param {Event} e - Evento click
	 */
	handleQuickVote(e) {
		e.preventDefault();

		const $option = $(e.currentTarget);
		const sondaggioId = $option.data('poll-id');
		const opzioneIndex = parseInt($option.data('option'));

		if ($option.hasClass('voted')) {
			return; // Già votato
		}

		$option.addClass('voting');
		this.sendVote(sondaggioId, [opzioneIndex], null, null, $option);
	},

	/**
	 * Invia voto
	 * @param {number} sondaggioId - ID sondaggio
	 * @param {Array} options - Array di opzioni scelte
	 * @param {jQuery} $form - Form (opzionale)
	 * @param {jQuery} $submitBtn - Pulsante submit (opzionale)
	 * @param {jQuery} $option - Opzione clickata (opzionale)
	 */
	sendVote(sondaggioId, options, $form, $submitBtn, $option) {
		$.ajax({
			url: cdvData.ajaxUrl,
			type: 'POST',
			data: {
				action: 'cdv_vota_sondaggio',
				nonce: cdvData.nonce,
				sondaggio_id: sondaggioId,
				options: options
			},
			success: (response) => {
				if (response.success) {
					this.handleSuccess(response, sondaggioId, options);
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
	 * @param {Array} options - Opzioni votate
	 */
	handleSuccess(response, sondaggioId, options) {
		// Nascondi form
		$(`.cdv-sondaggio-wrapper[data-sondaggio-id="${sondaggioId}"] .cdv-sondaggio-form`).hide();

		// Mostra risultati se disponibili
		if (response.data.results && response.data.results.length > 0) {
			this.displayResults(sondaggioId, response.data.results);
		}

		// Marca come votato
		$(`.cdv-sondaggio-wrapper[data-sondaggio-id="${sondaggioId}"]`).addClass('voted');

		// Analytics
		if (window.AnalyticsTracker) {
			window.AnalyticsTracker.trackSondaggioVoted(sondaggioId, options);
		}

		// Mostra messaggio di successo
		const $wrapper = $(`.cdv-sondaggio-wrapper[data-sondaggio-id="${sondaggioId}"]`);
		const $message = $('<div>')
			.addClass('cdv-response success')
			.text(response.data.message)
			.insertAfter($wrapper.find('.cdv-sondaggio-form'));
		
		setTimeout(() => {
			$message.fadeOut(400, function() {
				$(this).remove();
			});
		}, 5000);
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
	 * @param {Array} risultati - Risultati sondaggio (array di oggetti)
	 */
	displayResults(sondaggioId, risultati) {
		const $wrapper = $(`.cdv-sondaggio-wrapper[data-sondaggio-id="${sondaggioId}"]`);
		let $results = $wrapper.find('.cdv-sondaggio-results');
		
		if (!$results.length) {
			// Crea contenitore risultati
			$results = $('<div>')
				.addClass('cdv-sondaggio-results')
				.insertAfter($wrapper.find('.cdv-sondaggio-form'));
			
			this.renderResults($results, risultati);
		} else {
			this.updateResults($results, risultati);
		}

		$results.slideDown();
	},

	/**
	 * Renderizza risultati
	 * @param {jQuery} $container - Contenitore
	 * @param {Array} risultati - Array di risultati {option, votes, percentage}
	 */
	renderResults($container, risultati) {
		$container.empty();
		
		if (!risultati || risultati.length === 0) {
			return;
		}

		const $title = $('<h4>').text('Risultati').appendTo($container);

		risultati.forEach(result => {
			const $resultRow = $('<div>')
				.addClass('cdv-result-row')
				.html(`
					<span class="cdv-result-option">${result.option}</span>
					<div class="cdv-result-bar">
						<div class="cdv-result-fill" style="width: ${result.percentage}%"></div>
					</div>
					<span class="cdv-result-percentage">${result.percentage}% (${result.votes} voti)</span>
				`);

			$container.append($resultRow);
		});
	},

	/**
	 * Aggiorna risultati esistenti
	 * @param {jQuery} $results - Contenitore risultati
	 * @param {Array} risultati - Nuovi risultati
	 */
	updateResults($results, risultati) {
		if (!risultati || risultati.length === 0) {
			return;
		}

		risultati.forEach(result => {
			const $resultRow = $results.find(`.cdv-result-row:contains("${result.option}")`).first();
			if ($resultRow.length) {
				$resultRow.find('.cdv-result-percentage').text(`${result.percentage}% (${result.votes} voti)`);
				$resultRow.find('.cdv-result-fill').animate({ width: result.percentage + '%' }, 500);
			}
		});
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
