<?php
/**
 * Page de configuration du plugin
 *
 * @package GFSirenAutocomplete
 */

namespace GFSirenAutocomplete\Admin;

defined( 'ABSPATH' ) || exit;

use GFSirenAutocomplete\Core\Constants;
use GFSirenAutocomplete\Helpers\SecurityHelper;
use GFSirenAutocomplete\Helpers\DataHelper;
use GFSirenAutocomplete\Modules\GravityForms\GFFieldMapper;

/**
 * Classe de gestion de la page de configuration
 */
class SettingsPage {

	/**
	 * Instance du field mapper
	 *
	 * @var GFFieldMapper
	 */
	private $field_mapper;

	/**
	 * Constructeur
	 *
	 * @param GFFieldMapper $field_mapper Instance du mapper.
	 */
	public function __construct( GFFieldMapper $field_mapper ) {
		$this->field_mapper = $field_mapper;
	}

	/**
	 * Affiche la page de configuration
	 */
	public function render() {
		// Vérifier les permissions.
		if ( ! SecurityHelper::check_permissions() ) {
			wp_die( __( 'Vous n\'avez pas les permissions nécessaires.', Constants::TEXT_DOMAIN ) );
		}

		// Traiter la sauvegarde si nécessaire.
		$this->maybe_save_settings();

		// Récupérer les paramètres actuels.
		$settings = get_option( Constants::SETTINGS_OPTION, array() );

		?>
		<div class="wrap gf-siren-settings">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<?php settings_errors( 'gf_siren_messages' ); ?>

			<nav class="nav-tab-wrapper">
				<a href="#tab-general" class="nav-tab nav-tab-active"><?php esc_html_e( 'Configuration générale', Constants::TEXT_DOMAIN ); ?></a>
				<a href="#tab-mapping" class="nav-tab"><?php esc_html_e( 'Mapping des champs', Constants::TEXT_DOMAIN ); ?></a>
			</nav>

			<form method="post" action="">
				<?php wp_nonce_field( 'gf_siren_save_settings', 'gf_siren_settings_nonce' ); ?>

				<!-- Onglet Configuration générale -->
				<div id="tab-general" class="tab-content" style="display:block;">
					<?php $this->render_general_tab( $settings ); ?>
				</div>

				<!-- Onglet Mapping -->
				<div id="tab-mapping" class="tab-content" style="display:none;">
					<?php $this->render_mapping_tab( $settings ); ?>
				</div>

				<p class="submit">
					<button type="submit" name="gf_siren_save" class="button button-primary">
						<?php esc_html_e( 'Enregistrer les paramètres', Constants::TEXT_DOMAIN ); ?>
					</button>
				</p>
			</form>
		</div>
		<?php
	}

