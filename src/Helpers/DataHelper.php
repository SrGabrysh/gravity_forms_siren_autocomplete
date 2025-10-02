<?php
/**
 * Utilitaires de manipulation de données
 *
 * @package GFSirenAutocomplete
 */

namespace GFSirenAutocomplete\Helpers;

defined( 'ABSPATH' ) || exit;

use GFSirenAutocomplete\Core\Constants;

/**
 * Classe d'utilitaires pour la manipulation des données
 */
class DataHelper {

	/**
	 * Nettoie et sanitize un numéro SIRET
	 *
	 * @param string $siret Le numéro SIRET à nettoyer.
	 * @return string Le SIRET nettoyé (uniquement des chiffres).
	 */
	public static function sanitize_siret( $siret ) {
		if ( empty( $siret ) ) {
			return '';
		}

		// Supprimer tous les caractères non numériques.
		$cleaned = preg_replace( '/[^0-9]/', '', $siret );

		return \sanitize_text_field( $cleaned );
	}

	/**
	 * Formate une date
	 *
	 * @param string $date La date à formater.
	 * @param string $format Le format de sortie (par défaut: d/m/Y).
	 * @return string La date formatée.
	 */
	public static function format_date( $date, $format = 'd/m/Y' ) {
		if ( empty( $date ) ) {
			return '';
		}

		$timestamp = strtotime( $date );

		if ( false === $timestamp ) {
			return $date;
		}

		return \date_i18n( $format, $timestamp );
	}

	/**
	 * Extrait une valeur d'un tableau de manière sécurisée
	 *
	 * @param array  $data Le tableau de données.
	 * @param string $path Le chemin vers la valeur (ex: 'unite_legale.denomination').
	 * @param mixed  $default La valeur par défaut si non trouvée.
	 * @return mixed La valeur extraite ou la valeur par défaut.
	 */
	public static function extract_field_value( $data, $path, $default = '' ) {
		if ( ! is_array( $data ) ) {
			return $default;
		}

		$keys = explode( '.', $path );

		foreach ( $keys as $key ) {
			if ( ! isset( $data[ $key ] ) ) {
				return $default;
			}
			$data = $data[ $key ];
		}

		return $data;
	}

	/**
	 * Formate un numéro SIRET avec des espaces (XXX XXX XXX XXXXX)
	 *
	 * @param string $siret Le numéro SIRET à formater.
	 * @return string Le SIRET formaté.
	 */
	public static function format_siret( $siret ) {
		if ( empty( $siret ) || strlen( $siret ) !== Constants::SIRET_LENGTH ) {
			return $siret;
		}

		// Format: 3 + 3 + 3 + 5.
		return substr( $siret, 0, 3 ) . ' ' . substr( $siret, 3, 3 ) . ' ' . substr( $siret, 6, 3 ) . ' ' . substr( $siret, 9, 5 );
	}

	/**
	 * Formate un numéro SIREN avec des espaces (XXX XXX XXX)
	 *
	 * @param string $siren Le numéro SIREN à formater.
	 * @return string Le SIREN formaté.
	 */
	public static function format_siren( $siren ) {
		if ( empty( $siren ) || strlen( $siren ) !== Constants::SIREN_LENGTH ) {
			return $siren;
		}

		// Format: 3 + 3 + 3.
		return substr( $siren, 0, 3 ) . ' ' . substr( $siren, 3, 3 ) . ' ' . substr( $siren, 6, 3 );
	}

	/**
	 * Sanitize une clé API
	 *
	 * @param string $api_key La clé API à sanitize.
	 * @return string La clé API sanitizée.
	 */
	public static function sanitize_api_key( $api_key ) {
		return sanitize_text_field( trim( $api_key ) );
	}

	/**
	 * Masque une clé API pour l'affichage (ne montre que les 4 derniers caractères)
	 *
	 * @param string $api_key La clé API à masquer.
	 * @return string La clé API masquée.
	 */
	public static function mask_api_key( $api_key ) {
		if ( empty( $api_key ) || strlen( $api_key ) < 8 ) {
			return '***';
		}

		return str_repeat( '*', strlen( $api_key ) - 4 ) . substr( $api_key, -4 );
	}

	/**
	 * Convertit un tableau en JSON de manière sécurisée
	 *
	 * @param mixed $data Les données à convertir.
	 * @return string|false Le JSON ou false en cas d'erreur.
	 */
	public static function to_json( $data ) {
		return wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
	}

	/**
	 * Décode un JSON de manière sécurisée
	 *
	 * @param string $json Le JSON à décoder.
	 * @param bool   $assoc Si true, retourne un tableau associatif.
	 * @return mixed Les données décodées ou null en cas d'erreur.
	 */
	public static function from_json( $json, $assoc = true ) {
		if ( empty( $json ) ) {
			return null;
		}

		return json_decode( $json, $assoc );
	}

	/**
	 * Vérifie si une chaîne est vide ou nulle
	 *
	 * @param mixed $value La valeur à vérifier.
	 * @return bool True si vide, false sinon.
	 */
	public static function is_empty( $value ) {
		// '0' ne doit pas être considéré comme vide.
		if ( '0' === $value || 0 === $value ) {
			return false;
		}
		return empty( $value ) || ( is_string( $value ) && '' === trim( $value ) );
	}

	/**
	 * Sanitize un tableau de données
	 *
	 * @param array $data Le tableau à sanitize.
	 * @return array Le tableau sanitizé.
	 */
	public static function sanitize_array( $data ) {
		if ( ! is_array( $data ) ) {
			return array();
		}

		$sanitized = array();

		foreach ( $data as $key => $value ) {
			$key = sanitize_key( $key );

			if ( is_array( $value ) ) {
				$sanitized[ $key ] = self::sanitize_array( $value );
			} else {
				$sanitized[ $key ] = sanitize_text_field( $value );
			}
		}

		return $sanitized;
	}
}

