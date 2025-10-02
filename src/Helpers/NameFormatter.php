<?php
/**
 * Formatage des noms et prénoms selon les règles françaises
 *
 * @package GFSirenAutocomplete
 */

namespace GFSirenAutocomplete\Helpers;

defined( 'ABSPATH' ) || exit;

/**
 * Classe de formatage des noms et prénoms
 * 
 * Basée sur les recommandations de l'Académie française, Le Robert, AFNOR et l'INSEE
 */
class NameFormatter {

	/**
	 * Particules françaises à mettre en minuscule (entre prénom et nom)
	 *
	 * @var array
	 */
	private static $particules_francaises = array( 'de', 'd\'', 'du', 'des' );

	/**
	 * Formate un nom ou prénom selon les règles françaises
	 *
	 * @param string $input Le nom/prénom à formater.
	 * @return array ['value' => string, 'valid' => bool, 'error' => string|null].
	 */
	public static function format( $input ) {
		if ( empty( $input ) ) {
			return array(
				'value' => '',
				'valid' => false,
				'error' => 'Le champ ne peut pas être vide.',
			);
		}

		// Étape 1 : Validation (refus des chiffres).
		if ( preg_match( '/\d/', $input ) ) {
			return array(
				'value' => $input,
				'valid' => false,
				'error' => 'Les chiffres ne sont pas autorisés dans les noms et prénoms.',
			);
		}

		// Étape 2 : Nettoyage.
		$cleaned = self::clean( $input );

		// Étape 3 : Formatage.
		$formatted = self::apply_capitalization( $cleaned );

		return array(
			'value' => $formatted,
			'valid' => true,
			'error' => null,
		);
	}

	/**
	 * Nettoie une chaîne (trim, espaces multiples, normalisation apostrophe)
	 *
	 * @param string $input Chaîne à nettoyer.
	 * @return string Chaîne nettoyée.
	 */
	private static function clean( $input ) {
		// Trim.
		$cleaned = trim( $input );

		// Remplacer les espaces multiples par un seul espace.
		$cleaned = preg_replace( '/\s+/', ' ', $cleaned );

		// Normaliser l'apostrophe ASCII vers l'apostrophe typographique.
		$cleaned = str_replace( "'", "'", $cleaned );

		return $cleaned;
	}

	/**
	 * Applique la capitalisation selon les règles françaises
	 *
	 * @param string $input Chaîne nettoyée.
	 * @return string Chaîne formatée.
	 */
	private static function apply_capitalization( $input ) {
		// Séparer par espaces.
		$words = explode( ' ', $input );
		$formatted_words = array();

		foreach ( $words as $word ) {
			$formatted_words[] = self::format_word( $word );
		}

		return implode( ' ', $formatted_words );
	}

	/**
	 * Formate un "mot" (peut contenir traits d'union et apostrophes)
	 *
	 * @param string $word Le mot à formater.
	 * @return string Le mot formaté.
	 */
	private static function format_word( $word ) {
		// Cas spécial : "de La" (particule + article).
		if ( mb_strtolower( $word, 'UTF-8' ) === 'de' ) {
			return 'de';
		}

		// Gestion des traits d'union (noms composés).
		if ( strpos( $word, '-' ) !== false ) {
			$parts = explode( '-', $word );
			$formatted_parts = array();

			foreach ( $parts as $part ) {
				$formatted_parts[] = self::format_segment( $part );
			}

			return implode( '-', $formatted_parts );
		}

		// Gestion des apostrophes (O'Connor, d'Artagnan).
		if ( strpos( $word, '\'' ) !== false ) {
			// Remplacer l'apostrophe ASCII par typographique si présente.
			$word = str_replace( '\'', '\'', $word );

			$parts = explode( '\'', $word );

			// Si c'est une particule (d', l'), la laisser en minuscule.
			if ( in_array( mb_strtolower( $parts[0], 'UTF-8' ) . '\'', self::$particules_francaises, true ) ) {
				$parts[0] = mb_strtolower( $parts[0], 'UTF-8' );
				if ( isset( $parts[1] ) ) {
					$parts[1] = self::format_segment( $parts[1] );
				}
			} else {
				// Sinon, capitaliser chaque partie.
				$formatted_parts = array();
				foreach ( $parts as $part ) {
					$formatted_parts[] = self::format_segment( $part );
				}
				return implode( '\'', $formatted_parts );
			}

			return implode( '\'', $parts );
		}

		// Cas normal : un seul segment.
		return self::format_segment( $word );
	}

	/**
	 * Formate un segment simple (sans espace, tiret, apostrophe)
	 *
	 * @param string $segment Le segment à formater.
	 * @return string Le segment formaté.
	 */
	private static function format_segment( $segment ) {
		if ( empty( $segment ) ) {
			return $segment;
		}

		$lower = mb_strtolower( $segment, 'UTF-8' );

		// Cas spécial : particules françaises simples.
		if ( in_array( $lower, array( 'de', 'du', 'des', 'la' ), true ) ) {
			// "la" après "de" reste "La" (de La Fontaine).
			if ( $lower === 'la' ) {
				return 'La';
			}
			// Autres particules : minuscule.
			return $lower;
		}

		// Capitalisation normale : première lettre majuscule, reste minuscule.
		return mb_strtoupper( mb_substr( $segment, 0, 1, 'UTF-8' ), 'UTF-8' ) . mb_substr( $lower, 1, null, 'UTF-8' );
	}

	/**
	 * Valide un nom ou prénom (vérifie l'absence de chiffres)
	 *
	 * @param string $input Le nom/prénom à valider.
	 * @return bool True si valide, false sinon.
	 */
	public static function validate( $input ) {
		if ( empty( $input ) ) {
			return false;
		}

		// Refuser les chiffres.
		if ( preg_match( '/\d/', $input ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Formate le nom complet du représentant (Nom Prénom)
	 *
	 * @param string $nom Le nom.
	 * @param string $prenom Le prénom.
	 * @return string Le nom complet formaté "Nom Prénom".
	 */
	public static function format_full_name( $nom, $prenom ) {
		$nom_formatted = self::format( $nom );
		$prenom_formatted = self::format( $prenom );

		if ( ! $nom_formatted['valid'] || ! $prenom_formatted['valid'] ) {
			return '';
		}

		// Format : "Nom Prénom" (selon spécification utilisateur).
		return $nom_formatted['value'] . ' ' . $prenom_formatted['value'];
	}
}
