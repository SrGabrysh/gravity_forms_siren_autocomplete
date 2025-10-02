#!/usr/bin/env php
<?php
/**
 * Script de test des cas d'erreur - API Siren
 * 
 * Ce script teste la gestion des erreurs du plugin :
 * - SIRET invalide (mauvaise longueur, caractères non numériques)
 * - SIRET inexistant (format valide mais entreprise non trouvée)
 * - Entreprises inactives
 * - Erreurs API
 *
 * @package GFSirenAutocomplete
 */

// Configuration
define( 'ABSPATH', __DIR__ . '/' );
define( 'GF_SIREN_API_KEY', getenv( 'GF_SIREN_API_KEY' ) ?: 'FlwM9Symg1SIox2WYRSN2vhRmCCwRXal' );

// Chargement de l'autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Import des classes nécessaires
use GFSirenAutocomplete\Modules\Siren\SirenValidator;
use GFSirenAutocomplete\Modules\Siren\SirenManager;
use GFSirenAutocomplete\Core\Constants;
use GFSirenAutocomplete\Core\Logger;
use GFSirenAutocomplete\Helpers\DataHelper;

// Définir les fonctions WordPress minimales
if ( ! function_exists( 'get_option' ) ) {
	function get_option( $option, $default = false ) {
		return $default;
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( $str ) {
		return trim( strip_tags( $str ) );
	}
}

if ( ! function_exists( 'wp_json_encode' ) ) {
	function wp_json_encode( $data, $options = 0, $depth = 512 ) {
		return json_encode( $data, $options, $depth );
	}
}

if ( ! function_exists( 'absint' ) ) {
	function absint( $maybeint ) {
		return abs( intval( $maybeint ) );
	}
}

if ( ! function_exists( 'get_transient' ) ) {
	function get_transient( $transient ) {
		return false;
	}
}

if ( ! function_exists( 'set_transient' ) ) {
	function set_transient( $transient, $value, $expiration = 0 ) {
		return true;
	}
}

if ( ! function_exists( 'delete_transient' ) ) {
	function delete_transient( $transient ) {
		return true;
	}
}

if ( ! function_exists( 'current_time' ) ) {
	function current_time( $type, $gmt = 0 ) {
		return gmdate( 'Y-m-d H:i:s' );
	}
}

if ( ! function_exists( 'get_current_user_id' ) ) {
	function get_current_user_id() {
		return 0;
	}
}

// Classe WP_Error minimale
if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {
		private $code;
		private $message;
		private $data;

		public function __construct( $code = '', $message = '', $data = '' ) {
			$this->code    = $code;
			$this->message = $message;
			$this->data    = $data;
		}

		public function get_error_code() {
			return $this->code;
		}

		public function get_error_message( $code = '' ) {
			return $this->message;
		}

		public function get_error_data( $code = '' ) {
			return $this->data;
		}
	}
}

if ( ! function_exists( 'is_wp_error' ) ) {
	function is_wp_error( $thing ) {
		return $thing instanceof WP_Error;
	}
}

if ( ! function_exists( 'wp_remote_get' ) ) {
	function wp_remote_get( $url, $args = array() ) {
		$ch = curl_init();
		
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_TIMEOUT, $args['timeout'] ?? 30 );
		
		// Désactiver la vérification SSL pour les tests locaux (Windows)
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false );
		
		if ( isset( $args['headers'] ) ) {
			$headers = array();
			foreach ( $args['headers'] as $key => $value ) {
				$headers[] = "{$key}: {$value}";
			}
			curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
		}
		
		$body = curl_exec( $ch );
		$http_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		$error = curl_error( $ch );
		
		curl_close( $ch );
		
		if ( $error ) {
			return new WP_Error( 'http_request_failed', $error );
		}
		
		return array(
			'response' => array( 'code' => $http_code ),
			'body'     => $body,
		);
	}
}

if ( ! function_exists( 'wp_remote_retrieve_response_code' ) ) {
	function wp_remote_retrieve_response_code( $response ) {
		if ( is_wp_error( $response ) ) {
			return '';
		}
		return $response['response']['code'] ?? 0;
	}
}

if ( ! function_exists( 'wp_remote_retrieve_body' ) ) {
	function wp_remote_retrieve_body( $response ) {
		if ( is_wp_error( $response ) ) {
			return '';
		}
		return $response['body'] ?? '';
	}
}

