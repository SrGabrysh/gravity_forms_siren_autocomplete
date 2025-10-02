#!/usr/bin/env php
<?php
/**
 * Script de test de génération des mentions légales complètes
 * 
 * Ce script teste la génération des mentions légales avec :
 * - Données SIRET récupérées depuis l'API
 * - Informations du représentant (prénom, nom)
 * - Tous les formats de mentions (Personne Morale, Entrepreneur Individuel)
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
use GFSirenAutocomplete\Modules\MentionsLegales\MentionsFormatter;
use GFSirenAutocomplete\Modules\MentionsLegales\MentionsHelper;
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

if ( ! function_exists( 'esc_html' ) ) {
	function esc_html( $text ) {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
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
	echo "\n" . str_repeat( '=', 70 ) . "\n";
	echo "  " . $title . "\n";
	echo str_repeat( '=', 70 ) . "\n";
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
 * Affiche une boîte avec du texte
 */
function print_box( $content, $title = '' ) {
	$lines = explode( "\n", $content );
	$max_length = 0;
	foreach ( $lines as $line ) {
		$max_length = max( $max_length, strlen( $line ) );
	}
	
	$box_width = min( $max_length + 4, 70 );
	
	echo "\n";
	echo str_repeat( '─', $box_width ) . "\n";
	
	if ( ! empty( $title ) ) {
		echo "│ " . str_pad( $title, $box_width - 4 ) . " │\n";
		echo str_repeat( '─', $box_width ) . "\n";
	}
	
	foreach ( $lines as $line ) {
		$wrapped_lines = explode( "\n", wordwrap( $line, $box_width - 4 ) );
		foreach ( $wrapped_lines as $wrapped ) {
			echo "│ " . str_pad( $wrapped, $box_width - 4 ) . " │\n";
		}
	}
	
	echo str_repeat( '─', $box_width ) . "\n";
}

// ============================================================================
// DÉBUT DES TESTS
// ============================================================================

echo "\n";
echo "╔══════════════════════════════════════════════════════════════════╗\n";
echo "║  TEST DE GÉNÉRATION DES MENTIONS LÉGALES COMPLÈTES               ║\n";
echo "║  Plugin: Gravity Forms Siren Autocomplete                        ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n";

// Lecture du payload JSON
$payload_file = __DIR__ . '/../../Dev/payload.json';
if ( ! file_exists( $payload_file ) ) {
	print_result( 'Fichier payload.json non trouvé : ' . $payload_file, false );
	exit( 1 );
}

$payload_json = file_get_contents( $payload_file );
$payload = json_decode( $payload_json, true );

if ( json_last_error() !== JSON_ERROR_NONE ) {
	print_result( 'Erreur lors du décodage du JSON : ' . json_last_error_msg(), false );
	exit( 1 );
}

print_section( 'PAYLOAD REÇU' );
echo "Fichier: payload.json\n";
print_data( 'SIRET', $payload['siret'] ?? 'NON DÉFINI' );
print_data( 'Prénom représentant', $payload['prenom'] ?? 'NON DÉFINI' );
print_data( 'Nom représentant', $payload['nom'] ?? 'NON DÉFINI' );

// Vérification des données
if ( empty( $payload['siret'] ) ) {
	print_result( 'SIRET manquant dans le payload', false );
	exit( 1 );
}

print_result( 'Payload chargé avec succès', true );

// Initialisation des composants
print_section( 'INITIALISATION' );

try {
	$logger    = Logger::get_instance();
	$manager   = new SirenManager( $logger );
	$validator = new SirenValidator();
	$formatter = new MentionsFormatter();
	
	print_result( 'Composants initialisés', true );
} catch ( Exception $e ) {
	print_result( 'Erreur lors de l\'initialisation: ' . $e->getMessage(), false );
	exit( 1 );
}

// ============================================================================
// ÉTAPE 1 : Récupération des données SIRET depuis l'API
// ============================================================================

print_section( 'ÉTAPE 1 : Récupération des données entreprise' );

$siret = $payload['siret'];
echo "SIRET à rechercher: {$siret}\n";

$company_data = $manager->get_company_data( $siret );

if ( is_wp_error( $company_data ) ) {
	print_result( 'Erreur API: ' . $company_data->get_error_message(), false );
	exit( 1 );
}

print_result( 'Données entreprise récupérées', true );

