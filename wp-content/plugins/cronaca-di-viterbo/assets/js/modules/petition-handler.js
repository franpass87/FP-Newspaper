/**
 * Petition Handler Module
 * Gestisce la firma delle petizioni
 * 
 * @package CdV
 */

(function($) {
	'use strict';

	const PetitionHandler = {
		/**
		 * Inizializza il modulo
		 */
		init() {
			this.bindEvents();
			this.initProgressBars();
		},

		/**
		 * Collega gli eventi
		 */
		bindEvents() {
			$(document).on('submit', '.cdv-petition-form', this.handleSubmit.bind(this));
			$(document).on('click', '.cdv-sign-petition-btn', this.handleQuickSign.bind(this));
		},

		/**
		 * Gestisce invio firma
		 * @param {Event} e - Evento submit
		 */
		handleSubmit(e) {
			e.preventDefault();

			const $form = $(e.target);
			const $submitBtn = $form.find('button[type="submit"]');
			const petizioneId = $form.find('[name="petizione_id"]').val();

			// Disabilita pulsante
			$submitBtn.prop('disabled', true).text('Firma in corso...');

			// Raccolta dati
			const formData = {
				action: 'cdv_firma_petizione',
				nonce: cdvData.nonce,
				petizione_id: petizioneId,
				nome: $form.find('[name="nome"]').val(),
				email: $form.find('[name="email"]').val(),
				privacy: $form.find('[name="privacy"]').is(':checked') ? 'on' : 'off'
			};

			// Invio AJAX
			this.sendSignature(formData, $form, $submitBtn);
		},

		/**
		 * Firma rapida (per utenti loggati)
		 * @param {Event} e - Evento click
		 */
		handleQuickSign(e) {
			e.preventDefault();

			const $btn = $(e.currentTarget);
			const petizioneId = $btn.data('petition-id');

			if ($btn.hasClass('signed')) {
				return; // Già firmato
			}

			$btn.prop('disabled', true);

			$.ajax({
				url: cdvData.ajaxUrl,
				type: 'POST',
				data: {
					action: 'cdv_firma_petizione',
					nonce: cdvData.nonce,
					petizione_id: petizioneId
				},
				success: (response) => {
					if (response.success) {
						$btn.addClass('signed').text('✓ Firmato');
						this.updateSignatureCount(petizioneId, response.data.firme);
						
						// Analytics
						if (window.AnalyticsTracker) {
							window.AnalyticsTracker.trackPetizioneSigned(petizioneId);
						}
					} else {
						alert(response.data.message);
						$btn.prop('disabled', false);
					}
				},
				error: () => {
					alert('Errore di connessione');
					$btn.prop('disabled', false);
				}
			});
		},

		/**
		 * Invia firma via AJAX
		 * @param {Object} formData - Dati firma
		 * @param {jQuery} $form - Form
		 * @param {jQuery} $submitBtn - Pulsante submit
		 */
		sendSignature(formData, $form, $submitBtn) {
			$.ajax({
				url: cdvData.ajaxUrl,
				type: 'POST',
				data: formData,
				success: (response) => {
					if (response.success) {
						this.handleSuccess(response, $form, formData.petizione_id);
					} else {
						this.handleError(response.data.message);
					}
				},
				error: () => {
					this.handleError('Errore di connessione. Riprova.');
				},
				complete: () => {
					$submitBtn.prop('disabled', false).text('Firma Petizione');
				}
			});
		},

		/**
		 * Gestisce successo
		 * @param {Object} response - Risposta AJAX
		 * @param {jQuery} $form - Form
		 * @param {number} petizioneId - ID petizione
		 */
		handleSuccess(response, $form, petizioneId) {
			// Mostra messaggio
			const $message = $('<div>')
				.addClass('cdv-response success')
				.text(response.data.message)
				.insertAfter($form);

			// Reset form
			$form[0].reset();

			// Aggiorna contatore
			this.updateSignatureCount(petizioneId, response.data.firme);

			// Analytics
			if (window.AnalyticsTracker) {
				window.AnalyticsTracker.trackPetizioneSigned(petizioneId);
			}

			// Rimuovi messaggio dopo 5 secondi
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
		 * Aggiorna contatore firme
		 * @param {number} petizioneId - ID petizione
		 * @param {number} count - Numero firme
		 */
		updateSignatureCount(petizioneId, count) {
			$(`.cdv-petition-${petizioneId} .cdv-signatures-count`).text(count);
			this.updateProgressBar(petizioneId);
		},

		/**
		 * Inizializza progress bars
		 */
		initProgressBars() {
			$('.cdv-petition-progress-bar').each(function() {
				const $bar = $(this);
				const current = parseInt($bar.data('current'));
				const goal = parseInt($bar.data('goal'));
				const percentage = Math.min((current / goal) * 100, 100);
				
				$bar.find('.cdv-progress-fill').css('width', percentage + '%');
				$bar.find('.cdv-progress-text').text(`${current} / ${goal}`);
			});
		},

		/**
		 * Aggiorna progress bar
		 * @param {number} petizioneId - ID petizione
		 */
		updateProgressBar(petizioneId) {
			const $progressBar = $(`.cdv-petition-${petizioneId} .cdv-petition-progress-bar`);
			if (!$progressBar.length) return;

			const current = parseInt($(`.cdv-petition-${petizioneId} .cdv-signatures-count`).text());
			const goal = parseInt($progressBar.data('goal'));
			const percentage = Math.min((current / goal) * 100, 100);

			$progressBar.find('.cdv-progress-fill')
				.animate({ width: percentage + '%' }, 500);
			$progressBar.find('.cdv-progress-text').text(`${current} / ${goal}`);

			// Celebra se obiettivo raggiunto
			if (percentage >= 100) {
				this.celebrateGoalReached(petizioneId);
			}
		},

		/**
		 * Celebra obiettivo raggiunto
		 * @param {number} petizioneId - ID petizione
		 */
		celebrateGoalReached(petizioneId) {
			const $petition = $(`.cdv-petition-${petizioneId}`);
			
			// Aggiungi badge
			if (!$petition.find('.cdv-goal-reached').length) {
				$('<span>')
					.addClass('cdv-goal-reached')
					.text('🎉 Obiettivo Raggiunto!')
					.appendTo($petition.find('.cdv-petition-header'));
			}
		}
	};

	// Esporta il modulo
	window.CdV = window.CdV || {};
	window.CdV.PetitionHandler = PetitionHandler;

})(jQuery);
