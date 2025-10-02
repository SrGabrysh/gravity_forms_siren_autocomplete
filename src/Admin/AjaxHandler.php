<?php
/**
 * Gestion des requêtes AJAX
 *
 * @package GFSirenAutocomplete
 */

namespace GFSirenAutocomplete\Admin;

defined( 'ABSPATH' ) || exit;

use GFSirenAutocomplete\Core\Constants;
use GFSirenAutocomplete\Core\Logger;
use GFSirenAutocomplete\Helpers\SecurityHelper;
use GFSirenAutocomplete\Helpers\NameFormatter;
use GFSirenAutocomplete\Modules\Siren\SirenManager;
use GFSirenAutocomplete\Modules\MentionsLegales\MentionsManager;
use GFSirenAutocomplete\Modules\GravityForms\GFManager;

/**
 * Classe de gestion des requêtes AJAX
 */
class AjaxHandler {

	/**
	 * Instance du Siren Manager
	 *
	 * @var SirenManager
	 */
	private $siren_manager;

	/**
	 * Instance du GF Manager
	 *
	 * @var GFManager
	 */
	private $gf_manager;

	/**
	 * Instance du logger
	 *
	 * @var Logger
	 */
	private $logger;

	/**
	 * Constructeur
	 *
	 * @param SirenManager $siren_manager Instance du Siren Manager.
	 * @param GFManager    $gf_manager Instance du GF Manager.
	 * @param Logger       $logger Instance du logger.
	 */
	public function __construct( SirenManager $siren_manager, GFManager $gf_manager, Logger $logger ) {
		$this->siren_manager = $siren_manager;
		$this->gf_manager    = $gf_manager;
		$this->logger        = $logger;
	}

	/**
	 * Initialise les hooks AJAX
	 */
	public function init_hooks() {
		// Actions AJAX publiques (frontend).
		add_action( 'wp_ajax_' . Constants::AJAX_VERIFY_SIRET, array( $this, 'handle_verify_siret' ) );
		add_action( 'wp_ajax_nopriv_' . Constants::AJAX_VERIFY_SIRET, array( $this, 'handle_verify_siret' ) );

		// Actions AJAX admin uniquement.
		add_action( 'wp_ajax_' . Constants::AJAX_TEST_API, array( $this, 'handle_test_api' ) );
		add_action( 'wp_ajax_' . Constants::AJAX_CLEAR_CACHE, array( $this, 'handle_clear_cache' ) );
		add_action( 'wp_ajax_' . Constants::AJAX_GET_LOGS, array( $this, 'handle_get_logs' ) );
		add_action( 'wp_ajax_' . Constants::AJAX_EXPORT_LOGS, array( $this, 'handle_export_logs' ) );
		add_action( 'wp_ajax_' . Constants::AJAX_LOAD_FORM_FIELDS, array( $this, 'handle_load_form_fields' ) );
	}

