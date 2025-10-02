<?php
/**
 * Gestion du stockage des données de tracking
 *
 * @package GFSirenAutocomplete
 */

namespace GFSirenAutocomplete\Modules\Tracking;

defined( 'ABSPATH' ) || exit;

use GFSirenAutocomplete\Core\Constants;
use GFSirenAutocomplete\Core\Logger;

/**
 * Classe de gestion du stockage des données de tracking
 */
class TrackingStorage {

	/**
	 * Nom de la table
	 *
	 * @var string
	 */
	private $table_name;

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
		global $wpdb;
		$this->table_name = $wpdb->prefix . Constants::TRACKING_TABLE;
		$this->logger     = $logger;
	}

	/**
	 * Récupère le nom de la table
	 *
	 * @return string
	 */
	public function get_table_name() {
		return $this->table_name;
	}

	/**
	 * Crée la table de tracking
	 *
	 * @return bool True si succès, false sinon.
	 */
	public function create_table() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			form_id int(11) NOT NULL,
			entry_id bigint(20) NULL,
			created_at datetime NOT NULL,
			user_ip varchar(45) NULL,
			user_agent text NULL,
			source_url text NULL,
			fields_data longtext NULL,
			siren_metadata longtext NULL,
			PRIMARY KEY (id),
			KEY form_id (form_id),
			KEY entry_id (entry_id),
			KEY created_at (created_at)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		// Vérifier la création.
		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$this->table_name}'" ) === $this->table_name ) {
			$this->logger->info( 'Table de tracking créée avec succès', array( 'table' => $this->table_name ) );
			return true;
		}

		$this->logger->error( 'Échec de la création de la table de tracking', array( 'error' => $wpdb->last_error ) );
		return false;
	}

	/**
	 * Insère une entrée de tracking
	 *
	 * @param array $data Données à insérer.
	 * @return int|false ID de l'entrée créée ou false en cas d'erreur.
	 */
	public function insert_entry( $data ) {
		global $wpdb;

		$result = $wpdb->insert(
			$this->table_name,
			array(
				'form_id'         => $data['form_id'] ?? 0,
				'entry_id'        => $data['entry_id'] ?? 0,
				'created_at'      => $data['created_at'] ?? current_time( 'mysql' ),
				'user_ip'         => $data['user_ip'] ?? '',
				'user_agent'      => $data['user_agent'] ?? '',
				'source_url'      => $data['source_url'] ?? '',
				'fields_data'     => $data['fields_data'] ?? '',
				'siren_metadata'  => $data['siren_metadata'] ?? '',
			),
			array( '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		if ( false === $result ) {
			$this->logger->error( 'Échec insertion tracking', array( 'error' => $wpdb->last_error ) );
			return false;
		}

		return (int) $wpdb->insert_id;
	}

	/**
	 * Compte le nombre d'entrées selon des filtres
	 *
	 * @param array $filters Filtres à appliquer.
	 * @return int Nombre d'entrées.
	 */
	public function count_entries( $filters = array() ) {
		global $wpdb;

		$where = $this->build_where_clause( $filters );
		$sql   = "SELECT COUNT(*) FROM {$this->table_name} {$where}";

		$count = $wpdb->get_var( $sql );

		if ( $wpdb->last_error ) {
			$this->logger->error( 'Erreur comptage tracking', array( 'error' => $wpdb->last_error ) );
			return 0;
		}

		return (int) $count;
	}

	/**
	 * Récupère les entrées avec pagination
	 *
	 * @param int   $page Numéro de page (commence à 1).
	 * @param int   $per_page Nombre d'entrées par page.
	 * @param array $filters Filtres à appliquer.
	 * @return array Liste des entrées.
	 */
	public function get_entries_paginated( $page, $per_page, $filters = array() ) {
		global $wpdb;

		$offset = ( $page - 1 ) * $per_page;
		$where  = $this->build_where_clause( $filters );

		$sql = $wpdb->prepare(
			"SELECT * FROM {$this->table_name} {$where} ORDER BY id DESC LIMIT %d OFFSET %d",
			$per_page,
			$offset
		);

		$results = $wpdb->get_results( $sql, ARRAY_A );

		if ( $wpdb->last_error ) {
			$this->logger->error( 'Erreur récupération tracking', array( 'error' => $wpdb->last_error ) );
			return array();
		}

		// Désérialiser les données JSON.
		foreach ( $results as &$row ) {
			if ( ! empty( $row['fields_data'] ) ) {
				$decoded = json_decode( $row['fields_data'], true );
				$row['fields'] = ( JSON_ERROR_NONE === json_last_error() ) ? $decoded : array();
			} else {
				$row['fields'] = array();
			}

			if ( ! empty( $row['siren_metadata'] ) ) {
				$decoded = json_decode( $row['siren_metadata'], true );
				$row['siren_meta'] = ( JSON_ERROR_NONE === json_last_error() ) ? $decoded : array();
			} else {
				$row['siren_meta'] = array();
			}
		}

		return $results;
	}

	/**
	 * Récupère toutes les entrées selon des filtres
	 *
	 * @param array $filters Filtres à appliquer.
	 * @return array Liste des entrées.
	 */
	public function get_all_entries( $filters = array() ) {
		global $wpdb;

		$where = $this->build_where_clause( $filters );
		$sql   = "SELECT * FROM {$this->table_name} {$where} ORDER BY id DESC";

		$results = $wpdb->get_results( $sql, ARRAY_A );

		if ( $wpdb->last_error ) {
			$this->logger->error( 'Erreur récupération totale tracking', array( 'error' => $wpdb->last_error ) );
			return array();
		}

		// Désérialiser les données JSON.
		foreach ( $results as &$row ) {
			if ( ! empty( $row['fields_data'] ) ) {
				$decoded = json_decode( $row['fields_data'], true );
				$row['fields'] = ( JSON_ERROR_NONE === json_last_error() ) ? $decoded : array();
			} else {
				$row['fields'] = array();
			}

			if ( ! empty( $row['siren_metadata'] ) ) {
				$decoded = json_decode( $row['siren_metadata'], true );
				$row['siren_meta'] = ( JSON_ERROR_NONE === json_last_error() ) ? $decoded : array();
			} else {
				$row['siren_meta'] = array();
			}
		}

		return $results;
	}

	/**
	 * Supprime une entrée
	 *
	 * @param int $id ID de l'entrée.
	 * @return int Nombre de lignes supprimées.
	 */
	public function delete_entry( $id ) {
		global $wpdb;

		$result = $wpdb->delete( $this->table_name, array( 'id' => $id ), array( '%d' ) );

		if ( false === $result ) {
			$this->logger->error( 'Échec suppression tracking', array( 'error' => $wpdb->last_error, 'id' => $id ) );
			return 0;
		}

		return $result;
	}

	/**
	 * Supprime plusieurs entrées
	 *
	 * @param array $ids Liste des IDs.
	 * @return int Nombre de lignes supprimées.
	 */
	public function bulk_delete( $ids ) {
		global $wpdb;

		if ( empty( $ids ) ) {
			return 0;
		}

		$ids_string = implode( ',', array_map( 'absint', $ids ) );
		$sql        = "DELETE FROM {$this->table_name} WHERE id IN ({$ids_string})";

		$result = $wpdb->query( $sql );

		if ( false === $result ) {
			$this->logger->error( 'Échec suppression multiple tracking', array( 'error' => $wpdb->last_error ) );
			return 0;
		}

		return $result;
	}

	/**
	 * Purge toutes les entrées
	 *
	 * @return int Nombre de lignes supprimées.
	 */
	public function purge_all() {
		global $wpdb;

		$result = $wpdb->query( "DELETE FROM {$this->table_name}" );

		if ( false === $result ) {
			$this->logger->error( 'Échec purge tracking', array( 'error' => $wpdb->last_error ) );
			return 0;
		}

		$this->logger->warning( 'Purge totale du tracking', array( 'rows' => $result ) );
		return $result;
	}

	/**
	 * Construit la clause WHERE SQL selon les filtres
	 *
	 * @param array $filters Filtres à appliquer.
	 * @return string Clause WHERE SQL.
	 */
	private function build_where_clause( $filters ) {
		global $wpdb;

		$conditions = array();

		if ( ! empty( $filters['form_id'] ) ) {
			$conditions[] = $wpdb->prepare( 'form_id = %d', $filters['form_id'] );
		}

		if ( ! empty( $filters['ip'] ) ) {
			$conditions[] = $wpdb->prepare( 'user_ip LIKE %s', '%' . $wpdb->esc_like( $filters['ip'] ) . '%' );
		}

		if ( ! empty( $filters['date_min'] ) ) {
			$conditions[] = $wpdb->prepare( 'DATE(created_at) >= %s', $filters['date_min'] );
		}

		if ( ! empty( $filters['date_max'] ) ) {
			$conditions[] = $wpdb->prepare( 'DATE(created_at) <= %s', $filters['date_max'] );
		}

		return empty( $conditions ) ? '' : 'WHERE ' . implode( ' AND ', $conditions );
	}
}

