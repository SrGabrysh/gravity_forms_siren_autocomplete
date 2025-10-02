<?php
/**
 * Validation des données SIRET/SIREN
 *
 * @package GFSirenAutocomplete
 */

namespace GFSirenAutocomplete\Modules\Siren;

defined( 'ABSPATH' ) || exit;

use GFSirenAutocomplete\Core\Constants;
use GFSirenAutocomplete\Helpers\DataHelper;

/**
 * Classe de validation des SIRET/SIREN
 */
class SirenValidator {

	/**
	 * Nettoie un numéro SIRET (supprime les caractères non numériques)
	 *
	 * @param string $siret Le SIRET à nettoyer.
	 * @return string Le SIRET nettoyé.
	 */
	public function clean_siret( $siret ) {
		return DataHelper::sanitize_siret( $siret );
	}

	/**
	 * Valide le format d'un numéro SIRET (14 chiffres)
	 *
	 * @param string $siret Le SIRET à valider.
	 * @return bool True si valide, false sinon.
	 */
	public function validate_siret( $siret ) {
		if ( empty( $siret ) ) {
			return false;
		}

		// Vérifier que le SIRET contient exactement 14 chiffres.
		if ( ! preg_match( '/^\d{14}$/', $siret ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Extrait le numéro SIREN (9 premiers chiffres) d'un SIRET
	 *
	 * @param string $siret Le SIRET complet.
	 * @return string Le SIREN extrait.
	 */
	public function extract_siren( $siret ) {
		if ( empty( $siret ) || strlen( $siret ) < Constants::SIREN_LENGTH ) {
			return '';
		}

		return substr( $siret, 0, Constants::SIREN_LENGTH );
	}

	/**
	 * Vérifie si une entreprise est active
	 *
	 * @param array $unite_legale Les données de l'unité légale.
	 * @return bool True si active, false sinon.
	 */
	public function is_active( $unite_legale ) {
		if ( ! is_array( $unite_legale ) ) {
			return false;
		}

		$etat_administratif = DataHelper::extract_field_value( $unite_legale, 'etat_administratif', '' );

		// 'A' pour actif, 'C' pour cessé.
		return 'A' === $etat_administratif;
	}

	/**
	 * Détermine le type d'entreprise en fonction des données
	 *
	 * @param array $unite_legale Les données de l'unité légale.
	 * @return string Le type d'entreprise (constante).
	 */
	public function determine_entreprise_type( $unite_legale ) {
		if ( ! is_array( $unite_legale ) ) {
			return Constants::ENTREPRISE_TYPE_INCONNU;
		}

		// Vérifier s'il s'agit d'une personne morale (dénomination).
		$denomination = DataHelper::extract_field_value( $unite_legale, 'denomination', '' );

		if ( ! empty( $denomination ) ) {
			return Constants::ENTREPRISE_TYPE_PERSONNE_MORALE;
		}

		// Vérifier s'il s'agit d'un entrepreneur individuel (nom + prénom).
		$nom             = DataHelper::extract_field_value( $unite_legale, 'nom', '' );
		$prenom_1        = DataHelper::extract_field_value( $unite_legale, 'prenom_1', '' );
		$prenom_usuel    = DataHelper::extract_field_value( $unite_legale, 'prenom_usuel', '' );

		if ( ! empty( $nom ) && ( ! empty( $prenom_1 ) || ! empty( $prenom_usuel ) ) ) {
			return Constants::ENTREPRISE_TYPE_ENTREPRENEUR_INDIVIDUEL;
		}

		// Autres critères : catégorie juridique.
		$categorie_juridique = DataHelper::extract_field_value( $unite_legale, 'categorie_juridique', '' );

		if ( ! empty( $categorie_juridique ) ) {
			// Entrepreneur individuel (catégories 11xx, 12xx, 13xx, 14xx).
			if ( preg_match( '/^1[1-4]/', $categorie_juridique ) ) {
				return Constants::ENTREPRISE_TYPE_ENTREPRENEUR_INDIVIDUEL;
			}

			// Personne morale (catégories à partir de 20xx).
			if ( preg_match( '/^[2-9]/', $categorie_juridique ) ) {
				return Constants::ENTREPRISE_TYPE_PERSONNE_MORALE;
			}
		}

		return Constants::ENTREPRISE_TYPE_INCONNU;
	}

	/**
	 * Valide un numéro SIRET complet (nettoyage + validation)
	 *
	 * @param string $siret Le SIRET à valider.
	 * @return array ['valid' => bool, 'cleaned' => string, 'message' => string].
	 */
	public function validate_siret_complete( $siret ) {
		$cleaned = $this->clean_siret( $siret );

		if ( ! $this->validate_siret( $cleaned ) ) {
			return array(
				'valid'   => false,
				'cleaned' => $cleaned,
				'message' => __( 'Le SIRET doit contenir exactement 14 chiffres.', Constants::TEXT_DOMAIN ),
			);
		}

		return array(
			'valid'   => true,
			'cleaned' => $cleaned,
			'message' => '',
		);
	}
}

