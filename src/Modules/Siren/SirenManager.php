<?php
/**
 * Orchestration du module Siren
 *
 * @package GFSirenAutocomplete
 */

namespace GFSirenAutocomplete\Modules\Siren;

defined( 'ABSPATH' ) || exit;

use GFSirenAutocomplete\Core\Logger;

/**
 * Classe d'orchestration du module Siren
 */
class SirenManager {

	/**
	 * Instance du validator
	 *
	 * @var SirenValidator
	 */
	private $validator;

	/**
	 * Instance du client API
	 *
	 * @var SirenClient
	 */
	private $client;

	/**
	 * Instance du cache
	 *
	 * @var SirenCache
	 */
	private $cache;

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
		$this->validator = new SirenValidator();
		$this->client    = new SirenClient( null, $logger );
		$this->cache     = new SirenCache( $logger );
	}

	/**
	 * Récupère les données d'une entreprise par son SIRET (point d'entrée principal)
	 *
	 * @param string $siret Le numéro SIRET.
	 * @return array|WP_Error Les données de l'entreprise ou une erreur.
	 */
	public function get_company_data( $siret ) {
		// Étape 1 : Nettoyage et validation du SIRET.
		$validation = $this->validator->validate_siret_complete( $siret );

		if ( ! $validation['valid'] ) {
			$this->logger->warning( 'SIRET invalide fourni', array( 'siret' => $siret ) );
			return new \WP_Error( 'invalid_siret', $validation['message'] );
		}

		$siret_cleaned = $validation['cleaned'];
		$this->logger->info( "Recherche de données pour le SIRET: {$siret_cleaned}" );

		// Étape 2 : Vérifier le cache.
		$cached_data = $this->cache->get( $siret_cleaned );

		if ( false !== $cached_data ) {
			$this->logger->info( "Données récupérées depuis le cache pour le SIRET: {$siret_cleaned}" );
			return $cached_data;
		}

		// Étape 3 : Extraire le SIREN.
		$siren = $this->validator->extract_siren( $siret_cleaned );

		if ( empty( $siren ) ) {
			$this->logger->error( 'Impossible d\'extraire le SIREN', array( 'siret' => $siret_cleaned ) );
			return new \WP_Error( 'invalid_siren', __( 'Impossible d\'extraire le SIREN du SIRET.', 'gravity-forms-siren-autocomplete' ) );
		}

		// Étape 4 : Appel à l'API pour l'établissement.
		$etablissement_data = $this->client->get_etablissement_by_siret( $siret_cleaned );

		if ( is_wp_error( $etablissement_data ) ) {
			$this->logger->error(
				'Erreur API lors de la récupération de l\'établissement',
				array(
					'siret' => $siret_cleaned,
					'error' => $etablissement_data->get_error_message(),
				)
			);
			return $etablissement_data;
		}

		// Étape 5 : Appel à l'API pour l'unité légale.
		$unite_legale_data = $this->client->get_unite_legale_by_siren( $siren );

		if ( is_wp_error( $unite_legale_data ) ) {
			$this->logger->error(
				'Erreur API lors de la récupération de l\'unité légale',
				array(
					'siren' => $siren,
					'error' => $unite_legale_data->get_error_message(),
				)
			);
			return $unite_legale_data;
		}

		// Étape 6 : Fusionner les données.
		$company_data = $this->merge_company_data( $etablissement_data, $unite_legale_data, $siret_cleaned, $siren );

		// Étape 7 : Mettre en cache.
		$this->cache->set( $siret_cleaned, $company_data );

		$this->logger->info( "Données récupérées avec succès pour le SIRET: {$siret_cleaned}" );

		return $company_data;
	}

	/**
	 * Fusionne les données de l'établissement et de l'unité légale
	 *
	 * @param array  $etablissement_data Données de l'établissement.
	 * @param array  $unite_legale_data Données de l'unité légale.
	 * @param string $siret SIRET nettoyé.
	 * @param string $siren SIREN extrait.
	 * @return array Données fusionnées.
	 */
	private function merge_company_data( $etablissement_data, $unite_legale_data, $siret, $siren ) {
		$etablissement = $etablissement_data['etablissement'] ?? array();
		$unite_legale  = $unite_legale_data['unite_legale'] ?? array();

		// Déterminer le type d'entreprise.
		$entreprise_type = $this->validator->determine_entreprise_type( $unite_legale );

		// Vérifier si l'entreprise est active.
		$est_actif = $this->validator->is_active( $unite_legale );

		if ( ! $est_actif ) {
			$this->logger->warning( "Entreprise inactive détectée pour le SIRET: {$siret}" );
		}

		// Construire la dénomination.
		$denomination = $unite_legale['denomination'] ?? '';

		if ( empty( $denomination ) ) {
			$nom     = $unite_legale['nom'] ?? '';
			$prenom  = $unite_legale['prenom_1'] ?? $unite_legale['prenom_usuel'] ?? '';
			$denomination = trim( "{$prenom} {$nom}" );
		}

		// Établissement de référence (siege).
		$etablissement_siege = $unite_legale['etablissement_siege'] ?? $etablissement;

		return array(
			'siret'              => $siret,
			'siren'              => $siren,
			'denomination'       => $denomination,
			'etablissement'      => $etablissement,
			'unite_legale'       => $unite_legale,
			'etablissement_siege' => $etablissement_siege,
			'type_entreprise'    => $entreprise_type,
			'est_actif'          => $est_actif,
			'api_timestamp'      => current_time( 'timestamp' ),
		);
	}

	/**
	 * Teste la connexion à l'API
	 *
	 * @param string $test_siret SIRET de test.
	 * @return array ['success' => bool, 'message' => string].
	 */
	public function test_api_connection( $test_siret = '73282932000074' ) {
		return $this->client->test_connection( $test_siret );
	}

	/**
	 * Vide le cache
	 *
	 * @return int Nombre d'entrées supprimées.
	 */
	public function clear_cache() {
		$count = $this->cache->flush_all();
		$this->logger->info( "Cache vidé : {$count} entrée(s) supprimée(s)" );
		return $count;
	}

	/**
	 * Récupère le nombre d'entrées en cache
	 *
	 * @return int
	 */
	public function get_cache_count() {
		return $this->cache->get_cache_count();
	}
}

