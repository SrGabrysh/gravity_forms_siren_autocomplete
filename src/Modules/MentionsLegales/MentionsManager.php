<?php
/**
 * Orchestration du module Mentions Légales
 *
 * @package GFSirenAutocomplete
 */

namespace GFSirenAutocomplete\Modules\MentionsLegales;

defined( 'ABSPATH' ) || exit;

use GFSirenAutocomplete\Core\Constants;
use GFSirenAutocomplete\Core\Logger;
use GFSirenAutocomplete\Helpers\DataHelper;

/**
 * Classe d'orchestration des mentions légales
 */
class MentionsManager {

	/**
	 * Instance du formatter
	 *
	 * @var MentionsFormatter
	 */
	private $formatter;

	/**
	 * Instance du logger
	 *
	 * @var Logger
	 */
	private $logger;

	/**
	 * Constructeur
	 *
	 * @param Logger $logger Instance du logger.
	 */
	public function __construct( Logger $logger ) {
		$this->logger    = $logger;
		$this->formatter = new MentionsFormatter();
	}

	/**
	 * Génère les mentions légales à partir des données de l'entreprise
	 *
	 * @param array $company_data Données de l'entreprise.
	 * @param array $representant_data Données du représentant (prénom, nom).
	 * @return string Les mentions légales formatées.
	 */
	public function generate( $company_data, $representant_data = array() ) {
		if ( ! is_array( $company_data ) ) {
			$this->logger->error( 'Données d\'entreprise invalides pour la génération des mentions légales' );
			return '';
		}

		$type_entreprise = $company_data['type_entreprise'] ?? Constants::ENTREPRISE_TYPE_INCONNU;
		$this->logger->info( "Génération des mentions légales pour le type: {$type_entreprise}" );

		$mentions = '';

		switch ( $type_entreprise ) {
			case Constants::ENTREPRISE_TYPE_PERSONNE_MORALE:
				$mentions = $this->generate_for_personne_morale( $company_data, $representant_data );
				break;

			case Constants::ENTREPRISE_TYPE_ENTREPRENEUR_INDIVIDUEL:
				$mentions = $this->formatter->format_entrepreneur_individuel( $company_data );
				break;

			default:
				$this->logger->warning( "Type d'entreprise inconnu: {$type_entreprise}. Utilisation du format fallback." );
				$mentions = $this->formatter->format_fallback( $company_data );
				break;
		}

		// Appliquer un filtre pour permettre la personnalisation.
		$mentions = apply_filters( 'gf_siren_mentions_legales', $mentions, $company_data, $representant_data );

		$this->logger->info( 'Mentions légales générées avec succès' );

		return $mentions;
	}

	/**
	 * Génère les mentions pour une personne morale
	 *
	 * @param array $company_data Données de l'entreprise.
	 * @param array $representant_data Données du représentant.
	 * @return string Mentions légales.
	 */
	private function generate_for_personne_morale( $company_data, $representant_data ) {
		$unite_legale        = $company_data['unite_legale'] ?? array();
		$categorie_juridique = DataHelper::extract_field_value( $unite_legale, 'categorie_juridique', '' );
		$forme_juridique     = MentionsHelper::get_forme_juridique( $categorie_juridique );

		$this->logger->debug( "Forme juridique détectée: {$forme_juridique} (code: {$categorie_juridique})" );

		// Si c'est une société à capital (SARL, SAS, etc.).
		if ( MentionsHelper::is_societe_capital( $forme_juridique ) ) {
			return $this->formatter->format_societe_capital( $company_data, $forme_juridique, $representant_data );
		}

		// Sinon, format personne morale standard.
		return $this->formatter->format_personne_morale( $company_data );
	}

	/**
	 * Détermine le type d'entreprise (alias pour compatibilité)
	 *
	 * @param array $unite_legale Données de l'unité légale.
	 * @return string Type d'entreprise.
	 */
	public function determine_type( $unite_legale ) {
		if ( ! is_array( $unite_legale ) ) {
			return Constants::ENTREPRISE_TYPE_INCONNU;
		}

		$denomination = DataHelper::extract_field_value( $unite_legale, 'denomination', '' );

		if ( ! empty( $denomination ) ) {
			return Constants::ENTREPRISE_TYPE_PERSONNE_MORALE;
		}

		$nom    = DataHelper::extract_field_value( $unite_legale, 'nom', '' );
		$prenom = DataHelper::extract_field_value( $unite_legale, 'prenom_1', '' );

		if ( ! empty( $nom ) && ! empty( $prenom ) ) {
			return Constants::ENTREPRISE_TYPE_ENTREPRENEUR_INDIVIDUEL;
		}

		return Constants::ENTREPRISE_TYPE_INCONNU;
	}
}

