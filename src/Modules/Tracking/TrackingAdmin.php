<?php
/**
 * Interface d'administration du module de tracking
 *
 * @package GFSirenAutocomplete
 */

namespace GFSirenAutocomplete\Modules\Tracking;

defined( 'ABSPATH' ) || exit;

use GFSirenAutocomplete\Core\Constants;
use GFSirenAutocomplete\Helpers\SecurityHelper;

/**
 * Classe de gestion de l'interface admin du tracking
 */
class TrackingAdmin {

	/**
	 * Instance du storage
	 *
	 * @var TrackingStorage
	 */
	private $storage;

	/**
	 * Constructeur
	 *
	 * @param TrackingStorage $storage Instance du storage.
	 */
	public function __construct( TrackingStorage $storage ) {
		$this->storage = $storage;
	}

	/**
	 * Initialise les hooks admin
	 */
	public function init_hooks() {
		// Actions pour exports et suppressions.
		add_action( 'admin_post_gf_siren_tracking_download_json', array( $this, 'handle_download_json' ) );
		add_action( 'admin_post_gf_siren_tracking_download_csv', array( $this, 'handle_download_csv' ) );
		add_action( 'admin_post_gf_siren_tracking_purge', array( $this, 'handle_purge' ) );
		add_action( 'admin_post_gf_siren_tracking_delete', array( $this, 'handle_delete_single' ) );
		add_action( 'admin_post_gf_siren_tracking_bulk_delete', array( $this, 'handle_bulk_delete' ) );
	}

