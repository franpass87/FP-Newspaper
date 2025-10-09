/**
 * Cronaca di Viterbo - Frontend JavaScript
 */

(function($) {
	'use strict';

	/**
	 * Form Proposta AJAX
	 */
	$(document).on('submit', '#cdv-proposta-form', function(e) {
		e.preventDefault();

		var $form = $(this);
		var $response = $('#cdv-proposta-response');
		var $submitBtn = $form.find('button[type="submit"]');

		// Disabilita pulsante
		$submitBtn.prop('disabled', true).text('Invio in corso...');

		// Dati form
		var formData = {
			action: 'cdv_submit_proposta',
			nonce: $form.find('[name="nonce"]').val(),
			title: $form.find('[name="title"]').val(),
			content: $form.find('[name="content"]').val(),
			quartiere: $form.find('[name="quartiere"]').val(),
			tematica: $form.find('[name="tematica"]').val(),
			privacy: $form.find('[name="privacy"]').is(':checked') ? 'on' : 'off'
		};

		// AJAX
		$.ajax({
			url: cdvData.ajaxUrl,
			type: 'POST',
			data: formData,
			success: function(response) {
				if (response.success) {
					$response
						.removeClass('error')
						.addClass('success')
						.text(response.data.message)
						.show();
					
					// Reset form
					$form[0].reset();

					// GA4 tracking
					if (typeof dataLayer !== 'undefined') {
						dataLayer.push({
							'event': 'proposta_submitted',
							'proposta_id': response.data.id
						});
					}
				} else {
					$response
						.removeClass('success')
						.addClass('error')
						.text(response.data.message)
						.show();
				}
			},
			error: function() {
				$response
					.removeClass('success')
					.addClass('error')
					.text('Errore di connessione. Riprova.')
					.show();
			},
			complete: function() {
				$submitBtn.prop('disabled', false).text('Invia Proposta');
			}
		});
	});

	/**
	 * Voto Proposta AJAX
	 */
	$(document).on('click', '.cdv-vote-btn', function(e) {
		e.preventDefault();

		var $btn = $(this);
		var propostaId = $btn.data('id');
		var $count = $btn.find('.cdv-vote-count');

		if ($btn.hasClass('voted')) {
			return; // Già votato
		}

		// Disabilita pulsante
		$btn.prop('disabled', true);

		$.ajax({
			url: cdvData.ajaxUrl,
			type: 'POST',
			data: {
				action: 'cdv_vote_proposta',
				nonce: cdvData.nonce,
				id: propostaId
			},
			success: function(response) {
				if (response.success) {
					$count.text(response.data.votes);
					$btn.addClass('voted');

					// GA4 tracking
					if (typeof dataLayer !== 'undefined') {
						dataLayer.push({
							'event': 'proposta_voted',
							'proposta_id': propostaId
						});
					}
				} else {
					alert(response.data.message);
					$btn.prop('disabled', false);
				}
			},
			error: function() {
				alert('Errore di connessione.');
				$btn.prop('disabled', false);
			}
		});
	});

})(jQuery);