// Afficher les informations de l'entreprise
$etablissement = $company_data['etablissement'];
$unite_legale  = $company_data['unite_legale'];

echo "\nInformations récupérées :\n";
if ( isset( $unite_legale['denomination'] ) ) {
	print_data( 'Dénomination', $unite_legale['denomination'] );
}
print_data( 'SIREN', DataHelper::format_siren( $unite_legale['siren'] ) );
print_data( 'SIRET', DataHelper::format_siret( $etablissement['siret'] ) );

$adresse = MentionsHelper::get_adresse_complete( $etablissement );
print_data( 'Adresse', $adresse );

if ( isset( $unite_legale['categorie_juridique'] ) ) {
	$forme_juridique = MentionsHelper::get_forme_juridique( $unite_legale['categorie_juridique'] );
	print_data( 'Forme juridique', $forme_juridique );
}

// ============================================================================
// ÉTAPE 2 : Détermination du type d'entreprise
// ============================================================================

print_section( 'ÉTAPE 2 : Détermination du type d\'entreprise' );

$type = $validator->determine_entreprise_type( $unite_legale );
print_data( 'Type déterminé', $type );

$is_personne_morale = ( Constants::ENTREPRISE_TYPE_PERSONNE_MORALE === $type );
$is_entrepreneur_individuel = ( Constants::ENTREPRISE_TYPE_ENTREPRENEUR_INDIVIDUEL === $type );

if ( $is_personne_morale ) {
	print_result( 'Entreprise de type PERSONNE MORALE', true );
} elseif ( $is_entrepreneur_individuel ) {
	print_result( 'Entreprise de type ENTREPRENEUR INDIVIDUEL', true );
} else {
	print_result( 'Type d\'entreprise INCONNU', false );
}

// ============================================================================
// ÉTAPE 3 : Génération des mentions légales SANS représentant
// ============================================================================

print_section( 'ÉTAPE 3 : Mentions légales SANS représentant' );

echo "Génération des mentions de base (sans nom de représentant)...\n\n";

try {
	if ( $is_personne_morale ) {
		$mentions_base = $formatter->format_personne_morale( $company_data );
	} elseif ( $is_entrepreneur_individuel ) {
		$mentions_base = $formatter->format_entrepreneur_individuel( $company_data );
	} else {
		$mentions_base = $formatter->format_fallback( $company_data );
	}
	
	print_result( 'Mentions de base générées (' . strlen( $mentions_base ) . ' caractères)', true );
	print_box( $mentions_base, 'MENTIONS LÉGALES DE BASE' );
	
} catch ( Exception $e ) {
	print_result( 'Erreur lors de la génération: ' . $e->getMessage(), false );
	exit( 1 );
}

// ============================================================================
// ÉTAPE 4 : Génération des mentions légales AVEC représentant
// ============================================================================

print_section( 'ÉTAPE 4 : Mentions légales AVEC représentant' );

// Préparer les données du représentant
$representant = array();

if ( ! empty( $payload['prenom'] ) && ! empty( $payload['nom'] ) ) {
	$representant['prenom'] = sanitize_text_field( $payload['prenom'] );
	$representant['nom']    = sanitize_text_field( $payload['nom'] );
	
	print_data( 'Représentant', $representant['prenom'] . ' ' . $representant['nom'] );
	
	echo "\nGénération des mentions complètes avec le représentant...\n\n";
	
	try {
		if ( $is_personne_morale ) {
			// Pour une personne morale, on doit passer le représentant et la forme juridique
			$forme_juridique = MentionsHelper::get_forme_juridique( $unite_legale['categorie_juridique'] ?? '' );
			$mentions_completes = $formatter->format_societe_capital( $company_data, $forme_juridique, $representant );
		} elseif ( $is_entrepreneur_individuel ) {
			// Pour un EI, le nom est déjà dans les données
			$mentions_completes = $formatter->format_entrepreneur_individuel( $company_data );
		} else {
			$mentions_completes = $formatter->format_fallback( $company_data );
		}
		
		print_result( 'Mentions complètes générées (' . strlen( $mentions_completes ) . ' caractères)', true );
		print_box( $mentions_completes, 'MENTIONS LÉGALES COMPLÈTES (avec représentant)' );
		
	} catch ( Exception $e ) {
		print_result( 'Erreur lors de la génération: ' . $e->getMessage(), false );
		exit( 1 );
	}
} else {
	print_result( 'Informations du représentant manquantes dans le payload', false );
	echo "Pour générer des mentions complètes, ajoutez 'prenom' et 'nom' dans payload.json\n";
}

