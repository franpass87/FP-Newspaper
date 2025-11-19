/**
 * FP Newspaper - Frontend JavaScript
 * Animazioni, accessibilità e interazioni frontend
 * 
 * @package FPNewspaper
 * @version 1.6.0
 */

(function($) {
    'use strict';
    
    /**
     * FP Newspaper Frontend App
     */
    const FPNewspaper = {
        
        /**
         * Initialize
         */
        init() {
            this.initFadeInAnimations();
            this.initAccessibility();
            this.initLazyLoad();
        },
        
        /**
         * Fade-in animations on scroll (Intersection Observer)
         */
        initFadeInAnimations() {
            // Check browser support
            if (!('IntersectionObserver' in window)) {
                // Fallback: mostra subito senza animazione
                $('.fp-fade-in').addClass('fp-visible');
                return;
            }
            
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };
            
            const observer = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('fp-visible');
                        // Unobserve dopo animazione (performance)
                        observer.unobserve(entry.target);
                    }
                });
            }, observerOptions);
            
            // Observe elementi
            document.querySelectorAll('.fp-author-box, .fp-related-articles').forEach(function(el) {
                el.classList.add('fp-fade-in');
                observer.observe(el);
            });
        },
        
        /**
         * Accessibility enhancements
         */
        initAccessibility() {
            // Focus visible solo da tastiera
            document.body.addEventListener('mousedown', function() {
                document.body.classList.add('using-mouse');
            });
            
            document.body.addEventListener('keydown', function() {
                document.body.classList.remove('using-mouse');
            });
            
            // Skip to content link (se non esiste già)
            if (!$('#fp-skip-to-content').length) {
                $('<a id="fp-skip-to-content" href="#content" class="fp-sr-only fp-focus-visible">Skip to content</a>')
                    .prependTo('body')
                    .on('click', function(e) {
                        e.preventDefault();
                        $('#content, main, [role="main"]').first().focus();
                    });
            }
        },
        
        /**
         * Lazy load images (se supportato)
         */
        initLazyLoad() {
            if ('loading' in HTMLImageElement.prototype) {
                // Browser supporta lazy loading nativo
                $('.fp-related-thumb img').attr('loading', 'lazy');
            } else {
                // Fallback con Intersection Observer
                if ('IntersectionObserver' in window) {
                    const lazyImages = document.querySelectorAll('.fp-related-thumb img');
                    
                    const imageObserver = new IntersectionObserver(function(entries) {
                        entries.forEach(function(entry) {
                            if (entry.isIntersecting) {
                                const img = entry.target;
                                if (img.dataset.src) {
                                    img.src = img.dataset.src;
                                    img.removeAttribute('data-src');
                                }
                                imageObserver.unobserve(img);
                            }
                        });
                    });
                    
                    lazyImages.forEach(function(img) {
                        imageObserver.observe(img);
                    });
                }
            }
        }
    };
    
    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        FPNewspaper.init();
    });
    
    // Expose to global scope (per debug/estensioni)
    window.FPNewspaper = FPNewspaper;
    
})(jQuery);
