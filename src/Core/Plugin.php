<?php
/**
 * Classe principale du plugin
 *
 * @package GFSirenAutocomplete
 */

namespace GFSirenAutocomplete\Core;

defined( 'ABSPATH' ) || exit;

use GFSirenAutocomplete\Admin\AdminManager;
use GFSirenAutocomplete\Modules\Siren\SirenManager;
use GFSirenAutocomplete\Modules\MentionsLegales\MentionsManager;
use GFSirenAutocomplete\Modules\GravityForms\GFManager;

/**
 * Classe principale du plugin
 */
class Plugin {

	/**
	 * Instance unique (Singleton)
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Version du plugin
	 */
	const VERSION = '1.0.6';

	/**
	 * Instance du logger
	 *
	 * @var Logger
	 */
	private $logger;

	/**
	 * Instance du Siren Manager
	 *
	 * @var SirenManager
	 */
	private $siren_manager;

	/**
	 * Instance du Mentions Manager
	 *
	 * @var MentionsManager
	 */
	private $mentions_manager;

	/**
	 * Instance du GF Manager
	 *
	 * @var GFManager
	 */
	private $gf_manager;

	/**
	 * Instance de l'Admin Manager
	 *
	 * @var AdminManager
	 */
	private $admin_manager;

	/**
	 * Constructeur privé (Singleton)
	 */
	private function __construct() {
		$this->init_core();
		$this->init_modules();
		$this->init_hooks();

		if ( is_admin() ) {
			$this->init_admin();
		}
	}

	/**
	 * Récupère l'instance unique
	 *
	 * @return Plugin
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initialisation du noyau
	 */
	private function init_core() {
		// Initialiser le logger.
		$this->logger = Logger::get_instance();
	}

	/**
	 * Initialisation des modules métier
	 */
	private function init_modules() {
		// Module Siren.
		$this->siren_manager = new SirenManager( $this->logger );

		// Module Mentions Légales.
		$this->mentions_manager = new MentionsManager( $this->logger );

		// Module Gravity Forms.
		$this->gf_manager = new GFManager( $this->siren_manager, $this->mentions_manager, $this->logger );
	}

	/**
	 * Initialisation de l'administration
	 */
	private function init_admin() {
		$this->admin_manager = new AdminManager( $this->siren_manager, $this->gf_manager, $this->logger );
		$this->admin_manager->init_hooks();
	}

	/**
	 * Initialisation des hooks WordPress
	 */
	private function init_hooks() {
		// Hooks principaux.
		add_action( 'init', array( $this, 'on_init' ) );
		add_action( 'plugins_loaded', array( $this, 'on_plugins_loaded' ) );

		// Initialiser les hooks Gravity Forms.
		$this->gf_manager->init_hooks();
	}

	/**
	 * Hook init de WordPress
	 */
	public function on_init() {
		// Chargement des traductions.
		load_plugin_textdomain(
			Constants::TEXT_DOMAIN,
			false,
			dirname( plugin_basename( GRAVITY_FORMS_SIREN_AUTOCOMPLETE_PLUGIN_FILE ) ) . '/languages'
		);
	}

	/**
	 * Hook plugins_loaded de WordPress
	 */
	public function on_plugins_loaded() {
		// Vérifications de compatibilité.
		$this->check_requirements();
	}

	/**
	 * Vérifie les prérequis du plugin
	 */
	private function check_requirements() {
		// Vérifier la version PHP.
		if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
			add_action( 'admin_notices', array( $this, 'show_php_version_notice' ) );
			return;
		}

		// Vérifier la version WordPress.
		if ( version_compare( get_bloginfo( 'version' ), '5.8', '<' ) ) {
			add_action( 'admin_notices', array( $this, 'show_wp_version_notice' ) );
			return;
		}
	}

	/**
	 * Affiche un avertissement si la version PHP est trop ancienne
	 */
	public function show_php_version_notice() {
		?>
		<div class="notice notice-error">
			<p>
				<?php
				printf(
					/* translators: %s: version PHP requise */
					esc_html__( 'Le plugin Gravity Forms Siren Autocomplete requiert PHP %s ou supérieur.', Constants::TEXT_DOMAIN ),
					'7.4'
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Affiche un avertissement si la version WordPress est trop ancienne
	 */
	public function show_wp_version_notice() {
		?>
		<div class="notice notice-error">
			<p>
				<?php
				printf(
					/* translators: %s: version WordPress requise */
					esc_html__( 'Le plugin Gravity Forms Siren Autocomplete requiert WordPress %s ou supérieur.', Constants::TEXT_DOMAIN ),
					'5.8'
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Récupère la version du plugin
	 *
	 * @return string
	 */
	public function get_version() {
		return self::VERSION;
	}

	/**
	 * Hook d'activation du plugin
	 */
	public static function activate() {
		// Créer la table des logs.
		$logger = Logger::get_instance();
		$logger->create_table();

		// Créer les options par défaut.
		$default_settings = array(
			'cache_duration' => Constants::CACHE_DURATION,
			'form_mappings'  => array(),
		);

		add_option( Constants::SETTINGS_OPTION, $default_settings );

		// Installer la configuration par défaut du formulaire ID: 1.
		self::install_default_form_mapping();

		// Flush rewrite rules.
		flush_rewrite_rules();

		// Log.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '[gravity-forms-siren-autocomplete] Plugin activé avec succès.' );
		}
	}

	/**
	 * Installe la configuration par défaut du formulaire ID: 1
	 */
	private static function install_default_form_mapping() {
		// Configuration du mapping pour le formulaire ID: 1.
		$form_mapping_config = array(
			'form_id'         => 1,
			'form_name'       => 'Test de positionnement Révélation Digitale',
			'enable_plugin'   => true,
			'enable_button'   => true,
			'button_position' => 'after',
			'siret'           => '1',
			'denomination'    => '12',
			'adresse'         => '8.1',
			'ville'           => '8.3',
			'code_postal'     => '8.5',
			'pays'            => '8.6',
			'mentions_legales'=> '13',
			'prenom'          => '7.3',
			'nom'             => '7.6',
			'forme_juridique' => '',
			'code_ape'        => '',
			'libelle_ape'     => '',
			'date_creation'   => '',
			'statut_actif'    => '',
			'type_entreprise' => '',
		);

		// Récupérer les settings actuels.
		$settings = get_option( Constants::SETTINGS_OPTION, array() );

		// Ajouter le mapping s'il n'existe pas déjà.
		if ( ! isset( $settings['form_mappings'] ) ) {
			$settings['form_mappings'] = array();
		}

		// N'installer que si le mapping n'existe pas encore.
		if ( ! isset( $settings['form_mappings'][1] ) ) {
			$settings['form_mappings'][1] = $form_mapping_config;

			// Sauvegarder.
			update_option( Constants::SETTINGS_OPTION, $settings );

			// Log.
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( '[gravity-forms-siren-autocomplete] Mapping du formulaire ID: 1 installé automatiquement.' );
			}
		}
	}

	/**
	 * Hook de désactivation du plugin
	 */
	public static function deactivate() {
		Deactivator::run();
	}
}
