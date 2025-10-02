<?php
/**
 * Système de logs centralisé
 *
 * @package GFSirenAutocomplete
 */

namespace GFSirenAutocomplete\Core;

defined( 'ABSPATH' ) || exit;

use GFSirenAutocomplete\Helpers\DataHelper;

/**
 * Classe de gestion des logs
 */
class Logger {

	/**
	 * Instance unique (Singleton)
	 *
	 * @var Logger|null
	 */
	private static $instance = null;

	/**
	 * Nom de la table des logs
	 *
	 * @var string
	 */
	private $table_name;

	/**
	 * Constructeur privé (Singleton)
	 */
	private function __construct() {
		global $wpdb;
		$this->table_name = $wpdb->prefix . Constants::LOGS_TABLE;
	}

	/**
	 * Récupère l'instance unique
	 *
	 * @return Logger
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Log un message
	 *
	 * @param string $level Niveau du log (INFO, WARNING, ERROR, DEBUG).
	 * @param string $message Message à logger.
	 * @param array  $context Contexte additionnel (optionnel).
	 * @return bool True si succès, false sinon.
	 */
	public function log( $level, $message, $context = array() ) {
		global $wpdb;

		// Valider le niveau.
		$valid_levels = array(
			Constants::LOG_LEVEL_DEBUG,
			Constants::LOG_LEVEL_INFO,
			Constants::LOG_LEVEL_WARNING,
			Constants::LOG_LEVEL_ERROR,
		);

		if ( ! in_array( $level, $valid_levels, true ) ) {
			$level = Constants::LOG_LEVEL_INFO;
		}

		// Préparer les données.
		$data = array(
			'date'       => current_time( 'mysql' ),
			'level'      => $level,
			'message'    => sanitize_text_field( $message ),
			'context'    => DataHelper::to_json( $context ),
			'user_id'    => get_current_user_id(),
			'ip_address' => $this->get_client_ip(),
		);

		// Insérer dans la base de données.
		$result = $wpdb->insert(
			$this->table_name,
			$data,
			array( '%s', '%s', '%s', '%s', '%d', '%s' )
		);

		// Rotation des logs si nécessaire.
		if ( $result ) {
			$this->rotate_logs();
		}

		return (bool) $result;
	}

	/**
	 * Log de niveau INFO
	 *
	 * @param string $message Message à logger.
	 * @param array  $context Contexte additionnel.
	 * @return bool
	 */
	public function info( $message, $context = array() ) {
		return $this->log( Constants::LOG_LEVEL_INFO, $message, $context );
	}

	/**
	 * Log de niveau WARNING
	 *
	 * @param string $message Message à logger.
	 * @param array  $context Contexte additionnel.
	 * @return bool
	 */
	public function warning( $message, $context = array() ) {
		return $this->log( Constants::LOG_LEVEL_WARNING, $message, $context );
	}

	/**
	 * Log de niveau ERROR
	 *
	 * @param string $message Message à logger.
	 * @param array  $context Contexte additionnel.
	 * @return bool
	 */
	public function error( $message, $context = array() ) {
		return $this->log( Constants::LOG_LEVEL_ERROR, $message, $context );
	}

	/**
	 * Log de niveau DEBUG
	 *
	 * @param string $message Message à logger.
	 * @param array  $context Contexte additionnel.
	 * @return bool
	 */
	public function debug( $message, $context = array() ) {
		// Ne logger en DEBUG que si WP_DEBUG est actif.
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return false;
		}

