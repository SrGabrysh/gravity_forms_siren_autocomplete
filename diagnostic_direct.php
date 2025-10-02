<?php
/**
 * Script de diagnostic direct pour vérifier le fonctionnement du plugin
 * À exécuter via WP-CLI: wp eval-file diagnostic_direct.php
 */

// Charger WordPress
define( 'WP_USE_THEMES', false );
require_once( __DIR__ . '/../../../../../../wp-load.php' );

echo "\n" . str_repeat( "=", 80 ) . "\n";
echo "DIAGNOSTIC DIRECT - Gravity Forms Siren Autocomplete\n";
echo str_repeat( "=", 80 ) . "\n\n";

// 1. Vérifier que le plugin est actif
echo "1️⃣ PLUGIN ACTIF\n";
echo str_repeat( "-", 80 ) . "\n";
if ( ! function_exists( 'is_plugin_active' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
}

$plugin_file = 'gravity_forms_siren_autocomplete/gravity_forms_siren_autocomplete.php';
$is_active = is_plugin_active( $plugin_file );
echo "Plugin actif : " . ( $is_active ? "✅ OUI" : "❌ NON" ) . "\n\n";

// 2. Vérifier la clé API
echo "2️⃣ CLÉ API SIREN\n";
echo str_repeat( "-", 80 ) . "\n";
$api_key = defined( 'GF_SIREN_API_KEY' ) ? GF_SIREN_API_KEY : null;
if ( $api_key ) {
	$masked = substr( $api_key, 0, 8 ) . str_repeat( '*', strlen( $api_key ) - 8 );
	echo "Clé API : ✅ Définie ($masked)\n\n";
} else {
	echo "Clé API : ❌ NON DÉFINIE\n\n";
}

// 3. Vérifier les options
echo "3️⃣ OPTIONS WORDPRESS (gf_siren_settings)\n";
echo str_repeat( "-", 80 ) . "\n";
$settings = get_option( 'gf_siren_settings', array() );
echo "Option existe : " . ( ! empty( $settings ) ? "✅ OUI" : "❌ NON" ) . "\n";
echo "Cache duration : " . ( $settings['cache_duration'] ?? 'N/A' ) . " secondes\n";
echo "Form mappings : " . ( ! empty( $settings['form_mappings'] ) ? "✅ PRÉSENTS" : "❌ VIDES" ) . "\n";

if ( ! empty( $settings['form_mappings'] ) ) {
	echo "\nMappings trouvés :\n";
	foreach ( $settings['form_mappings'] as $form_id => $mapping ) {
		echo "  - Form ID: $form_id\n";
		echo "    Nom: " . ( $mapping['form_name'] ?? 'N/A' ) . "\n";
		echo "    Plugin activé: " . ( $mapping['enable_plugin'] ? 'OUI' : 'NON' ) . "\n";
		echo "    Bouton activé: " . ( $mapping['enable_button'] ? 'OUI' : 'NON' ) . "\n";
		echo "    Champ SIRET: " . ( $mapping['siret'] ?? 'N/A' ) . "\n";
	}
}
echo "\n";

// 4. Vérifier la table des logs
echo "4️⃣ TABLE DES LOGS\n";
echo str_repeat( "-", 80 ) . "\n";
global $wpdb;
$table_name = $wpdb->prefix . 'gf_siren_logs';
$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) === $table_name;
echo "Table existe : " . ( $table_exists ? "✅ OUI" : "❌ NON" ) . "\n";

if ( $table_exists ) {
	$count = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name" );
	echo "Nombre de logs : $count\n";
	
	if ( $count > 0 ) {
		echo "\nDerniers logs :\n";
		$logs = $wpdb->get_results( "SELECT date, level, message FROM $table_name ORDER BY date DESC LIMIT 5" );
		foreach ( $logs as $log ) {
			echo "  [{$log->date}] {$log->level}: {$log->message}\n";
		}
	}
}
echo "\n";

// 5. Tester le chargement des classes
echo "5️⃣ CHARGEMENT DES CLASSES\n";
echo str_repeat( "-", 80 ) . "\n";

