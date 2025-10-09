/**
 * Admin Settings Module
 * Gestisce le impostazioni amministrative
 * 
 * @package CdV
 */

(function($) {
	'use strict';

	const AdminSettings = {
		/**
		 * Inizializza il modulo
		 */
		init() {
			this.initTabs();
			this.initToggleSwitches();
			this.initFormChanges();
			this.initColorPickers();
		},

		/**
		 * Inizializza tabs
		 */
		initTabs() {
			$('.cdv-settings-tab').on('click', function(e) {
				e.preventDefault();
				
				const target = $(this).data('tab');
				
				// Update tabs
				$('.cdv-settings-tab').removeClass('active');
				$(this).addClass('active');
				
				// Update content
				$('.cdv-settings-tab-content').removeClass('active');
				$(`#${target}`).addClass('active');
				
				// Update URL hash
				window.location.hash = target;
			});

			// Attiva tab da hash URL
			if (window.location.hash) {
				const hash = window.location.hash.substring(1);
				$(`.cdv-settings-tab[data-tab="${hash}"]`).trigger('click');
			}
		},

		/**
		 * Inizializza toggle switches
		 */
		initToggleSwitches() {
			$('.cdv-toggle input').on('change', function() {
				const $toggle = $(this).closest('.cdv-toggle');
				const value = $(this).is(':checked');
				
				// Trigger event personalizzato
				$toggle.trigger('cdv-toggle-change', [value]);
			});
		},

		/**
		 * Traccia modifiche form
		 */
		initFormChanges() {
			let formChanged = false;

			$('.cdv-settings-section input, .cdv-settings-section select, .cdv-settings-section textarea').on('change', function() {
				formChanged = true;
				$('.cdv-settings-save-bar').addClass('show');
			});

			// Previeni uscita senza salvare
			$(window).on('beforeunload', function() {
				if (formChanged) {
					return 'Ci sono modifiche non salvate. Sei sicuro di voler uscire?';
				}
			});

			// Reset flag al submit
			$('form.cdv-settings-form').on('submit', function() {
				formChanged = false;
			});

			// Pulsante salva sticky
			$('.cdv-settings-save-bar .button-primary').on('click', function(e) {
				e.preventDefault();
				$('form.cdv-settings-form').submit();
			});
		},

		/**
		 * Inizializza color pickers
		 */
		initColorPickers() {
			if (typeof $.fn.wpColorPicker !== 'undefined') {
				$('.cdv-color-picker').wpColorPicker({
					change: function() {
						$(this).trigger('change');
					}
				});
			}
		},

		/**
		 * Valida form
		 * @param {jQuery} $form - Form da validare
		 * @returns {boolean}
		 */
		validateForm($form) {
			let valid = true;

			$form.find('[required]').each(function() {
				if (!$(this).val()) {
					$(this).addClass('error');
					valid = false;
				} else {
					$(this).removeClass('error');
				}
			});

			// Valida email
			$form.find('input[type="email"]').each(function() {
				const email = $(this).val();
				if (email && !this.isValidEmail(email)) {
					$(this).addClass('error');
					valid = false;
				}
			});

			return valid;
		},

		/**
		 * Valida email
		 * @param {string} email - Email da validare
		 * @returns {boolean}
		 */
		isValidEmail(email) {
			const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
			return re.test(email);
		},

		/**
		 * Import/Export settings
		 */
		initImportExport() {
			// Export
			$('.cdv-export-settings').on('click', function(e) {
				e.preventDefault();
				AdminSettings.exportSettings();
			});

			// Import
			$('.cdv-import-settings').on('click', function(e) {
				e.preventDefault();
				$('#cdv-import-file').trigger('click');
			});

			$('#cdv-import-file').on('change', function() {
				AdminSettings.importSettings(this.files[0]);
			});
		},

		/**
		 * Esporta impostazioni
		 */
		exportSettings() {
			$.post(ajaxurl, {
				action: 'cdv_export_settings',
				nonce: cdvAdminData.nonce
			}, (response) => {
				if (response.success) {
					const dataStr = JSON.stringify(response.data, null, 2);
					const dataUri = 'data:application/json;charset=utf-8,'+ encodeURIComponent(dataStr);
					
					const exportFileDefaultName = 'cdv-settings-' + Date.now() + '.json';
					
					const linkElement = document.createElement('a');
					linkElement.setAttribute('href', dataUri);
					linkElement.setAttribute('download', exportFileDefaultName);
					linkElement.click();
				}
			});
		},

		/**
		 * Importa impostazioni
		 * @param {File} file - File da importare
		 */
		importSettings(file) {
			if (!file) return;

			const reader = new FileReader();
			reader.onload = (e) => {
				try {
					const settings = JSON.parse(e.target.result);
					
					$.post(ajaxurl, {
						action: 'cdv_import_settings',
						nonce: cdvAdminData.nonce,
						settings: settings
					}, (response) => {
						if (response.success) {
							alert('Impostazioni importate con successo!');
							location.reload();
						} else {
							alert('Errore importazione: ' + response.data.message);
						}
					});
				} catch(err) {
					alert('File non valido');
				}
			};
			reader.readAsText(file);
		}
	};

	// Esporta il modulo
	window.CdVAdmin = window.CdVAdmin || {};
	window.CdVAdmin.Settings = AdminSettings;

})(jQuery);