		return $this->log( Constants::LOG_LEVEL_DEBUG, $message, $context );
	}

	/**
	 * Récupère les logs
	 *
	 * @param array $args Arguments de filtrage (level, limit, offset, date_from, date_to).
	 * @return array Liste des logs.
	 */
	public function get_logs( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'level'     => '',
			'limit'     => 20,
			'offset'    => 0,
			'date_from' => '',
			'date_to'   => '',
			'order'     => 'DESC',
		);

		$args = wp_parse_args( $args, $defaults );

		$where = array( '1=1' );
		$params = array();

		// Filtre par niveau.
		if ( ! empty( $args['level'] ) ) {
			$where[] = 'level = %s';
			$params[] = $args['level'];
		}

		// Filtre par date de début.
		if ( ! empty( $args['date_from'] ) ) {
			$where[] = 'date >= %s';
			$params[] = $args['date_from'];
		}

		// Filtre par date de fin.
		if ( ! empty( $args['date_to'] ) ) {
			$where[] = 'date <= %s';
			$params[] = $args['date_to'];
		}

		$where_clause = implode( ' AND ', $where );
		$order = 'DESC' === strtoupper( $args['order'] ) ? 'DESC' : 'ASC';
		$limit = absint( $args['limit'] );
		$offset = absint( $args['offset'] );

		$query = "SELECT * FROM {$this->table_name} WHERE {$where_clause} ORDER BY date {$order} LIMIT {$limit} OFFSET {$offset}";

		if ( ! empty( $params ) ) {
			$query = $wpdb->prepare( $query, $params );
		}

		$logs = $wpdb->get_results( $query, ARRAY_A );

		return $logs ?? array();
	}

	/**
	 * Compte le nombre total de logs
	 *
	 * @param array $args Arguments de filtrage (level, date_from, date_to).
	 * @return int Nombre de logs.
	 */
	public function count_logs( $args = array() ) {
		global $wpdb;

		$where = array( '1=1' );
		$params = array();

		if ( ! empty( $args['level'] ) ) {
			$where[] = 'level = %s';
			$params[] = $args['level'];
		}

		if ( ! empty( $args['date_from'] ) ) {
			$where[] = 'date >= %s';
			$params[] = $args['date_from'];
		}

		if ( ! empty( $args['date_to'] ) ) {
			$where[] = 'date <= %s';
			$params[] = $args['date_to'];
		}

		$where_clause = implode( ' AND ', $where );
		$query = "SELECT COUNT(*) FROM {$this->table_name} WHERE {$where_clause}";

		if ( ! empty( $params ) ) {
			$query = $wpdb->prepare( $query, $params );
		}

		return (int) $wpdb->get_var( $query );
	}

	/**
	 * Supprime les logs anciens (rotation)
	 */
	private function rotate_logs() {
		global $wpdb;

		// Supprimer les logs de plus de X jours.
		$retention_days = Constants::LOGS_RETENTION_DAYS;
		$date_limit = date( 'Y-m-d H:i:s', strtotime( "-{$retention_days} days" ) );

		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$this->table_name} WHERE date < %s",
				$date_limit
			)
		);

		// Limiter le nombre total d'entrées.
		$max_entries = Constants::MAX_LOG_ENTRIES;
		$current_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table_name}" );

		if ( $current_count > $max_entries ) {
			$to_delete = $current_count - $max_entries;
			$wpdb->query( "DELETE FROM {$this->table_name} ORDER BY date ASC LIMIT {$to_delete}" );
		}
	}

	/**
	 * Supprime tous les logs
	 *
	 * @return bool True si succès, false sinon.
	 */
	public function clear_all_logs() {
		global $wpdb;

		$result = $wpdb->query( "TRUNCATE TABLE {$this->table_name}" );

		return (bool) $result;
	}

	/**
	 * Récupère l'adresse IP du client
	 *
	 * @return string L'adresse IP.
	 */
	private function get_client_ip() {
		$ip = '';

		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = $_SERVER['REMOTE_ADDR'];
		}

		return sanitize_text_field( $ip );
	}

	/**
	 * Crée la table des logs
	 */
	public function create_table() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			date datetime NOT NULL,
			level varchar(20) NOT NULL,
			message text NOT NULL,
			context longtext,
			user_id bigint(20),
			ip_address varchar(45),
			PRIMARY KEY (id),
			KEY level (level),
			KEY date (date)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}
}