	/**
	 * Gère la vérification d'un SIRET (frontend)
	 */
	public function handle_verify_siret() {
		// Vérifier le nonce.
		$nonce = $_POST['nonce'] ?? '';

		if ( ! SecurityHelper::verify_nonce( $nonce ) ) {
			SecurityHelper::die_json_error( __( 'Sécurité : nonce invalide.', Constants::TEXT_DOMAIN ), 403 );
		}

		// Récupérer les paramètres.
		$form_id = isset( $_POST['form_id'] ) ? absint( $_POST['form_id'] ) : 0;
		$siret   = isset( $_POST['siret'] ) ? sanitize_text_field( $_POST['siret'] ) : '';

		// Récupérer les données du représentant (OBLIGATOIRES).
		$prenom_raw = isset( $_POST['prenom'] ) ? sanitize_text_field( $_POST['prenom'] ) : '';
		$nom_raw    = isset( $_POST['nom'] ) ? sanitize_text_field( $_POST['nom'] ) : '';

		if ( empty( $siret ) ) {
			SecurityHelper::die_json_error( __( 'Le SIRET est requis.', Constants::TEXT_DOMAIN ), 400 );
		}

		// Validation : Nom et Prénom sont OBLIGATOIRES.
		if ( empty( $nom_raw ) || empty( $prenom_raw ) ) {
			SecurityHelper::die_json_error(
				__( '⚠️ Veuillez renseigner le nom et le prénom du représentant avant de vérifier le SIRET.', Constants::TEXT_DOMAIN ),
				400
			);
		}

		// Formatage et validation des noms/prénoms.
		$nom_formatted = NameFormatter::format( $nom_raw );
		$prenom_formatted = NameFormatter::format( $prenom_raw );

		if ( ! $nom_formatted['valid'] ) {
			$this->logger->warning( 'Nom invalide fourni', array( 'nom' => $nom_raw, 'error' => $nom_formatted['error'] ) );
			SecurityHelper::die_json_error( $nom_formatted['error'], 400 );
		}

		if ( ! $prenom_formatted['valid'] ) {
			$this->logger->warning( 'Prénom invalide fourni', array( 'prenom' => $prenom_raw, 'error' => $prenom_formatted['error'] ) );
			SecurityHelper::die_json_error( $prenom_formatted['error'], 400 );
		}

		// Préparer les données formatées du représentant.
		$representant_data = array(
			'prenom' => $prenom_formatted['value'],
			'nom'    => $nom_formatted['value'],
		);

		$this->logger->info(
			'Noms/prénoms formatés avec succès',
			array(
				'prenom_avant' => $prenom_raw,
				'prenom_apres' => $prenom_formatted['value'],
				'nom_avant'    => $nom_raw,
				'nom_apres'    => $nom_formatted['value'],
			)
		);

		// Traiter la vérification via le GFManager.
		$result = $this->gf_manager->process_verification_request( $form_id, $siret, $representant_data );

		if ( $result['success'] ) {
			SecurityHelper::send_json_success( $result );
		} else {
			SecurityHelper::die_json_error( $result['message'], 400 );
		}
	}

	/**
	 * Gère le test de connexion à l'API (admin)
	 */
	public function handle_test_api() {
		// Vérifier le nonce et les permissions.
		$nonce = $_POST['nonce'] ?? '';

		if ( ! SecurityHelper::verify_ajax_request( $nonce ) ) {
			SecurityHelper::die_json_error( __( 'Permissions insuffisantes.', Constants::TEXT_DOMAIN ), 403 );
		}

		// SIRET de test (optionnel).
		$test_siret = isset( $_POST['test_siret'] ) ? sanitize_text_field( $_POST['test_siret'] ) : '73282932000074';

		// Tester la connexion.
		$result = $this->siren_manager->test_api_connection( $test_siret );

		if ( $result['success'] ) {
			$this->logger->info( 'Test de connexion API réussi' );
			SecurityHelper::send_json_success( $result );
		} else {
			$this->logger->error( 'Test de connexion API échoué', array( 'error' => $result['message'] ) );
			SecurityHelper::die_json_error( $result['message'], 500 );
		}
	}

	/**
	 * Gère le vidage du cache (admin)
	 */
	public function handle_clear_cache() {
		// Vérifier le nonce et les permissions.
		$nonce = $_POST['nonce'] ?? '';

		if ( ! SecurityHelper::verify_ajax_request( $nonce ) ) {
			SecurityHelper::die_json_error( __( 'Permissions insuffisantes.', Constants::TEXT_DOMAIN ), 403 );
		}

		// Vider le cache.
		$count = $this->siren_manager->clear_cache();

		SecurityHelper::send_json_success(
			array(
				'message' => sprintf(
					/* translators: %d: nombre d'entrées supprimées */
					_n( '%d entrée supprimée du cache.', '%d entrées supprimées du cache.', $count, Constants::TEXT_DOMAIN ),
					$count
				),
				'count'   => $count,
			)
		);
	}

	/**
	 * Gère la récupération des logs (admin)
	 */
	public function handle_get_logs() {
		// Vérifier le nonce et les permissions.
		$nonce = $_POST['nonce'] ?? '';

		if ( ! SecurityHelper::verify_ajax_request( $nonce ) ) {
			SecurityHelper::die_json_error( __( 'Permissions insuffisantes.', Constants::TEXT_DOMAIN ), 403 );
		}

		// Récupérer les paramètres de filtrage.
		$args = array(
			'level'     => isset( $_POST['level'] ) ? sanitize_text_field( $_POST['level'] ) : '',
			'limit'     => isset( $_POST['limit'] ) ? absint( $_POST['limit'] ) : 20,
			'offset'    => isset( $_POST['offset'] ) ? absint( $_POST['offset'] ) : 0,
			'date_from' => isset( $_POST['date_from'] ) ? sanitize_text_field( $_POST['date_from'] ) : '',
			'date_to'   => isset( $_POST['date_to'] ) ? sanitize_text_field( $_POST['date_to'] ) : '',
		);

		// Récupérer les logs.
		$logs        = $this->logger->get_logs( $args );
		$total_logs  = $this->logger->count_logs( $args );

		SecurityHelper::send_json_success(
			array(
				'logs'  => $logs,
				'total' => $total_logs,
			)
		);
	}