	/**
	 * Affiche l'onglet de configuration générale
	 *
	 * @param array $settings Paramètres actuels.
	 */
	private function render_general_tab( $settings ) {
		$api_key        = get_option( Constants::API_KEY_OPTION, '' );
		$cache_duration = $settings['cache_duration'] ?? Constants::CACHE_DURATION;
		$has_constant   = defined( Constants::API_KEY_CONSTANT ) && constant( Constants::API_KEY_CONSTANT );

		?>
		<table class="form-table" role="presentation">
			<tbody>
				<!-- Clé API -->
				<tr>
					<th scope="row">
						<label for="api_key"><?php esc_html_e( 'Clé API Siren', Constants::TEXT_DOMAIN ); ?></label>
					</th>
					<td>
						<?php if ( $has_constant ) : ?>
							<p class="description">
								<?php esc_html_e( '✅ La clé API est définie dans wp-config.php (recommandé)', Constants::TEXT_DOMAIN ); ?>
							</p>
						<?php else : ?>
							<input type="password" id="api_key" name="gf_siren[api_key]" 
								value="<?php echo esc_attr( $api_key ); ?>" 
								class="regular-text" />
							<p class="description">
								<?php
								printf(
									/* translators: %s: nom de la constante */
									esc_html__( 'Recommandation : définir la clé dans wp-config.php avec la constante %s', Constants::TEXT_DOMAIN ),
									'<code>' . esc_html( Constants::API_KEY_CONSTANT ) . '</code>'
								);
								?>
							</p>
						<?php endif; ?>
						<p>
							<button type="button" id="gf-siren-test-api" class="button button-secondary">
								<?php esc_html_e( 'Tester la connexion', Constants::TEXT_DOMAIN ); ?>
							</button>
							<span id="gf-siren-api-test-result"></span>
						</p>
					</td>
				</tr>

				<!-- Durée du cache -->
				<tr>
					<th scope="row">
						<label for="cache_duration"><?php esc_html_e( 'Durée du cache', Constants::TEXT_DOMAIN ); ?></label>
					</th>
					<td>
						<input type="number" id="cache_duration" name="gf_siren[cache_duration]" 
							value="<?php echo esc_attr( $cache_duration ); ?>" 
							min="0" step="3600" class="small-text" />
						<?php esc_html_e( 'secondes', Constants::TEXT_DOMAIN ); ?>
						<p class="description">
							<?php esc_html_e( 'Durée pendant laquelle les résultats API sont mis en cache (24h par défaut = 86400 secondes)', Constants::TEXT_DOMAIN ); ?>
						</p>
						<p>
							<button type="button" id="gf-siren-clear-cache" class="button button-secondary">
								<?php esc_html_e( 'Vider le cache maintenant', Constants::TEXT_DOMAIN ); ?>
							</button>
							<span id="gf-siren-cache-result"></span>
						</p>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Affiche l'onglet de mapping des champs
	 *
	 * @param array $settings Paramètres actuels.
	 */
	private function render_mapping_tab( $settings ) {
		// Récupérer la liste des formulaires Gravity Forms.
		$forms = $this->get_gravity_forms_list();

		if ( empty( $forms ) ) {
			?>
			<div class="notice notice-warning inline">
				<p><?php esc_html_e( 'Aucun formulaire Gravity Forms trouvé. Veuillez créer un formulaire d\'abord.', Constants::TEXT_DOMAIN ); ?></p>
			</div>
			<?php
			return;
		}

		?>
		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row">
						<label for="form_select"><?php esc_html_e( 'Sélectionner un formulaire', Constants::TEXT_DOMAIN ); ?></label>
					</th>
					<td>
						<select id="form_select" name="gf_siren[form_id]" class="regular-text">
							<option value=""><?php esc_html_e( '-- Choisir un formulaire --', Constants::TEXT_DOMAIN ); ?></option>
							<?php foreach ( $forms as $form ) : ?>
								<option value="<?php echo esc_attr( $form['id'] ); ?>">
									<?php echo esc_html( $form['title'] . ' (ID: ' . $form['id'] . ')' ); ?>
								</option>
			<?php endforeach; ?>
						</select>
					</td>
				</tr>
			</tbody>
		</table>

		<div id="gf-siren-mapping-fields" style="display:none; margin-top:20px;">
			<h3><?php esc_html_e( 'Mapping des champs', Constants::TEXT_DOMAIN ); ?></h3>
			<p class="description">
				<?php esc_html_e( 'Associez les champs de votre formulaire aux données de l\'API Siren.', Constants::TEXT_DOMAIN ); ?>
			</p>

			<table class="form-table" role="presentation">
				<tbody>
					<?php $this->render_mapping_fields(); ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Affiche les champs de mapping
	 */
	private function render_mapping_fields() {
		$mappable_fields = array(
			'siret'             => __( 'SIRET', Constants::TEXT_DOMAIN ),
			'denomination'      => __( 'Dénomination', Constants::TEXT_DOMAIN ),
			'adresse'           => __( 'Adresse', Constants::TEXT_DOMAIN ),
			'code_postal'       => __( 'Code postal', Constants::TEXT_DOMAIN ),
			'ville'             => __( 'Ville', Constants::TEXT_DOMAIN ),
			'forme_juridique'   => __( 'Forme juridique', Constants::TEXT_DOMAIN ),
			'code_ape'          => __( 'Code APE', Constants::TEXT_DOMAIN ),
			'libelle_ape'       => __( 'Libellé APE', Constants::TEXT_DOMAIN ),
			'date_creation'     => __( 'Date de création', Constants::TEXT_DOMAIN ),
			'statut_actif'      => __( 'Statut actif/inactif', Constants::TEXT_DOMAIN ),
			'type_entreprise'   => __( 'Type d\'entreprise', Constants::TEXT_DOMAIN ),
			'mentions_legales'  => __( 'Mentions légales', Constants::TEXT_DOMAIN ),
			'prenom'            => __( 'Prénom (représentant)', Constants::TEXT_DOMAIN ),
			'nom'               => __( 'Nom (représentant)', Constants::TEXT_DOMAIN ),
		);

		foreach ( $mappable_fields as $key => $label ) :
			?>
			<tr>
				<th scope="row">
					<label for="mapping_<?php echo esc_attr( $key ); ?>">
						<?php echo esc_html( $label ); ?>
					</label>
				</th>
				<td>
					<select id="mapping_<?php echo esc_attr( $key ); ?>" 
						name="gf_siren[mapping][<?php echo esc_attr( $key ); ?>]" 
						class="gf-field-select regular-text">
						<option value=""><?php esc_html_e( '-- Non mappé --', Constants::TEXT_DOMAIN ); ?></option>
					</select>
				</td>
			</tr>
			<?php
		endforeach;
	}

	/**
	 * Traite la sauvegarde des paramètres
	 */
	private function maybe_save_settings() {
		if ( ! isset( $_POST['gf_siren_save'] ) ) {
			return;
		}

		// Vérifier le nonce.
		if ( ! isset( $_POST['gf_siren_settings_nonce'] ) || ! wp_verify_nonce( $_POST['gf_siren_settings_nonce'], 'gf_siren_save_settings' ) ) {
			add_settings_error( 'gf_siren_messages', 'gf_siren_nonce_error', __( 'Erreur de sécurité.', Constants::TEXT_DOMAIN ), 'error' );
			return;
		}

		// Vérifier les permissions.
		if ( ! SecurityHelper::check_permissions() ) {
			add_settings_error( 'gf_siren_messages', 'gf_siren_permission_error', __( 'Permissions insuffisantes.', Constants::TEXT_DOMAIN ), 'error' );
			return;
		}

		$post_data = $_POST['gf_siren'] ?? array();
		$settings  = get_option( Constants::SETTINGS_OPTION, array() );

		// Sauvegarder la clé API (si pas en constante).
		if ( isset( $post_data['api_key'] ) && ! defined( Constants::API_KEY_CONSTANT ) ) {
			$api_key = DataHelper::sanitize_api_key( $post_data['api_key'] );
			update_option( Constants::API_KEY_OPTION, $api_key );
		}

		// Sauvegarder la durée du cache.
		if ( isset( $post_data['cache_duration'] ) ) {
			$settings['cache_duration'] = absint( $post_data['cache_duration'] );
		}

		// Sauvegarder le mapping (si formulaire sélectionné).
		if ( isset( $post_data['form_id'] ) && ! empty( $post_data['form_id'] ) ) {
			$form_id = absint( $post_data['form_id'] );
			$mapping = $post_data['mapping'] ?? array();

			// Sauvegarder le mapping via le field mapper.
			$this->field_mapper->save_field_mapping( $form_id, $mapping );
		}

		// Mettre à jour les paramètres.
		update_option( Constants::SETTINGS_OPTION, $settings );

		add_settings_error( 'gf_siren_messages', 'gf_siren_settings_saved', __( 'Paramètres enregistrés avec succès.', Constants::TEXT_DOMAIN ), 'success' );
	}

	/**
	 * Récupère la liste des formulaires Gravity Forms
	 *
	 * @return array Liste des formulaires.
	 */
	private function get_gravity_forms_list() {
		if ( ! class_exists( 'GFAPI' ) ) {
			return array();
		}

		$forms = \GFAPI::get_forms();

		return is_array( $forms ) ? $forms : array();
	}
}

