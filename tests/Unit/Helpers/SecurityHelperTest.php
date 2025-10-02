<?php
/**
 * Tests unitaires pour SecurityHelper
 *
 * @package GFSirenAutocomplete
 */

namespace GFSirenAutocomplete\Tests\Unit\Helpers;

use PHPUnit\Framework\TestCase;
use GFSirenAutocomplete\Helpers\SecurityHelper;

/**
 * Classe de tests pour SecurityHelper
 */
class SecurityHelperTest extends TestCase {

	/**
	 * Test de sanitize_input avec différents types
	 */
	public function test_sanitize_input_text() {
		$this->assertEquals( 'Test', SecurityHelper::sanitize_input( '<script>Test</script>', 'text' ) );
	}

	/**
	 * Test de sanitize_input textarea
	 */
	public function test_sanitize_input_textarea() {
		$input = "Line 1\nLine 2";
		$result = SecurityHelper::sanitize_input( $input, 'textarea' );
		$this->assertIsString( $result );
	}

	/**
	 * Test de sanitize_input email
	 */
	public function test_sanitize_input_email() {
		$this->assertEquals( 'test@example.com', SecurityHelper::sanitize_input( 'test@example.com', 'email' ) );
	}

	/**
	 * Test de sanitize_input url
	 */
	public function test_sanitize_input_url() {
		$result = SecurityHelper::sanitize_input( 'https://example.com', 'url' );
		$this->assertStringContainsString( 'example.com', $result );
	}

	/**
	 * Test de escape_output html
	 */
	public function test_escape_output_html() {
		$this->assertEquals( '&lt;script&gt;', SecurityHelper::escape_output( '<script>', 'html' ) );
	}

	/**
	 * Test de escape_output attr
	 */
	public function test_escape_output_attr() {
		$result = SecurityHelper::escape_output( 'test"value', 'attr' );
		$this->assertIsString( $result );
	}

	/**
	 * Test de escape_output js
	 */
	public function test_escape_output_js() {
		$result = SecurityHelper::escape_output( "test'value", 'js' );
		$this->assertStringContainsString( "\\'", $result );
	}

	/**
	 * Test de validate_api_key
	 */
	public function test_validate_api_key_valid() {
		$this->assertTrue( SecurityHelper::validate_api_key( 'abcdef123456' ) );
	}

	/**
	 * Test de validate_api_key invalide (trop court)
	 */
	public function test_validate_api_key_too_short() {
		$this->assertFalse( SecurityHelper::validate_api_key( 'abc' ) );
	}

	/**
	 * Test de validate_api_key vide
	 */
	public function test_validate_api_key_empty() {
		$this->assertFalse( SecurityHelper::validate_api_key( '' ) );
	}
}

