<?php
/**
 * Formatage des mentions légales selon le type d'entreprise
 *
 * @package GFSirenAutocomplete
 */

namespace GFSirenAutocomplete\Modules\MentionsLegales;

defined( 'ABSPATH' ) || exit;

use GFSirenAutocomplete\Core\Constants;
use GFSirenAutocomplete\Helpers\DataHelper;

/**
 * Classe de formatage des mentions légales
 */
class MentionsFormatter {

	/**
	 * Formate les mentions légales pour une société à capital
	 *
	 * @param array  $company_data Données de l'entreprise.
	 * @param string $forme_juridique Forme juridique.
	 * @param array  $representant_data Données du représentant (prénom, nom, titre optionnel).
	 * @param bool   $include_titre Si true, inclut le titre du représentant (par défaut: false).
	 * @return string Mentions légales formatées.
	 */
	public function format_societe_capital( $company_data, $forme_juridique, $representant_data = array(), $include_titre = false ) {
		$unite_legale        = $company_data['unite_legale'] ?? array();
		$etablissement_siege = $company_data['etablissement_siege'] ?? array();

		// Informations de base.
		$denomination = DataHelper::extract_field_value( $unite_legale, 'denomination', '' );
		$enseigne     = MentionsHelper::get_enseigne( $etablissement_siege );
		$adresse      = MentionsHelper::get_adresse_complete( $etablissement_siege );
		$siren        = $company_data['siren'] ?? '';
		$siren_formate = DataHelper::format_siren( $siren );
		$ville_rcs    = MentionsHelper::get_ville( $etablissement_siege );

		// Représentant légal (format : Nom Prénom).
		$prenom_representant = $representant_data['prenom'] ?? '';
		$nom_representant    = $representant_data['nom'] ?? '';
		$representant        = ! empty( $prenom_representant ) && ! empty( $nom_representant )
			? "{$nom_representant} {$prenom_representant}"
			: '{REPRESENTANT}';

		// Construction des mentions.
		$mentions = "{$denomination}, ";

		$mentions .= 'dont le siège social est situé au ';

		if ( ! empty( $enseigne ) ) {
			$mentions .= "{$enseigne} ";
		}

		$mentions .= "{$adresse}, ";
		$mentions .= "immatriculée au Registre du Commerce et des Sociétés de {$ville_rcs} ";
		$mentions .= "sous le numéro {$siren_formate} ";
		
		// Formulation générique sans présumer du titre exact (plus prudent juridiquement)
		$mentions .= "représentée par {$representant} agissant et ayant les pouvoirs nécessaires";
		
		// Si un titre spécifique est fourni et que l'utilisateur souhaite l'inclure
		if ( $include_titre ) {
			$titre_representant = $representant_data['titre'] ?? MentionsHelper::get_titre_representant( $forme_juridique );
			$mentions .= " en tant que {$titre_representant}";
		}
		
		$mentions .= '.';

		return $mentions;
	}

	/**
	 * Formate les mentions légales pour une personne morale standard
	 *
	 * @param array $company_data Données de l'entreprise.
	 * @return string Mentions légales formatées.
	 */
	public function format_personne_morale( $company_data ) {
		$unite_legale        = $company_data['unite_legale'] ?? array();
		$etablissement_siege = $company_data['etablissement_siege'] ?? array();

		$denomination  = DataHelper::extract_field_value( $unite_legale, 'denomination', '' );
		$enseigne      = MentionsHelper::get_enseigne( $etablissement_siege );
		$adresse       = MentionsHelper::get_adresse_complete( $etablissement_siege );
		$siren         = $company_data['siren'] ?? '';
		$siren_formate = DataHelper::format_siren( $siren );
		$ville_rcs     = MentionsHelper::get_ville( $etablissement_siege );

		$mentions = "{$denomination}, ";
		$mentions .= 'dont le siège social est situé au ';

		if ( ! empty( $enseigne ) ) {
			$mentions .= "{$enseigne} ";
		}

		$mentions .= "{$adresse}, ";
		$mentions .= "immatriculée au Registre du Commerce et des Sociétés de {$ville_rcs} ";
		$mentions .= "sous le numéro {$siren_formate}.";

		return $mentions;
	}

	/**
	 * Formate les mentions légales pour un entrepreneur individuel
	 *
	 * @param array $company_data Données de l'entreprise.
	 * @return string Mentions légales formatées.
	 */
	public function format_entrepreneur_individuel( $company_data ) {
		$unite_legale  = $company_data['unite_legale'] ?? array();
		$etablissement = $company_data['etablissement'] ?? array();

		$nom           = DataHelper::extract_field_value( $unite_legale, 'nom', '' );
		$prenom_usuel  = DataHelper::extract_field_value( $unite_legale, 'prenom_usuel', '' );
		$prenom_1      = DataHelper::extract_field_value( $unite_legale, 'prenom_1', '' );
		$prenom        = ! empty( $prenom_usuel ) ? $prenom_usuel : $prenom_1;

		$enseigne      = MentionsHelper::get_enseigne( $etablissement );
		$adresse       = MentionsHelper::get_adresse_complete( $etablissement );
		$siret         = $company_data['siret'] ?? '';
		$siret_formate = DataHelper::format_siret( $siret );

		$mentions = strtoupper( $nom ) . " {$prenom}, ";

		if ( ! empty( $enseigne ) ) {
			$mentions .= "sous le nom commercial {$enseigne}, ";
		}

		$mentions .= "demeurant au {$adresse}, ";
		$mentions .= "immatriculé au répertoire des entreprises et établissements de l'INSEE ";
		$mentions .= "sous le numéro {$siret_formate}, agissant en sa qualité d'Entrepreneur individuel.";

		return $mentions;
	}

	/**
	 * Formate les mentions pour un type inconnu (fallback)
	 *
	 * @param array $company_data Données de l'entreprise.
	 * @return string Mentions légales formatées.
	 */
	public function format_fallback( $company_data ) {
		$unite_legale  = $company_data['unite_legale'] ?? array();
		$etablissement = $company_data['etablissement_siege'] ?? array();

		$denomination = DataHelper::extract_field_value( $unite_legale, 'denomination', '' );

		if ( empty( $denomination ) ) {
			$nom    = DataHelper::extract_field_value( $unite_legale, 'nom', '' );
			$prenom = DataHelper::extract_field_value( $unite_legale, 'prenom_1', '' );
			$denomination = trim( "{$prenom} {$nom}" );
		}

		$adresse       = MentionsHelper::get_adresse_complete( $etablissement );
		$siret         = $company_data['siret'] ?? '';
		$siret_formate = DataHelper::format_siret( $siret );

		$mentions = "{$denomination}, ";
		$mentions .= "situé au {$adresse}, ";
		$mentions .= "immatriculé sous le numéro SIRET {$siret_formate}.";

		return $mentions;
	}
}

