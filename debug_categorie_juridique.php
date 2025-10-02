#!/usr/bin/env php
<?php
/**
 * Script de debug pour vérifier la catégorie juridique réelle de l'API
 */

define( 'ABSPATH', __DIR__ . '/' );
define( 'GF_SIREN_API_KEY', 'FlwM9Symg1SIox2WYRSN2vhRmCCwRXal' );

require_once __DIR__ . '/vendor/autoload.php';

use GFSirenAutocomplete\Modules\Siren\SirenManager;
use GFSirenAutocomplete\Modules\MentionsLegales\MentionsHelper;
use GFSirenAutocomplete\Core\Logger;

// Fonctions WordPress minimales
if ( ! function_exists( 'get_option' ) ) {
	function get_option( $option, $default = false ) { return $default; }
}
if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( $str ) { return trim( strip_tags( $str ) ); }
}
if ( ! function_exists( 'wp_json_encode' ) ) {
	function wp_json_encode( $data, $options = 0, $depth = 512 ) { return json_encode( $data, $options, $depth ); }
}
if ( ! function_exists( 'absint' ) ) {
	function absint( $maybeint ) { return abs( intval( $maybeint ) ); }
}
if ( ! function_exists( 'get_transient' ) ) {
	function get_transient( $transient ) { return false; }
}
if ( ! function_exists( 'set_transient' ) ) {
	function set_transient( $transient, $value, $expiration = 0 ) { return true; }
}
if ( ! function_exists( 'delete_transient' ) ) {
	function delete_transient( $transient ) { return true; }
}
if ( ! function_exists( 'current_time' ) ) {
	function current_time( $type, $gmt = 0 ) { return gmdate( 'Y-m-d H:i:s' ); }
}
if ( ! function_exists( 'get_current_user_id' ) ) {
	function get_current_user_id() { return 0; }
}

class WP_Error {
	private $code, $message, $data;
	public function __construct( $code = '', $message = '', $data = '' ) {
		$this->code = $code; $this->message = $message; $this->data = $data;
	}
	public function get_error_code() { return $this->code; }
	public function get_error_message( $code = '' ) { return $this->message; }
	public function get_error_data( $code = '' ) { return $this->data; }
}

if ( ! function_exists( 'is_wp_error' ) ) {
	function is_wp_error( $thing ) { return $thing instanceof WP_Error; }
}

if ( ! function_exists( 'wp_remote_get' ) ) {
	function wp_remote_get( $url, $args = array() ) {
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_TIMEOUT, $args['timeout'] ?? 30 );
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
		
		return array( 'response' => array( 'code' => $http_code ), 'body' => $body );
	}
}

if ( ! function_exists( 'wp_remote_retrieve_response_code' ) ) {
	function wp_remote_retrieve_response_code( $response ) {
		if ( is_wp_error( $response ) ) return '';
		return $response['response']['code'] ?? 0;
	}
}

if ( ! function_exists( 'wp_remote_retrieve_body' ) ) {
	function wp_remote_retrieve_body( $response ) {
		if ( is_wp_error( $response ) ) return '';
		return $response['body'] ?? '';
	}
}

if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = 'default' ) { return $text; }
}

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

// ============================================================================
// DEBUG
// ============================================================================

echo "\n╔══════════════════════════════════════════════════════════════════╗\n";
echo "║  DEBUG CATÉGORIE JURIDIQUE - ESTHESUD                            ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n\n";

$logger = Logger::get_instance();
$manager = new SirenManager( $logger );

$siret = '89498206500019';
echo "SIRET: {$siret}\n\n";

$company_data = $manager->get_company_data( $siret );

if ( is_wp_error( $company_data ) ) {
	echo "Erreur: " . $company_data->get_error_message() . "\n";
	exit( 1 );
}

$unite_legale = $company_data['unite_legale'];

echo "=== DONNÉES BRUTES DE L'API ===\n\n";
echo "Dénomination: " . ( $unite_legale['denomination'] ?? 'N/A' ) . "\n";
echo "Catégorie juridique (CODE): " . ( $unite_legale['categorie_juridique'] ?? 'N/A' ) . "\n";
echo "Catégorie juridique (LIBELLE): " . ( $unite_legale['libelle_categorie_juridique'] ?? 'N/A' ) . "\n\n";

$code_categorie = $unite_legale['categorie_juridique'] ?? '';
$forme_juridique = MentionsHelper::get_forme_juridique( $code_categorie );

echo "=== MAPPING DU PLUGIN ===\n\n";
echo "Code catégorie juridique API: {$code_categorie}\n";
echo "Forme juridique mappée: {$forme_juridique}\n";
echo "Titre représentant: " . MentionsHelper::get_titre_representant( $forme_juridique ) . "\n\n";

echo "=== ANALYSE ===\n\n";

// Vérifier si c'est vraiment une SARL
if ( $forme_juridique === 'SARL' ) {
	echo "✓ Forme juridique correcte : SARL\n";
	echo "✓ Titre correct : Gérant\n";
} elseif ( $forme_juridique === 'SA' ) {
	echo "✗ PROBLÈME DÉTECTÉ !\n";
	echo "  Le code API '{$code_categorie}' est mappé vers 'SA' au lieu de 'SARL'\n";
	echo "  Titre incorrect : Directeur Général (devrait être 'Gérant')\n";
} else {
	echo "⚠ Forme juridique : {$forme_juridique}\n";
}

echo "\n=== CODES SUPPORTÉS ACTUELLEMENT ===\n\n";
$codes = array(
	'5710' => 'SAS',
	'5720' => 'SASU',
	'5499' => 'SA',
	'5410' => 'SARL',
	'5422' => 'EURL',
	'5498' => 'SELARL',
);

foreach ( $codes as $code => $forme ) {
	$marker = ( $code === $code_categorie ) ? '→ ' : '  ';
	echo "{$marker}{$code} = {$forme}\n";
}

echo "\n";

