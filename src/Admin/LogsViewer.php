<?php
/**
 * Visualisation des logs
 *
 * @package GFSirenAutocomplete
 */

namespace GFSirenAutocomplete\Admin;

defined( 'ABSPATH' ) || exit;

use GFSirenAutocomplete\Core\Constants;
use GFSirenAutocomplete\Core\Logger;
use GFSirenAutocomplete\Helpers\SecurityHelper;

/**
 * Classe de visualisation des logs
 */
class LogsViewer {

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
		$this->logger = $logger;
	}

	/**
	 * Affiche la page de visualisation des logs
	 */
	public function render() {
		// Vérifier les permissions.
		if ( ! SecurityHelper::check_permissions() ) {
			wp_die( __( 'Vous n\'avez pas les permissions nécessaires.', Constants::TEXT_DOMAIN ) );
		}

		// Gérer les actions (suppression, etc.).
		$this->maybe_handle_actions();

		// Paramètres de pagination et filtrage.
		$current_page = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
		$per_page     = 20;
		$offset       = ( $current_page - 1 ) * $per_page;

		$filter_level = isset( $_GET['level'] ) ? sanitize_text_field( $_GET['level'] ) : '';
		$filter_date  = isset( $_GET['date_filter'] ) ? sanitize_text_field( $_GET['date_filter'] ) : '';

		// Préparer les arguments de récupération.
		$args = array(
			'limit'  => $per_page,
			'offset' => $offset,
			'level'  => $filter_level,
			'order'  => 'DESC',
		);

		// Appliquer les filtres de date.
		if ( 'today' === $filter_date ) {
			$args['date_from'] = date( 'Y-m-d 00:00:00' );
		} elseif ( '7days' === $filter_date ) {
			$args['date_from'] = date( 'Y-m-d H:i:s', strtotime( '-7 days' ) );
		} elseif ( '30days' === $filter_date ) {
			$args['date_from'] = date( 'Y-m-d H:i:s', strtotime( '-30 days' ) );
		}

		// Récupérer les logs.
		$logs       = $this->logger->get_logs( $args );
		$total_logs = $this->logger->count_logs( $args );
		$total_pages = ceil( $total_logs / $per_page );

		?>
		<div class="wrap gf-siren-logs">
			<h1><?php esc_html_e( 'Logs - Siren Autocomplete', Constants::TEXT_DOMAIN ); ?></h1>

			<?php settings_errors( 'gf_siren_logs_messages' ); ?>

			<!-- Filtres -->
			<form method="get" class="gf-siren-logs-filters">
				<input type="hidden" name="page" value="<?php echo esc_attr( $_GET['page'] ?? '' ); ?>" />

				<select name="level">
					<option value=""><?php esc_html_e( 'Tous les niveaux', Constants::TEXT_DOMAIN ); ?></option>
					<option value="INFO" <?php selected( $filter_level, 'INFO' ); ?>><?php esc_html_e( 'INFO', Constants::TEXT_DOMAIN ); ?></option>
					<option value="WARNING" <?php selected( $filter_level, 'WARNING' ); ?>><?php esc_html_e( 'WARNING', Constants::TEXT_DOMAIN ); ?></option>
					<option value="ERROR" <?php selected( $filter_level, 'ERROR' ); ?>><?php esc_html_e( 'ERROR', Constants::TEXT_DOMAIN ); ?></option>
					<option value="DEBUG" <?php selected( $filter_level, 'DEBUG' ); ?>><?php esc_html_e( 'DEBUG', Constants::TEXT_DOMAIN ); ?></option>
				</select>

				<select name="date_filter">
					<option value=""><?php esc_html_e( 'Toutes les dates', Constants::TEXT_DOMAIN ); ?></option>
					<option value="today" <?php selected( $filter_date, 'today' ); ?>><?php esc_html_e( 'Aujourd\'hui', Constants::TEXT_DOMAIN ); ?></option>
					<option value="7days" <?php selected( $filter_date, '7days' ); ?>><?php esc_html_e( '7 derniers jours', Constants::TEXT_DOMAIN ); ?></option>
					<option value="30days" <?php selected( $filter_date, '30days' ); ?>><?php esc_html_e( '30 derniers jours', Constants::TEXT_DOMAIN ); ?></option>
				</select>

				<button type="submit" class="button"><?php esc_html_e( 'Filtrer', Constants::TEXT_DOMAIN ); ?></button>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . Constants::ADMIN_MENU_SLUG . '-logs' ) ); ?>" class="button">
					<?php esc_html_e( 'Réinitialiser', Constants::TEXT_DOMAIN ); ?>
				</a>
			</form>

			<!-- Actions -->
			<div class="tablenav top">
				<div class="alignleft actions">
					<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=gf_siren_clear_logs' ), 'gf_siren_clear_logs' ) ); ?>" 
						class="button button-secondary" 
						onclick="return confirm('<?php esc_attr_e( 'Êtes-vous sûr de vouloir supprimer tous les logs ?', Constants::TEXT_DOMAIN ); ?>');">
						<?php esc_html_e( 'Supprimer tous les logs', Constants::TEXT_DOMAIN ); ?>
					</a>
					<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-ajax.php?action=' . Constants::AJAX_EXPORT_LOGS . '&nonce=' . SecurityHelper::create_nonce() ), 'gf_siren_export_logs' ) ); ?>" 
						class="button button-secondary">
						<?php esc_html_e( 'Exporter (CSV)', Constants::TEXT_DOMAIN ); ?>
					</a>
				</div>
			</div>

			<!-- Tableau des logs -->
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th style="width: 150px;"><?php esc_html_e( 'Date/Heure', Constants::TEXT_DOMAIN ); ?></th>
						<th style="width: 80px;"><?php esc_html_e( 'Niveau', Constants::TEXT_DOMAIN ); ?></th>
						<th><?php esc_html_e( 'Message', Constants::TEXT_DOMAIN ); ?></th>
						<th style="width: 200px;"><?php esc_html_e( 'Contexte', Constants::TEXT_DOMAIN ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $logs ) ) : ?>
						<tr>
							<td colspan="4" style="text-align:center;">
								<?php esc_html_e( 'Aucun log trouvé.', Constants::TEXT_DOMAIN ); ?>
							</td>
						</tr>
					<?php else : ?>
						<?php foreach ( $logs as $log ) : ?>
							<tr>
								<td><?php echo esc_html( $log['date'] ); ?></td>
								<td>
									<span class="gf-siren-log-level gf-siren-log-<?php echo esc_attr( strtolower( $log['level'] ) ); ?>">
										<?php echo esc_html( $log['level'] ); ?>
									</span>
								</td>
								<td><?php echo esc_html( $log['message'] ); ?></td>
								<td>
									<?php if ( ! empty( $log['context'] ) ) : ?>
										<details>
											<summary><?php esc_html_e( 'Voir le contexte', Constants::TEXT_DOMAIN ); ?></summary>
											<pre style="margin-top:5px; font-size:11px; background:#f5f5f5; padding:5px; overflow:auto;"><?php echo esc_html( $log['context'] ); ?></pre>
										</details>
									<?php else : ?>
										<em><?php esc_html_e( 'Aucun contexte', Constants::TEXT_DOMAIN ); ?></em>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>

			<!-- Pagination -->
			<?php if ( $total_pages > 1 ) : ?>
				<div class="tablenav bottom">
					<div class="tablenav-pages">
						<span class="displaying-num">
							<?php
							printf(
								/* translators: %d: nombre total de logs */
								esc_html( _n( '%d log', '%d logs', $total_logs, Constants::TEXT_DOMAIN ) ),
								number_format_i18n( $total_logs )
							);
							?>
						</span>
						<?php
						$base_url = add_query_arg(
							array(
								'page'        => $_GET['page'] ?? '',
								'level'       => $filter_level,
								'date_filter' => $filter_date,
							),
							admin_url( 'admin.php' )
						);

						echo wp_kses_post(
							paginate_links(
								array(
									'base'      => add_query_arg( 'paged', '%#%', $base_url ),
									'format'    => '',
									'current'   => $current_page,
									'total'     => $total_pages,
									'prev_text' => '‹',
									'next_text' => '›',
								)
							)
						);
						?>
					</div>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Gère les actions (suppression, etc.)
	 */
	private function maybe_handle_actions() {
		// Suppression des logs.
		if ( isset( $_GET['action'] ) && 'gf_siren_clear_logs' === $_GET['action'] ) {
			// Vérifier le nonce.
			if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'gf_siren_clear_logs' ) ) {
				add_settings_error( 'gf_siren_logs_messages', 'gf_siren_nonce_error', __( 'Erreur de sécurité.', Constants::TEXT_DOMAIN ), 'error' );
				return;
			}

			// Supprimer les logs.
			$this->logger->clear_all_logs();

			add_settings_error( 'gf_siren_logs_messages', 'gf_siren_logs_cleared', __( 'Tous les logs ont été supprimés.', Constants::TEXT_DOMAIN ), 'success' );
		}
	}
}

