<?php
/**
 * Bootstrap principale del plugin Cronaca di Viterbo.
 *
 * @package CdV
 */

namespace CdV;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Classe Bootstrap per l'inizializzazione del plugin.
 */
class Bootstrap {

	/**
	 * Istanza singleton.
	 *
	 * @var Bootstrap|null
	 */
	private static $instance = null;

	/**
	 * Inizializza il plugin (singleton).
	 *
	 * @return Bootstrap
	 */
	public static function init() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Costruttore privato (singleton).
	 */
	private function __construct() {
		$this->load_dependencies();
		$this->init_components();
		$this->register_hooks();
	}

	/**
	 * Carica le dipendenze del plugin.
	 */
	private function load_dependencies() {
		// PostTypes
		require_once CDV_PLUGIN_DIR . 'src/PostTypes/Dossier.php';
		require_once CDV_PLUGIN_DIR . 'src/PostTypes/Proposta.php';
		require_once CDV_PLUGIN_DIR . 'src/PostTypes/Evento.php';
		require_once CDV_PLUGIN_DIR . 'src/PostTypes/Persona.php';
		require_once CDV_PLUGIN_DIR . 'src/PostTypes/RispostaAmministrazione.php';
		require_once CDV_PLUGIN_DIR . 'src/PostTypes/Petizione.php';
		require_once CDV_PLUGIN_DIR . 'src/PostTypes/Sondaggio.php';

		// Taxonomies
		require_once CDV_PLUGIN_DIR . 'src/Taxonomies/Quartiere.php';
		require_once CDV_PLUGIN_DIR . 'src/Taxonomies/Tematica.php';

		// Admin
		require_once CDV_PLUGIN_DIR . 'src/Admin/Screens.php';
		require_once CDV_PLUGIN_DIR . 'src/Admin/Settings.php';
		require_once CDV_PLUGIN_DIR . 'src/Admin/Dashboard.php';
		require_once CDV_PLUGIN_DIR . 'src/Admin/Analytics.php';
		require_once CDV_PLUGIN_DIR . 'src/Admin/ImportExport.php';
		require_once CDV_PLUGIN_DIR . 'src/Admin/BulkActions.php';

		// Shortcodes
		require_once CDV_PLUGIN_DIR . 'src/Shortcodes/PropostaForm.php';
		require_once CDV_PLUGIN_DIR . 'src/Shortcodes/ProposteList.php';
		require_once CDV_PLUGIN_DIR . 'src/Shortcodes/DossierHero.php';
		require_once CDV_PLUGIN_DIR . 'src/Shortcodes/EventiList.php';
		require_once CDV_PLUGIN_DIR . 'src/Shortcodes/PersonaCard.php';
		require_once CDV_PLUGIN_DIR . 'src/Shortcodes/PetizioneForm.php';
		require_once CDV_PLUGIN_DIR . 'src/Shortcodes/PetizioniList.php';
		require_once CDV_PLUGIN_DIR . 'src/Shortcodes/SondaggioForm.php';
		require_once CDV_PLUGIN_DIR . 'src/Shortcodes/UserProfile.php';
		require_once CDV_PLUGIN_DIR . 'src/Shortcodes/MappaInterattiva.php';

		// WPBakery
		if ( defined( 'WPB_VC_VERSION' ) ) {
			require_once CDV_PLUGIN_DIR . 'src/WPBakery/Map.php';
		}

		// Ajax
		require_once CDV_PLUGIN_DIR . 'src/Ajax/SubmitProposta.php';
		require_once CDV_PLUGIN_DIR . 'src/Ajax/VoteProposta.php';
		require_once CDV_PLUGIN_DIR . 'src/Ajax/FirmaPetizione.php';
		require_once CDV_PLUGIN_DIR . 'src/Ajax/VotaSondaggio.php';

		// Services
		require_once CDV_PLUGIN_DIR . 'src/Services/Schema.php';
		require_once CDV_PLUGIN_DIR . 'src/Services/GA4.php';
		require_once CDV_PLUGIN_DIR . 'src/Services/Sanitization.php';
		require_once CDV_PLUGIN_DIR . 'src/Services/Security.php';
		require_once CDV_PLUGIN_DIR . 'src/Services/Migration.php';
		require_once CDV_PLUGIN_DIR . 'src/Services/Compat.php';
		require_once CDV_PLUGIN_DIR . 'src/Services/Notifiche.php';
		require_once CDV_PLUGIN_DIR . 'src/Services/Reputazione.php';
		require_once CDV_PLUGIN_DIR . 'src/Services/VotazioneAvanzata.php';

		// Roles
		require_once CDV_PLUGIN_DIR . 'src/Roles/Capabilities.php';

		// Utils
		require_once CDV_PLUGIN_DIR . 'src/Utils/View.php';

		// Widgets
		require_once CDV_PLUGIN_DIR . 'src/Widgets/ProposteWidget.php';
		require_once CDV_PLUGIN_DIR . 'src/Widgets/EventiWidget.php';
		require_once CDV_PLUGIN_DIR . 'src/Widgets/StatsWidget.php';

		// Gutenberg
		require_once CDV_PLUGIN_DIR . 'src/Gutenberg/Blocks.php';

		// API
		require_once CDV_PLUGIN_DIR . 'src/API/RestAPI.php';
	}

