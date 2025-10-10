/**
 * Cronaca di Viterbo - AI Chatbot Script
 * @since 2.0.0
 */

(function($) {
	'use strict';

	const CdvChatbot = {
		$widget: null,
		$window: null,
		$messages: null,
		$input: null,
		$form: null,
		isOpen: false,
		conversationHistory: [],

		init() {
			this.cacheElements();
			this.bindEvents();
			this.loadHistory();
		},

		cacheElements() {
			this.$widget = $('#cdv-chatbot-widget');
			this.$window = $('#cdv-chatbot-window');
			this.$messages = $('#cdv-chat-messages');
			this.$input = $('#cdv-chat-input');
			this.$form = $('#cdv-chat-form');
		},

		bindEvents() {
			// Toggle chat
			$('#cdv-chatbot-toggle, .cdv-chat-minimize').on('click', (e) => {
				e.preventDefault();
				this.toggleChat();
			});

			// Submit message
			this.$form.on('submit', (e) => {
				e.preventDefault();
				this.sendMessage();
			});

			// Quick action buttons
			$(document).on('click', '.cdv-quick-btn', (e) => {
				e.preventDefault();
				const message = $(e.currentTarget).data('message');
				this.$input.val(message);
				this.sendMessage();
			});

			// Auto-resize input
			this.$input.on('input', () => {
				this.autoResizeInput();
			});

			// Enter to send (Shift+Enter for new line)
			this.$input.on('keydown', (e) => {
				if (e.key === 'Enter' && !e.shiftKey) {
					e.preventDefault();
					this.sendMessage();
				}
			});
		},

		toggleChat() {
			this.isOpen = !this.isOpen;
			
			if (this.isOpen) {
				this.$widget.addClass('cdv-chatbot-open').removeClass('cdv-chatbot-closed');
				this.$input.focus();
				this.scrollToBottom();
				this.hideNotificationBadge();
				
				// Track open event
				if (typeof gtag !== 'undefined') {
					gtag('event', 'chatbot_opened', {
						'event_category': 'Engagement',
						'event_label': 'Chatbot'
					});
				}
			} else {
				this.$widget.addClass('cdv-chatbot-closed').removeClass('cdv-chatbot-open');
			}
		},

		sendMessage() {
			const message = this.$input.val().trim();
			
			if (!message) return;

			// Add user message to UI
			this.addMessage(message, 'user');
			
			// Clear input
			this.$input.val('').trigger('input');
			
			// Show typing indicator
			this.showTyping();
			
			// Save to history
			this.conversationHistory.push({
				role: 'user',
				content: message,
				timestamp: Date.now()
			});
			this.saveHistory();

			// Send to server
			$.ajax({
				url: cdvData.ajaxUrl,
				type: 'POST',
				data: {
					action: 'cdv_chatbot_message',
					nonce: cdvData.nonce,
					message: message
				},
				success: (response) => {
					this.hideTyping();
					
					if (response.success && response.data.message) {
						this.addMessage(response.data.message, 'bot');
						
						// Save bot response to history
						this.conversationHistory.push({
							role: 'bot',
							content: response.data.message,
							timestamp: Date.now()
						});
						this.saveHistory();
					} else {
						this.addMessage(
							response.data?.message || 'Si è verificato un errore. Riprova.',
							'bot',
							true
						);
					}
				},
				error: () => {
					this.hideTyping();
					this.addMessage(
						'Errore di connessione. Verifica la tua connessione internet.',
						'bot',
						true
					);
				}
			});
		},

		addMessage(content, role = 'bot', isError = false) {
			const timestamp = new Date().toLocaleTimeString('it-IT', {
				hour: '2-digit',
				minute: '2-digit'
			});

			const avatar = role === 'user' 
				? '👤' 
				: '🤖';

			const messageClass = `cdv-message cdv-message-${role}${isError ? ' cdv-message-error' : ''}`;

			const $message = $(`
				<div class="${messageClass}">
					<div class="cdv-message-avatar">${avatar}</div>
					<div class="cdv-message-bubble">
						<p>${content}</p>
					</div>
				</div>
			`);

			// Remove quick actions after first message
			if (role === 'user') {
				$('.cdv-quick-actions').fadeOut(300, function() {
					$(this).remove();
				});
			}

			this.$messages.append($message);
			this.scrollToBottom();

			// Track message
			if (typeof gtag !== 'undefined') {
				gtag('event', 'chatbot_message_sent', {
					'event_category': 'Engagement',
					'event_label': 'Chatbot',
					'value': content.length
				});
			}
		},

		showTyping() {
			$('.cdv-chat-typing').fadeIn(200);
		},

		hideTyping() {
			$('.cdv-chat-typing').fadeOut(200);
		},

		scrollToBottom() {
			this.$messages.animate({
				scrollTop: this.$messages[0].scrollHeight
			}, 300);
		},

		autoResizeInput() {
			const input = this.$input[0];
			input.style.height = 'auto';
			input.style.height = Math.min(input.scrollHeight, 100) + 'px';
		},

		showNotificationBadge(count = 1) {
			$('.cdv-chat-badge').text(count).fadeIn(200);
		},

		hideNotificationBadge() {
			$('.cdv-chat-badge').fadeOut(200);
		},

		saveHistory() {
			// Save last 50 messages to localStorage
			const history = this.conversationHistory.slice(-50);
			localStorage.setItem('cdv_chatbot_history', JSON.stringify(history));
		},

		loadHistory() {
			// Load previous conversation from localStorage
			const saved = localStorage.getItem('cdv_chatbot_history');
			if (saved) {
				try {
					this.conversationHistory = JSON.parse(saved);
					
					// Restore messages from today only
					const today = new Date().setHours(0,0,0,0);
					const todayMessages = this.conversationHistory.filter(msg => 
						new Date(msg.timestamp).setHours(0,0,0,0) === today
					);

					// Remove welcome message and quick actions first
					$('.cdv-message-welcome, .cdv-quick-actions').remove();

					todayMessages.forEach(msg => {
						this.addMessage(msg.content, msg.role);
					});

					if (todayMessages.length > 0) {
						this.scrollToBottom();
					}
				} catch (e) {
					console.error('Error loading chatbot history:', e);
				}
			}
		},

		clearHistory() {
			this.conversationHistory = [];
			localStorage.removeItem('cdv_chatbot_history');
			this.$messages.find('.cdv-message:not(.cdv-message-welcome)').remove();
			$('.cdv-quick-actions').show();
		}
	};

	/* ========================================
	   ADMIN SETTINGS
	======================================== */

	const CdvChatbotAdmin = {
		init() {
			if (!$('body').hasClass('wp-admin')) return;

			this.bindSettings();
		},

		bindSettings() {
			// Toggle API key field based on provider
			$('#cdv_chatbot_provider').on('change', function() {
				const provider = $(this).val();
				const $apiRow = $('#cdv_chatbot_api_key').closest('tr');
				
				if (provider === 'none') {
					$apiRow.hide();
				} else {
					$apiRow.show();
				}
			}).trigger('change');

			// Test chatbot connection
			$(document).on('click', '#cdv-test-chatbot', function(e) {
				e.preventDefault();
				
				const $btn = $(this);
				const $result = $('#cdv-chatbot-test-result');
				
				$btn.prop('disabled', true).text('Testing...');
				$result.html('');

				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'cdv_test_chatbot',
						nonce: $('#cdv_chatbot_nonce').val(),
						provider: $('#cdv_chatbot_provider').val(),
						api_key: $('#cdv_chatbot_api_key').val()
					},
					success(response) {
						$btn.prop('disabled', false).text('Test Connessione');
						
						if (response.success) {
							$result.html(`<div class="notice notice-success"><p>✅ ${response.data.message}</p></div>`);
						} else {
							$result.html(`<div class="notice notice-error"><p>❌ ${response.data}</p></div>`);
						}
					},
					error() {
						$btn.prop('disabled', false).text('Test Connessione');
						$result.html('<div class="notice notice-error"><p>❌ Errore di connessione</p></div>');
					}
				});
			});

			// Clear conversation history
			$(document).on('click', '#cdv-clear-chatbot-history', function(e) {
				e.preventDefault();
				
				if (confirm('Sei sicuro di voler cancellare tutta la cronologia chat?')) {
					localStorage.removeItem('cdv_chatbot_history');
					alert('Cronologia cancellata!');
				}
			});
		}
	};

	/* ========================================
	   INIT
	======================================== */

	$(document).ready(function() {
		// Frontend chatbot
		if ($('#cdv-chatbot-widget').length > 0) {
			CdvChatbot.init();
		}

		// Admin settings
		CdvChatbotAdmin.init();

		// Auto-open chatbot on certain pages (optional)
		const urlParams = new URLSearchParams(window.location.search);
		if (urlParams.get('chat') === 'open') {
			setTimeout(() => {
				CdvChatbot.toggleChat();
			}, 1000);
		}
	});

	// Expose globally for external access
	window.CdvChatbot = CdvChatbot;

})(jQuery);
