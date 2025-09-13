jQuery(function($) {
    'use strict';

    // Handle dossier follow form submission
    $(document).on('submit', '.cv-follow', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $submitBtn = $form.find('button[type="submit"]');
        var originalText = $submitBtn.text();
        
        // Disable form during submission
        $submitBtn.prop('disabled', true).text('...');
        
        var data = {
            action: 'cv_follow_dossier',
            nonce: CVDossier.nonce,
            email: $form.find('input[name="email"]').val(),
            dossier_id: $form.find('input[name="dossier_id"]').val()
        };
        
        $.post(CVDossier.ajax, data, function(response) {
            if (response && response.success) {
                // Replace form with success message
                $form.replaceWith('<span class="cv-ok">Iscritto ✔</span>');
                
                // Track successful follow for analytics
                if (typeof gtag !== 'undefined') {
                    gtag('event', 'dossier_follow', {
                        'dossier_id': data.dossier_id,
                        'event_category': 'engagement'
                    });
                }
            } else {
                // Show error message
                var errorMessage = (response && response.data && response.data.message) 
                    ? response.data.message 
                    : 'Errore durante l\'iscrizione. Riprova.';
                alert(errorMessage);
                
                // Re-enable form
                $submitBtn.prop('disabled', false).text(originalText);
            }
        }).fail(function() {
            alert('Errore di connessione. Riprova.');
            $submitBtn.prop('disabled', false).text(originalText);
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

    // Initialize any maps that might need special handling
    $('.cv-map').each(function() {
        var $map = $(this);
        
        // Add loading indicator
        if (!$map.find('.leaflet-container').length) {
            $map.append('<div class="cv-map-loading">Caricamento mappa...</div>');
        }
        
        // Clean up loading indicator when map loads
        setTimeout(function() {
            if ($map.find('.leaflet-container').length) {
                $map.find('.cv-map-loading').remove();
            }
        }, 2000);
    });

    // Accessibility improvements
    $('.cv-card').each(function() {
        var $card = $(this);
        
        // Add ARIA labels for screen readers
        $card.attr('role', 'complementary');
        $card.attr('aria-label', 'Scheda dossier');
        
        // Make follow form more accessible
        var $followForm = $card.find('.cv-follow');
        if ($followForm.length) {
            $followForm.attr('aria-label', 'Modulo per seguire il dossier');
            $followForm.find('input[type="email"]').attr('aria-label', 'Inserisci la tua email per ricevere aggiornamenti');
        }
    });
});