	/**
	 * Inizializza i componenti del plugin.
	 */
	private function init_components() {
		// PostTypes
		new PostTypes\Dossier();
		new PostTypes\Proposta();
		new PostTypes\Evento();
		new PostTypes\Persona();
		PostTypes\RispostaAmministrazione::register();
		PostTypes\Petizione::register();
		PostTypes\Sondaggio::register();

		// Taxonomies
		new Taxonomies\Quartiere();
		new Taxonomies\Tematica();

		// Admin
		new Admin\Screens();
		new Admin\Settings();
		Admin\Dashboard::init();
		Admin\Analytics::init();
		Admin\ImportExport::init();
		Admin\BulkActions::init();

		// Shortcodes
		new Shortcodes\PropostaForm();
		new Shortcodes\ProposteList();
		new Shortcodes\DossierHero();
		new Shortcodes\EventiList();
		new Shortcodes\PersonaCard();
		add_shortcode( 'cdv_petizione_form', [ Shortcodes\PetizioneForm::class, 'render' ] );
		add_shortcode( 'cdv_petizioni', [ Shortcodes\PetizioniList::class, 'render' ] );
		add_shortcode( 'cdv_sondaggio_form', [ Shortcodes\SondaggioForm::class, 'render' ] );
		add_shortcode( 'cdv_user_profile', [ Shortcodes\UserProfile::class, 'render' ] );
		add_shortcode( 'cdv_mappa', [ Shortcodes\MappaInterattiva::class, 'render' ] );

		// WPBakery
		if ( defined( 'WPB_VC_VERSION' ) ) {
			new WPBakery\Map();
		}

		// Ajax
		new Ajax\SubmitProposta();
		new Ajax\VoteProposta();
		add_action( 'wp_ajax_cdv_firma_petizione', [ Ajax\FirmaPetizione::class, 'handle' ] );
		add_action( 'wp_ajax_nopriv_cdv_firma_petizione', [ Ajax\FirmaPetizione::class, 'handle' ] );
		add_action( 'wp_ajax_cdv_vota_sondaggio', [ Ajax\VotaSondaggio::class, 'handle' ] );
		add_action( 'wp_ajax_nopriv_cdv_vota_sondaggio', [ Ajax\VotaSondaggio::class, 'handle' ] );

		// Services
		new Services\Schema();
		new Services\GA4();
		new Services\Compat();
		Services\Notifiche::init();
		Services\Reputazione::init();
		Services\VotazioneAvanzata::init();

		// Widgets
		add_action( 'widgets_init', function() {
			register_widget( Widgets\ProposteWidget::class );
			register_widget( Widgets\EventiWidget::class );
			register_widget( Widgets\StatsWidget::class );
		});

		// Gutenberg
		Gutenberg\Blocks::init();

		// API REST
		API\RestAPI::init();
	}

