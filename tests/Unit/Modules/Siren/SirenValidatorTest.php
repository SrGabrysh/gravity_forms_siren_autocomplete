<?php
/**
 * Tests unitaires pour SirenValidator
 *
 * @package GFSirenAutocomplete
 */

namespace GFSirenAutocomplete\Tests\Unit\Modules\Siren;

use PHPUnit\Framework\TestCase;
use GFSirenAutocomplete\Modules\Siren\SirenValidator;
use GFSirenAutocomplete\Core\Constants;

/**
 * Classe de tests pour SirenValidator
 */
class SirenValidatorTest extends TestCase {

	/**
	 * Instance du validator
	 *
	 * @var SirenValidator
	 */
	private $validator;

	/**
	 * Configuration avant chaque test
	 */
	protected function setUp(): void {
		$this->validator = new SirenValidator();
	}

	/**
	 * Test de clean_siret
	 */
	public function test_clean_siret() {
		$this->assertEquals( '73282932000074', $this->validator->clean_siret( '732 829 320 00074' ) );
		$this->assertEquals( '73282932000074', $this->validator->clean_siret( '732-829-320-00074' ) );
		$this->assertEquals( '73282932000074', $this->validator->clean_siret( '732.829.320.00074' ) );
	}

	/**
	 * Test de clean_siret avec valeur vide
	 */
	public function test_clean_siret_empty() {
		$this->assertEquals( '', $this->validator->clean_siret( '' ) );
	}

	/**
	 * Test de validate_siret valide
	 */
	public function test_validate_siret_valid() {
		$this->assertTrue( $this->validator->validate_siret( '73282932000074' ) );
	}

	/**
	 * Test de validate_siret invalide (longueur)
	 */
	public function test_validate_siret_invalid_length() {
		$this->assertFalse( $this->validator->validate_siret( '123' ) );
		$this->assertFalse( $this->validator->validate_siret( '123456789012345' ) );
	}

	/**
	 * Test de validate_siret invalide (caractères non numériques)
	 */
	public function test_validate_siret_invalid_chars() {
		$this->assertFalse( $this->validator->validate_siret( '7328293200007A' ) );
	}

	/**
	 * Test de validate_siret vide
	 */
	public function test_validate_siret_empty() {
		$this->assertFalse( $this->validator->validate_siret( '' ) );
	}

	/**
	 * Test de extract_siren
	 */
	public function test_extract_siren() {
		$this->assertEquals( '732829320', $this->validator->extract_siren( '73282932000074' ) );
	}

	/**
	 * Test de extract_siren avec SIRET trop court
	 */
	public function test_extract_siren_short() {
		$this->assertEquals( '', $this->validator->extract_siren( '123' ) );
	}

	/**
	 * Test de is_active avec entreprise active
	 */
	public function test_is_active_true() {
		$unite_legale = array( 'etat_administratif' => 'A' );
		$this->assertTrue( $this->validator->is_active( $unite_legale ) );
	}

	/**
	 * Test de is_active avec entreprise inactive
	 */
	public function test_is_active_false() {
		$unite_legale = array( 'etat_administratif' => 'C' );
		$this->assertFalse( $this->validator->is_active( $unite_legale ) );
	}

	/**
	 * Test de is_active avec données invalides
	 */
	public function test_is_active_invalid_data() {
		$this->assertFalse( $this->validator->is_active( 'not_an_array' ) );
	}

	/**
	 * Test de determine_entreprise_type - Personne morale
	 */
	public function test_determine_entreprise_type_personne_morale() {
		$unite_legale = array( 'denomination' => 'Test Company SARL' );
		$this->assertEquals( Constants::ENTREPRISE_TYPE_PERSONNE_MORALE, $this->validator->determine_entreprise_type( $unite_legale ) );
	}

	/**
	 * Test de determine_entreprise_type - Entrepreneur individuel
	 */
	public function test_determine_entreprise_type_entrepreneur_individuel() {
		$unite_legale = array(
			'nom'       => 'DUPONT',
			'prenom_1'  => 'Jean',
		);
		$this->assertEquals( Constants::ENTREPRISE_TYPE_ENTREPRENEUR_INDIVIDUEL, $this->validator->determine_entreprise_type( $unite_legale ) );
	}

	/**
	 * Test de determine_entreprise_type avec prenom_usuel
	 */
	public function test_determine_entreprise_type_entrepreneur_with_prenom_usuel() {
		$unite_legale = array(
			'nom'           => 'MARTIN',
			'prenom_usuel'  => 'Marie',
		);
		$this->assertEquals( Constants::ENTREPRISE_TYPE_ENTREPRENEUR_INDIVIDUEL, $this->validator->determine_entreprise_type( $unite_legale ) );
	}

	/**
	 * Test de determine_entreprise_type par catégorie juridique - EI
	 */
	public function test_determine_entreprise_type_by_category_ei() {
		$unite_legale = array( 'categorie_juridique' => '1100' );
		$this->assertEquals( Constants::ENTREPRISE_TYPE_ENTREPRENEUR_INDIVIDUEL, $this->validator->determine_entreprise_type( $unite_legale ) );
	}

	/**
	 * Test de determine_entreprise_type par catégorie juridique - PM
	 */
	public function test_determine_entreprise_type_by_category_pm() {
		$unite_legale = array( 'categorie_juridique' => '5410' );
		$this->assertEquals( Constants::ENTREPRISE_TYPE_PERSONNE_MORALE, $this->validator->determine_entreprise_type( $unite_legale ) );
	}

	/**
	 * Test de determine_entreprise_type - Type inconnu
	 */
	public function test_determine_entreprise_type_unknown() {
		$unite_legale = array();
		$this->assertEquals( Constants::ENTREPRISE_TYPE_INCONNU, $this->validator->determine_entreprise_type( $unite_legale ) );
	}

	/**
	 * Test de validate_siret_complete avec SIRET valide
	 */
	public function test_validate_siret_complete_valid() {
		$result = $this->validator->validate_siret_complete( '732 829 320 00074' );

		$this->assertTrue( $result['valid'] );
		$this->assertEquals( '73282932000074', $result['cleaned'] );
		$this->assertEquals( '', $result['message'] );
	}

	/**
	 * Test de validate_siret_complete avec SIRET invalide
	 */
	public function test_validate_siret_complete_invalid() {
		$result = $this->validator->validate_siret_complete( '123' );

		$this->assertFalse( $result['valid'] );
		$this->assertEquals( '123', $result['cleaned'] );
		$this->assertNotEmpty( $result['message'] );
	}
}

