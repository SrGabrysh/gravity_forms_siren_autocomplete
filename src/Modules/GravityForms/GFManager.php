<?php
/**
 * Orchestration du module Gravity Forms
 *
 * @package GFSirenAutocomplete
 */

namespace GFSirenAutocomplete\Modules\GravityForms;

defined( 'ABSPATH' ) || exit;

use GFSirenAutocomplete\Core\Logger;
use GFSirenAutocomplete\Modules\Siren\SirenManager;
use GFSirenAutocomplete\Modules\MentionsLegales\MentionsManager;

/**
 * Classe d'orchestration du module Gravity Forms
 */
class GFManager {

	/**
	 * Instance du field mapper
	 *
	 * @var GFFieldMapper
	 */
	private $field_mapper;

	/**
	 * Instance du button injector
	 *
	 * @var GFButtonInjector
	 */
	private $button_injector;

	/**
	 * Instance du validator
	 *
	 * @var GFValidator
	 */
	private $validator;

	/**
	 * Instance du Siren Manager
	 *
	 * @var SirenManager
	 */
	private $siren_manager;

	/**
	 * Instance du Mentions Manager
	 *
	 * @var MentionsManager
	 */
	private $mentions_manager;

	/**
	 * Instance du logger
	 *
	 * @var Logger
	 */
	private $logger;

	/**
	 * Constructeur
	 *
	 * @param SirenManager    $siren_manager Instance du Siren Manager.
	 * @param MentionsManager $mentions_manager Instance du Mentions Manager.
	 * @param Logger          $logger Instance du logger.
	 */
	public function __construct( SirenManager $siren_manager, MentionsManager $mentions_manager, Logger $logger ) {
		$this->siren_manager    = $siren_manager;
		$this->mentions_manager = $mentions_manager;
		$this->logger           = $logger;

		// Initialiser les composants.
		$this->field_mapper     = new GFFieldMapper();
		$this->button_injector  = new GFButtonInjector( $this->field_mapper );
		$this->validator        = new GFValidator( $this->field_mapper, $logger );
	}

	/**
	 * Initialise les hooks Gravity Forms
	 */
	public function init_hooks() {
		// Vérifier si Gravity Forms est actif.
		if ( ! class_exists( 'GFForms' ) ) {
			add_action( 'admin_notices', array( $this, 'show_gf_required_notice' ) );
			return;
		}

		// Initialiser les composants.
		$this->button_injector->init_hooks();
		$this->validator->init_hooks();
	}

	/**
	 * Traite une requête de vérification SIRET (appelé depuis AJAX)
	 *
	 * @param int    $form_id ID du formulaire.
	 * @param string $siret SIRET à vérifier.
	 * @param array  $representant_data Données du représentant.
	 * @return array ['success' => bool, 'data' => array|null, 'message' => string].
	 */
	public function process_verification_request( $form_id, $siret, $representant_data = array() ) {
		$this->logger->info(
			"Traitement de la vérification SIRET pour le formulaire #{$form_id}",
			array( 'siret' => $siret )
		);

		// Étape 1 : Récupérer les données depuis l'API.
		$company_data = $this->siren_manager->get_company_data( $siret );

		if ( is_wp_error( $company_data ) ) {
			$this->logger->error(
				'Échec de la vérification SIRET',
				array(
					'siret' => $siret,
					'error' => $company_data->get_error_message(),
				)
			);

			return array(
				'success' => false,
				'message' => $company_data->get_error_message(),
			);
		}

		// Étape 2 : Générer les mentions légales.
		$mentions_legales = $this->mentions_manager->generate( $company_data, $representant_data );

		// Étape 3 : Mapper les données aux champs du formulaire.
		$mapping = $this->field_mapper->get_field_mapping( $form_id );

		if ( false === $mapping ) {
			$this->logger->error( 'Mapping manquant pour le formulaire', array( 'form_id' => $form_id ) );

			return array(
				'success' => false,
				'message' => __( 'Configuration du formulaire manquante. Veuillez contacter l\'administrateur.', 'gravity-forms-siren-autocomplete' ),
			);
		}

		$mapped_data = $this->field_mapper->map_data_to_fields( $company_data, $mapping, $mentions_legales );

		// Étape 4 : Préparer la réponse (incluant les noms/prénoms formatés pour réinjection).
		$response = array(
			'success'          => true,
			'data'             => $mapped_data,
			'denomination'     => $company_data['denomination'] ?? '',
			'est_actif'        => $company_data['est_actif'] ?? true,
			'type_entreprise'  => $company_data['type_entreprise'] ?? '',
			'message'          => sprintf(
				__( 'Entreprise trouvée : %s', 'gravity-forms-siren-autocomplete' ),
				$company_data['denomination'] ?? ''
			),
			// Ajouter les noms/prénoms formatés pour réinjection dans les champs
			'representant'     => array(
				'nom'    => $representant_data['nom'] ?? '',
				'prenom' => $representant_data['prenom'] ?? '',
			),
		);

		$this->logger->info(
			'Vérification SIRET réussie',
			array(
				'siret'        => $siret,
				'form_id'      => $form_id,
				'denomination' => $company_data['denomination'] ?? '',
			)
		);

		return $response;
	}

	/**
	 * Affiche un avertissement si Gravity Forms n'est pas actif
	 */
	public function show_gf_required_notice() {
		?>
		<div class="notice notice-error">
			<p>
				<?php
				echo wp_kses_post(
					sprintf(
						/* translators: %s: lien vers Gravity Forms */
						__( '<strong>Gravity Forms Siren Autocomplete</strong> requiert le plugin <a href="%s" target="_blank">Gravity Forms</a> pour fonctionner.', 'gravity-forms-siren-autocomplete' ),
						'https://www.gravityforms.com/'
					)
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Récupère l'instance du field mapper (utile pour l'admin)
	 *
	 * @return GFFieldMapper
	 */
	public function get_field_mapper() {
		return $this->field_mapper;
	}
}

