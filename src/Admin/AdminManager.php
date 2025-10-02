<?php
/**
 * Orchestration du module Admin
 *
 * @package GFSirenAutocomplete
 */

namespace GFSirenAutocomplete\Admin;

defined( 'ABSPATH' ) || exit;

use GFSirenAutocomplete\Core\Constants;
use GFSirenAutocomplete\Core\Logger;
use GFSirenAutocomplete\Modules\Siren\SirenManager;
use GFSirenAutocomplete\Modules\GravityForms\GFManager;

/**
 * Classe d'orchestration de l'interface d'administration
 */
class AdminManager {

	/**
	 * Instance du Siren Manager
	 *
	 * @var SirenManager
	 */
	private $siren_manager;

	/**
	 * Instance du GF Manager
	 *
	 * @var GFManager
	 */
	private $gf_manager;

	/**
	 * Instance du logger
	 *
	 * @var Logger
	 */
	private $logger;

	/**
	 * Instance de la page de configuration
	 *
	 * @var SettingsPage
	 */
	private $settings_page;

	/**
	 * Instance du visualiseur de logs
	 *
	 * @var LogsViewer
	 */
	private $logs_viewer;

	/**
	 * Instance du gestionnaire AJAX
	 *
	 * @var AjaxHandler
	 */
	private $ajax_handler;

	/**
	 * Constructeur
	 *
	 * @param SirenManager $siren_manager Instance du Siren Manager.
	 * @param GFManager    $gf_manager Instance du GF Manager.
	 * @param Logger       $logger Instance du logger.
	 */
	public function __construct( SirenManager $siren_manager, GFManager $gf_manager, Logger $logger ) {
		$this->siren_manager = $siren_manager;
		$this->gf_manager    = $gf_manager;
		$this->logger        = $logger;

		// Initialiser les composants admin.
		$this->settings_page = new SettingsPage( $gf_manager->get_field_mapper() );
		$this->logs_viewer   = new LogsViewer( $logger );
		$this->ajax_handler  = new AjaxHandler( $siren_manager, $gf_manager, $logger );
	}

	/**
	 * Initialise les hooks de l'administration
	 */
	public function init_hooks() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

		// Initialiser le gestionnaire AJAX.
		$this->ajax_handler->init_hooks();
	}

	/**
	 * Ajoute le menu dans l'administration WordPress
	 */
	public function add_admin_menu() {
		// Toujours utiliser manage_options pour éviter les problèmes de permissions.
		$capability = Constants::ADMIN_CAPABILITY;
		
		// Si Gravity Forms est actif, ajouter comme sous-menu de GF.
		if ( class_exists( 'GFForms' ) ) {
			$parent_slug = 'gf_edit_forms';
		} else {
			$parent_slug = 'options-general.php';
		}

		// Page principale : Configuration.
		add_submenu_page(
			$parent_slug,
			__( 'Siren Autocomplete', Constants::TEXT_DOMAIN ),
			__( 'Siren Autocomplete', Constants::TEXT_DOMAIN ),
			$capability,
			Constants::ADMIN_MENU_SLUG,
			array( $this->settings_page, 'render' )
		);

		// Sous-page : Logs.
		add_submenu_page(
			$parent_slug,
			__( 'Logs - Siren Autocomplete', Constants::TEXT_DOMAIN ),
			__( 'Logs Siren', Constants::TEXT_DOMAIN ),
			$capability,
			Constants::ADMIN_MENU_SLUG . '-logs',
			array( $this->logs_viewer, 'render' )
		);
	}

	/**
	 * Enqueue les assets CSS/JS de l'administration
	 *
	 * @param string $hook Hook de la page courante.
	 */
	public function enqueue_admin_assets( $hook ) {
		// Ne charger que sur nos pages.
		$our_pages = array(
			'forms_page_' . Constants::ADMIN_MENU_SLUG,
			'forms_page_' . Constants::ADMIN_MENU_SLUG . '-logs',
			'settings_page_' . Constants::ADMIN_MENU_SLUG,
			'settings_page_' . Constants::ADMIN_MENU_SLUG . '-logs',
		);

		if ( ! in_array( $hook, $our_pages, true ) ) {
			return;
		}

		// Enqueue CSS admin.
		wp_enqueue_style(
			'gf-siren-admin',
			plugin_dir_url( GRAVITY_FORMS_SIREN_AUTOCOMPLETE_PLUGIN_FILE ) . 'assets/css/admin.css',
			array(),
			'1.0.0'
		);

		// Enqueue JS admin.
		wp_enqueue_script(
			'gf-siren-admin',
			plugin_dir_url( GRAVITY_FORMS_SIREN_AUTOCOMPLETE_PLUGIN_FILE ) . 'assets/js/admin.js',
			array( 'jquery' ),
			'1.0.0',
			true
		);

		// Localiser le script.
		wp_localize_script(
			'gf-siren-admin',
			'gfSirenAdmin',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( Constants::NONCE_ACTION ),
				'messages' => array(
					'test_success'     => __( 'Connexion réussie !', Constants::TEXT_DOMAIN ),
					'test_error'       => __( 'Erreur de connexion', Constants::TEXT_DOMAIN ),
					'cache_cleared'    => __( 'Cache vidé avec succès', Constants::TEXT_DOMAIN ),
					'confirm_delete'   => __( 'Êtes-vous sûr ?', Constants::TEXT_DOMAIN ),
				),
			)
		);
	}
}

