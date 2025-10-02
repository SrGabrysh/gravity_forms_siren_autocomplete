<?php
/**
 * Tests unitaires pour DataHelper
 *
 * @package GFSirenAutocomplete
 */

namespace GFSirenAutocomplete\Tests\Unit\Helpers;

use PHPUnit\Framework\TestCase;
use GFSirenAutocomplete\Helpers\DataHelper;
use GFSirenAutocomplete\Core\Constants;

/**
 * Classe de tests pour DataHelper
 */
class DataHelperTest extends TestCase {

	/**
	 * Test de sanitize_siret
	 */
	public function test_sanitize_siret_removes_non_numeric() {
		$this->assertEquals( '12345678901234', DataHelper::sanitize_siret( '123 456 789 01234' ) );
		$this->assertEquals( '12345678901234', DataHelper::sanitize_siret( '123-456-789-01234' ) );
		$this->assertEquals( '12345678901234', DataHelper::sanitize_siret( '123.456.789.01234' ) );
	}

	/**
	 * Test de sanitize_siret avec valeur vide
	 */
	public function test_sanitize_siret_empty() {
		$this->assertEquals( '', DataHelper::sanitize_siret( '' ) );
		$this->assertEquals( '', DataHelper::sanitize_siret( null ) );
	}

	/**
	 * Test de format_siret
	 */
	public function test_format_siret() {
		$this->assertEquals( '732 829 320 00074', DataHelper::format_siret( '73282932000074' ) );
	}

	/**
	 * Test de format_siret avec SIRET invalide
	 */
	public function test_format_siret_invalid_length() {
		$this->assertEquals( '123', DataHelper::format_siret( '123' ) );
	}

	/**
	 * Test de format_siren
	 */
	public function test_format_siren() {
		$this->assertEquals( '732 829 320', DataHelper::format_siren( '732829320' ) );
	}

	/**
	 * Test de format_siren avec SIREN invalide
	 */
	public function test_format_siren_invalid_length() {
		$this->assertEquals( '123', DataHelper::format_siren( '123' ) );
	}

	/**
	 * Test de format_date
	 */
	public function test_format_date() {
		$result = DataHelper::format_date( '2023-05-15' );
		$this->assertNotEmpty( $result );
	}

	/**
	 * Test de format_date avec date invalide
	 */
	public function test_format_date_invalid() {
		$this->assertEquals( 'invalid-date', DataHelper::format_date( 'invalid-date' ) );
	}

	/**
	 * Test de extract_field_value
	 */
	public function test_extract_field_value() {
		$data = array(
			'unite_legale' => array(
				'denomination' => 'Test Company',
			),
		);

		$this->assertEquals( 'Test Company', DataHelper::extract_field_value( $data, 'unite_legale.denomination' ) );
	}

	/**
	 * Test de extract_field_value avec chemin inexistant
	 */
	public function test_extract_field_value_missing() {
		$data = array( 'foo' => 'bar' );
		$this->assertEquals( '', DataHelper::extract_field_value( $data, 'unite_legale.denomination' ) );
		$this->assertEquals( 'default', DataHelper::extract_field_value( $data, 'missing', 'default' ) );
	}

	/**
	 * Test de sanitize_api_key
	 */
	public function test_sanitize_api_key() {
		$this->assertEquals( 'abc123def', DataHelper::sanitize_api_key( '  abc123def  ' ) );
	}

	/**
	 * Test de mask_api_key
	 */
	public function test_mask_api_key() {
		// Pour une clé de 14 caractères, on masque 10 caractères et on garde les 4 derniers.
		$this->assertEquals( '**********ef45', DataHelper::mask_api_key( 'abcdef1234ef45' ) );
	}

	/**
	 * Test de mask_api_key avec clé courte
	 */
	public function test_mask_api_key_short() {
		$this->assertEquals( '***', DataHelper::mask_api_key( 'abc' ) );
	}

	/**
	 * Test de to_json et from_json
	 */
	public function test_json_encoding() {
		$data = array( 'key' => 'value', 'number' => 42 );
		$json = DataHelper::to_json( $data );

		$this->assertIsString( $json );
		$this->assertEquals( $data, DataHelper::from_json( $json ) );
	}

	/**
	 * Test de is_empty
	 */
	public function test_is_empty() {
		$this->assertTrue( DataHelper::is_empty( '' ) );
		$this->assertTrue( DataHelper::is_empty( '   ' ) );
		$this->assertTrue( DataHelper::is_empty( null ) );
		$this->assertFalse( DataHelper::is_empty( 'test' ) );
		$this->assertFalse( DataHelper::is_empty( '0' ) );
	}

	/**
	 * Test de sanitize_array
	 */
	public function test_sanitize_array() {
		$input = array(
			'key1' => '<script>alert("xss")</script>',
			'key2' => array( 'nested' => '<b>test</b>' ),
		);

		$result = DataHelper::sanitize_array( $input );

		$this->assertIsArray( $result );
		$this->assertStringNotContainsString( '<script>', $result['key1'] );
	}
}