	/**
	 * Registra gli hooks WordPress.
	 */
	private function register_hooks() {
		// Activation/Deactivation
		register_activation_hook( CDV_PLUGIN_FILE, [ $this, 'activate' ] );
		register_deactivation_hook( CDV_PLUGIN_FILE, [ $this, 'deactivate' ] );

		// Text domain
		add_action( 'plugins_loaded', [ $this, 'load_textdomain' ] );

		// Assets
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_frontend_assets' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );

		// Meta boxes per nuovi CPT
		add_action( 'add_meta_boxes', [ PostTypes\RispostaAmministrazione::class, 'add_meta_boxes' ] );
		add_action( 'save_post', [ PostTypes\RispostaAmministrazione::class, 'save_meta_box' ] );
		add_action( 'add_meta_boxes', [ PostTypes\Petizione::class, 'add_meta_boxes' ] );
		add_action( 'save_post', [ PostTypes\Petizione::class, 'save_meta_box' ] );
		add_action( 'add_meta_boxes', [ PostTypes\Sondaggio::class, 'add_meta_boxes' ] );
		add_action( 'save_post', [ PostTypes\Sondaggio::class, 'save_meta_box' ] );
	}

	/**
	 * Attivazione del plugin.
	 */
	public function activate() {
		// Esegui migrazioni
		Services\Migration::run();

		// Aggiungi ruoli e capabilities
		Roles\Capabilities::add_roles();

		// Crea tabelle database
		PostTypes\Petizione::create_firme_table();
		PostTypes\Sondaggio::create_votes_table();
		Services\VotazioneAvanzata::init(); // Creates votes details table

		// Flush rewrite rules
		flush_rewrite_rules();
	}

	/**
	 * Disattivazione del plugin.
	 */
	public function deactivate() {
		// Flush rewrite rules
		flush_rewrite_rules();
	}

