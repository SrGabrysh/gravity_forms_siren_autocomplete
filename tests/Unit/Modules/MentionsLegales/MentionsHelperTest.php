<?php
/**
 * Tests unitaires pour MentionsHelper
 *
 * @package GFSirenAutocomplete
 */

namespace GFSirenAutocomplete\Tests\Unit\Modules\MentionsLegales;

use PHPUnit\Framework\TestCase;
use GFSirenAutocomplete\Modules\MentionsLegales\MentionsHelper;

/**
 * Classe de tests pour MentionsHelper
 */
class MentionsHelperTest extends TestCase {

	/**
	 * Test de get_forme_juridique
	 */
	public function test_get_forme_juridique() {
		$this->assertEquals( 'SAS', MentionsHelper::get_forme_juridique( '5710' ) );
		$this->assertEquals( 'SARL', MentionsHelper::get_forme_juridique( '5410' ) );
		$this->assertEquals( 'SA', MentionsHelper::get_forme_juridique( '5499' ) );
		$this->assertEquals( 'EURL', MentionsHelper::get_forme_juridique( '5422' ) );
	}

	/**
	 * Test de get_forme_juridique avec code inconnu
	 */
	public function test_get_forme_juridique_unknown() {
		$this->assertEquals( 'Autre forme juridique', MentionsHelper::get_forme_juridique( '9999' ) );
	}

	/**
	 * Test de get_adresse_complete
	 */
	public function test_get_adresse_complete() {
		$etablissement = array(
			'numero_voie'      => '10',
			'type_voie'        => 'RUE',
			'libelle_voie'     => 'DE LA PAIX',
			'code_postal'      => '75001',
			'libelle_commune'  => 'PARIS',
		);

		$result = MentionsHelper::get_adresse_complete( $etablissement );

		$this->assertStringContainsString( '10', $result );
		$this->assertStringContainsString( 'RUE', $result );
		$this->assertStringContainsString( 'DE LA PAIX', $result );
		$this->assertStringContainsString( '75001', $result );
		$this->assertStringContainsString( 'PARIS', $result );
	}

	/**
	 * Test de get_adresse_complete avec complément
	 */
	public function test_get_adresse_complete_with_complement() {
		$etablissement = array(
			'complement_adresse' => 'BATIMENT A',
			'numero_voie'        => '10',
			'type_voie'          => 'RUE',
			'libelle_voie'       => 'DE LA PAIX',
			'code_postal'        => '75001',
			'libelle_commune'    => 'PARIS',
		);

		$result = MentionsHelper::get_adresse_complete( $etablissement );

		$this->assertStringContainsString( 'BATIMENT A', $result );
	}

	/**
	 * Test de get_adresse_sans_cp_ville
	 */
	public function test_get_adresse_sans_cp_ville() {
		$etablissement = array(
			'numero_voie'      => '10',
			'type_voie'        => 'RUE',
			'libelle_voie'     => 'DE LA PAIX',
			'code_postal'      => '75001',
			'libelle_commune'  => 'PARIS',
		);

		$result = MentionsHelper::get_adresse_sans_cp_ville( $etablissement );

		$this->assertStringContainsString( '10', $result );
		$this->assertStringContainsString( 'RUE', $result );
		$this->assertStringNotContainsString( '75001', $result );
		$this->assertStringNotContainsString( 'PARIS', $result );
	}

	/**
	 * Test de get_code_postal
	 */
	public function test_get_code_postal() {
		$etablissement = array( 'code_postal' => '75001' );
		$this->assertEquals( '75001', MentionsHelper::get_code_postal( $etablissement ) );
	}

	/**
	 * Test de get_ville
	 */
	public function test_get_ville() {
		$etablissement = array( 'libelle_commune' => 'PARIS' );
		$this->assertEquals( 'PARIS', MentionsHelper::get_ville( $etablissement ) );
	}

	/**
	 * Test de get_titre_representant
	 */
	public function test_get_titre_representant() {
		$this->assertEquals( 'Gérant', MentionsHelper::get_titre_representant( 'SARL' ) );
		$this->assertEquals( 'Gérant', MentionsHelper::get_titre_representant( 'EURL' ) );
		$this->assertEquals( 'Président', MentionsHelper::get_titre_representant( 'SAS' ) );
		$this->assertEquals( 'Président', MentionsHelper::get_titre_representant( 'SASU' ) );
		$this->assertEquals( 'Directeur Général', MentionsHelper::get_titre_representant( 'SA' ) );
	}

	/**
	 * Test de get_titre_representant avec forme inconnue
	 */
	public function test_get_titre_representant_unknown() {
		$this->assertEquals( '{TITRE}', MentionsHelper::get_titre_representant( 'UNKNOWN' ) );
	}

	/**
	 * Test de is_societe_capital
	 */
	public function test_is_societe_capital() {
		$this->assertTrue( MentionsHelper::is_societe_capital( 'SARL' ) );
		$this->assertTrue( MentionsHelper::is_societe_capital( 'SAS' ) );
		$this->assertTrue( MentionsHelper::is_societe_capital( 'SA' ) );
		$this->assertFalse( MentionsHelper::is_societe_capital( 'SCI' ) );
		$this->assertFalse( MentionsHelper::is_societe_capital( 'Association' ) );
	}

	/**
	 * Test de get_enseigne
	 */
	public function test_get_enseigne() {
		$etablissement = array( 'enseigne_1' => 'Mon Enseigne' );
		$this->assertEquals( 'Mon Enseigne', MentionsHelper::get_enseigne( $etablissement ) );
	}

	/**
	 * Test de get_enseigne avec denomination_usuelle
	 */
	public function test_get_enseigne_fallback() {
		$etablissement = array( 'denomination_usuelle' => 'Ma Dénomination' );
		$this->assertEquals( 'Ma Dénomination', MentionsHelper::get_enseigne( $etablissement ) );
	}

	/**
	 * Test de get_enseigne vide
	 */
	public function test_get_enseigne_empty() {
		$etablissement = array();
		$this->assertEquals( '', MentionsHelper::get_enseigne( $etablissement ) );
	}
}

