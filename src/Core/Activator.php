<?php
/**
 * Classe d'activation du plugin Gravity Forms Siren Autocomplete
 *
 * @package GravityFormsSirenAutocomplete
 */

namespace GravityFormsSirenAutocomplete\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Classe d'activation du plugin
 */
class Activator {

	/**
	 * Actions à effectuer lors de l'activation du plugin
	 */
	public static function run() {
		// Créer les options par défaut.
		add_option( 'gravity_forms_siren_autocomplete_version', '1.0.0' );
		add_option( 'gravity_forms_siren_autocomplete_settings', array() );

		// Planifier les tâches CRON si nécessaire.
		if ( ! wp_next_scheduled( 'gravity_forms_siren_autocomplete_daily_task' ) ) {
			wp_schedule_event( time(), 'daily', 'gravity_forms_siren_autocomplete_daily_task' );
		}

		// Flush des règles de réécriture.
		flush_rewrite_rules();

		// Log d'activation.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '[gravity_forms_siren_autocomplete] Plugin activé avec succès.' );
		}
	}
}