	/**
	 * Affiche l'onglet de tracking
	 */
	public function render_tracking_tab() {
		// Vérifier les permissions.
		if ( ! SecurityHelper::check_permissions() ) {
			wp_die( __( 'Vous n\'avez pas les permissions nécessaires.', Constants::TEXT_DOMAIN ) );
		}

		// Récupération et validation des paramètres.
		$per_page  = max( 1, intval( $_GET['per_page'] ?? 20 ) );
		$page      = max( 1, intval( $_GET['paged'] ?? 1 ) );
		$form_id   = ! empty( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : 0;
		$ip_filter = sanitize_text_field( $_GET['ip'] ?? '' );
		$dmin      = sanitize_text_field( $_GET['dmin'] ?? '' );
		$dmax      = sanitize_text_field( $_GET['dmax'] ?? '' );

		$filters = array(
			'form_id'  => $form_id,
			'ip'       => $ip_filter,
			'date_min' => $dmin,
			'date_max' => $dmax,
		);

		// Comptage et récupération des entrées.
		$total   = $this->storage->count_entries( $filters );
		$entries = $this->storage->get_entries_paginated( $page, $per_page, $filters );

		// URLs d'action.
		$json_url  = wp_nonce_url( admin_url( 'admin-post.php?action=gf_siren_tracking_download_json' ), 'gf_siren_tracking_action', '_gf_siren_tracking_nonce' );
		$csv_url   = wp_nonce_url( admin_url( 'admin-post.php?action=gf_siren_tracking_download_csv' ), 'gf_siren_tracking_action', '_gf_siren_tracking_nonce' );
		$purge_url = wp_nonce_url( admin_url( 'admin-post.php?action=gf_siren_tracking_purge' ), 'gf_siren_tracking_action', '_gf_siren_tracking_nonce' );
		$bulk_url  = wp_nonce_url( admin_url( 'admin-post.php?action=gf_siren_tracking_bulk_delete' ), 'gf_siren_tracking_action', '_gf_siren_tracking_nonce' );

		?>
		<div class="gf-siren-tracking-section">
			<h2><?php esc_html_e( 'Suivi des Soumissions', Constants::TEXT_DOMAIN ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'Table de stockage :', Constants::TEXT_DOMAIN ); ?>
				<code><?php echo esc_html( $this->storage->get_table_name() ); ?></code>
			</p>

			<!-- Actions rapides -->
			<div style="margin: 20px 0; display: flex; gap: 10px;">
				<a href="<?php echo esc_url( $json_url ); ?>" class="button button-primary">
					<?php esc_html_e( 'Télécharger JSON', Constants::TEXT_DOMAIN ); ?>
				</a>
				<a href="<?php echo esc_url( $csv_url ); ?>" class="button">
					<?php esc_html_e( 'Télécharger CSV', Constants::TEXT_DOMAIN ); ?>
				</a>
				<a href="<?php echo esc_url( $purge_url ); ?>" class="button button-secondary" 
					onclick="return confirm('<?php echo esc_js( __( 'Confirmer la purge de toutes les entrées ?', Constants::TEXT_DOMAIN ) ); ?>');">
					<?php esc_html_e( 'Purger toutes les entrées', Constants::TEXT_DOMAIN ); ?>
				</a>
			</div>

			<!-- Filtres -->
			<h3><?php esc_html_e( 'Filtres', Constants::TEXT_DOMAIN ); ?></h3>
			<form method="get" action="">
				<input type="hidden" name="page" value="<?php echo esc_attr( Constants::ADMIN_MENU_SLUG ); ?>">
				<input type="hidden" name="tab" value="tracking">
				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row">
								<label for="tracking_form_id"><?php esc_html_e( 'Formulaire', Constants::TEXT_DOMAIN ); ?></label>
							</th>
							<td>
								<select id="tracking_form_id" name="form_id">
									<option value=""><?php esc_html_e( 'Tous les formulaires', Constants::TEXT_DOMAIN ); ?></option>
									<?php
									$forms = $this->get_tracked_forms();
									foreach ( $forms as $form ) :
										?>
										<option value="<?php echo esc_attr( $form->id ); ?>" <?php selected( $form_id, $form->id ); ?>>
											<?php echo esc_html( $form->title . ' (ID: ' . $form->id . ')' ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="tracking_ip"><?php esc_html_e( 'IP contient', Constants::TEXT_DOMAIN ); ?></label>
							</th>
							<td>
								<input type="text" class="regular-text" id="tracking_ip" name="ip" value="<?php echo esc_attr( $ip_filter ); ?>">
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="tracking_dmin"><?php esc_html_e( 'Date min', Constants::TEXT_DOMAIN ); ?></label>
							</th>
							<td>
								<input type="date" id="tracking_dmin" name="dmin" value="<?php echo esc_attr( $dmin ); ?>">
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="tracking_dmax"><?php esc_html_e( 'Date max', Constants::TEXT_DOMAIN ); ?></label>
							</th>
							<td>
								<input type="date" id="tracking_dmax" name="dmax" value="<?php echo esc_attr( $dmax ); ?>">
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="tracking_per_page"><?php esc_html_e( 'Éléments par page', Constants::TEXT_DOMAIN ); ?></label>
							</th>
							<td>
								<input type="number" id="tracking_per_page" name="per_page" min="1" max="200" value="<?php echo esc_attr( $per_page ); ?>">
							</td>
						</tr>
					</tbody>
				</table>
				<p class="submit">
					<button type="submit" class="button button-primary"><?php esc_html_e( 'Filtrer', Constants::TEXT_DOMAIN ); ?></button>
				</p>
			</form>

			<!-- Résultats -->
			<h3><?php esc_html_e( 'Soumissions', Constants::TEXT_DOMAIN ); ?> (<?php echo (int) $total; ?>)</h3>

			<?php if ( 0 === $total ) : ?>
				<div class="notice notice-info inline">
					<p><?php esc_html_e( 'Aucune soumission enregistrée.', Constants::TEXT_DOMAIN ); ?></p>
				</div>
			<?php else : ?>
				<form method="post" action="<?php echo esc_url( $bulk_url ); ?>">
					<?php wp_nonce_field( 'gf_siren_tracking_action', '_gf_siren_tracking_nonce' ); ?>
					<div style="margin-bottom: 10px;">
						<select name="bulk_action">
							<option value=""><?php esc_html_e( 'Actions groupées', Constants::TEXT_DOMAIN ); ?></option>
							<option value="delete"><?php esc_html_e( 'Supprimer', Constants::TEXT_DOMAIN ); ?></option>
						</select>
						<button type="submit" class="button" onclick="return confirm('<?php echo esc_js( __( 'Supprimer les entrées sélectionnées ?', Constants::TEXT_DOMAIN ) ); ?>');">
							<?php esc_html_e( 'Appliquer', Constants::TEXT_DOMAIN ); ?>
						</button>
					</div>

					<table class="widefat striped">
						<thead>
							<tr>
								<td class="manage-column column-cb check-column">
									<input type="checkbox" onclick="const cbs=document.querySelectorAll('.tracking-cb'); for(const cb of cbs){ cb.checked=this.checked; }">
								</td>
								<th><?php esc_html_e( 'ID', Constants::TEXT_DOMAIN ); ?></th>
								<th><?php esc_html_e( 'Formulaire', Constants::TEXT_DOMAIN ); ?></th>
								<th><?php esc_html_e( 'Entry ID', Constants::TEXT_DOMAIN ); ?></th>
								<th><?php esc_html_e( 'Date', Constants::TEXT_DOMAIN ); ?></th>
								<th><?php esc_html_e( 'IP', Constants::TEXT_DOMAIN ); ?></th>
								<th><?php esc_html_e( 'Métadonnées Siren', Constants::TEXT_DOMAIN ); ?></th>
								<th><?php esc_html_e( 'Actions', Constants::TEXT_DOMAIN ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $entries as $e ) :
								$delete_url = wp_nonce_url(
									add_query_arg(
										array( 'action' => 'gf_siren_tracking_delete', 'id' => (int) $e['id'] ),
										admin_url( 'admin-post.php' )
									),
									'gf_siren_tracking_action',
									'_gf_siren_tracking_nonce'
								);
								?>
								<tr>
									<th scope="row" class="check-column">
										<input class="tracking-cb" type="checkbox" name="ids[]" value="<?php echo (int) $e['id']; ?>">
									</th>
									<td><?php echo (int) $e['id']; ?></td>
									<td><?php echo (int) $e['form_id']; ?></td>
									<td><?php echo (int) $e['entry_id']; ?></td>
									<td><?php echo esc_html( $e['created_at'] ); ?></td>
									<td><?php echo esc_html( $e['user_ip'] ); ?></td>
									<td>
										<?php
										if ( ! empty( $e['siren_meta'] ) && is_array( $e['siren_meta'] ) ) {
											echo '<ul style="margin:0;">';
											foreach ( $e['siren_meta'] as $key => $value ) {
												echo '<li><strong>' . esc_html( $key ) . '</strong>: ' . esc_html( is_bool( $value ) ? ( $value ? 'true' : 'false' ) : $value ) . '</li>';
											}
											echo '</ul>';
										} else {
											esc_html_e( 'Aucune', Constants::TEXT_DOMAIN );
										}
										?>
									</td>
									<td>
										<a class="button-link-delete" href="<?php echo esc_url( $delete_url ); ?>" 
											onclick="return confirm('<?php echo esc_js( __( 'Supprimer cette entrée ?', Constants::TEXT_DOMAIN ) ); ?>');">
											<?php esc_html_e( 'Supprimer', Constants::TEXT_DOMAIN ); ?>
										</a>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</form>

				<?php
				// Pagination.
				if ( $total > $per_page ) {
					$base = add_query_arg( array_merge( $_GET, array( 'paged' => '%#%' ) ), admin_url( 'options-general.php?page=' . Constants::ADMIN_MENU_SLUG ) );
					echo '<div class="tablenav"><div class="tablenav-pages">';
					echo paginate_links(
						array(
							'base'      => $base,
							'format'    => '',
							'current'   => $page,
							'total'     => max( 1, (int) ceil( $total / $per_page ) ),
							'prev_text' => '«',
							'next_text' => '»',
						)
					);
					echo '</div></div>';
				}
				?>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Récupère la liste des formulaires trackés
	 *
	 * @return array Liste des formulaires.
	 */
	private function get_tracked_forms() {
		if ( ! class_exists( 'GFAPI' ) ) {
			return array();
		}

		$settings = get_option( Constants::SETTINGS_OPTION, array() );
		$tracked_forms_ids = $settings['tracked_forms'] ?? array();

		if ( empty( $tracked_forms_ids ) ) {
			return array();
		}

		$all_forms = \GFAPI::get_forms();

		return array_filter(
			$all_forms,
			function( $form ) use ( $tracked_forms_ids ) {
				return in_array( (int) $form['id'], $tracked_forms_ids, true );
			}
		);
	}

	/**
	 * Exporte les données en JSON
	 */
	public function handle_download_json() {
		SecurityHelper::check_admin_referer( 'gf_siren_tracking_action', '_gf_siren_tracking_nonce' );

		$filters = $this->get_filters_from_request();
		$data    = $this->storage->get_all_entries( $filters );

		$filename = 'gf-siren-tracking-' . gmdate( 'Ymd-His' ) . '.json';
		$json     = wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT );

		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Content-Length: ' . strlen( $json ) );
		echo $json;
		exit;
	}

	/**
	 * Exporte les données en CSV
	 */
	public function handle_download_csv() {
		SecurityHelper::check_admin_referer( 'gf_siren_tracking_action', '_gf_siren_tracking_nonce' );

		$filters  = $this->get_filters_from_request();
		$data     = $this->storage->get_all_entries( $filters );
		$filename = 'gf-siren-tracking-' . gmdate( 'Ymd-His' ) . '.csv';

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );

		$out = fopen( 'php://output', 'w' );
		fputcsv( $out, array( 'id', 'form_id', 'entry_id', 'created_at', 'user_ip', 'siret_verified', 'api_status' ) );

		foreach ( $data as $row ) {
			$siren_meta = $row['siren_meta'] ?? array();
			fputcsv(
				$out,
				array(
					$row['id'],
					$row['form_id'],
					$row['entry_id'],
					$row['created_at'],
					$row['user_ip'],
					$siren_meta['siret_verified'] ?? '',
					$siren_meta['api_status'] ?? '',
				)
			);
		}

		fclose( $out );
		exit;
	}

	/**
	 * Purge toutes les entrées
	 */
	public function handle_purge() {
		SecurityHelper::check_admin_referer( 'gf_siren_tracking_action', '_gf_siren_tracking_nonce' );

		$this->storage->purge_all();

		wp_safe_redirect(
			add_query_arg(
				array( 'page' => Constants::ADMIN_MENU_SLUG, 'tab' => 'tracking' ),
				admin_url( 'options-general.php' )
			)
		);
		exit;
	}

	/**
	 * Supprime une entrée unique
	 */
	public function handle_delete_single() {
		SecurityHelper::check_admin_referer( 'gf_siren_tracking_action', '_gf_siren_tracking_nonce' );

		$id = absint( $_GET['id'] ?? 0 );

		if ( $id ) {
			$this->storage->delete_entry( $id );
		}

		wp_safe_redirect(
			add_query_arg(
				array( 'page' => Constants::ADMIN_MENU_SLUG, 'tab' => 'tracking' ),
				admin_url( 'options-general.php' )
			)
		);
		exit;
	}

	/**
	 * Suppression multiple
	 */
	public function handle_bulk_delete() {
		SecurityHelper::check_admin_referer( 'gf_siren_tracking_action', '_gf_siren_tracking_nonce' );

		$action = sanitize_text_field( $_POST['bulk_action'] ?? '' );
		$ids    = isset( $_POST['ids'] ) && is_array( $_POST['ids'] ) ? array_map( 'absint', $_POST['ids'] ) : array();

		if ( 'delete' === $action && ! empty( $ids ) ) {
			$this->storage->bulk_delete( $ids );
		}

		wp_safe_redirect(
			add_query_arg(
				array( 'page' => Constants::ADMIN_MENU_SLUG, 'tab' => 'tracking' ),
				admin_url( 'options-general.php' )
			)
		);
		exit;
	}

	/**
	 * Récupère les filtres depuis la requête
	 *
	 * @return array Filtres.
	 */
	private function get_filters_from_request() {
		return array(
			'form_id'  => ! empty( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : 0,
			'ip'       => sanitize_text_field( $_GET['ip'] ?? '' ),
			'date_min' => sanitize_text_field( $_GET['dmin'] ?? '' ),
			'date_max' => sanitize_text_field( $_GET['dmax'] ?? '' ),
		);
	}
}