$classes_to_test = array(
	'GFSirenAutocomplete\\Core\\Plugin',
	'GFSirenAutocomplete\\Core\\Logger',
	'GFSirenAutocomplete\\Modules\\GravityForms\\GFFieldMapper',
	'GFSirenAutocomplete\\Modules\\GravityForms\\GFButtonInjector',
	'GFSirenAutocomplete\\Admin\\AjaxHandler',
);

foreach ( $classes_to_test as $class ) {
	$exists = class_exists( $class );
	echo $class . ": " . ( $exists ? "✅ CHARGÉE" : "❌ NON CHARGÉE" ) . "\n";
}
echo "\n";

// 6. Vérifier les hooks WordPress
echo "6️⃣ HOOKS WORDPRESS\n";
echo str_repeat( "-", 80 ) . "\n";

$hooks_to_check = array(
	'wp_ajax_gf_siren_verify',
	'wp_ajax_nopriv_gf_siren_verify',
	'gform_field_content',
	'gform_enqueue_scripts',
);

global $wp_filter;
foreach ( $hooks_to_check as $hook ) {
	$has_callbacks = isset( $wp_filter[ $hook ] ) && ! empty( $wp_filter[ $hook ]->callbacks );
	echo "$hook: " . ( $has_callbacks ? "✅ ENREGISTRÉ" : "❌ NON ENREGISTRÉ" ) . "\n";
}
echo "\n";

// 7. Tester le GFFieldMapper
echo "7️⃣ TEST GFFieldMapper\n";
echo str_repeat( "-", 80 ) . "\n";

if ( class_exists( 'GFSirenAutocomplete\\Modules\\GravityForms\\GFFieldMapper' ) ) {
	$mapper = new \GFSirenAutocomplete\Modules\GravityForms\GFFieldMapper();
	
	// Test form_has_mapping pour le formulaire 1
	$has_mapping = $mapper->form_has_mapping( 1 );
	echo "form_has_mapping(1): " . ( $has_mapping ? "✅ TRUE" : "❌ FALSE" ) . "\n";
	
	// Récupérer le mapping
	$mapping = $mapper->get_field_mapping( 1 );
	if ( $mapping ) {
		echo "Mapping Form 1 :\n";
		echo "  - SIRET field: " . ( $mapping['siret'] ?? 'N/A' ) . "\n";
		echo "  - Denomination field: " . ( $mapping['denomination'] ?? 'N/A' ) . "\n";
		echo "  - Enable plugin: " . ( $mapping['enable_plugin'] ? 'YES' : 'NO' ) . "\n";
	} else {
		echo "❌ Aucun mapping trouvé pour le formulaire 1\n";
	}
	
	// Test get_siret_field_id
	$siret_field_id = $mapper->get_siret_field_id( 1 );
	echo "get_siret_field_id(1): " . ( $siret_field_id !== false ? "✅ $siret_field_id" : "❌ FALSE" ) . "\n";
} else {
	echo "❌ Classe GFFieldMapper non disponible\n";
}
echo "\n";

// 8. Vérifier Gravity Forms
echo "8️⃣ GRAVITY FORMS\n";
echo str_repeat( "-", 80 ) . "\n";
$gf_active = class_exists( 'GFForms' );
echo "Gravity Forms actif : " . ( $gf_active ? "✅ OUI" : "❌ NON" ) . "\n";

if ( $gf_active ) {
	echo "Version Gravity Forms : " . GFForms::$version . "\n";
	
	// Vérifier le formulaire ID 1
	if ( class_exists( 'GFAPI' ) ) {
		$form = GFAPI::get_form( 1 );
		if ( $form ) {
			echo "Formulaire ID 1 existe : ✅ OUI\n";
			echo "Titre : " . $form['title'] . "\n";
			echo "Nombre de champs : " . count( $form['fields'] ) . "\n";
		} else {
			echo "Formulaire ID 1 : ❌ NON TROUVÉ\n";
		}
	}
}
echo "\n";

echo str_repeat( "=", 80 ) . "\n";
echo "FIN DU DIAGNOSTIC\n";
echo str_repeat( "=", 80 ) . "\n\n";