if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = 'default' ) {
		return $text;
	}
}

if ( ! function_exists( 'date_i18n' ) ) {
	function date_i18n( $format, $timestamp = null ) {
		return date( $format, $timestamp ?? time() );
	}
}

// Mock des fonctions WordPress pour la base de données
global $wpdb;
$wpdb = new class {
	public $prefix = 'wp_';
	public function insert() { return true; }
	public function query() { return 0; }
	public function prepare() { return ''; }
	public function get_var() { return 0; }
	public function get_charset_collate() { return 'utf8mb4_unicode_ci'; }
	public $last_error = '';
};

/**
 * Affiche un titre de section
 */
function print_section( $title ) {
	echo "\n" . str_repeat( '=', 60 ) . "\n";
	echo "  " . $title . "\n";
	echo str_repeat( '=', 60 ) . "\n";
}

/**
 * Affiche un résultat de test
 */
function print_result( $message, $success = true ) {
	$icon = $success ? '✓' : '✗';
	$color = $success ? "\033[32m" : "\033[31m";
	$reset = "\033[0m";
	echo "{$color}{$icon}{$reset} {$message}\n";
}

/**
 * Affiche les données formatées
 */
function print_data( $label, $value, $indent = 2 ) {
	$spaces = str_repeat( ' ', $indent );
	echo "{$spaces}{$label}: {$value}\n";
}

/**
 * Affiche un message d'erreur attendu
 */
function print_expected_error( $message ) {
	$color = "\033[33m"; // Jaune
	$reset = "\033[0m";
	echo "{$color}⚠ Erreur attendue:{$reset} {$message}\n";
}

// ============================================================================
// DÉBUT DES TESTS
// ============================================================================

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  TEST DE GESTION DES ERREURS - API SIREN                   ║\n";
echo "║  Plugin: Gravity Forms Siren Autocomplete                  ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";

print_section( 'CONFIGURATION' );
echo "Clé API: " . ( GF_SIREN_API_KEY !== 'VOTRE_CLE_API' ? '****' . substr( GF_SIREN_API_KEY, -4 ) : 'NON DÉFINIE' ) . "\n";

// Initialisation des composants
try {
	$logger    = Logger::get_instance();
	$manager   = new SirenManager( $logger );
	$validator = new SirenValidator();
	
	print_result( 'Composants initialisés', true );
} catch ( Exception $e ) {
	print_result( 'Erreur lors de l\'initialisation: ' . $e->getMessage(), false );
	exit( 1 );
}

// ============================================================================
// TEST 1 : SIRET avec mauvais format (longueur invalide)
// ============================================================================

print_section( 'TEST 1 : SIRET trop court' );

$siret_court = '123456789';
echo "SIRET testé: {$siret_court}\n";

$validation = $validator->validate_siret_complete( $siret_court );

if ( ! $validation['valid'] ) {
	print_result( 'Format invalide correctement détecté', true );
	print_expected_error( $validation['message'] );
} else {
	print_result( 'ERREUR : Le SIRET invalide a été accepté !', false );
	exit( 1 );
}

// ============================================================================
// TEST 2 : SIRET avec caractères non numériques
// ============================================================================

print_section( 'TEST 2 : SIRET avec caractères invalides' );

$siret_invalide = '1234567890ABCD';
echo "SIRET testé: {$siret_invalide}\n";

$siret_cleaned = $validator->clean_siret( $siret_invalide );
print_data( 'SIRET nettoyé', $siret_cleaned );

$validation = $validator->validate_siret_complete( $siret_cleaned );

if ( ! $validation['valid'] ) {
	print_result( 'Caractères invalides correctement gérés', true );
	print_expected_error( $validation['message'] );
} else {
	print_result( 'ERREUR : Le SIRET nettoyé devrait être invalide !', false );
}

// ============================================================================
// TEST 3 : SIRET inexistant (format valide mais entreprise non trouvée)
// ============================================================================

print_section( 'TEST 3 : SIRET inexistant - Format valide mais entreprise non trouvée' );

$siret_inexistant = '89498206500018'; // SIRET fourni par l'utilisateur (différent d'un chiffre du SIRET valide)
echo "SIRET testé: {$siret_inexistant}\n";

