<?php
/**
 * Injection du bouton "Vérifier" dans les formulaires
 *
 * @package GFSirenAutocomplete
 */

namespace GFSirenAutocomplete\Modules\GravityForms;

defined( 'ABSPATH' ) || exit;

use GFSirenAutocomplete\Core\Constants;
use GFSirenAutocomplete\Helpers\SecurityHelper;

/**
 * Classe d'injection du bouton de vérification SIRET
 */
class GFButtonInjector {

	/**
	 * Instance du field mapper
	 *
	 * @var GFFieldMapper
	 */
	private $field_mapper;

	/**
	 * Constructeur
	 *
	 * @param GFFieldMapper $field_mapper Instance du mapper.
	 */
	public function __construct( GFFieldMapper $field_mapper ) {
		$this->field_mapper = $field_mapper;
	}

	/**
	 * Initialise les hooks Gravity Forms
	 */
	public function init_hooks() {
		add_filter( 'gform_field_content', array( $this, 'inject_button' ), 10, 5 );
		add_action( 'gform_enqueue_scripts', array( $this, 'enqueue_assets' ), 10, 2 );
	}

	/**
	 * Injecte le bouton "Vérifier" après le champ SIRET
	 *
	 * @param string $field_content Contenu HTML du champ.
	 * @param object $field Objet champ Gravity Forms.
	 * @param mixed  $value Valeur du champ.
	 * @param int    $entry_id ID de l'entrée (0 si nouveau).
	 * @param int    $form_id ID du formulaire.
	 * @return string Contenu HTML modifié.
	 */
	public function inject_button( $field_content, $field, $value, $entry_id, $form_id ) {
		// Vérifier si le formulaire a un mapping.
		if ( ! $this->field_mapper->form_has_mapping( $form_id ) ) {
			return $field_content;
		}

		// Récupérer l'ID du champ SIRET mappé.
		$siret_field_id = $this->field_mapper->get_siret_field_id( $form_id );

		// Vérifier si c'est le champ SIRET.
		if ( false === $siret_field_id || (string) $field->id !== (string) $siret_field_id ) {
			return $field_content;
		}

		// Générer le bouton de vérification.
		$button_html = $this->get_button_html( $form_id, $field->id );

		// Injecter le bouton après le champ.
		$field_content .= $button_html;

		return $field_content;
	}

	/**
	 * Génère le HTML du bouton de vérification
	 *
	 * @param int    $form_id ID du formulaire.
	 * @param string $field_id ID du champ.
	 * @return string HTML du bouton.
	 */
	private function get_button_html( $form_id, $field_id ) {
		$nonce = SecurityHelper::create_nonce();

		$button_text = apply_filters( 'gf_siren_button_text', __( 'Vérifier le SIRET', Constants::TEXT_DOMAIN ), $form_id );

		$html = '<div class="gf-siren-verify-container">';
		$html .= sprintf(
			'<button type="button" class="gf-siren-verify-button" data-form-id="%d" data-field-id="%s" data-nonce="%s">%s</button>',
			esc_attr( $form_id ),
			esc_attr( $field_id ),
			esc_attr( $nonce ),
			esc_html( $button_text )
		);
		$html .= '<span class="gf-siren-loader" style="display:none;"></span>';
		$html .= '<div class="gf-siren-message"></div>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * Enqueue les assets CSS/JS pour le formulaire
	 *
	 * @param array $form Formulaire Gravity Forms.
	 * @param bool  $is_ajax Si le formulaire utilise AJAX.
	 */
	public function enqueue_assets( $form, $is_ajax ) {
		$form_id = $form['id'] ?? 0;

		// Vérifier si le formulaire a un mapping.
		if ( ! $this->field_mapper->form_has_mapping( $form_id ) ) {
			return;
		}

		// Enqueue CSS frontend.
		wp_enqueue_style(
			'gf-siren-frontend',
			plugin_dir_url( GRAVITY_FORMS_SIREN_AUTOCOMPLETE_PLUGIN_FILE ) . 'assets/css/frontend.css',
			array(),
			'1.0.0'
		);

		// Enqueue JS frontend.
		wp_enqueue_script(
			'gf-siren-frontend',
			plugin_dir_url( GRAVITY_FORMS_SIREN_AUTOCOMPLETE_PLUGIN_FILE ) . 'assets/js/frontend.js',
			array( 'jquery' ),
			'1.0.0',
			true
		);

		// Localiser le script avec les données nécessaires.
		wp_localize_script(
			'gf-siren-frontend',
			'gfSirenData',
			array(
				'ajax_url'   => admin_url( 'admin-ajax.php' ),
				'nonce'      => SecurityHelper::create_nonce(),
				'form_id'    => $form_id,
				'messages'   => $this->get_frontend_messages(),
			)
		);
	}

	/**
	 * Récupère les messages frontend traduisibles
	 *
	 * @return array Messages.
	 */
	private function get_frontend_messages() {
		return array(
			'verifying'                    => __( 'Vérification en cours...', Constants::TEXT_DOMAIN ),
			'success'                      => __( 'Entreprise trouvée : %s', Constants::TEXT_DOMAIN ),
			'error_invalid'                => __( 'Le SIRET fourni est invalide (format ou clé de vérification incorrecte).', Constants::TEXT_DOMAIN ),
			'error_not_found'              => __( 'Aucune entreprise trouvée avec ce SIRET.', Constants::TEXT_DOMAIN ),
			'error_api'                    => __( 'Erreur lors de la vérification. Veuillez réessayer.', Constants::TEXT_DOMAIN ),
			'error_timeout'                => __( 'La vérification a pris trop de temps. Veuillez réessayer.', Constants::TEXT_DOMAIN ),
			'error_representant_required'  => __( '⚠️ Veuillez renseigner le nom et le prénom du représentant avant de vérifier le SIRET.', Constants::TEXT_DOMAIN ),
			'error_representant_invalid'   => __( 'Les chiffres ne sont pas autorisés dans les noms et prénoms.', Constants::TEXT_DOMAIN ),
			'warning_inactive'             => __( 'Cette entreprise est inactive.', Constants::TEXT_DOMAIN ),
			'warning_modified'             => __( 'Vous avez modifié les données vérifiées.', Constants::TEXT_DOMAIN ),
			'warning_representant_modified' => __( 'Vous avez modifié les données du représentant.', Constants::TEXT_DOMAIN ),
		);
	}
}

