<?php
/**
 * Utilitaires de sécurité
 *
 * @package GFSirenAutocomplete
 */

namespace GFSirenAutocomplete\Helpers;

defined( 'ABSPATH' ) || exit;

use GFSirenAutocomplete\Core\Constants;

/**
 * Classe d'utilitaires pour la sécurité
 */
class SecurityHelper {

	/**
	 * Vérifie un nonce WordPress
	 *
	 * @param string $nonce Le nonce à vérifier.
	 * @param string $action L'action associée au nonce.
	 * @return bool True si le nonce est valide, false sinon.
	 */
	public static function verify_nonce( $nonce, $action = Constants::NONCE_ACTION ) {
		return (bool) wp_verify_nonce( $nonce, $action );
	}

	/**
	 * Crée un nonce WordPress
	 *
	 * @param string $action L'action associée au nonce.
	 * @return string Le nonce créé.
	 */
	public static function create_nonce( $action = Constants::NONCE_ACTION ) {
		return wp_create_nonce( $action );
	}

	/**
	 * Vérifie si l'utilisateur courant a les permissions requises
	 *
	 * @param string $capability La capability à vérifier (par défaut: manage_options).
	 * @return bool True si l'utilisateur a la permission, false sinon.
	 */
	public static function check_permissions( $capability = Constants::ADMIN_CAPABILITY ) {
		return current_user_can( $capability );
	}

	/**
	 * Vérifie les permissions et le nonce pour les requêtes AJAX
	 *
	 * @param string $nonce Le nonce à vérifier.
	 * @param string $capability La capability requise.
	 * @param string $action L'action du nonce.
	 * @return bool True si tout est valide, false sinon.
	 */
	public static function verify_ajax_request( $nonce, $capability = Constants::ADMIN_CAPABILITY, $action = Constants::NONCE_ACTION ) {
		if ( ! self::verify_nonce( $nonce, $action ) ) {
			return false;
		}

		if ( ! self::check_permissions( $capability ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Sanitize une entrée utilisateur
	 *
	 * @param string $input L'entrée à sanitize.
	 * @param string $type Le type de sanitization ('text', 'textarea', 'email', 'url').
	 * @return string L'entrée sanitizée.
	 */
	public static function sanitize_input( $input, $type = 'text' ) {
		switch ( $type ) {
			case 'textarea':
				return sanitize_textarea_field( $input );
			case 'email':
				return sanitize_email( $input );
			case 'url':
				return esc_url_raw( $input );
			case 'html':
				return wp_kses_post( $input );
			case 'text':
			default:
				return sanitize_text_field( $input );
		}
	}

	/**
	 * Échappe une sortie pour l'affichage HTML
	 *
	 * @param string $output La sortie à échapper.
	 * @param string $context Le contexte ('html', 'attr', 'url', 'js').
	 * @return string La sortie échappée.
	 */
	public static function escape_output( $output, $context = 'html' ) {
		switch ( $context ) {
			case 'attr':
				return esc_attr( $output );
			case 'url':
				return esc_url( $output );
			case 'js':
				return esc_js( $output );
			case 'textarea':
				return esc_textarea( $output );
			case 'html':
			default:
				return esc_html( $output );
		}
	}

	/**
	 * Termine la requête avec un message d'erreur JSON
	 *
	 * @param string $message Le message d'erreur.
	 * @param int    $code Le code HTTP (par défaut: 403).
	 */
	public static function die_json_error( $message, $code = 403 ) {
		wp_send_json_error(
			array(
				'message' => $message,
				'success' => false,
			),
			$code
		);
	}

	/**
	 * Termine la requête avec un succès JSON
	 *
	 * @param mixed $data Les données à retourner.
	 * @param int   $code Le code HTTP (par défaut: 200).
	 */
	public static function send_json_success( $data, $code = 200 ) {
		wp_send_json_success( $data, $code );
	}

	/**
	 * Valide une clé API (format basique)
	 *
	 * @param string $api_key La clé API à valider.
	 * @return bool True si la clé semble valide, false sinon.
	 */
	public static function validate_api_key( $api_key ) {
		// Vérifier que la clé n'est pas vide et a une longueur minimale.
		return ! empty( $api_key ) && strlen( $api_key ) >= 10;
	}
}

