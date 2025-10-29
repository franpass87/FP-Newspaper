/**
 * FP Newspaper - Admin Scripts
 * 
 * @package FPNewspaper
 * @version 1.0.0
 */

(function($) {
    'use strict';
    
    /**
     * Inizializza il plugin quando il DOM è pronto
     */
    $(document).ready(function() {
        FPNewspaperAdmin.init();
    });
    
    /**
     * Oggetto principale per gestione admin
     */
    const FPNewspaperAdmin = {
        
        /**
         * Inizializzazione
         */
        init: function() {
            this.handleMetaBoxes();
            this.handleBreakingNews();
            this.handleFeatured();
        },
        
        /**
         * Gestisce i meta boxes
         */
        handleMetaBoxes: function() {
            // Aggiungi classe personalizzata ai meta boxes
            $('#fp_article_options, #fp_article_stats').addClass('fp-article-meta-box');
        },
        
        /**
         * Gestisce Breaking News
         */
        handleBreakingNews: function() {
            const $breakingCheckbox = $('input[name="fp_breaking_news"]');
            
            if ($breakingCheckbox.length) {
                $breakingCheckbox.on('change', function() {
                    if ($(this).is(':checked')) {
                        if (!confirm('Vuoi davvero contrassegnare questo articolo come Breaking News?')) {
                            $(this).prop('checked', false);
                        }
                    }
                });
            }
        },
        
        /**
         * Gestisce articoli in evidenza
         */
        handleFeatured: function() {
            const $featuredCheckbox = $('input[name="fp_featured"]');
            
            if ($featuredCheckbox.length) {
                $featuredCheckbox.on('change', function() {
                    const $label = $(this).closest('label');
                    
                    if ($(this).is(':checked')) {
                        $label.css('font-weight', 'bold');
                    } else {
                        $label.css('font-weight', 'normal');
                    }
                });
                
                // Imposta stato iniziale
                if ($featuredCheckbox.is(':checked')) {
                    $featuredCheckbox.closest('label').css('font-weight', 'bold');
                }
            }
        }
    };
    
})(jQuery);

