<?php
/**
 * Gestion du cache des résultats API
 *
 * @package GFSirenAutocomplete
 */

namespace GFSirenAutocomplete\Modules\Siren;

defined( 'ABSPATH' ) || exit;

use GFSirenAutocomplete\Core\Constants;

/**
 * Classe de gestion du cache WordPress (Transients)
 */
class SirenCache {

	/**
	 * Instance du logger
	 *
	 * @var object
	 */
	private $logger;

	/**
	 * Constructeur
	 *
	 * @param object $logger Instance du logger.
	 */
	public function __construct( $logger = null ) {
		$this->logger = $logger;
	}

	/**
	 * Génère la clé de cache pour un SIRET
	 *
	 * @param string $siret Le numéro SIRET.
	 * @return string La clé de cache.
	 */
	private function get_cache_key( $siret ) {
		return Constants::CACHE_PREFIX . $siret;
	}

	/**
	 * Récupère les données depuis le cache
	 *
	 * @param string $siret Le numéro SIRET.
	 * @return array|false Les données en cache ou false si non trouvées.
	 */
	public function get( $siret ) {
		$cache_key = $this->get_cache_key( $siret );
		$data      = get_transient( $cache_key );

		if ( false !== $data ) {
			$this->log( 'INFO', "Données récupérées depuis le cache pour le SIRET: {$siret}" );
			return $data;
		}

		$this->log( 'DEBUG', "Aucune donnée en cache pour le SIRET: {$siret}" );
		return false;
	}

	/**
	 * Stocke les données en cache
	 *
	 * @param string $siret Le numéro SIRET.
	 * @param array  $data Les données à mettre en cache.
	 * @param int    $duration Durée du cache en secondes (optionnel).
	 * @return bool True si succès, false sinon.
	 */
	public function set( $siret, $data, $duration = null ) {
		if ( ! is_array( $data ) ) {
			$this->log( 'WARNING', "Tentative de mise en cache de données invalides pour le SIRET: {$siret}" );
			return false;
		}

		$cache_key = $this->get_cache_key( $siret );
		$duration  = $duration ?? $this->get_cache_duration();

		$result = set_transient( $cache_key, $data, $duration );

		if ( $result ) {
			$this->log( 'INFO', "Données mises en cache pour le SIRET: {$siret} (durée: {$duration}s)" );
		} else {
			$this->log( 'ERROR', "Échec de la mise en cache pour le SIRET: {$siret}" );
		}

		return $result;
	}

	/**
	 * Supprime les données du cache pour un SIRET
	 *
	 * @param string $siret Le numéro SIRET.
	 * @return bool True si succès, false sinon.
	 */
	public function delete( $siret ) {
		$cache_key = $this->get_cache_key( $siret );
		$result    = delete_transient( $cache_key );

		if ( $result ) {
			$this->log( 'INFO', "Cache supprimé pour le SIRET: {$siret}" );
		}

		return $result;
	}

	/**
	 * Vide tout le cache Siren
	 *
	 * @return int Nombre d'entrées supprimées.
	 */
	public function flush_all() {
		global $wpdb;

		$this->log( 'INFO', 'Vidage de tout le cache Siren' );

		$prefix      = Constants::CACHE_PREFIX;
		$like_prefix = $wpdb->esc_like( '_transient_' . $prefix ) . '%';

		// Supprimer les transients.
		$deleted = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
				$like_prefix
			)
		);

		// Supprimer les timeout des transients.
		$like_timeout_prefix = $wpdb->esc_like( '_transient_timeout_' . $prefix ) . '%';

		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
				$like_timeout_prefix
			)
		);

		$this->log( 'INFO', "Cache vidé : {$deleted} entrée(s) supprimée(s)" );

		return (int) $deleted;
	}

	/**
	 * Récupère le nombre d'entrées en cache
	 *
	 * @return int Le nombre d'entrées.
	 */
	public function get_cache_count() {
		global $wpdb;

		$prefix      = Constants::CACHE_PREFIX;
		$like_prefix = $wpdb->esc_like( '_transient_' . $prefix ) . '%';

		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE %s",
				$like_prefix
			)
		);

		return (int) $count;
	}

	/**
	 * Récupère la durée de cache configurée
	 *
	 * @return int La durée en secondes.
	 */
	private function get_cache_duration() {
		$settings = get_option( Constants::SETTINGS_OPTION, array() );
		$duration = $settings['cache_duration'] ?? Constants::CACHE_DURATION;

		return absint( $duration );
	}

	/**
	 * Log un message
	 *
	 * @param string $level Niveau du log.
	 * @param string $message Message à logger.
	 */
	private function log( $level, $message ) {
		if ( $this->logger && method_exists( $this->logger, 'log' ) ) {
			$this->logger->log( $level, $message, array( 'source' => 'SirenCache' ) );
		}
	}
}

