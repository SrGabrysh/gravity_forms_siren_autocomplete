<?php
/**
 * Constantes du plugin Gravity Forms Siren Autocomplete
 *
 * @package GFSirenAutocomplete
 */

namespace GFSirenAutocomplete\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Classe de constantes centralisées
 */
class Constants {

	/**
	 * Longueur d'un numéro SIRET (14 chiffres)
	 */
	const SIRET_LENGTH = 14;

	/**
	 * Longueur d'un numéro SIREN (9 chiffres)
	 */
	const SIREN_LENGTH = 9;

	/**
	 * Timeout des requêtes API en secondes
	 */
	const API_TIMEOUT = 10;

	/**
	 * Durée du cache en secondes (24 heures)
	 */
	const CACHE_DURATION = 86400;

	/**
	 * Nombre maximum de tentatives pour les appels API
	 */
	const MAX_RETRY_ATTEMPTS = 3;

	/**
	 * Délai entre les tentatives en secondes
	 */
	const RETRY_WAIT_SECONDS = 2;

	/**
	 * URL de base de l'API Siren
	 */
	const API_BASE_URL = 'https://data.siren-api.fr';

	/**
	 * Endpoint pour les unités légales
	 */
	const ENDPOINT_UNITE_LEGALE = '/v3/unites_legales';

	/**
	 * Endpoint pour les établissements
	 */
	const ENDPOINT_ETABLISSEMENT = '/v3/etablissements';

	/**
	 * Nom de la constante pour la clé API dans wp-config.php
	 */
	const API_KEY_CONSTANT = 'GF_SIREN_API_KEY';

	/**
	 * Nom de l'option WordPress pour la clé API
	 */
	const API_KEY_OPTION = 'gf_siren_api_key';

	/**
	 * Nom de l'option WordPress pour les paramètres
	 */
	const SETTINGS_OPTION = 'gf_siren_settings';

	/**
	 * Préfixe pour les clés de cache
	 */
	const CACHE_PREFIX = 'gf_siren_data_';

	/**
	 * Nom de la table des logs
	 */
	const LOGS_TABLE = 'gf_siren_logs';

	/**
	 * Durée de conservation des logs en jours
	 */
	const LOGS_RETENTION_DAYS = 90;

	/**
	 * Nombre maximum d'entrées de logs
	 */
	const MAX_LOG_ENTRIES = 1000;

	/**
	 * Types d'entreprise
	 */
	const ENTREPRISE_TYPE_PERSONNE_MORALE = 'PERSONNE_MORALE';
	const ENTREPRISE_TYPE_ENTREPRENEUR_INDIVIDUEL = 'ENTREPRENEUR_INDIVIDUEL';
	const ENTREPRISE_TYPE_INCONNU = 'INCONNU';

	/**
	 * Niveaux de logs
	 */
	const LOG_LEVEL_DEBUG = 'DEBUG';
	const LOG_LEVEL_INFO = 'INFO';
	const LOG_LEVEL_WARNING = 'WARNING';
	const LOG_LEVEL_ERROR = 'ERROR';

	/**
	 * Actions AJAX
	 */
	const AJAX_VERIFY_SIRET = 'gf_siren_verify';
	const AJAX_TEST_API = 'gf_siren_test_api';
	const AJAX_CLEAR_CACHE = 'gf_siren_clear_cache';
	const AJAX_GET_LOGS = 'gf_siren_get_logs';
	const AJAX_EXPORT_LOGS = 'gf_siren_export_logs';

	/**
	 * Nonce pour les requêtes AJAX
	 */
	const NONCE_ACTION = 'gf_siren_verify_nonce';

	/**
	 * Capability requise pour l'administration
	 */
	const ADMIN_CAPABILITY = 'manage_options';

	/**
	 * Slug du menu admin
	 */
	const ADMIN_MENU_SLUG = 'gf-siren-autocomplete';

	/**
	 * Text domain pour la traduction
	 */
	const TEXT_DOMAIN = 'gravity-forms-siren-autocomplete';
}

