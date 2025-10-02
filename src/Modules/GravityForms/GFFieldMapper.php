<?php
/**
 * Mapping des champs Gravity Forms
 *
 * @package GFSirenAutocomplete
 */

namespace GFSirenAutocomplete\Modules\GravityForms;

defined( 'ABSPATH' ) || exit;

use GFSirenAutocomplete\Core\Constants;
use GFSirenAutocomplete\Helpers\DataHelper;
use GFSirenAutocomplete\Modules\MentionsLegales\MentionsHelper;

/**
 * Classe de mapping des champs entre l'API et Gravity Forms
 */
class GFFieldMapper {

	/**
	 * Récupère le mapping configuré pour un formulaire
	 *
	 * @param int $form_id ID du formulaire.
	 * @return array|false Le mapping ou false si non trouvé.
	 */
	public function get_field_mapping( $form_id ) {
		$settings = get_option( Constants::SETTINGS_OPTION, array() );
		$mappings = $settings['form_mappings'] ?? array();

		return $mappings[ $form_id ] ?? false;
	}

	/**
	 * Enregistre le mapping pour un formulaire
	 *
	 * @param int   $form_id ID du formulaire.
	 * @param array $mapping Le mapping des champs.
	 * @return bool True si succès, false sinon.
	 */
	public function save_field_mapping( $form_id, $mapping ) {
		$settings = get_option( Constants::SETTINGS_OPTION, array() );

		if ( ! isset( $settings['form_mappings'] ) ) {
			$settings['form_mappings'] = array();
		}

		$settings['form_mappings'][ $form_id ] = $mapping;

		return update_option( Constants::SETTINGS_OPTION, $settings );
	}

	/**
	 * Transforme les données de l'API en données de formulaire selon le mapping
	 *
	 * @param array $company_data Données de l'entreprise depuis l'API.
	 * @param array $mapping Mapping des champs.
	 * @param string $mentions_legales Mentions légales générées.
	 * @return array Données mappées pour le formulaire.
	 */
	public function map_data_to_fields( $company_data, $mapping, $mentions_legales = '' ) {
		$unite_legale        = $company_data['unite_legale'] ?? array();
		$etablissement_siege = $company_data['etablissement_siege'] ?? array();

		$mapped_data = array();

		// SIRET.
		if ( ! empty( $mapping['siret'] ) ) {
			$mapped_data[ $mapping['siret'] ] = $company_data['siret'] ?? '';
		}

		// Dénomination.
		if ( ! empty( $mapping['denomination'] ) ) {
			$mapped_data[ $mapping['denomination'] ] = $company_data['denomination'] ?? '';
		}

		// Adresse (sans CP/Ville).
		if ( ! empty( $mapping['adresse'] ) ) {
			$mapped_data[ $mapping['adresse'] ] = MentionsHelper::get_adresse_sans_cp_ville( $etablissement_siege );
		}

		// Code postal.
		if ( ! empty( $mapping['code_postal'] ) ) {
			$mapped_data[ $mapping['code_postal'] ] = MentionsHelper::get_code_postal( $etablissement_siege );
		}

		// Ville.
		if ( ! empty( $mapping['ville'] ) ) {
			$mapped_data[ $mapping['ville'] ] = MentionsHelper::get_ville( $etablissement_siege );
		}

		// Forme juridique.
		if ( ! empty( $mapping['forme_juridique'] ) ) {
			$categorie = DataHelper::extract_field_value( $unite_legale, 'categorie_juridique', '' );
			$mapped_data[ $mapping['forme_juridique'] ] = MentionsHelper::get_forme_juridique( $categorie );
		}

		// Code APE.
		if ( ! empty( $mapping['code_ape'] ) ) {
			$mapped_data[ $mapping['code_ape'] ] = DataHelper::extract_field_value( $unite_legale, 'activite_principale', '' );
		}

		// Libellé APE.
		if ( ! empty( $mapping['libelle_ape'] ) ) {
			$mapped_data[ $mapping['libelle_ape'] ] = DataHelper::extract_field_value( $unite_legale, 'nomenclature_activite_principale', '' );
		}

		// Date de création.
		if ( ! empty( $mapping['date_creation'] ) ) {
			$date_creation = DataHelper::extract_field_value( $unite_legale, 'date_creation', '' );
			$mapped_data[ $mapping['date_creation'] ] = DataHelper::format_date( $date_creation );
		}

		// Statut actif/inactif.
		if ( ! empty( $mapping['statut_actif'] ) ) {
			$est_actif = $company_data['est_actif'] ?? true;
			$mapped_data[ $mapping['statut_actif'] ] = $est_actif ? __( 'Actif', Constants::TEXT_DOMAIN ) : __( 'Inactif', Constants::TEXT_DOMAIN );
		}

		// Type d'entreprise.
		if ( ! empty( $mapping['type_entreprise'] ) ) {
			$type = $company_data['type_entreprise'] ?? '';
			$mapped_data[ $mapping['type_entreprise'] ] = $this->get_type_entreprise_label( $type );
		}

		// Mentions légales.
		if ( ! empty( $mapping['mentions_legales'] ) && ! empty( $mentions_legales ) ) {
			$mapped_data[ $mapping['mentions_legales'] ] = $mentions_legales;
		}

		return $mapped_data;
	}