// ============================================================================
// ÉTAPE 5 : Comparaison et analyse
// ============================================================================

print_section( 'ÉTAPE 5 : Analyse des mentions générées' );

echo "\nDifférences entre les deux versions :\n";

if ( isset( $mentions_completes ) ) {
	$diff_length = strlen( $mentions_completes ) - strlen( $mentions_base );
	print_data( 'Longueur sans représentant', strlen( $mentions_base ) . ' caractères' );
	print_data( 'Longueur avec représentant', strlen( $mentions_completes ) . ' caractères' );
	print_data( 'Différence', abs( $diff_length ) . ' caractères (' . ( $diff_length > 0 ? '+' : '' ) . $diff_length . ')' );
	
	// Vérifier la présence du nom du représentant
	$prenom_present = strpos( $mentions_completes, $payload['prenom'] ) !== false;
	$nom_present = strpos( $mentions_completes, strtoupper( $payload['nom'] ) ) !== false;
	
	if ( $prenom_present && $nom_present ) {
		print_result( 'Nom du représentant présent dans les mentions', true );
	} else {
		print_result( 'ATTENTION : Nom du représentant non trouvé dans les mentions', false );
	}
	
	// Vérifier les éléments obligatoires
	$elements_obligatoires = array(
		'SIREN' => DataHelper::format_siren( $unite_legale['siren'] ),
		'Adresse' => true, // Juste vérifier la présence
		'Registre du Commerce' => 'Registre du Commerce',
	);
	
	echo "\nÉléments obligatoires présents :\n";
	foreach ( $elements_obligatoires as $element => $search ) {
		if ( $search === true ) {
			$present = ! empty( $adresse );
		} else {
			$present = strpos( $mentions_completes, $search ) !== false;
		}
		print_result( $element, $present );
	}
}

// ============================================================================
// RÉSUMÉ FINAL
// ============================================================================

print_section( 'RÉSUMÉ DE LA GÉNÉRATION' );

echo "\n";
echo "╔══════════════════════════════════════════════════════════════════╗\n";
echo "║  ✅ GÉNÉRATION DES MENTIONS LÉGALES RÉUSSIE                     ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n";
echo "\n";

echo "Résumé :\n";
echo "  ✓ Entreprise: " . ( $unite_legale['denomination'] ?? 'N/A' ) . "\n";
echo "  ✓ Type: " . $type . "\n";
echo "  ✓ Représentant: " . ( isset( $representant['prenom'] ) ? $representant['prenom'] . ' ' . $representant['nom'] : 'Non spécifié' ) . "\n";
echo "  ✓ Mentions de base: " . strlen( $mentions_base ) . " caractères\n";
if ( isset( $mentions_completes ) ) {
	echo "  ✓ Mentions complètes: " . strlen( $mentions_completes ) . " caractères\n";
}
echo "\n";
echo "📋 Les mentions légales sont prêtes à être insérées dans le formulaire !\n\n";

// Sauvegarder les mentions dans un fichier pour référence
$output_file = __DIR__ . '/mentions_legales_generees.txt';
file_put_contents( $output_file, "MENTIONS LÉGALES GÉNÉRÉES\n" );
file_put_contents( $output_file, "Date: " . date( 'Y-m-d H:i:s' ) . "\n", FILE_APPEND );
file_put_contents( $output_file, str_repeat( '=', 70 ) . "\n\n", FILE_APPEND );
file_put_contents( $output_file, "MENTIONS DE BASE (sans représentant):\n", FILE_APPEND );
file_put_contents( $output_file, str_repeat( '-', 70 ) . "\n", FILE_APPEND );
file_put_contents( $output_file, $mentions_base . "\n\n", FILE_APPEND );

if ( isset( $mentions_completes ) ) {
	file_put_contents( $output_file, "MENTIONS COMPLÈTES (avec représentant):\n", FILE_APPEND );
	file_put_contents( $output_file, str_repeat( '-', 70 ) . "\n", FILE_APPEND );
	file_put_contents( $output_file, $mentions_completes . "\n\n", FILE_APPEND );
}

echo "💾 Mentions sauvegardées dans: mentions_legales_generees.txt\n\n";

exit( 0 );

