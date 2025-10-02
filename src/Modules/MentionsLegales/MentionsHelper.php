<?php
/**
 * Utilitaires pour le formatage des mentions légales
 *
 * @package GFSirenAutocomplete
 */

namespace GFSirenAutocomplete\Modules\MentionsLegales;

defined( 'ABSPATH' ) || exit;

use GFSirenAutocomplete\Helpers\DataHelper;

/**
 * Classe d'utilitaires pour les mentions légales
 */
class MentionsHelper {

	/**
	 * Récupère la forme juridique à partir du code de catégorie juridique
	 *
	 * @param string $code_categorie Le code de catégorie juridique.
	 * @return string La forme juridique.
	 */
	public static function get_forme_juridique( $code_categorie ) {
		$formes_juridiques = array(
			'5710' => 'SAS',
			'5720' => 'SASU',
			'5499' => 'SA',
			'5410' => 'SARL',
			'5422' => 'EURL',
			'5498' => 'SELARL',
			'5306' => 'SCI',
			'5385' => 'SNC',
			'5370' => 'SCOP',
			'1000' => 'Entrepreneur individuel',
		);

		return $formes_juridiques[ $code_categorie ] ?? 'Autre forme juridique';
	}

	/**
	 * Construit l'adresse complète d'un établissement
	 *
	 * @param array $etablissement Données de l'établissement.
	 * @return string L'adresse complète.
	 */
	public static function get_adresse_complete( $etablissement ) {
		$adresse_elements = array();

		// Complément d'adresse.
		$complement = DataHelper::extract_field_value( $etablissement, 'complement_adresse', '' );
		if ( ! empty( $complement ) ) {
			$adresse_elements[] = $complement;
		}

		// Numéro et voie.
		$numero_voie       = DataHelper::extract_field_value( $etablissement, 'numero_voie', '' );
		$indice_repetition = DataHelper::extract_field_value( $etablissement, 'indice_repetition', '' );
		$type_voie         = DataHelper::extract_field_value( $etablissement, 'type_voie', '' );
		$libelle_voie      = DataHelper::extract_field_value( $etablissement, 'libelle_voie', '' );

		$voie = implode( ' ', array_filter( array( $numero_voie, $indice_repetition, $type_voie, $libelle_voie ) ) );

		if ( ! empty( $voie ) ) {
			$adresse_elements[] = $voie;
		}

		// Code postal et commune.
		$code_postal      = DataHelper::extract_field_value( $etablissement, 'code_postal', '' );
		$libelle_commune  = DataHelper::extract_field_value( $etablissement, 'libelle_commune', '' );

		if ( ! empty( $code_postal ) || ! empty( $libelle_commune ) ) {
			$cp_commune = implode( ' ', array_filter( array( $code_postal, $libelle_commune ) ) );
			$adresse_elements[] = $cp_commune;
		}

		// Pays.
		$pays = DataHelper::extract_field_value( $etablissement, 'libelle_pays_etranger', '' );

		if ( empty( $pays ) ) {
			$adresse_elements[] = 'France';
		} else {
			$adresse_elements[] = $pays;
		}

		return implode( ', ', $adresse_elements );
	}

	/**
	 * Récupère l'adresse sans code postal ni ville
	 *
	 * @param array $etablissement Données de l'établissement.
	 * @return string L'adresse (rue).
	 */
	public static function get_adresse_sans_cp_ville( $etablissement ) {
		$adresse_elements = array();

		// Complément d'adresse.
		$complement = DataHelper::extract_field_value( $etablissement, 'complement_adresse', '' );
		if ( ! empty( $complement ) ) {
			$adresse_elements[] = $complement;
		}

		// Numéro et voie.
		$numero_voie       = DataHelper::extract_field_value( $etablissement, 'numero_voie', '' );
		$indice_repetition = DataHelper::extract_field_value( $etablissement, 'indice_repetition', '' );
		$type_voie         = DataHelper::extract_field_value( $etablissement, 'type_voie', '' );
		$libelle_voie      = DataHelper::extract_field_value( $etablissement, 'libelle_voie', '' );

		$voie = implode( ' ', array_filter( array( $numero_voie, $indice_repetition, $type_voie, $libelle_voie ) ) );

		if ( ! empty( $voie ) ) {
			$adresse_elements[] = $voie;
		}

		return implode( ', ', $adresse_elements );
	}

	/**
	 * Récupère le code postal
	 *
	 * @param array $etablissement Données de l'établissement.
	 * @return string Le code postal.
	 */
	public static function get_code_postal( $etablissement ) {
		return DataHelper::extract_field_value( $etablissement, 'code_postal', '' );
	}

	/**
	 * Récupère la ville
	 *
	 * @param array $etablissement Données de l'établissement.
	 * @return string La ville.
	 */
	public static function get_ville( $etablissement ) {
		return DataHelper::extract_field_value( $etablissement, 'libelle_commune', '' );
	}

	/**
	 * Récupère le titre du représentant selon la forme juridique
	 *
	 * @param string $forme_juridique La forme juridique.
	 * @return string Le titre (Gérant, Président, etc.).
	 */
	public static function get_titre_representant( $forme_juridique ) {
		$titres = array(
			'SARL'    => 'Gérant',
			'EURL'    => 'Gérant',
			'SELARL'  => 'Gérant',
			'SAS'     => 'Président',
			'SASU'    => 'Président',
			'SA'      => 'Directeur Général',
		);

		return $titres[ $forme_juridique ] ?? '{TITRE}';
	}

	/**
	 * Vérifie si une forme juridique est une société à capital
	 *
	 * @param string $forme_juridique La forme juridique.
	 * @return bool True si c'est une société à capital.
	 */
	public static function is_societe_capital( $forme_juridique ) {
		$societes_capital = array( 'SARL', 'EURL', 'SELARL', 'SAS', 'SASU', 'SA' );
		return in_array( $forme_juridique, $societes_capital, true );
	}

	/**
	 * Récupère l'enseigne d'un établissement
	 *
	 * @param array $etablissement Données de l'établissement.
	 * @return string L'enseigne.
	 */
	public static function get_enseigne( $etablissement ) {
		$enseigne = DataHelper::extract_field_value( $etablissement, 'enseigne_1', '' );

		if ( empty( $enseigne ) ) {
			$enseigne = DataHelper::extract_field_value( $etablissement, 'denomination_usuelle', '' );
		}

		return $enseigne;
	}
}