	/**
	 * Récupère les données du représentant légal depuis le formulaire
	 *
	 * @param int   $form_id ID du formulaire.
	 * @param array $entry Données de l'entrée (soumission).
	 * @return array ['prenom' => string, 'nom' => string].
	 */
	public function get_representant_data( $form_id, $entry ) {
		$mapping = $this->get_field_mapping( $form_id );

		if ( false === $mapping ) {
			return array(
				'prenom' => '',
				'nom'    => '',
			);
		}

		$prenom = '';
		$nom    = '';

		if ( ! empty( $mapping['prenom'] ) && isset( $entry[ $mapping['prenom'] ] ) ) {
			$prenom = sanitize_text_field( $entry[ $mapping['prenom'] ] );
		}

		if ( ! empty( $mapping['nom'] ) && isset( $entry[ $mapping['nom'] ] ) ) {
			$nom = sanitize_text_field( $entry[ $mapping['nom'] ] );
		}

		return array(
			'prenom' => $prenom,
			'nom'    => $nom,
		);
	}

	/**
	 * Convertit un type d'entreprise en libellé lisible
	 *
	 * @param string $type Type d'entreprise.
	 * @return string Libellé.
	 */
	private function get_type_entreprise_label( $type ) {
		$labels = array(
			Constants::ENTREPRISE_TYPE_PERSONNE_MORALE           => __( 'Personne morale', Constants::TEXT_DOMAIN ),
			Constants::ENTREPRISE_TYPE_ENTREPRENEUR_INDIVIDUEL   => __( 'Entrepreneur individuel', Constants::TEXT_DOMAIN ),
			Constants::ENTREPRISE_TYPE_INCONNU                   => __( 'Inconnu', Constants::TEXT_DOMAIN ),
		);

		return $labels[ $type ] ?? $type;
	}

	/**
	 * Vérifie si un formulaire a un mapping configuré
	 *
	 * @param int $form_id ID du formulaire.
	 * @return bool True si mappé, false sinon.
	 */
	public function form_has_mapping( $form_id ) {
		$mapping = $this->get_field_mapping( $form_id );
		return false !== $mapping && ! empty( $mapping['siret'] );
	}

	/**
	 * Récupère l'ID du champ SIRET pour un formulaire
	 *
	 * @param int $form_id ID du formulaire.
	 * @return string|false L'ID du champ SIRET ou false.
	 */
	public function get_siret_field_id( $form_id ) {
		$mapping = $this->get_field_mapping( $form_id );

		if ( false === $mapping ) {
			return false;
		}

		return $mapping['siret'] ?? false;
	}
}