	/**
	 * Carica il text domain per le traduzioni.
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'cronaca-di-viterbo',
			false,
			dirname( CDV_PLUGIN_BASENAME ) . '/languages/'
		);
	}

	/**
	 * Carica gli assets frontend.
	 */
	public function enqueue_frontend_assets() {
		// Determina se usare versione minificata (produzione)
		$min = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		// CSS principale (entry point modulare)
		wp_enqueue_style(
			'cdv-frontend',
			CDV_PLUGIN_URL . 'assets/css/main.css',
			[],
			CDV_VERSION
		);

		// JavaScript - Moduli Core (sempre caricati)
		wp_enqueue_script(
			'cdv-utils',
			CDV_PLUGIN_URL . 'assets/js/modules/utils.js',
			[ 'jquery' ],
			CDV_VERSION,
			true
		);

		wp_enqueue_script(
			'cdv-analytics',
			CDV_PLUGIN_URL . 'assets/js/modules/analytics-tracker.js',
			[],
			CDV_VERSION,
			true
		);

		// Moduli condizionali - Carica solo se necessari
		global $post;
		$load_form_handler = false;
		$load_voting = false;
		$load_petition = false;
		$load_poll = false;

		// Verifica contenuto pagina per caricamento condizionale
		if ( is_singular() && $post ) {
			$content = $post->post_content;
			
			// Form proposte
			if ( has_shortcode( $content, 'cdv_proposta_form' ) || is_post_type_archive( 'cdv_proposta' ) ) {
				$load_form_handler = true;
			}
			
			// Sistema votazione
			if ( has_shortcode( $content, 'cdv_proposte_list' ) || is_singular( 'cdv_proposta' ) ) {
				$load_voting = true;
			}
			
			// Petizioni
			if ( has_shortcode( $content, 'cdv_petizione_form' ) || 
			     has_shortcode( $content, 'cdv_petizioni' ) || 
			     is_singular( 'cdv_petizione' ) ) {
				$load_petition = true;
			}
			
			// Sondaggi
			if ( has_shortcode( $content, 'cdv_sondaggio_form' ) || is_singular( 'cdv_sondaggio' ) ) {
				$load_poll = true;
			}
		}

		// Carica modulo form handler se necessario
		if ( $load_form_handler || is_post_type_archive( 'cdv_proposta' ) ) {
			wp_enqueue_script(
				'cdv-form-handler',
				CDV_PLUGIN_URL . 'assets/js/modules/form-handler.js',
				[ 'jquery', 'cdv-analytics' ],
				CDV_VERSION,
				true
			);
		}

		// Carica modulo voting system se necessario
		if ( $load_voting || is_post_type_archive( 'cdv_proposta' ) ) {
			wp_enqueue_script(
				'cdv-voting-system',
				CDV_PLUGIN_URL . 'assets/js/modules/voting-system.js',
				[ 'jquery', 'cdv-analytics' ],
				CDV_VERSION,
				true
			);
		}

		// Carica modulo petizioni se necessario
		if ( $load_petition ) {
			wp_enqueue_script(
				'cdv-petition-handler',
				CDV_PLUGIN_URL . 'assets/js/modules/petition-handler.js',
				[ 'jquery', 'cdv-analytics', 'cdv-utils' ],
				CDV_VERSION,
				true
			);
		}

		// Carica modulo sondaggi se necessario
		if ( $load_poll ) {
			wp_enqueue_script(
				'cdv-poll-handler',
				CDV_PLUGIN_URL . 'assets/js/modules/poll-handler.js',
				[ 'jquery', 'cdv-analytics', 'cdv-utils' ],
				CDV_VERSION,
				true
			);
		}

		// JavaScript principale (entry point - sempre caricato)
		wp_enqueue_script(
			'cdv-frontend',
			CDV_PLUGIN_URL . 'assets/js/main.js',
			[ 'jquery', 'cdv-utils' ],
			CDV_VERSION,
			true
		);

		// Localizza dati per JavaScript
		wp_localize_script(
			'cdv-frontend',
			'cdvData',
			[
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'cdv_ajax_nonce' ),
				'strings' => [
					'loading'       => __( 'Caricamento...', 'cronaca-di-viterbo' ),
					'error'         => __( 'Si è verificato un errore. Riprova.', 'cronaca-di-viterbo' ),
					'success'       => __( 'Operazione completata!', 'cronaca-di-viterbo' ),
					'confirmDelete' => __( 'Sei sicuro di voler eliminare?', 'cronaca-di-viterbo' ),
				],
			]
		);
	}

	/**
	 * Carica gli assets admin.
	 */
	public function enqueue_admin_assets( $hook ) {
		// Carica solo nelle pagine del plugin
		$cdv_pages = [
			'toplevel_page_cdv-dashboard',
			'cdv_page_cdv-settings',
			'cdv_page_cdv-analytics',
			'edit-cdv_proposta',
			'edit-cdv_petizione',
			'edit-cdv_sondaggio',
		];

		// CSS Admin modulare
		wp_enqueue_style(
			'cdv-admin',
			CDV_PLUGIN_URL . 'assets/css/admin-main.css',
			[],
			CDV_VERSION
		);

		// JavaScript Admin - Moduli
		wp_enqueue_script(
			'cdv-admin-dashboard',
			CDV_PLUGIN_URL . 'assets/js/admin/dashboard.js',
			[ 'jquery' ],
			CDV_VERSION,
			true
		);

		wp_enqueue_script(
			'cdv-admin-moderation',
			CDV_PLUGIN_URL . 'assets/js/admin/moderation.js',
			[ 'jquery' ],
			CDV_VERSION,
			true
		);

		wp_enqueue_script(
			'cdv-admin-settings',
			CDV_PLUGIN_URL . 'assets/js/admin/settings.js',
			[ 'jquery', 'wp-color-picker' ],
			CDV_VERSION,
			true
		);

		// Color picker
		wp_enqueue_style( 'wp-color-picker' );

		// JavaScript Admin principale
		wp_enqueue_script(
			'cdv-admin',
			CDV_PLUGIN_URL . 'assets/js/admin-main.js',
			[ 'jquery', 'cdv-admin-dashboard', 'cdv-admin-moderation', 'cdv-admin-settings' ],
			CDV_VERSION,
			true
		);

		// Localizza dati per JavaScript admin
		wp_localize_script(
			'cdv-admin',
			'cdvAdminData',
			[
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'cdv_admin_nonce' ),
				'strings' => [
					'confirmDelete' => __( 'Sei sicuro di voler eliminare?', 'cronaca-di-viterbo' ),
					'saved'         => __( 'Salvato!', 'cronaca-di-viterbo' ),
					'error'         => __( 'Errore', 'cronaca-di-viterbo' ),
				],
			]
		);
	}
}
