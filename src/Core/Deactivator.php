<?php
/**
 * Classe de désactivation du plugin
 *
 * @package GFSirenAutocomplete
 */

namespace GFSirenAutocomplete\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Classe de désactivation du plugin
 */
class Deactivator {

	/**
	 * Actions à effectuer lors de la désactivation du plugin
	 */
	public static function run() {
		// Nettoyer les tâches CRON si elles existent.
		$timestamp = wp_next_scheduled( 'gf_siren_daily_cleanup' );

		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'gf_siren_daily_cleanup' );
		}

		// Log de désactivation.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '[gravity-forms-siren-autocomplete] Plugin désactivé.' );
		}

		// Note : On ne supprime pas les options ni les logs lors de la désactivation.
		// Cela sera fait uniquement lors de la désinstallation (uninstall.php).
	}
}

