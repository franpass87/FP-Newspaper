jQuery(function($) {
    'use strict';

    var localized = window.CVDossier || {};

    // Handle dossier follow form submission
    $(document).on('submit', '.cv-follow', function(e) {
        e.preventDefault();

        var $form = $(this);
        var $submitBtn = $form.find('button[type="submit"]');
        var originalText = $submitBtn.text();

        $form.find('.cv-follow__error').remove();

        // Disable form during submission
        $submitBtn.prop('disabled', true).text(localized.submitLoadingText || '…');
        $form.attr('aria-busy', 'true');

        var data = {
            action: 'cv_follow_dossier',
            nonce: localized.nonce,
            email: $form.find('input[name="email"]').val(),
            dossier_id: $form.find('input[name="dossier_id"]').val()
        };

        if (!localized.ajax) {
            console.error('CVDossier ajax endpoint missing');
            $submitBtn.prop('disabled', false).text(originalText);
            $form.attr('aria-busy', 'false');
            return;
        }

        function showError(message) {
            var errorText = message || localized.followGenericError || 'Errore durante l\'iscrizione. Riprova.';
            var $error = $form.find('.cv-follow__error');
            if ($error.length) {
                $error.text(errorText);
            } else {
                $error = $('<div class="cv-follow__error" role="alert" tabindex="-1"></div>').text(errorText);
                $form.append($error);
            }
            setTimeout(function() {
                $error.trigger('focus');
            }, 0);
            $submitBtn.prop('disabled', false).text(originalText);
            $form.attr('aria-busy', 'false');
        }

        $.post(localized.ajax, data, function(response) {
            if (response && response.success) {
                // Replace form with success message
                var successHtml = localized.followSuccessHtml || '<span class="cv-ok" role="status" tabindex="-1">OK</span>';
                var $message = $(successHtml);
                $form.replaceWith($message);
                if ($message.attr('tabindex') !== undefined) {
                    setTimeout(function() {
                        $message.trigger('focus');
                    }, 0);
                }

                // Track successful follow for analytics
                if (typeof gtag !== 'undefined') {
                    gtag('event', 'dossier_follow', {
                        'dossier_id': data.dossier_id,
                        'event_category': 'engagement'
                    });
                }
                $form.attr('aria-busy', 'false');
            } else {
                // Show error message
                var errorMessage = (response && response.data && response.data.message)
                    ? response.data.message
                    : (localized.followGenericError || 'Errore durante l\'iscrizione. Riprova.');
                showError(errorMessage);
            }
        }).fail(function(jqXHR, textStatus) {
            var fallbackNetwork = localized.followNetworkError || 'Errore di connessione. Riprova.';
            var fallbackGeneric = localized.followGenericError || 'Errore durante l\'iscrizione. Riprova.';
            var message = '';

            if (jqXHR && jqXHR.responseJSON) {
                if (jqXHR.responseJSON.data && jqXHR.responseJSON.data.message) {
                    message = jqXHR.responseJSON.data.message;
                } else if (jqXHR.responseJSON.message) {
                    message = jqXHR.responseJSON.message;
                }
            } else if (jqXHR && jqXHR.responseText) {
                try {
                    var parsed = JSON.parse(jqXHR.responseText);
                    if (parsed && parsed.data && parsed.data.message) {
                        message = parsed.data.message;
                    } else if (parsed && parsed.message) {
                        message = parsed.message;
                    }
                } catch (err) {
                    message = '';
                }
            }

            if (!message) {
                message = (jqXHR && jqXHR.status) ? fallbackGeneric : fallbackNetwork;
            }

            showError(message);
        });
    });

    // Track dossier interactions for analytics
    $('.cv-card').on('click', '[data-ga4]', function() {
        var action = $(this).data('ga4');
        var dossierId = $(this).closest('[data-dossier]').data('dossier');
        
        if (typeof gtag !== 'undefined' && action && dossierId) {
            gtag('event', action, {
                'dossier_id': dossierId,
                'event_category': 'dossier_interaction'
            });
        }
    });

    // Email validation enhancement
    $(document).on('input', '.cv-follow input[type="email"]', function() {
        var $input = $(this);
        var email = $input.val();
        var $submitBtn = $input.closest('.cv-follow').find('button[type="submit"]');
        $input.closest('.cv-follow').find('.cv-follow__error').remove();

        // Simple email validation
        var isValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);

        if (email.length > 0) {
            if (isValid) {
                $input.removeClass('invalid').addClass('valid');
                $submitBtn.prop('disabled', false);
            } else {
                $input.removeClass('valid').addClass('invalid');
                $submitBtn.prop('disabled', true);
            }
        } else {
            $input.removeClass('valid invalid');
            $submitBtn.prop('disabled', false);
        }
    });

    // Timeline item click tracking
    $('.cv-timeline').on('click', '.cv-tl-item', function() {
        if (typeof gtag !== 'undefined') {
            gtag('event', 'timeline_item_click', {
                'event_category': 'dossier_interaction'
            });
        }
    });

    // Map marker interaction (if Leaflet is available)
    if (typeof L !== 'undefined') {
        // This will be called when maps are created
        window.cvTrackMapInteraction = function(action, data) {
            if (typeof gtag !== 'undefined') {
                gtag('event', action, {
                    'event_category': 'map_interaction',
                    'custom_data': data
                });
            }
        };
    }

    function hasLeafletContainer($map) {
        return $map.hasClass('leaflet-container') || $map.find('.leaflet-container').length > 0;
    }

    function showMapError($map, code) {
        if ($map.children('.cv-map-error').length) {
            return;
        }

        var message = localized.mapErrorGeneric || 'Impossibile caricare la mappa.';
        if (code === 'leaflet-unavailable' && localized.mapErrorLeaflet) {
            message = localized.mapErrorLeaflet;
        } else if (code === 'timeout' && localized.mapErrorTimeout) {
            message = localized.mapErrorTimeout;
        }

        var $error = $('<div class="cv-map-error" role="alert" aria-live="assertive" tabindex="-1"></div>');
        $('<div class="cv-map-error__title"></div>').text(message).appendTo($error);

        if (localized.mapErrorRefresh) {
            $('<button type="button" class="cv-map-error__action"></button>')
                .text(localized.mapErrorRefresh)
                .on('click', function() {
                    window.location.reload();
                })
                .appendTo($error);
        }

        $map.append($error);
        setTimeout(function() {
            $error.trigger('focus');
        }, 0);
    }

    function removeLoadingWhenReady($map, attempts) {
        if (hasLeafletContainer($map)) {
            $map.find('.cv-map-loading').remove();
            return;
        }

        var errorCode = $map.attr('data-map-error');
        if (errorCode) {
            $map.find('.cv-map-loading').remove();
            showMapError($map, errorCode);
            return;
        }

        if (attempts > 20) { // ~6s fallback to avoid endless polling
            $map.find('.cv-map-loading').remove();
            showMapError($map, 'timeout');
            return;
        }

        setTimeout(function() {
            removeLoadingWhenReady($map, attempts + 1);
        }, 300);
    }

    // Initialize any maps that might need special handling
    $('.cv-map').each(function() {
        var $map = $(this);

        if (!hasLeafletContainer($map) && !$map.children('.cv-map-loading').length) {
            var $loading = $('<div class="cv-map-loading" role="status" aria-live="polite"></div>');
            $loading.text(localized.mapLoadingLabel || 'Caricamento mappa...');
            $map.append($loading);
        }

        if (window.MutationObserver && !$map.data('cvMapObserver')) {
            var observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'attributes' && mutation.attributeName === 'data-map-error') {
                        var code = $map.attr('data-map-error');
                        $map.find('.cv-map-loading').remove();
                        showMapError($map, code);
                    }
                });
            });
            observer.observe($map.get(0), { attributes: true });
            $map.data('cvMapObserver', observer);
        }

        removeLoadingWhenReady($map, 0);
    });

    // Map image lightbox
    var lightboxHtml = '<div class="cv-map-lightbox" role="dialog" aria-modal="true" aria-hidden="true" aria-label="' + (localized.lightboxDialogLabel || 'Immagine ingrandita') + '">' +
        '<div class="cv-map-lightbox__inner">' +
        '<button type="button" class="cv-map-lightbox__close" aria-label="' + (localized.lightboxCloseLabel || 'Chiudi immagine') + '">&times;</button>' +
        '<img src="" alt="" />' +
        '</div></div>';
    var $lightbox = $(lightboxHtml).appendTo('body');
    var lastFocusedElement = null;
    var focusableSelectors = 'a[href], button:not([disabled]), input:not([disabled]), textarea:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])';

    function closeLightbox() {
        $lightbox.removeClass('is-visible');
        $lightbox.find('img').attr({ src: '', alt: '' });
        $('body').removeClass('cv-map-lightbox-open');
        $lightbox.attr('aria-hidden', 'true');
        if (lastFocusedElement) {
            $(lastFocusedElement).trigger('focus');
            lastFocusedElement = null;
        }
    }

    function openLightbox($link) {
        var full = $link.attr('href') || $link.data('full');
        if (!full) {
            return;
        }
        var alt = $link.data('alt') || '';
        lastFocusedElement = document.activeElement;
        $lightbox.find('img').attr({ src: full, alt: alt });
        $lightbox.addClass('is-visible');
        $('body').addClass('cv-map-lightbox-open');
        $lightbox.attr('aria-hidden', 'false');
        setTimeout(function() {
            $lightbox.find('.cv-map-lightbox__close').trigger('focus');
        }, 10);
    }

    $(document).on('click', '.cv-map-popup-image', function(e) {
        e.preventDefault();
        openLightbox($(this));
    });

    $(document).on('keydown', '.cv-map-popup-image', function(e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            openLightbox($(this));
        }
    });

    $lightbox.on('click', function(e) {
        if ($(e.target).closest('.cv-map-lightbox__inner').length === 0) {
            closeLightbox();
        }
    });

    $lightbox.on('click', '.cv-map-lightbox__close', function(e) {
        e.preventDefault();
        closeLightbox();
    });

    $(document).on('keyup', function(e) {
        if (e.key === 'Escape' && $lightbox.hasClass('is-visible')) {
            closeLightbox();
        }
    });

    $lightbox.on('keydown', function(e) {
        if (e.key !== 'Tab' || !$lightbox.hasClass('is-visible')) {
            return;
        }

        var $focusable = $lightbox.find(focusableSelectors).filter(':visible');
        if (!$focusable.length) {
            e.preventDefault();
            return;
        }

        var first = $focusable.first()[0];
        var last = $focusable.last()[0];

        if (e.shiftKey && document.activeElement === first) {
            e.preventDefault();
            last.focus();
        } else if (!e.shiftKey && document.activeElement === last) {
            e.preventDefault();
            first.focus();
        }
    });

    // Accessibility improvements
    $('.cv-card').each(function() {
        var $card = $(this);
        
        // Add ARIA labels for screen readers
        $card.attr('role', 'complementary');
        $card.attr('aria-label', localized.cardAriaLabel || 'Scheda dossier');
        
        // Make follow form more accessible
        var $followForm = $card.find('.cv-follow');
        if ($followForm.length) {
            $followForm.attr('aria-label', localized.followFormAriaLabel || 'Modulo per seguire il dossier');
            $followForm.find('input[type="email"]').attr('aria-label', localized.followEmailAriaLabel || 'Inserisci la tua email per ricevere aggiornamenti');
        }
    });
});
