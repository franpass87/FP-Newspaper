jQuery(function($) {
    'use strict';

    var $container = $('#cv-map-markers');
    if (!$container.length || typeof CVDossierAdmin === 'undefined') {
        return;
    }

    var template = CVDossierAdmin.markerTemplate || '';
    var noImageText = CVDossierAdmin.noImage || '';

    function refreshIndices() {
        $container.find('.cv-map-marker').each(function(index) {
            var $marker = $(this);
            $marker.attr('data-index', index);
            $marker.find('.cv-map-marker__number').text(index + 1);

            $marker.find('input, textarea').each(function() {
                var $field = $(this);
                var name = $field.attr('name');
                if (!name) {
                    return;
                }
                $field.attr('name', name.replace(/\[\d+\]/, '[' + index + ']'));
            });
        });
    }

    function clearMarker($marker) {
        $marker.find('input[type="text"], input[type="number"], input[type="url"], textarea').val('');
        $marker.find('.cv-map-marker__image-id').val('');
        $marker.find('.cv-map-marker__image-preview').html('<span class="cv-map-marker__image-placeholder">' + noImageText + '</span>');
    }

    function appendMarker() {
        var index = $container.find('.cv-map-marker').length;
        if (!template) {
            return;
        }
        var html = template.replace(/__INDEX__/g, index);
        var $newMarker = $(html);
        $container.append($newMarker);
        refreshIndices();
    }

    $('#cv-map-add-marker').on('click', function(e) {
        e.preventDefault();
        appendMarker();
    });

    $container.on('click', '.cv-map-marker__remove', function(e) {
        e.preventDefault();
        var $marker = $(this).closest('.cv-map-marker');
        var total = $container.find('.cv-map-marker').length;
        if (total <= 1) {
            clearMarker($marker);
            return;
        }
        $marker.remove();
        refreshIndices();
    });

    $container.on('click', '.cv-map-marker__select-media', function(e) {
        e.preventDefault();
        var $marker = $(this).closest('.cv-map-marker');
        var frame = wp.media({
            title: CVDossierAdmin.chooseImage,
            multiple: false,
            library: { type: 'image' }
        });

        frame.on('select', function() {
            var attachment = frame.state().get('selection').first().toJSON();
            $marker.find('.cv-map-marker__image-id').val(attachment.id);
            $marker.find('.cv-map-marker__image-url').val('');
            $marker.find('.cv-map-marker__image-alt').val(attachment.alt || '');
            if (attachment.sizes && attachment.sizes.medium) {
                $marker.find('.cv-map-marker__image-preview').html('<img src="' + attachment.sizes.medium.url + '" alt="" />');
            } else {
                $marker.find('.cv-map-marker__image-preview').html('<img src="' + attachment.url + '" alt="" />');
            }
        });

        frame.open();
    });

    $container.on('click', '.cv-map-marker__clear-media', function(e) {
        e.preventDefault();
        var $marker = $(this).closest('.cv-map-marker');
        $marker.find('.cv-map-marker__image-id').val('');
        $marker.find('.cv-map-marker__image-url').val('');
        $marker.find('.cv-map-marker__image-preview').html('<span class="cv-map-marker__image-placeholder">' + noImageText + '</span>');
    });

    $container.on('input', '.cv-map-marker__image-url', function() {
        var $input = $(this);
        var url = $input.val();
        var $marker = $input.closest('.cv-map-marker');
        if (url) {
            $marker.find('.cv-map-marker__image-id').val('');
            $marker.find('.cv-map-marker__image-preview').html('<img src="' + url + '" alt="" />');
        }
    });

    refreshIndices();
});
