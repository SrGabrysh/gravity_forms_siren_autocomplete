<?php
/**
 * Configuration du formulaire Gravity Forms ID: 1
 * 
 * Ce script configure automatiquement le mapping des champs
 * pour le formulaire "Test de positionnement Révélation Digitale"
 * 
 * @package GFSirenAutocomplete
 */

// Sécurité : Empêcher l'accès direct.
defined( 'ABSPATH' ) || die( 'Accès direct interdit.' );

/**
 * Configuration du mapping pour le formulaire ID: 1
 */
$form_mapping_config = array(
	'form_id'         => 1,
	'form_name'       => 'Test de positionnement Révélation Digitale',
	'enable_plugin'   => true,
	'enable_button'   => true,
	'button_position' => 'after', // Après le champ SIRET
	
	// ===================================================================
	// CHAMPS À REMPLIR (ÉCRITURE)
	// ===================================================================
	
	'siret'           => '1',     // Champ SIRET (saisie utilisateur)
	'denomination'    => '12',    // Champ Nom de l'entreprise
	
	// Champ Adresse (composite - ID: 8)
	'adresse'         => '8.1',   // Sous-champ Rue
	'ville'           => '8.3',   // Sous-champ Ville
	'code_postal'     => '8.5',   // Sous-champ Code postal
	'pays'            => '8.6',   // Sous-champ Pays (sera rempli avec "France")
	
	'mentions_legales'=> '13',    // Champ Mentions légales (textarea)
	
	// ===================================================================
	// CHAMPS À LIRE (LECTURE POUR MENTIONS LÉGALES)
	// ===================================================================
	
	// Champ Name (composite - ID: 7)
	'prenom'          => '7.3',   // Sous-champ Prénom
	'nom'             => '7.6',   // Sous-champ Nom
	
	// ===================================================================
	// CHAMPS OPTIONNELS (non utilisés actuellement)
	// ===================================================================
	
	'forme_juridique' => '',      // Non mappé
	'code_ape'        => '',      // Non mappé
	'libelle_ape'     => '',      // Non mappé
	'date_creation'   => '',      // Non mappé
	'statut_actif'    => '',      // Non mappé
	'type_entreprise' => '',      // Non mappé
);

/**
 * Configuration du bouton HTML (pour le champ ID: 11)
 */
$button_html_config = array(
	'field_id'        => 11,
	'button_text'     => '🔍 Vérifier le SIRET',
	'button_class'    => 'button gform_button gf-siren-verify-button',
	'show_loader'     => true,
	'loader_text'     => '⏳ Vérification en cours...',
	'container_class' => 'gf-siren-verify-container',
	'message_class'   => 'gf-siren-message',
);

/**
 * Configuration des messages
 */
$messages_config = array(
	'success'           => 'Entreprise trouvée : {denomination}',
	'error_invalid'     => 'Veuillez saisir un SIRET valide (14 chiffres).',
	'error_not_found'   => 'Aucune entreprise trouvée avec ce SIRET.',
	'error_api'         => 'Erreur lors de la vérification. Veuillez réessayer.',
	'error_timeout'     => 'La vérification a pris trop de temps. Veuillez réessayer.',
	'warning_inactive'  => '⚠️ Attention : Cette entreprise est inactive.',
	'warning_modified'  => 'Ce champ a été modifié manuellement.',
);

/**
 * Fonction pour installer la configuration
 */
function gf_siren_install_form_1_config() {
	global $form_mapping_config, $button_html_config, $messages_config;
	
	// Récupérer les settings actuels
	$settings = get_option( 'gf_siren_autocomplete_settings', array() );
	
	// Ajouter le mapping du formulaire
	if ( ! isset( $settings['form_mappings'] ) ) {
		$settings['form_mappings'] = array();
	}
	
	$settings['form_mappings'][1] = $form_mapping_config;
	
	// Ajouter la configuration du bouton
	if ( ! isset( $settings['button_configs'] ) ) {
		$settings['button_configs'] = array();
	}
	
	$settings['button_configs'][1] = $button_html_config;
	
	// Ajouter les messages personnalisés
	if ( ! isset( $settings['messages'] ) ) {
		$settings['messages'] = array();
	}
	
	$settings['messages'][1] = $messages_config;
	
	// Sauvegarder
	$result = update_option( 'gf_siren_autocomplete_settings', $settings );
	
	if ( $result ) {
		echo "✅ Configuration du formulaire ID: 1 installée avec succès !\n";
		echo "\n";
		echo "📋 Récapitulatif de la configuration :\n";
		echo "------------------------------------\n";
		echo "Form ID: {$form_mapping_config['form_id']}\n";
		echo "Nom: {$form_mapping_config['form_name']}\n";
		echo "\n";
		echo "Champs mappés :\n";
		echo "  - SIRET (saisie) : ID {$form_mapping_config['siret']}\n";
		echo "  - Nom entreprise : ID {$form_mapping_config['denomination']}\n";
		echo "  - Adresse rue : ID {$form_mapping_config['adresse']}\n";
		echo "  - Ville : ID {$form_mapping_config['ville']}\n";
		echo "  - Code postal : ID {$form_mapping_config['code_postal']}\n";
		echo "  - Pays : ID {$form_mapping_config['pays']}\n";
		echo "  - Mentions légales : ID {$form_mapping_config['mentions_legales']}\n";
		echo "  - Prénom représentant (lecture) : ID {$form_mapping_config['prenom']}\n";
		echo "  - Nom représentant (lecture) : ID {$form_mapping_config['nom']}\n";
		echo "\n";
		echo "Bouton HTML :\n";
		echo "  - Field ID: {$button_html_config['field_id']}\n";
		echo "  - Texte: {$button_html_config['button_text']}\n";
		
		return true;
	} else {
		echo "❌ Erreur lors de l'installation de la configuration.\n";
		return false;
	}
}

