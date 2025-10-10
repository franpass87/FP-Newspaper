<?php
/**
 * Service: AI Chatbot
 *
 * Assistente virtuale intelligente per supporto cittadini
 *
 * @package CdV
 * @subpackage Services
 * @since 2.0.0
 */

namespace CdV\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class AIChatbot
 */
class AIChatbot {
	/**
	 * Initialize chatbot
	 */
	public static function init(): void {
		add_action( 'wp_footer', [ self::class, 'render_chat_widget' ] );
		add_action( 'wp_ajax_cdv_chatbot_message', [ self::class, 'handle_message' ] );
		add_action( 'wp_ajax_nopriv_cdv_chatbot_message', [ self::class, 'handle_message' ] );
	}

	/**
	 * Render chat widget in footer
	 */
	public static function render_chat_widget(): void {
		// Check if chatbot is enabled
		$enabled = get_option( 'cdv_chatbot_enabled', '1' );
		if ( '1' !== $enabled ) {
			return;
		}

		?>
		<div id="cdv-chatbot-widget" class="cdv-chatbot-closed">
			<!-- Chat Button -->
			<button type="button" id="cdv-chatbot-toggle" class="cdv-chat-toggle" aria-label="<?php esc_attr_e( 'Apri chat assistente', 'cronaca-di-viterbo' ); ?>">
				<span class="cdv-chat-icon">
					<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M12 2C6.48 2 2 6.48 2 12C2 13.54 2.36 14.99 3 16.26V22L8.74 19C9.79 19.63 11 20 12 20C17.52 20 22 15.52 22 12C22 6.48 17.52 2 12 2Z" fill="currentColor"/>
						<circle cx="8" cy="12" r="1.5" fill="white"/>
						<circle cx="12" cy="12" r="1.5" fill="white"/>
						<circle cx="16" cy="12" r="1.5" fill="white"/>
					</svg>
				</span>
				<span class="cdv-chat-close-icon">✕</span>
				<span class="cdv-chat-badge" style="display:none;">1</span>
			</button>

			<!-- Chat Window -->
			<div id="cdv-chatbot-window" class="cdv-chat-window">
				<div class="cdv-chat-header">
					<div class="cdv-chat-avatar">
						<svg width="32" height="32" viewBox="0 0 24 24" fill="none">
							<circle cx="12" cy="12" r="10" fill="#667eea"/>
							<path d="M8 14C8 15.1046 9.34315 16 11 16H13C14.6569 16 16 15.1046 16 14C16 12.8954 14.6569 12 13 12H11C9.34315 12 8 12.8954 8 14Z" fill="white"/>
							<circle cx="9" cy="9" r="1.5" fill="white"/>
							<circle cx="15" cy="9" r="1.5" fill="white"/>
						</svg>
					</div>
					<div class="cdv-chat-info">
						<h4 class="cdv-chat-title"><?php esc_html_e( 'ViterboBot', 'cronaca-di-viterbo' ); ?></h4>
						<p class="cdv-chat-status">
							<span class="cdv-status-dot"></span>
							<?php esc_html_e( 'Online', 'cronaca-di-viterbo' ); ?>
						</p>
					</div>
					<button type="button" class="cdv-chat-minimize" aria-label="<?php esc_attr_e( 'Chiudi chat', 'cronaca-di-viterbo' ); ?>">
						<span class="dashicons dashicons-minus"></span>
					</button>
				</div>

				<div class="cdv-chat-messages" id="cdv-chat-messages">
					<!-- Welcome message -->
					<div class="cdv-message cdv-message-bot cdv-message-welcome">
						<div class="cdv-message-avatar">🤖</div>
						<div class="cdv-message-bubble">
							<p><?php esc_html_e( 'Ciao! Sono ViterboBot, il tuo assistente virtuale. Come posso aiutarti oggi?', 'cronaca-di-viterbo' ); ?></p>
						</div>
					</div>

					<!-- Quick actions -->
					<div class="cdv-quick-actions">
						<p class="cdv-quick-title"><?php esc_html_e( 'Domande frequenti:', 'cronaca-di-viterbo' ); ?></p>
						<button type="button" class="cdv-quick-btn" data-message="Come faccio a inviare una proposta?">
							💡 <?php esc_html_e( 'Come inviare una proposta?', 'cronaca-di-viterbo' ); ?>
						</button>
						<button type="button" class="cdv-quick-btn" data-message="Quali sono gli eventi in programma?">
							📅 <?php esc_html_e( 'Eventi in programma', 'cronaca-di-viterbo' ); ?>
						</button>
						<button type="button" class="cdv-quick-btn" data-message="Come firmare una petizione?">
							✍️ <?php esc_html_e( 'Firmare una petizione', 'cronaca-di-viterbo' ); ?>
						</button>
						<button type="button" class="cdv-quick-btn" data-message="Informazioni sul sistema di reputazione">
							🏆 <?php esc_html_e( 'Sistema reputazione', 'cronaca-di-viterbo' ); ?>
						</button>
					</div>
				</div>

				<div class="cdv-chat-input-wrapper">
					<form id="cdv-chat-form" class="cdv-chat-form">
						<input 
							type="text" 
							id="cdv-chat-input" 
							class="cdv-chat-input" 
							placeholder="<?php esc_attr_e( 'Scrivi un messaggio...', 'cronaca-di-viterbo' ); ?>"
							autocomplete="off"
						>
						<button type="submit" class="cdv-chat-send" aria-label="<?php esc_attr_e( 'Invia messaggio', 'cronaca-di-viterbo' ); ?>">
							<svg width="20" height="20" viewBox="0 0 24 24" fill="none">
								<path d="M2 21L23 12L2 3V10L17 12L2 14V21Z" fill="currentColor"/>
							</svg>
						</button>
					</form>
					<div class="cdv-chat-typing" style="display:none;">
						<span></span><span></span><span></span>
					</div>
				</div>

				<div class="cdv-chat-footer">
					<small><?php esc_html_e( 'Powered by AI • Cronaca di Viterbo', 'cronaca-di-viterbo' ); ?></small>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Handle chat message via AJAX
	 */
	public static function handle_message(): void {
		// Verify nonce
		check_ajax_referer( 'cdv_ajax_nonce', 'nonce' );

		// Get message
		$user_message = isset( $_POST['message'] ) ? sanitize_text_field( wp_unslash( $_POST['message'] ) ) : '';

		if ( empty( $user_message ) ) {
			wp_send_json_error( array(
				'message' => __( 'Messaggio vuoto.', 'cronaca-di-viterbo' ),
			) );
		}

		// Rate limiting (simple check)
		$user_id = get_current_user_id();
		$ip = self::get_client_ip();
		$cache_key = 'cdv_chatbot_' . ( $user_id ?: $ip );
		$last_request = get_transient( $cache_key );

		if ( false !== $last_request ) {
			wp_send_json_error( array(
				'message' => __( 'Per favore attendi qualche secondo prima di inviare un altro messaggio.', 'cronaca-di-viterbo' ),
			) );
		}

		// Set rate limit (3 seconds)
		set_transient( $cache_key, time(), 3 );

		// Process message and get response
		$response = self::process_message( $user_message );

		// Log conversation (optional)
		self::log_conversation( $user_message, $response );

		wp_send_json_success( array(
			'message' => $response,
			'timestamp' => current_time( 'timestamp' ),
		) );
	}

	/**
	 * Process user message and generate response
	 *
	 * @param string $message User message.
	 * @return string Bot response.
	 */
	private static function process_message( string $message ): string {
		$message_lower = strtolower( $message );

		// Pattern matching for common questions
		$patterns = self::get_response_patterns();

		foreach ( $patterns as $pattern ) {
			foreach ( $pattern['keywords'] as $keyword ) {
				if ( false !== strpos( $message_lower, $keyword ) ) {
					// Check context if multiple keywords match
					$context_match = true;
					if ( ! empty( $pattern['context'] ) ) {
						$context_match = false;
						foreach ( $pattern['context'] as $context_word ) {
							if ( false !== strpos( $message_lower, $context_word ) ) {
								$context_match = true;
								break;
							}
						}
					}

					if ( $context_match ) {
						// Random response variation
						return $pattern['responses'][ array_rand( $pattern['responses'] ) ];
					}
				}
			}
		}

		// If using external AI API (OpenAI/Claude)
		$ai_response = self::get_ai_response( $message );
		if ( $ai_response ) {
			return $ai_response;
		}

		// Default fallback
		return self::get_fallback_response();
	}

	/**
	 * Get response patterns (knowledge base)
	 *
	 * @return array Response patterns.
	 */
	private static function get_response_patterns(): array {
		return array(
			array(
				'keywords' => array( 'proposta', 'inviare', 'creare', 'pubblicare' ),
				'context'  => array( 'come', 'posso', 'faccio' ),
				'responses' => array(
					__( 'Per inviare una proposta, vai alla pagina "Proposte" e clicca su "Invia la tua idea". Compila il form con titolo, descrizione, quartiere e tematica. Le proposte vengono moderate prima della pubblicazione.', 'cronaca-di-viterbo' ),
					__( 'È facile! Cerca la pagina con il form proposte, compila i campi richiesti e invia. Un moderatore approverà la tua proposta entro 24-48 ore.', 'cronaca-di-viterbo' ),
				),
			),
			array(
				'keywords' => array( 'votare', 'voto', 'votazione' ),
				'context'  => array( 'come', 'posso' ),
				'responses' => array(
					__( 'Per votare una proposta, clicca sul pulsante "Vota" accanto alla proposta che ti interessa. Ogni utente può votare una volta per proposta. Il voto può essere ponderato in base alla tua residenza e verifica account.', 'cronaca-di-viterbo' ),
				),
			),
			array(
				'keywords' => array( 'evento', 'eventi', 'calendario' ),
				'context'  => array(),
				'responses' => array(
					sprintf(
						/* translators: %s: link to events page */
						__( 'Puoi vedere tutti gli eventi in programma <a href="%s">qui</a>. Gli eventi sono organizzati per quartiere e tematica.', 'cronaca-di-viterbo' ),
						home_url( '/eventi/' )
					),
				),
			),
			array(
				'keywords' => array( 'petizione', 'firmare', 'firma' ),
				'context'  => array(),
				'responses' => array(
					__( 'Per firmare una petizione, apri la petizione che ti interessa e compila il form con nome, cognome, email e comune. Puoi aggiungere una motivazione opzionale. Riceverai un\'email di conferma.', 'cronaca-di-viterbo' ),
				),
			),
			array(
				'keywords' => array( 'reputazione', 'punti', 'badge', 'livello' ),
				'context'  => array(),
				'responses' => array(
					__( 'Il sistema di reputazione premia la partecipazione attiva! Guadagni punti con: proposte (+50pt), voti ricevuti (+5pt), firme petizioni (+10pt), partecipazione eventi (+20pt). Accumula punti per salire di livello e sbloccare badge esclusivi!', 'cronaca-di-viterbo' ),
				),
			),
			array(
				'keywords' => array( 'quartiere', 'quartieri', 'zona' ),
				'context'  => array(),
				'responses' => array(
					__( 'Puoi filtrare contenuti per quartiere usando i menu di navigazione. Ogni proposta, evento e petizione è associato a uno o più quartieri di Viterbo.', 'cronaca-di-viterbo' ),
				),
			),
			array(
				'keywords' => array( 'video', 'foto', 'galleria', 'immagini' ),
				'context'  => array(),
				'responses' => array(
					__( 'Puoi esplorare le nostre gallerie fotografiche e video stories per scoprire reportage dal territorio. Se vuoi contribuire, contatta la redazione!', 'cronaca-di-viterbo' ),
				),
			),
			array(
				'keywords' => array( 'ciao', 'salve', 'buongiorno', 'buonasera', 'hey' ),
				'context'  => array(),
				'responses' => array(
					__( 'Ciao! 👋 Come posso aiutarti oggi?', 'cronaca-di-viterbo' ),
					__( 'Salve! Sono qui per rispondere alle tue domande su Cronaca di Viterbo.', 'cronaca-di-viterbo' ),
				),
			),
			array(
				'keywords' => array( 'grazie', 'ok', 'perfetto', 'bene' ),
				'context'  => array(),
				'responses' => array(
					__( 'Prego! 😊 C\'è altro che posso fare per te?', 'cronaca-di-viterbo' ),
					__( 'Felice di esserti stato utile! Se hai altre domande, sono qui.', 'cronaca-di-viterbo' ),
				),
			),
			array(
				'keywords' => array( 'aiuto', 'help', 'assistenza' ),
				'context'  => array(),
				'responses' => array(
					__( 'Certo! Posso aiutarti con: inviare proposte, votare, firmare petizioni, trovare eventi, capire il sistema di reputazione. Cosa ti interessa?', 'cronaca-di-viterbo' ),
				),
			),
		);
	}

	/**
	 * Get AI response from external API (OpenAI/Claude)
	 *
	 * @param string $message User message.
	 * @return string|false AI response or false.
	 */
	private static function get_ai_response( string $message ) {
		$api_key = get_option( 'cdv_chatbot_api_key', '' );
		$api_provider = get_option( 'cdv_chatbot_provider', 'none' ); // none, openai, claude

		if ( empty( $api_key ) || 'none' === $api_provider ) {
			return false;
		}

		// Build context about the platform
		$context = self::build_context();

		// Call API based on provider
		if ( 'openai' === $api_provider ) {
			return self::call_openai_api( $message, $context, $api_key );
		} elseif ( 'claude' === $api_provider ) {
			return self::call_claude_api( $message, $context, $api_key );
		}

		return false;
	}

	/**
	 * Build context about platform for AI
	 *
	 * @return string Context.
	 */
	private static function build_context(): string {
		$context = "Sei ViterboBot, l'assistente virtuale di Cronaca di Viterbo, una piattaforma di giornalismo locale partecipativo.\n\n";
		$context .= "Funzionalità principali:\n";
		$context .= "- Proposte: cittadini possono inviare proposte per migliorare la città\n";
		$context .= "- Votazione: ogni proposta può essere votata dalla community\n";
		$context .= "- Petizioni: raccolta firme digitali per iniziative popolari\n";
		$context .= "- Eventi: calendario eventi locali\n";
		$context .= "- Sondaggi: consultazioni pubbliche\n";
		$context .= "- Sistema reputazione: punti e badge per partecipazione attiva\n";
		$context .= "- Video e Foto: reportage multimediali dal territorio\n\n";
		$context .= "Rispondi sempre in italiano, in modo amichevole e conciso (max 100 parole).";

		return $context;
	}

	/**
	 * Call OpenAI API
	 *
	 * @param string $message User message.
	 * @param string $context Platform context.
	 * @param string $api_key API key.
	 * @return string|false Response.
	 */
	private static function call_openai_api( string $message, string $context, string $api_key ) {
		$api_url = 'https://api.openai.com/v1/chat/completions';

		$body = array(
			'model' => 'gpt-3.5-turbo',
			'messages' => array(
				array(
					'role' => 'system',
					'content' => $context,
				),
				array(
					'role' => 'user',
					'content' => $message,
				),
			),
			'max_tokens' => 150,
			'temperature' => 0.7,
		);

		$response = wp_remote_post(
			$api_url,
			array(
				'headers' => array(
					'Content-Type' => 'application/json',
					'Authorization' => 'Bearer ' . $api_key,
				),
				'body' => wp_json_encode( $body ),
				'timeout' => 15,
			)
		);

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( isset( $data['choices'][0]['message']['content'] ) ) {
			return trim( $data['choices'][0]['message']['content'] );
		}

		return false;
	}

	/**
	 * Call Claude API
	 *
	 * @param string $message User message.
	 * @param string $context Platform context.
	 * @param string $api_key API key.
	 * @return string|false Response.
	 */
	private static function call_claude_api( string $message, string $context, string $api_key ) {
		$api_url = 'https://api.anthropic.com/v1/messages';

		$body = array(
			'model' => 'claude-3-haiku-20240307',
			'max_tokens' => 150,
			'system' => $context,
			'messages' => array(
				array(
					'role' => 'user',
					'content' => $message,
				),
			),
		);

		$response = wp_remote_post(
			$api_url,
			array(
				'headers' => array(
					'Content-Type' => 'application/json',
					'x-api-key' => $api_key,
					'anthropic-version' => '2023-06-01',
				),
				'body' => wp_json_encode( $body ),
				'timeout' => 15,
			)
		);

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( isset( $data['content'][0]['text'] ) ) {
			return trim( $data['content'][0]['text'] );
		}

		return false;
	}

	/**
	 * Get fallback response
	 *
	 * @return string Fallback response.
	 */
	private static function get_fallback_response(): string {
		$fallbacks = array(
			__( 'Interessante! Purtroppo non ho una risposta specifica a questa domanda. Puoi riformulare o provare con una delle domande frequenti?', 'cronaca-di-viterbo' ),
			__( 'Non sono sicuro di aver capito bene. Puoi essere più specifico? Oppure scegli una delle opzioni rapide qui sotto!', 'cronaca-di-viterbo' ),
			__( 'Hmm, questa è nuova per me! Prova a chiedere qualcosa su proposte, votazioni, eventi o petizioni.', 'cronaca-di-viterbo' ),
		);

		return $fallbacks[ array_rand( $fallbacks ) ];
	}

	/**
	 * Get client IP (proxy-aware)
	 *
	 * @return string IP address.
	 */
	private static function get_client_ip(): string {
		$ip = '';

		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) );
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
		} elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}

		return $ip;
	}

	/**
	 * Log conversation (optional - for analytics/training)
	 *
	 * @param string $user_message User message.
	 * @param string $bot_response Bot response.
	 */
	private static function log_conversation( string $user_message, string $bot_response ): void {
		// Optional: save to custom table or post meta for analytics
		// For now, we skip this to respect privacy
		// You can implement this later if needed
	}
}
