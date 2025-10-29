/**
 * FP Newspaper - Frontend Scripts
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
        FPNewspaper.init();
    });
    
    /**
     * Oggetto principale per gestione frontend
     */
    const FPNewspaper = {
        
        /**
         * Inizializzazione
         */
        init: function() {
            this.trackViews();
            this.handleSharing();
            this.handleReadMore();
        },
        
        /**
         * Traccia visualizzazioni articoli
         */
        trackViews: function() {
            // Solo su articoli singoli
            if (!$('body').hasClass('single-fp_article')) {
                return;
            }
            
            const postId = $('article.fp_article').data('post-id');
            
            if (postId) {
                $.ajax({
                    url: '/wp-json/fp-newspaper/v1/articles/' + postId + '/view',
                    method: 'POST',
                    success: function(response) {
                        console.log('View tracked successfully');
                    },
                    error: function(xhr, status, error) {
                        console.error('Error tracking view:', error);
                    }
                });
            }
        },
        
        /**
         * Gestisce condivisione social
         */
        handleSharing: function() {
            $('.fp-share-button').on('click', function(e) {
                e.preventDefault();
                
                const url = $(this).data('url');
                const network = $(this).data('network');
                
                let shareUrl = '';
                
                switch(network) {
                    case 'facebook':
                        shareUrl = 'https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(url);
                        break;
                    case 'twitter':
                        shareUrl = 'https://twitter.com/intent/tweet?url=' + encodeURIComponent(url);
                        break;
                    case 'linkedin':
                        shareUrl = 'https://www.linkedin.com/sharing/share-offsite/?url=' + encodeURIComponent(url);
                        break;
                    case 'whatsapp':
                        shareUrl = 'https://wa.me/?text=' + encodeURIComponent(url);
                        break;
                }
                
                if (shareUrl) {
                    window.open(shareUrl, 'share-dialog', 'width=600,height=400');
                }
            });
        },
        
        /**
         * Gestisce pulsanti "Leggi di più"
         */
        handleReadMore: function() {
            $('.fp-article-read-more').on('click', function(e) {
                // Animazione smooth scroll se necessario
                const href = $(this).attr('href');
                if (href.indexOf('#') === 0) {
                    e.preventDefault();
                    $('html, body').animate({
                        scrollTop: $(href).offset().top - 100
                    }, 500);
                }
            });
        }
    };
    
})(jQuery);

