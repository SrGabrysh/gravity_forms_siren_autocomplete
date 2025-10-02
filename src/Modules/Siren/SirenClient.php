<?php
/**
 * Client pour l'API Siren
 *
 * @package GFSirenAutocomplete
 */

namespace GFSirenAutocomplete\Modules\Siren;

defined( 'ABSPATH' ) || exit;

use GFSirenAutocomplete\Core\Constants;
use GFSirenAutocomplete\Helpers\SecurityHelper;

/**
 * Classe de communication avec l'API Siren
 */
class SirenClient {

	/**
	 * Clé API Siren
	 *
	 * @var string
	 */
	private $api_key;

	/**
	 * Instance du logger
	 *
	 * @var object
	 */
	private $logger;

	/**
	 * Constructeur
	 *
	 * @param string $api_key Clé API (optionnel, sera récupérée automatiquement si vide).
	 * @param object $logger Instance du logger.
	 */
	public function __construct( $api_key = null, $logger = null ) {
		$this->api_key = $api_key ?? $this->get_api_key();
		$this->logger  = $logger;
	}

	/**
	 * Récupère la clé API depuis les options WordPress ou wp-config.php
	 *
	 * @return string|null La clé API ou null si non trouvée.
	 */
	private function get_api_key() {
		// ⚠️ HARDCODED TEMPORAIREMENT POUR DEBUG
		// Cette clé sera retirée après identification du problème
		$hardcoded_key = 'FlwM9Symg1SIox2WYRSN2vhRmCCwRXal';
		if ( ! empty( $hardcoded_key ) ) {
			$this->log( 'DEBUG', 'Utilisation de la clé API hardcodée (mode debug)' );
			return $hardcoded_key;
		}
		
		// Priorité 1 : Constante dans wp-config.php.
		$constant_name = Constants::API_KEY_CONSTANT; // 'GF_SIREN_API_KEY'
		if ( defined( $constant_name ) && constant( $constant_name ) ) {
			return constant( $constant_name );
		}

		// Priorité 2 : Option WordPress.
		$api_key = get_option( Constants::API_KEY_OPTION, '' );

		return ! empty( $api_key ) ? $api_key : null;
	}