	/**
	 * Gère l'export des logs en CSV (admin)
	 */
	public function handle_export_logs() {
		// Vérifier le nonce et les permissions.
		$nonce = $_GET['nonce'] ?? '';

		if ( ! SecurityHelper::verify_ajax_request( $nonce ) ) {
			wp_die( __( 'Permissions insuffisantes.', Constants::TEXT_DOMAIN ), 403 );
		}

		// Récupérer tous les logs.
		$logs = $this->logger->get_logs( array( 'limit' => 10000 ) );

		// Préparer le CSV.
		$filename = 'gf-siren-logs-' . date( 'Y-m-d-His' ) . '.csv';

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $filename );

		$output = fopen( 'php://output', 'w' );

		// En-têtes CSV.
		fputcsv( $output, array( 'Date', 'Niveau', 'Message', 'Contexte', 'Utilisateur', 'IP' ) );

		// Lignes de données.
		foreach ( $logs as $log ) {
			fputcsv(
				$output,
				array(
					$log['date'],
					$log['level'],
					$log['message'],
					$log['context'] ?? '',
					$log['user_id'] ?? '',
					$log['ip_address'] ?? '',
				)
			);
		}

		fclose( $output );
		exit;
	}

	/**
	 * Charge les champs d'un formulaire Gravity Forms et son mapping existant (admin)
	 */
	public function handle_load_form_fields() {
		// Vérifier le nonce et les permissions.
		$nonce = $_POST['nonce'] ?? '';

		if ( ! SecurityHelper::verify_ajax_request( $nonce ) ) {
			SecurityHelper::die_json_error( __( 'Permissions insuffisantes.', Constants::TEXT_DOMAIN ), 403 );
		}

		$form_id = isset( $_POST['form_id'] ) ? absint( $_POST['form_id'] ) : 0;

		if ( ! $form_id ) {
			SecurityHelper::die_json_error( __( 'ID de formulaire manquant.', Constants::TEXT_DOMAIN ), 400 );
		}

		// Vérifier que Gravity Forms est actif.
		if ( ! class_exists( 'GFAPI' ) ) {
			SecurityHelper::die_json_error( __( 'Gravity Forms n\'est pas actif.', Constants::TEXT_DOMAIN ), 400 );
		}

		// Récupérer le formulaire.
		$form = \GFAPI::get_form( $form_id );

		if ( ! $form ) {
			SecurityHelper::die_json_error( __( 'Formulaire introuvable.', Constants::TEXT_DOMAIN ), 404 );
		}

		// Récupérer les champs du formulaire.
		$fields = array();
		if ( ! empty( $form['fields'] ) ) {
			foreach ( $form['fields'] as $field ) {
				$field_id = $field->id;
				$label    = $field->label;

				// Pour les champs composites (adresse, nom), ajouter les sous-champs.
				if ( ! empty( $field->inputs ) ) {
					foreach ( $field->inputs as $input ) {
						$fields[] = array(
							'id'    => (string) $input['id'],
							'label' => $label . ' - ' . $input['label'],
						);
					}
				} else {
					$fields[] = array(
						'id'    => (string) $field_id,
						'label' => $label,
					);
				}
			}
		}

		// Récupérer le mapping existant pour ce formulaire.
		$settings       = get_option( Constants::SETTINGS_OPTION, array() );
		$form_mappings  = $settings['form_mappings'] ?? array();
		$current_mapping = $form_mappings[ $form_id ] ?? array();

		// Retourner les données.
		wp_send_json_success(
			array(
				'fields'  => $fields,
				'mapping' => $current_mapping,
			)
		);
	}
}

