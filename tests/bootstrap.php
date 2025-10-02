<?php
/**
 * Bootstrap pour les tests unitaires
 *
 * @package GFSirenAutocomplete
 */

// Définir ABSPATH pour les tests.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/../' );
}

// Charger Composer autoload.
require_once __DIR__ . '/../vendor/autoload.php';

// Charger la clé API Siren depuis les variables d'environnement pour les tests d'intégration.
if ( ! defined( 'GF_SIREN_API_KEY' ) ) {
	$api_key_from_env = getenv( 'GF_SIREN_API_KEY' );
	if ( ! empty( $api_key_from_env ) ) {
		define( 'GF_SIREN_API_KEY', $api_key_from_env );
	}
}

// Simuler les fonctions WordPress essentielles pour les tests unitaires.
if ( ! function_exists( 'esc_html' ) ) {
	function esc_html( $text ) {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_attr' ) ) {
	function esc_attr( $text ) {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_url' ) ) {
	function esc_url( $url ) {
		return filter_var( $url, FILTER_SANITIZE_URL );
	}
}

if ( ! function_exists( 'esc_url_raw' ) ) {
	function esc_url_raw( $url ) {
		return filter_var( $url, FILTER_SANITIZE_URL );
	}
}

if ( ! function_exists( 'esc_js' ) ) {
	function esc_js( $text ) {
		return addslashes( $text );
	}
}

if ( ! function_exists( 'esc_textarea' ) ) {
	function esc_textarea( $text ) {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( $str ) {
		return trim( strip_tags( $str ) );
	}
}

if ( ! function_exists( 'sanitize_textarea_field' ) ) {
	function sanitize_textarea_field( $str ) {
		return trim( strip_tags( $str ) );
	}
}

if ( ! function_exists( 'sanitize_email' ) ) {
	function sanitize_email( $email ) {
		return filter_var( $email, FILTER_SANITIZE_EMAIL );
	}
}

if ( ! function_exists( 'sanitize_key' ) ) {
	function sanitize_key( $key ) {
		return strtolower( preg_replace( '/[^a-z0-9_\-]/', '', $key ) );
	}
}

if ( ! function_exists( 'wp_kses_post' ) ) {
	function wp_kses_post( $data ) {
		return strip_tags( $data, '<p><a><strong><em><br><ul><li><ol>' );
	}
}

if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = 'default' ) {
		return $text;
	}
}

if ( ! function_exists( 'esc_html__' ) ) {
	function esc_html__( $text, $domain = 'default' ) {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( '_n' ) ) {
	function _n( $single, $plural, $number, $domain = 'default' ) {
		return $number === 1 ? $single : $plural;
	}
}

if ( ! function_exists( 'date_i18n' ) ) {
	function date_i18n( $format, $timestamp = null ) {
		return date( $format, $timestamp ?? time() );
	}
}

if ( ! function_exists( 'wp_json_encode' ) ) {
	function wp_json_encode( $data, $options = 0, $depth = 512 ) {
		return json_encode( $data, $options, $depth );
	}
}

// Fonctions pour le cache WordPress (Transients API) - Simulation simple.
if ( ! function_exists( 'get_transient' ) ) {
	function get_transient( $transient ) {
		global $_test_transients;
		if ( ! isset( $_test_transients ) ) {
			$_test_transients = array();
		}
		
		if ( isset( $_test_transients[ $transient ] ) ) {
			$data = $_test_transients[ $transient ];
			if ( $data['expiration'] > time() ) {
				return $data['value'];
			}
			unset( $_test_transients[ $transient ] );
		}
		return false;
	}
}

if ( ! function_exists( 'set_transient' ) ) {
	function set_transient( $transient, $value, $expiration = 0 ) {
		global $_test_transients;
		if ( ! isset( $_test_transients ) ) {
			$_test_transients = array();
		}
		
		$_test_transients[ $transient ] = array(
			'value'      => $value,
			'expiration' => time() + $expiration,
		);
		return true;
	}
}

if ( ! function_exists( 'delete_transient' ) ) {
	function delete_transient( $transient ) {
		global $_test_transients;
		if ( isset( $_test_transients[ $transient ] ) ) {
			unset( $_test_transients[ $transient ] );
			return true;
		}
		return false;
	}
}

if ( ! function_exists( 'get_option' ) ) {
	function get_option( $option, $default = false ) {
		global $_test_options;
		if ( ! isset( $_test_options ) ) {
			$_test_options = array();
		}
		return isset( $_test_options[ $option ] ) ? $_test_options[ $option ] : $default;
	}
}

if ( ! function_exists( 'update_option' ) ) {
	function update_option( $option, $value, $autoload = null ) {
		global $_test_options;
		if ( ! isset( $_test_options ) ) {
			$_test_options = array();
		}
		$_test_options[ $option ] = $value;
		return true;
	}
}

if ( ! function_exists( 'delete_option' ) ) {
	function delete_option( $option ) {
		global $_test_options;
		if ( isset( $_test_options[ $option ] ) ) {
			unset( $_test_options[ $option ] );
			return true;
		}
		return false;
	}
}

if ( ! function_exists( 'current_time' ) ) {
	function current_time( $type, $gmt = 0 ) {
		if ( 'mysql' === $type ) {
			return gmdate( 'Y-m-d H:i:s' );
		}
		return time();
	}
}

if ( ! function_exists( 'get_current_user_id' ) ) {
	function get_current_user_id() {
		return 0;
	}
}

echo "✓ Bootstrap chargé pour les tests unitaires\n";

