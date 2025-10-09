/**
 * Admin Dashboard Module
 * Gestisce la dashboard amministrativa
 * 
 * @package CdV
 */

(function($) {
	'use strict';

	const AdminDashboard = {
		/**
		 * Inizializza il modulo
		 */
		init() {
			this.initCharts();
			this.initRefreshStats();
			this.initQuickActions();
		},

		/**
		 * Inizializza i grafici
		 */
	initCharts() {
		// Placeholder per integrazione chart.js o similar
		if (window.console && window.cdvDebug) {
			console.log('Dashboard charts initialized');
		}
	},

		/**
		 * Inizializza refresh statistiche
		 */
		initRefreshStats() {
			$(document).on('click', '.cdv-refresh-stats', (e) => {
				e.preventDefault();
				this.refreshStatistics();
			});
		},

		/**
		 * Aggiorna le statistiche
		 */
		refreshStatistics() {
			const $btn = $('.cdv-refresh-stats');
			const $stats = $('.cdv-stats-grid');

			$btn.prop('disabled', true);
			$stats.css('opacity', '0.5');

			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'cdv_refresh_stats',
					nonce: cdvAdminData.nonce
				},
				success: (response) => {
					if (response.success) {
						this.updateStatsDisplay(response.data);
						this.showNotice('Statistiche aggiornate!', 'success');
					}
				},
				error: () => {
					this.showNotice('Errore aggiornamento statistiche', 'error');
				},
				complete: () => {
					$btn.prop('disabled', false);
					$stats.css('opacity', '1');
				}
			});
		},

		/**
		 * Aggiorna visualizzazione statistiche
		 * @param {Object} data - Dati statistiche
		 */
		updateStatsDisplay(data) {
			Object.keys(data).forEach(key => {
				$(`.cdv-stat-${key} .cdv-stat-number`).text(data[key]);
			});
		},

		/**
		 * Inizializza quick actions
		 */
		initQuickActions() {
			// Gestione azioni rapide
			$('.cdv-quick-action[data-action]').on('click', function(e) {
				e.preventDefault();
				const action = $(this).data('action');
				AdminDashboard.handleQuickAction(action);
			});
		},

		/**
		 * Gestisce azione rapida
		 * @param {string} action - Azione da eseguire
		 */
		handleQuickAction(action) {
			switch(action) {
				case 'export':
					this.exportData();
					break;
				case 'import':
					this.importData();
					break;
				case 'clear-cache':
					this.clearCache();
					break;
			default:
				if (window.console && window.cdvDebug) {
					console.log('Unknown action:', action);
				}
		}
		},

		/**
		 * Export dati
		 */
		exportData() {
			this.showNotice('Export in corso...', 'info');
			// Implementazione export
		},

		/**
		 * Import dati
		 */
		importData() {
			// Trigger file upload
			$('#cdv-import-file').trigger('click');
		},

		/**
		 * Pulisci cache
		 */
		clearCache() {
			if (!confirm('Vuoi pulire la cache?')) return;

			$.post(ajaxurl, {
				action: 'cdv_clear_cache',
				nonce: cdvAdminData.nonce
			}, (response) => {
				if (response.success) {
					this.showNotice('Cache pulita!', 'success');
				}
			});
		},

		/**
		 * Mostra notifica admin
		 * @param {string} message - Messaggio
		 * @param {string} type - Tipo (success, error, info)
		 */
		showNotice(message, type = 'info') {
			const $notice = $('<div>')
				.addClass(`cdv-admin-notice ${type}`)
				.html(`<p>${message}</p>`)
				.appendTo('.wrap');

			setTimeout(() => {
				$notice.fadeOut(400, function() {
					$(this).remove();
				});
			}, 3000);
		}
	};

	// Esporta il modulo
	window.CdVAdmin = window.CdVAdmin || {};
	window.CdVAdmin.Dashboard = AdminDashboard;

})(jQuery);
