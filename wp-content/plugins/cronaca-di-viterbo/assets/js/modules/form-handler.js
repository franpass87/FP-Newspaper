/**
 * Form Handler Module
 * Gestisce l'invio dei form proposte via AJAX
 * 
 * @package CdV
 */

(function($) {
	'use strict';

	const FormHandler = {
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
			$(document).on('submit', '#cdv-proposta-form', this.handleSubmit.bind(this));
		},

		/**
		 * Gestisce l'invio del form
		 * @param {Event} e - Evento submit
		 */
		handleSubmit(e) {
			e.preventDefault();

			const $form = $(e.target);
			const $response = $('#cdv-proposta-response');
			const $submitBtn = $form.find('button[type="submit"]');

			// Disabilita pulsante
			$submitBtn.prop('disabled', true).text('Invio in corso...');

			// Raccolta dati form
			const formData = this.collectFormData($form);

			// Invio AJAX
			this.sendAjaxRequest(formData, $form, $response, $submitBtn);
		},

		/**
		 * Raccoglie i dati dal form
		 * @param {jQuery} $form - Form jQuery object
		 * @returns {Object} Dati del form
		 */
		collectFormData($form) {
			return {
				action: 'cdv_submit_proposta',
				nonce: $form.find('[name="nonce"]').val(),
				title: $form.find('[name="title"]').val(),
				content: $form.find('[name="content"]').val(),
				quartiere: $form.find('[name="quartiere"]').val(),
				tematica: $form.find('[name="tematica"]').val(),
				privacy: $form.find('[name="privacy"]').is(':checked') ? 'on' : 'off'
			};
		},

		/**
		 * Invia la richiesta AJAX
		 * @param {Object} formData - Dati da inviare
		 * @param {jQuery} $form - Form jQuery object
		 * @param {jQuery} $response - Elemento di risposta
		 * @param {jQuery} $submitBtn - Pulsante submit
		 */
		sendAjaxRequest(formData, $form, $response, $submitBtn) {
			$.ajax({
				url: cdvData.ajaxUrl,
				type: 'POST',
				data: formData,
				success: (response) => {
					this.handleSuccess(response, $form, $response);
				},
				error: () => {
					this.handleError($response);
				},
				complete: () => {
					$submitBtn.prop('disabled', false).text('Invia Proposta');
				}
			});
		},

		/**
		 * Gestisce la risposta di successo
		 * @param {Object} response - Risposta AJAX
		 * @param {jQuery} $form - Form jQuery object
		 * @param {jQuery} $response - Elemento di risposta
		 */
		handleSuccess(response, $form, $response) {
			if (response.success) {
				$response
					.removeClass('error')
					.addClass('success')
					.text(response.data.message)
					.show();
				
				// Reset form
				$form[0].reset();

				// GA4 tracking
				if (window.AnalyticsTracker) {
					window.AnalyticsTracker.trackPropostaSubmitted(response.data.id);
				}
			} else {
				$response
					.removeClass('success')
					.addClass('error')
					.text(response.data.message)
					.show();
			}
		},

		/**
		 * Gestisce gli errori
		 * @param {jQuery} $response - Elemento di risposta
		 */
		handleError($response) {
			$response
				.removeClass('success')
				.addClass('error')
				.text('Errore di connessione. Riprova.')
				.show();
		}
	};

	// Esporta il modulo
	window.CdV = window.CdV || {};
	window.CdV.FormHandler = FormHandler;

})(jQuery);
