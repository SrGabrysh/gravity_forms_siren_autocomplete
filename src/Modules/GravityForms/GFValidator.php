<?php
/**
 * Validation côté serveur des formulaires
 *
 * @package GFSirenAutocomplete
 */

namespace GFSirenAutocomplete\Modules\GravityForms;

defined( 'ABSPATH' ) || exit;

use GFSirenAutocomplete\Core\Constants;
use GFSirenAutocomplete\Core\Logger;

/**
 * Classe de validation côté serveur
 */
class GFValidator {

	/**
	 * Instance du field mapper
	 *
	 * @var GFFieldMapper
	 */
	private $field_mapper;

	/**
	 * Instance du logger
	 *
	 * @var Logger
	 */
	private $logger;

	/**
	 * Constructeur
	 *
	 * @param GFFieldMapper $field_mapper Instance du mapper.
	 * @param Logger        $logger Instance du logger.
	 */
	public function __construct( GFFieldMapper $field_mapper, Logger $logger ) {
		$this->field_mapper = $field_mapper;
		$this->logger       = $logger;
	}

	/**
	 * Initialise les hooks de validation
	 */
	public function init_hooks() {
		add_filter( 'gform_validation', array( $this, 'validate_submission' ) );
	}

	/**
	 * Valide la soumission du formulaire
	 *
	 * @param array $validation_result Résultat de validation Gravity Forms.
	 * @return array Résultat de validation modifié.
	 */
	public function validate_submission( $validation_result ) {
		$form = $validation_result['form'];
		$form_id = $form['id'];

		// Vérifier si le formulaire a un mapping.
		if ( ! $this->field_mapper->form_has_mapping( $form_id ) ) {
			return $validation_result;
		}

		// Récupérer le champ SIRET.
		$siret_field_id = $this->field_mapper->get_siret_field_id( $form_id );

		if ( false === $siret_field_id ) {
			return $validation_result;
		}

		// Récupérer la valeur du champ SIRET depuis $_POST.
		$siret_input_name = 'input_' . str_replace( '.', '_', $siret_field_id );
		$siret_value      = rgpost( $siret_input_name );

		// Vérifier si le SIRET est rempli.
		if ( empty( $siret_value ) ) {
			return $validation_result;
		}

		// Vérifier si le SIRET a été vérifié (flag stocké en session ou dans le formulaire).
		$verified = $this->check_siret_verified( $form_id, $siret_value );

		if ( ! $verified ) {
			$this->logger->warning(
				'Tentative de soumission sans vérification SIRET',
				array(
					'form_id' => $form_id,
					'siret'   => $siret_value,
				)
			);

			// Marquer le formulaire comme invalide.
			$validation_result['is_valid'] = false;

			// Trouver le champ SIRET et ajouter un message d'erreur.
			foreach ( $form['fields'] as &$field ) {
				if ( (string) $field->id === (string) $siret_field_id ) {
					$field->failed_validation  = true;
					$field->validation_message = __( 'Veuillez vérifier le SIRET en cliquant sur le bouton "Vérifier le SIRET".', Constants::TEXT_DOMAIN );
					break;
				}
			}

			$validation_result['form'] = $form;
		}

		return $validation_result;
	}

	/**
	 * Vérifie si le SIRET a été vérifié via le bouton
	 *
	 * @param int    $form_id ID du formulaire.
	 * @param string $siret Valeur du SIRET.
	 * @return bool True si vérifié, false sinon.
	 */
	private function check_siret_verified( $form_id, $siret ) {
		// Vérifier un flag caché dans le formulaire (ajouté par JavaScript).
		$verified_flag = rgpost( 'gf_siren_verified_' . $form_id );

		if ( '1' === $verified_flag ) {
			// Vérifier que le SIRET vérifié correspond bien à celui soumis.
			$verified_siret = rgpost( 'gf_siren_verified_siret_' . $form_id );

			if ( $verified_siret === $siret ) {
				return true;
			}
		}

		return false;
	}
}

