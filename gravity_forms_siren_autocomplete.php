<?php
/**
 * Plugin Name: Gravity Forms Siren Autocomplete
 * Plugin URI: https://github.com/SrGabrysh/gravity-forms-siren-autocomplete
 * Description: Interroge l'API Siren pour récupérer les informations d'entreprise via un SIRET et génère des mentions légales formatées selon le type d'entreprise.
 * Version: 1.0.7
 * Author: TB-Web
 * Author URI: https://tb-web.fr
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: gravity-forms-siren-autocomplete
 * Domain Path: /languages
 * Requires at least: 5.8
 * Tested up to: 6.7
 * Requires PHP: 7.4
 * Network: false
 */

// Sécurité : Empêcher l'accès direct.
defined( 'ABSPATH' ) || exit;

// Constantes du plugin.
define( 'GRAVITY_FORMS_SIREN_AUTOCOMPLETE_VERSION', '1.0.7' );
define( 'GRAVITY_FORMS_SIREN_AUTOCOMPLETE_PLUGIN_FILE', __FILE__ );
define( 'GRAVITY_FORMS_SIREN_AUTOCOMPLETE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'GRAVITY_FORMS_SIREN_AUTOCOMPLETE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Chargement de Composer.
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
}

// Initialisation du plugin.
add_action(
	'plugins_loaded',
	function() {
		if ( class_exists( '\\GFSirenAutocomplete\\Core\\Plugin' ) ) {
			\GFSirenAutocomplete\Core\Plugin::get_instance();
		}
	}
);

// Hook d'activation.
register_activation_hook(
	__FILE__,
	array( 'GFSirenAutocomplete\\Core\\Plugin', 'activate' )
);

// Hook de désactivation.
register_deactivation_hook(
	__FILE__,
	array( 'GFSirenAutocomplete\\Core\\Plugin', 'deactivate' )
);
