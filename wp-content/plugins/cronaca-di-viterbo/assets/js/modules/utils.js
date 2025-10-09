/**
 * Utilities Module
 * Funzioni di utility condivise
 * 
 * @package CdV
 */

(function($) {
	'use strict';

	const Utils = {
		/**
		 * Debounce function
		 * @param {Function} func - Funzione da eseguire
		 * @param {number} wait - Millisecondi di attesa
		 * @returns {Function}
		 */
		debounce(func, wait = 300) {
			let timeout;
			return function executedFunction(...args) {
				const later = () => {
					clearTimeout(timeout);
					func(...args);
				};
				clearTimeout(timeout);
				timeout = setTimeout(later, wait);
			};
		},

		/**
		 * Throttle function
		 * @param {Function} func - Funzione da eseguire
		 * @param {number} limit - Limite di tempo in ms
		 * @returns {Function}
		 */
		throttle(func, limit = 300) {
			let inThrottle;
			return function(...args) {
				if (!inThrottle) {
					func.apply(this, args);
					inThrottle = true;
					setTimeout(() => inThrottle = false, limit);
				}
			};
		},

		/**
		 * Formatta un numero con separatore migliaia
		 * @param {number} num - Numero da formattare
		 * @returns {string}
		 */
		formatNumber(num) {
			return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
		},

		/**
		 * Sanitizza una stringa per uso HTML
		 * @param {string} str - Stringa da sanitizzare
		 * @returns {string}
		 */
		escapeHtml(str) {
			const div = document.createElement('div');
			div.textContent = str;
			return div.innerHTML;
		},

		/**
		 * Mostra un messaggio di notifica
		 * @param {string} message - Messaggio
		 * @param {string} type - Tipo (success, error, info)
		 * @param {number} duration - Durata in ms
		 */
		showNotification(message, type = 'info', duration = 3000) {
			const $notification = $('<div>')
				.addClass(`cdv-notification cdv-notification-${type}`)
				.text(message)
				.appendTo('body');

			setTimeout(() => {
				$notification.fadeOut(400, function() {
					$(this).remove();
				});
			}, duration);
		},

		/**
		 * Verifica se un elemento è visibile nel viewport
		 * @param {jQuery|HTMLElement} element - Elemento da verificare
		 * @returns {boolean}
		 */
		isInViewport(element) {
			const $element = $(element);
			if (!$element.length) return false;

			const elementTop = $element.offset().top;
			const elementBottom = elementTop + $element.outerHeight();
			const viewportTop = $(window).scrollTop();
			const viewportBottom = viewportTop + $(window).height();

			return elementBottom > viewportTop && elementTop < viewportBottom;
		},

		/**
		 * Ottiene parametro da URL
		 * @param {string} param - Nome parametro
		 * @returns {string|null}
		 */
		getUrlParameter(param) {
			const urlParams = new URLSearchParams(window.location.search);
			return urlParams.get(param);
		},

		/**
		 * Copia testo negli appunti
		 * @param {string} text - Testo da copiare
		 * @returns {Promise}
		 */
		copyToClipboard(text) {
			if (navigator.clipboard) {
				return navigator.clipboard.writeText(text);
			} else {
				// Fallback per browser più vecchi
				const textarea = document.createElement('textarea');
				textarea.value = text;
				textarea.style.position = 'fixed';
				textarea.style.opacity = '0';
				document.body.appendChild(textarea);
				textarea.select();
				document.execCommand('copy');
				document.body.removeChild(textarea);
				return Promise.resolve();
			}
		}
	};

	// Esporta il modulo
	window.CdV = window.CdV || {};
	window.CdV.Utils = Utils;

})(jQuery);
