/**
 * Admin Moderation Module
 * Gestisce la moderazione contenuti
 * 
 * @package CdV
 */

(function($) {
	'use strict';

	const AdminModeration = {
		/**
		 * Inizializza il modulo
		 */
		init() {
			this.initBulkActions();
			this.initQuickActions();
			this.initFilters();
		},

		/**
		 * Inizializza azioni bulk
		 */
		initBulkActions() {
			$('#doaction, #doaction2').on('click', (e) => {
				const action = $(e.target).siblings('select').val();
				if (action === '-1') {
					e.preventDefault();
					return;
				}

				const checked = $('tbody th.check-column input[type="checkbox"]:checked');
				if (checked.length === 0) {
					e.preventDefault();
					alert('Seleziona almeno un elemento');
					return;
				}

				if (!confirm(`Eseguire azione "${action}" su ${checked.length} elementi?`)) {
					e.preventDefault();
				}
			});
		},

		/**
		 * Inizializza azioni rapide
		 */
		initQuickActions() {
			// Approva
			$(document).on('click', '.cdv-action-approve', (e) => {
				e.preventDefault();
				const id = $(e.target).closest('tr').data('id');
				this.moderateContent(id, 'approve');
			});

			// Rifiuta
			$(document).on('click', '.cdv-action-reject', (e) => {
				e.preventDefault();
				const id = $(e.target).closest('tr').data('id');
				
				if (confirm('Sei sicuro di voler rifiutare questo contenuto?')) {
					this.moderateContent(id, 'reject');
				}
			});

			// Elimina
			$(document).on('click', '.cdv-action-delete', (e) => {
				e.preventDefault();
				const id = $(e.target).closest('tr').data('id');
				
				if (confirm('Sei sicuro di voler eliminare definitivamente?')) {
					this.moderateContent(id, 'delete');
				}
			});
		},

		/**
		 * Modera contenuto
		 * @param {number} id - ID contenuto
		 * @param {string} action - Azione (approve, reject, delete)
		 */
		moderateContent(id, action) {
			const $row = $(`tr[data-id="${id}"]`);
			
			$row.css('opacity', '0.5');

			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'cdv_moderate_content',
					nonce: cdvAdminData.nonce,
					id: id,
					moderate_action: action
				},
				success: (response) => {
					if (response.success) {
						if (action === 'delete') {
							$row.fadeOut(400, function() {
								$(this).remove();
							});
						} else {
							this.updateRowStatus($row, action);
						}
						this.showNotice(response.data.message, 'success');
					} else {
						this.showNotice(response.data.message, 'error');
						$row.css('opacity', '1');
					}
				},
				error: () => {
					this.showNotice('Errore di connessione', 'error');
					$row.css('opacity', '1');
				}
			});
		},

		/**
		 * Aggiorna stato riga
		 * @param {jQuery} $row - Riga tabella
		 * @param {string} status - Nuovo stato
		 */
		updateRowStatus($row, status) {
			const statusMap = {
				approve: 'approved',
				reject: 'rejected'
			};

			const $badge = $row.find('.cdv-status-badge');
			$badge
				.removeClass('pending approved rejected')
				.addClass(statusMap[status])
				.text(statusMap[status]);

			$row.css('opacity', '1');
		},

		/**
		 * Inizializza filtri
		 */
		initFilters() {
			$('.cdv-filter-status, .cdv-filter-date').on('change', function() {
				$(this).closest('form').submit();
			});
		},

		/**
		 * Mostra notifica
		 * @param {string} message - Messaggio
		 * @param {string} type - Tipo
		 */
		showNotice(message, type) {
			const $notice = $('<div>')
				.addClass(`cdv-admin-notice ${type}`)
				.html(`<p>${message}</p>`)
				.prependTo('.wrap');

			setTimeout(() => {
				$notice.fadeOut(400, function() {
					$(this).remove();
				});
			}, 3000);
		}
	};

	// Esporta il modulo
	window.CdVAdmin = window.CdVAdmin || {};
	window.CdVAdmin.Moderation = AdminModeration;

})(jQuery);