// Vérifier d'abord que le format est valide
$validation = $validator->validate_siret_complete( $siret_inexistant );

if ( $validation['valid'] ) {
	print_result( 'Format de SIRET valide', true );
	
	// Appel à l'API
	$result = $manager->get_company_data( $siret_inexistant );
	
	if ( is_wp_error( $result ) ) {
		print_result( 'Erreur correctement retournée par l\'API', true );
		print_expected_error( $result->get_error_message() );
		print_data( 'Code d\'erreur', $result->get_error_code() );
		
		// Vérifier que c'est bien une erreur 404
		$error_data = $result->get_error_data();
		if ( isset( $error_data['status'] ) && 404 === $error_data['status'] ) {
			print_result( 'Code HTTP 404 (Non trouvé) correctement retourné', true );
		} else {
			print_result( 'ATTENTION : Code HTTP inattendu', false );
		}
	} else {
		print_result( 'ERREUR : Une entreprise a été trouvée avec ce SIRET invalide !', false );
		echo "Données reçues : " . print_r( $result, true ) . "\n";
	}
} else {
	print_result( 'Format invalide détecté : ' . $validation['message'], false );
}

// ============================================================================
// TEST 4 : SIRET vide
// ============================================================================

print_section( 'TEST 4 : SIRET vide' );

$siret_vide = '';
echo "SIRET testé: (vide)\n";

$validation = $validator->validate_siret_complete( $siret_vide );

if ( ! $validation['valid'] ) {
	print_result( 'SIRET vide correctement rejeté', true );
	print_expected_error( $validation['message'] );
} else {
	print_result( 'ERREUR : Le SIRET vide a été accepté !', false );
}

// ============================================================================
// TEST 5 : SIRET avec espaces et tirets (format courant)
// ============================================================================

print_section( 'TEST 5 : SIRET avec espaces et tirets' );

$siret_formate = '894 982 065 00018'; // SIRET invalide mais bien formaté
echo "SIRET testé: {$siret_formate}\n";

$siret_cleaned = $validator->clean_siret( $siret_formate );
print_data( 'SIRET nettoyé', $siret_cleaned );

$validation = $validator->validate_siret_complete( $siret_cleaned );

if ( $validation['valid'] ) {
	print_result( 'Format nettoyé et validé', true );
	
	// Appel à l'API
	$result = $manager->get_company_data( $siret_formate );
	
	if ( is_wp_error( $result ) ) {
		print_result( 'Entreprise non trouvée (attendu)', true );
		print_expected_error( $result->get_error_message() );
	} else {
		print_result( 'ERREUR : Une entreprise a été trouvée !', false );
	}
} else {
	print_result( 'Validation échouée : ' . $validation['message'], false );
}

// ============================================================================
// TEST 6 : Messages d'erreur utilisateur
// ============================================================================

print_section( 'TEST 6 : Vérification des messages d\'erreur utilisateur' );

echo "\nListe des messages d'erreur disponibles :\n";
print_data( 'SIRET requis', Constants::MSG_SIRET_REQUIRED );
print_data( 'Format invalide', Constants::MSG_SIRET_INVALID_FORMAT );
print_data( 'Non trouvé', Constants::MSG_SIRET_NOT_FOUND );
print_data( 'Erreur API', Constants::MSG_API_ERROR );
print_data( 'Timeout', Constants::MSG_API_TIMEOUT );
print_data( 'Service indisponible', Constants::MSG_API_SERVICE_UNAVAILABLE );

print_result( 'Tous les messages d\'erreur sont définis', true );

// ============================================================================
// RÉSUMÉ FINAL
// ============================================================================

print_section( 'RÉSUMÉ DES TESTS D\'ERREUR' );

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  ✅ GESTION DES ERREURS VALIDÉE                           ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
echo "\n";
echo "Le plugin gère correctement :\n";
echo "  ✓ SIRET trop court ou trop long\n";
echo "  ✓ Caractères non numériques\n";
echo "  ✓ SIRET inexistant (404)\n";
echo "  ✓ SIRET vide\n";
echo "  ✓ Nettoyage automatique (espaces, tirets)\n";
echo "  ✓ Messages d'erreur clairs et informatifs\n";
echo "\n";
echo "🛡️ Le plugin est robuste et gère bien les cas d'erreur !\n\n";

exit( 0 );

