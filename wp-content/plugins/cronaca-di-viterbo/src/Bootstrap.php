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

		// Roles
		require_once CDV_PLUGIN_DIR . 'src/Roles/Capabilities.php';

		// Utils
		require_once CDV_PLUGIN_DIR . 'src/Utils/View.php';
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
		wp_enqueue_style(
			'cdv-frontend',
			CDV_PLUGIN_URL . 'assets/css/cdv.css',
			[],
			CDV_VERSION
		);

		wp_enqueue_script(
			'cdv-frontend',
			CDV_PLUGIN_URL . 'assets/js/cdv.js',
			[ 'jquery' ],
			CDV_VERSION,
			true
		);

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
	public function enqueue_admin_assets() {
		wp_enqueue_style(
			'cdv-admin',
			CDV_PLUGIN_URL . 'assets/css/cdv-admin.css',
			[],
			CDV_VERSION
		);

		wp_enqueue_script(
			'cdv-admin',
			CDV_PLUGIN_URL . 'assets/js/cdv-admin.js',
			[ 'jquery' ],
			CDV_VERSION,
			true
		);
	}
}