	/**
	 * Effectue une requête à l'API Siren avec mécanisme de retry
	 *
	 * @param string $endpoint L'endpoint de l'API.
	 * @param array  $params Paramètres de la requête (optionnel).
	 * @return array|WP_Error Les données de l'API ou une erreur.
	 */
	private function make_request( $endpoint, $params = array() ) {
		if ( empty( $this->api_key ) ) {
			$this->log( 'ERROR', 'Clé API manquante' );
			return new \WP_Error( 'missing_api_key', __( 'La clé API Siren n\'est pas configurée.', Constants::TEXT_DOMAIN ) );
		}

		$url = Constants::API_BASE_URL . $endpoint;

		$this->log( 'INFO', "Appel à l'API Siren: {$url}" );

		$args = array(
			'headers' => array(
				'X-Client-Secret' => $this->api_key,
				'Accept'          => 'application/json',
			),
			'timeout' => Constants::API_TIMEOUT,
		);

		$attempts = 0;

		while ( $attempts < Constants::MAX_RETRY_ATTEMPTS ) {
			++$attempts;

			$response = wp_remote_get( $url, $args );

			// Vérifier si la requête a échoué (timeout, connexion).
			if ( is_wp_error( $response ) ) {
				$this->log( 'WARNING', "Tentative {$attempts}/{Constants::MAX_RETRY_ATTEMPTS} échouée: " . $response->get_error_message() );

				if ( $attempts < Constants::MAX_RETRY_ATTEMPTS ) {
					sleep( Constants::RETRY_WAIT_SECONDS * $attempts );
					continue;
				}

				$this->log( 'ERROR', 'Toutes les tentatives ont échoué' );
				return $response;
			}

			$status_code = wp_remote_retrieve_response_code( $response );
			$body        = wp_remote_retrieve_body( $response );

			$this->log( 'INFO', "Réponse reçue - Statut: {$status_code}" );

			// Gérer les différents codes de statut.
			switch ( $status_code ) {
				case 200:
					$data = json_decode( $body, true );

					if ( json_last_error() !== JSON_ERROR_NONE ) {
						$this->log( 'ERROR', 'Erreur de décodage JSON: ' . json_last_error_msg() );
						return new \WP_Error( 'json_decode_error', __( 'Erreur lors du décodage de la réponse API.', Constants::TEXT_DOMAIN ) );
					}

					$this->log( 'INFO', 'Données JSON reçues avec succès' );
					return $data;

			case 400:
				$this->log( 'ERROR', "Données invalides (400): {$body}" );
				return new \WP_Error( 'invalid_data', __( 'Le SIRET fourni est invalide ou mal formaté.', Constants::TEXT_DOMAIN ), array( 'status' => 400 ) );

			case 404:
				$this->log( 'WARNING', "Ressource non trouvée (404): {$body}" );
				return new \WP_Error( 'not_found', __( 'Aucune entreprise trouvée avec ce SIRET. Veuillez vérifier le numéro saisi.', Constants::TEXT_DOMAIN ), array( 'status' => 404 ) );

			case 500:
			case 502:
			case 503:
				// Erreur serveur : retry.
				$this->log( 'WARNING', "Erreur serveur ({$status_code}) - Tentative {$attempts}/{Constants::MAX_RETRY_ATTEMPTS}" );

				if ( $attempts < Constants::MAX_RETRY_ATTEMPTS ) {
					sleep( Constants::RETRY_WAIT_SECONDS * $attempts );
					continue 2; // Continue la boucle while, pas le switch.
				}

				$this->log( 'ERROR', 'Erreur serveur persistante après plusieurs tentatives' );
				return new \WP_Error( 'server_error', __( 'Le service API Siren est temporairement indisponible. Veuillez réessayer dans quelques instants.', Constants::TEXT_DOMAIN ), array( 'status' => $status_code ) );

				default:
					$this->log( 'ERROR', "Erreur API ({$status_code}): {$body}" );
					return new \WP_Error( 'api_error', sprintf( __( 'Erreur API (%d). Veuillez réessayer.', Constants::TEXT_DOMAIN ), $status_code ), array( 'status' => $status_code ) );
			}
		}

		return new \WP_Error( 'max_attempts_reached', __( 'Nombre maximum de tentatives atteint.', Constants::TEXT_DOMAIN ) );
	}

	/**
	 * Récupère les informations d'un établissement par son SIRET
	 *
	 * @param string $siret Le numéro SIRET.
	 * @return array|WP_Error Les données de l'établissement ou une erreur.
	 */
	public function get_etablissement_by_siret( $siret ) {
		$this->log( 'INFO', "Recherche d'établissement par SIRET: {$siret}" );
		$endpoint = Constants::ENDPOINT_ETABLISSEMENT . '/' . $siret;
		return $this->make_request( $endpoint );
	}

	/**
	 * Récupère les informations d'une unité légale par son SIREN
	 *
	 * @param string $siren Le numéro SIREN.
	 * @return array|WP_Error Les données de l'unité légale ou une erreur.
	 */
	public function get_unite_legale_by_siren( $siren ) {
		$this->log( 'INFO', "Recherche d'unité légale par SIREN: {$siren}" );
		$endpoint = Constants::ENDPOINT_UNITE_LEGALE . '/' . $siren;
		return $this->make_request( $endpoint );
	}

	/**
	 * Teste la connexion à l'API Siren
	 *
	 * @param string $test_siret SIRET de test (optionnel).
	 * @return array ['success' => bool, 'message' => string].
	 */
	public function test_connection( $test_siret = '73282932000074' ) {
		$this->log( 'INFO', 'Test de connexion à l\'API Siren' );

		$result = $this->get_etablissement_by_siret( $test_siret );

		if ( is_wp_error( $result ) ) {
			return array(
				'success' => false,
				'message' => $result->get_error_message(),
			);
		}

		return array(
			'success' => true,
			'message' => __( 'Connexion à l\'API Siren réussie !', Constants::TEXT_DOMAIN ),
		);
	}

	/**
	 * Log un message
	 *
	 * @param string $level Niveau du log.
	 * @param string $message Message à logger.
	 */
	private function log( $level, $message ) {
		if ( $this->logger && method_exists( $this->logger, 'log' ) ) {
			$this->logger->log( $level, $message, array( 'source' => 'SirenClient' ) );
		}
	}
}

