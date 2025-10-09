/**
 * Cronaca di Viterbo - Frontend JavaScript
 * @version 1.2.0
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
		$submitBtn.prop('disabled', true).text(cdvData.strings.loading || 'Invio in corso...');

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
					if (typeof dataLayer !== 'undefined' && response.data.ga4_event) {
						dataLayer.push(response.data.ga4_event);
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
					.text(cdvData.strings.error || 'Errore di connessione. Riprova.')
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
					if (typeof dataLayer !== 'undefined' && response.data.ga4_event) {
						dataLayer.push(response.data.ga4_event);
					}
				} else {
					alert(response.data.message);
					$btn.prop('disabled', false);
				}
			},
			error: function() {
				alert(cdvData.strings.error || 'Errore di connessione.');
				$btn.prop('disabled', false);
			}
		});
	});

	/**
	 * Firma Petizione AJAX
	 */
	$(document).on('submit', '.cdv-petizione-form', function(e) {
		e.preventDefault();

		var $form = $(this);
		var $wrapper = $form.closest('.cdv-petizione-form-wrapper');
		var $message = $form.find('.cdv-form-message');
		var $submitBtn = $form.find('button[type="submit"]');
		var petizioneId = $wrapper.data('petizione-id');

		// Disabilita pulsante
		$submitBtn.prop('disabled', true).text(cdvData.strings.loading || 'Invio in corso...');

		// Dati form
		var formData = {
			action: 'cdv_firma_petizione',
			nonce: $form.data('nonce'),
			petizione_id: petizioneId,
			nome: $form.find('[name="nome"]').val(),
			cognome: $form.find('[name="cognome"]').val(),
			email: $form.find('[name="email"]').val(),
			comune: $form.find('[name="comune"]').val(),
			motivazione: $form.find('[name="motivazione"]').val(),
			privacy: $form.find('[name="privacy"]').is(':checked') ? 'on' : 'off'
		};

		// AJAX
		$.ajax({
			url: cdvData.ajaxUrl,
			type: 'POST',
			data: formData,
			success: function(response) {
				if (response.success) {
					$message
						.removeClass('error')
						.addClass('success')
						.html('<p>' + response.data.message + '</p>')
						.show();
					
					// Update count
					$wrapper.find('.cdv-firme-count').text(response.data.firme_count);
					$wrapper.find('.cdv-progress-fill').css('width', Math.min(response.data.percentuale, 100) + '%');

					// Reset form
					$form.fadeOut(300, function() {
						$(this).html('<p class="cdv-thank-you">' + response.data.message + '</p>').fadeIn(300);
					});

					// GA4 tracking
					if (typeof dataLayer !== 'undefined' && response.data.ga4_event) {
						dataLayer.push(response.data.ga4_event);
					}
				} else {
					$message
						.removeClass('success')
						.addClass('error')
						.html('<p>' + response.data.message + '</p>')
						.show();
					$submitBtn.prop('disabled', false).text('Firma Petizione');
				}
			},
			error: function() {
				$message
					.removeClass('success')
					.addClass('error')
					.html('<p>' + (cdvData.strings.error || 'Errore di connessione. Riprova.') + '</p>')
					.show();
				$submitBtn.prop('disabled', false).text('Firma Petizione');
			}
		});
	});

	/**
	 * Vota Sondaggio AJAX
	 */
	$(document).on('submit', '.cdv-sondaggio-form', function(e) {
		e.preventDefault();

		var $form = $(this);
		var $wrapper = $form.closest('.cdv-sondaggio-wrapper');
		var $message = $form.find('.cdv-form-message');
		var $submitBtn = $form.find('button[type="submit"]');
		var sondaggioId = $wrapper.data('sondaggio-id');
		var isMultiplo = $form.data('multiplo') === '1';

		// Get selected options
		var selectedOptions = [];
		$form.find('input[name="options"]:checked, input[name="options[]"]:checked').each(function() {
			selectedOptions.push($(this).val());
		});

		// Validation
		if (selectedOptions.length === 0) {
			alert('Seleziona almeno un\'opzione');
			return;
		}

		// Disabilita pulsante
		$submitBtn.prop('disabled', true).text(cdvData.strings.loading || 'Invio in corso...');

		// AJAX
		$.ajax({
			url: cdvData.ajaxUrl,
			type: 'POST',
			data: {
				action: 'cdv_vota_sondaggio',
				nonce: $form.data('nonce'),
				sondaggio_id: sondaggioId,
				options: selectedOptions
			},
			success: function(response) {
				if (response.success) {
					$message
						.removeClass('error')
						.addClass('success')
						.html('<p>' + response.data.message + '</p>')
						.show();

					// Update results if available
					if (response.data.results && response.data.results.length > 0) {
						updateSondaggioResults($wrapper, response.data.results);
					}

					// Disable form
					$form.find('input').prop('disabled', true);
					$submitBtn.hide();

					// GA4 tracking
					if (typeof dataLayer !== 'undefined' && response.data.ga4_event) {
						dataLayer.push(response.data.ga4_event);
					}
				} else {
					$message
						.removeClass('success')
						.addClass('error')
						.html('<p>' + response.data.message + '</p>')
						.show();
					$submitBtn.prop('disabled', false).text('Vota');
				}
			},
			error: function() {
				$message
					.removeClass('success')
					.addClass('error')
					.html('<p>' + (cdvData.strings.error || 'Errore di connessione. Riprova.') + '</p>')
					.show();
				$submitBtn.prop('disabled', false).text('Vota');
			}
		});
	});

	/**
	 * Update sondaggio results display
	 */
	function updateSondaggioResults($wrapper, results) {
		results.forEach(function(result, index) {
			var $option = $wrapper.find('.cdv-sondaggio-option').eq(index);
			var $stats = $option.find('.cdv-option-stats');
			
			if ($stats.length === 0) {
				$stats = $('<span class="cdv-option-stats"></span>').appendTo($option);
			}

			$stats.html(
				'<span class="cdv-option-percentage">' + result.percentage + '%</span>' +
				'<span class="cdv-option-votes">(' + result.votes + ' voti)</span>'
			);
		});
	}

	/**
	 * GA4 - Dossier Read Tracking (60 secondi)
	 */
	if ($('body').hasClass('single-cdv_dossier')) {
		var dossierId = $('[data-dossier-id]').data('dossier-id');
		var dossierTitle = $('h1.entry-title').text();
		
		setTimeout(function() {
			if (typeof dataLayer !== 'undefined') {
				dataLayer.push({
					'event': 'dossier_read_60s',
					'dossier_id': dossierId,
					'dossier_title': dossierTitle
				});
			}
		}, 60000); // 60 secondi
	}

	/**
	 * Character counter per textarea
	 */
	$('textarea[maxlength]').each(function() {
		var $textarea = $(this);
		var maxLength = $textarea.attr('maxlength');
		var $counter = $('<small class="char-counter"></small>');
		
		$textarea.after($counter);
		
		function updateCounter() {
			var remaining = maxLength - $textarea.val().length;
			$counter.text(remaining + ' caratteri rimanenti');
		}
		
		updateCounter();
		$textarea.on('input', updateCounter);
	});

	/**
	 * Smooth scroll to anchors
	 */
	$('a[href^="#"]').on('click', function(e) {
		var target = $(this.hash);
		if (target.length) {
			e.preventDefault();
			$('html, body').animate({
				scrollTop: target.offset().top - 100
			}, 500);
		}
	});

	/**
	 * Lazy load images
	 */
	if ('IntersectionObserver' in window) {
		var imageObserver = new IntersectionObserver(function(entries) {
			entries.forEach(function(entry) {
				if (entry.isIntersecting) {
					var img = entry.target;
					img.src = img.dataset.src;
					img.classList.add('loaded');
					imageObserver.unobserve(img);
				}
			});
		});

		$('.cdv-lazy-image').each(function() {
			imageObserver.observe(this);
		});
	}

	/**
	 * Tooltips
	 */
	$('[data-tooltip]').on('mouseenter', function() {
		var $this = $(this);
		var text = $this.data('tooltip');
		var $tooltip = $('<div class="cdv-tooltip">' + text + '</div>');
		
		$('body').append($tooltip);
		
		var pos = $this.offset();
		$tooltip.css({
			top: pos.top - $tooltip.outerHeight() - 10,
			left: pos.left + ($this.outerWidth() / 2) - ($tooltip.outerWidth() / 2)
		}).fadeIn(200);
		
		$this.data('tooltip-el', $tooltip);
	}).on('mouseleave', function() {
		var $tooltip = $(this).data('tooltip-el');
		if ($tooltip) {
			$tooltip.fadeOut(200, function() {
				$(this).remove();
			});
		}
	});

})(jQuery);
