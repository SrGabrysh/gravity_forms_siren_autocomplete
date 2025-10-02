<?php
/**
 * Gestionnaire principal du module de tracking
 *
 * @package GFSirenAutocomplete
 */

namespace GFSirenAutocomplete\Modules\Tracking;

defined( 'ABSPATH' ) || exit;

use GFSirenAutocomplete\Core\Constants;
use GFSirenAutocomplete\Core\Logger;

/**
 * Classe de gestion du tracking des soumissions GF
 */
class TrackingManager {

	/**
	 * Instance du storage
	 *
	 * @var TrackingStorage
	 */
	private $storage;

	/**
	 * Instance du logger
	 *
	 * @var Logger
	 */
	private $logger;

	/**
	 * Constructeur
	 *
	 * @param TrackingStorage $storage Instance du storage.
	 * @param Logger          $logger Instance du logger.
	 */
	public function __construct( TrackingStorage $storage, Logger $logger ) {
		$this->storage = $storage;
		$this->logger  = $logger;
	}

	/**
	 * Initialise les hooks WordPress
	 */
	public function init_hooks() {
		// Hook pour capturer les soumissions de formulaires.
		add_action( 'gform_after_submission', array( $this, 'capture_submission' ), 10, 2 );
	}

	/**
	 * Capture une soumission de formulaire Gravity Forms
	 *
	 * @param array $entry Entrée GF.
	 * @param array $form Formulaire GF.
	 */
	public function capture_submission( $entry, $form ) {
		// Vérifier si le formulaire doit être tracké.
		if ( ! $this->should_track_form( $form['id'] ) ) {
			return;
		}

		$this->logger->info( 'Capture soumission pour tracking', array( 'form_id' => $form['id'], 'entry_id' => $entry['id'] ?? null ) );

		// Préparer les données à capturer.
		$data = array(
			'form_id'        => $form['id'],
			'entry_id'       => $entry['id'] ?? 0,
			'created_at'     => $entry['date_created'] ?? current_time( 'mysql' ),
			'user_ip'        => $this->anonymize_ip( $entry['ip'] ?? '' ),
			'user_agent'     => $entry['user_agent'] ?? '',
			'source_url'     => $entry['source_url'] ?? '',
			'fields_data'    => $this->serialize_form_fields( $form, $entry ),
			'siren_metadata' => $this->serialize_siren_metadata( $entry ),
		);

		// Insérer en base de données.
		$insert_id = $this->storage->insert_entry( $data );

		if ( $insert_id ) {
			$this->logger->info( 'Soumission trackée avec succès', array( 'tracking_id' => $insert_id, 'entry_id' => $entry['id'] ?? 0 ) );
		} else {
			$this->logger->error( 'Échec du tracking de la soumission', array( 'form_id' => $form['id'], 'entry_id' => $entry['id'] ?? 0 ) );
		}
	}

	/**
	 * Vérifie si un formulaire doit être tracké
	 *
	 * @param int $form_id ID du formulaire.
	 * @return bool True si doit être tracké, false sinon.
	 */
	private function should_track_form( $form_id ) {
		$settings = get_option( Constants::SETTINGS_OPTION, array() );
		$tracked_forms = $settings['tracked_forms'] ?? array();

		// Si aucun formulaire n'est configuré pour le tracking, ne rien tracker.
		if ( empty( $tracked_forms ) ) {
			return false;
		}

		return in_array( (int) $form_id, $tracked_forms, true );
	}

	/**
	 * Anonymise une adresse IP (RGPD)
	 *
	 * @param string $ip Adresse IP.
	 * @return string IP anonymisée.
	 */
	private function anonymize_ip( $ip ) {
		if ( empty( $ip ) ) {
			return '';
		}

		// Utiliser la fonction WordPress si disponible.
		if ( function_exists( 'wp_privacy_anonymize_ip' ) ) {
			return wp_privacy_anonymize_ip( $ip );
		}

		// Fallback manuel pour IPv4.
		if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
			$parts = explode( '.', $ip );
			$parts[3] = '0';
			return implode( '.', $parts );
		}

		// Fallback manuel pour IPv6.
		if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
			$parts = explode( ':', $ip );
			$parts = array_slice( $parts, 0, 4 );
			return implode( ':', $parts ) . '::';
		}

		return $ip;
	}

	/**
	 * Sérialise les champs du formulaire
	 *
	 * @param array $form Formulaire GF.
	 * @param array $entry Entrée GF.
	 * @return string JSON encodé des champs.
	 */
	private function serialize_form_fields( $form, $entry ) {
		$fields_data = array();

		if ( empty( $form['fields'] ) || ! is_array( $form['fields'] ) ) {
			return wp_json_encode( $fields_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
		}

		foreach ( $form['fields'] as $field ) {
			if ( ! isset( $field->id ) ) {
				continue;
			}

			$field_id    = (string) $field->id;
			$field_label = (string) ( $field->label ?? '' );
			$field_value = rgar( $entry, $field_id );

			// Convertir les objets/tableaux en JSON.
			if ( is_array( $field_value ) || is_object( $field_value ) ) {
				$field_value = wp_json_encode( $field_value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
			}

			$fields_data[] = array(
				'field_id'    => $field_id,
				'field_label' => $field_label,
				'value'       => (string) $field_value,
			);
		}

		return wp_json_encode( $fields_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
	}

	/**
	 * Sérialise les métadonnées spécifiques au plugin Siren
	 *
	 * @param array $entry Entrée GF.
	 * @return string JSON encodé des métadonnées.
	 */
	private function serialize_siren_metadata( $entry ) {
		$metadata = array();

		// Récupérer les métadonnées custom du plugin si elles existent.
		// Ces métadonnées sont ajoutées par le plugin lors de la vérification SIRET.
		
		// Timestamp de vérification SIRET.
		if ( ! empty( $entry['gf_siren_verified_at'] ) ) {
			$metadata['verified_at'] = $entry['gf_siren_verified_at'];
		}

		// Statut de l'API.
		if ( ! empty( $entry['gf_siren_api_status'] ) ) {
			$metadata['api_status'] = $entry['gf_siren_api_status'];
		}

		// Cache hit/miss.
		if ( ! empty( $entry['gf_siren_cache_hit'] ) ) {
			$metadata['cache_hit'] = (bool) $entry['gf_siren_cache_hit'];
		}

		// SIRET vérifié.
		if ( ! empty( $entry['gf_siren_siret_verified'] ) ) {
			$metadata['siret_verified'] = $entry['gf_siren_siret_verified'];
		}

		// Type d'entreprise.
		if ( ! empty( $entry['gf_siren_company_type'] ) ) {
			$metadata['company_type'] = $entry['gf_siren_company_type'];
		}

		return wp_json_encode( $metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
	}

	/**
	 * Récupère l'instance du storage
	 *
	 * @return TrackingStorage
	 */
	public function get_storage() {
		return $this->storage;
	}
}