// Si exécuté depuis WP-CLI ou en tant que script admin
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::success( 'Installation de la configuration du formulaire ID: 1...' );
	gf_siren_install_form_1_config();
} elseif ( is_admin() && isset( $_GET['gf_siren_install_config'] ) && current_user_can( 'manage_options' ) ) {
	// Installation via l'admin WordPress
	check_admin_referer( 'gf_siren_install_config' );
	gf_siren_install_form_1_config();
	wp_redirect( admin_url( 'admin.php?page=gf-siren-settings&config_installed=1' ) );
	exit;
}

/**
 * Fonction pour afficher le contenu HTML du champ ID: 11
 * 
 * À copier-coller dans le champ HTML de Gravity Forms
 */
function gf_siren_get_button_html() {
	global $button_html_config;
	
	ob_start();
	?>
	<div class="<?php echo esc_attr( $button_html_config['container_class'] ); ?>" 
	     data-form-id="1" 
	     data-field-id="1">
		
		<!-- Bouton de vérification -->
		<button type="button" 
		        id="gf-verify-siret" 
		        class="<?php echo esc_attr( $button_html_config['button_class'] ); ?>"
		        data-form-id="1"
		        data-field-id="1"
		        data-nonce="<?php echo wp_create_nonce( 'gf_siren_nonce' ); ?>"
		        style="margin: 10px 0;">
			<?php echo esc_html( $button_html_config['button_text'] ); ?>
		</button>
		
		<!-- Loader (caché par défaut) -->
		<?php if ( $button_html_config['show_loader'] ) : ?>
		<div class="gf-siren-loader" style="display: none; margin: 10px 0;">
			<span class="spinner is-active" style="float: none; margin: 0 10px 0 0;"></span>
			<span><?php echo esc_html( $button_html_config['loader_text'] ); ?></span>
		</div>
		<?php endif; ?>
		
		<!-- Zone de message (succès/erreur) -->
		<div id="gf-siren-status" class="<?php echo esc_attr( $button_html_config['message_class'] ); ?>"></div>
	</div>
	
	<style>
		.gf-siren-verify-container {
			margin: 15px 0;
		}
		
		.gf-siren-loader {
			display: inline-flex;
			align-items: center;
			padding: 8px 12px;
			background-color: #f0f0f1;
			border-radius: 4px;
			font-size: 14px;
		}
		
		.gf-siren-message {
			margin: 10px 0;
		}
		
		.gf-siren-message-box {
			padding: 12px 15px;
			border-radius: 4px;
			border-left: 4px solid;
			font-size: 14px;
			line-height: 1.5;
		}
		
		.gf-siren-message-success {
			background-color: #d4edda;
			color: #155724;
			border-color: #28a745;
		}
		
		.gf-siren-message-error {
			background-color: #f8d7da;
			color: #721c24;
			border-color: #dc3545;
		}
		
		.gf-siren-message-warning {
			background-color: #fff3cd;
			color: #856404;
			border-color: #ffc107;
		}
		
		.gf-siren-message-box .icon {
			font-weight: bold;
			margin-right: 8px;
			font-size: 16px;
		}
		
		.gf-siren-edit-warning {
			display: block;
			margin-top: 5px;
			color: #856404;
			font-size: 13px;
			font-style: italic;
		}
		
		input.gf-siren-manually-edited {
			border-left: 3px solid #ffc107 !important;
		}
	</style>
	<?php
	return ob_get_clean();
}

// Pour obtenir le HTML du bouton
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command(
		'gf-siren get-button-html',
		function() {
			WP_CLI::line( gf_siren_get_button_html() );
		}
	);
}

