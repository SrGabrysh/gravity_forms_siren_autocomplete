<?php
/**
 * Tests unitaires pour MentionsFormatter
 *
 * @package GFSirenAutocomplete
 */

namespace GFSirenAutocomplete\Tests\Unit\Modules\MentionsLegales;

use PHPUnit\Framework\TestCase;
use GFSirenAutocomplete\Modules\MentionsLegales\MentionsFormatter;

/**
 * Classe de tests pour MentionsFormatter
 */
class MentionsFormatterTest extends TestCase {

	/**
	 * Instance du formatter
	 *
	 * @var MentionsFormatter
	 */
	private $formatter;

	/**
	 * Données d'entreprise de test
	 *
	 * @var array
	 */
	private $company_data;

	/**
	 * Configuration avant chaque test
	 */
	protected function setUp(): void {
		$this->formatter = new MentionsFormatter();

		$this->company_data = array(
			'siret' => '73282932000074',
			'siren' => '732829320',
			'unite_legale' => array(
				'denomination'          => 'TEST COMPANY SARL',
				'siren'                 => '732829320',
				'categorie_juridique'   => '5410',
			),
			'etablissement_siege' => array(
				'numero_voie'      => '10',
				'type_voie'        => 'RUE',
				'libelle_voie'     => 'DE LA PAIX',
				'code_postal'      => '75001',
				'libelle_commune'  => 'PARIS',
			),
		);
	}

	/**
	 * Test de format_societe_capital pour SARL
	 */
	public function test_format_societe_capital_sarl() {
		$representant = array(
			'prenom' => 'Jean',
			'nom'    => 'DUPONT',
		);

		$result = $this->formatter->format_societe_capital( $this->company_data, 'SARL', $representant );

		$this->assertStringContainsString( 'TEST COMPANY SARL', $result );
		$this->assertStringContainsString( '732 829 320', $result );
		$this->assertStringContainsString( 'Jean DUPONT', $result );
		$this->assertStringContainsString( 'agissant et ayant les pouvoirs nécessaires', $result );
		$this->assertStringContainsString( 'PARIS', $result );
	}

	/**
	 * Test de format_societe_capital pour SAS
	 */
	public function test_format_societe_capital_sas() {
		$representant = array(
			'prenom' => 'Marie',
			'nom'    => 'MARTIN',
		);

		$result = $this->formatter->format_societe_capital( $this->company_data, 'SAS', $representant );

		$this->assertStringContainsString( 'agissant et ayant les pouvoirs nécessaires', $result );
		$this->assertStringContainsString( 'Marie MARTIN', $result );
	}

	/**
	 * Test de format_societe_capital sans représentant
	 */
	public function test_format_societe_capital_no_representant() {
		$result = $this->formatter->format_societe_capital( $this->company_data, 'SARL', array() );

		$this->assertStringContainsString( '{REPRESENTANT}', $result );
	}

	/**
	 * Test de format_societe_capital avec enseigne
	 */
	public function test_format_societe_capital_with_enseigne() {
		$this->company_data['etablissement_siege']['enseigne_1'] = 'MON MAGASIN';

		$result = $this->formatter->format_societe_capital( $this->company_data, 'SARL', array() );

		$this->assertStringContainsString( 'MON MAGASIN', $result );
	}

	/**
	 * Test de format_personne_morale
	 */
	public function test_format_personne_morale() {
		$result = $this->formatter->format_personne_morale( $this->company_data );

		$this->assertStringContainsString( 'TEST COMPANY SARL', $result );
		$this->assertStringContainsString( '732 829 320', $result );
		$this->assertStringContainsString( 'PARIS', $result );
		$this->assertStringNotContainsString( 'représentée par', $result );
	}

	/**
	 * Test de format_entrepreneur_individuel
	 */
	public function test_format_entrepreneur_individuel() {
		$ei_data = array(
			'siret' => '12345678901234',
			'unite_legale' => array(
				'nom'           => 'DUPONT',
				'prenom_1'      => 'Jean',
			),
			'etablissement' => array(
				'numero_voie'      => '5',
				'type_voie'        => 'AVENUE',
				'libelle_voie'     => 'DES CHAMPS',
				'code_postal'      => '69000',
				'libelle_commune'  => 'LYON',
				'siret'            => '12345678901234',
			),
		);

		$result = $this->formatter->format_entrepreneur_individuel( $ei_data );

		$this->assertStringContainsString( 'DUPONT Jean', $result );
		$this->assertStringContainsString( '123 456 789 01234', $result );
		$this->assertStringContainsString( 'Entrepreneur individuel', $result );
	}

	/**
	 * Test de format_entrepreneur_individuel avec prenom_usuel
	 */
	public function test_format_entrepreneur_individuel_prenom_usuel() {
		$ei_data = array(
			'siret' => '12345678901234',
			'unite_legale' => array(
				'nom'           => 'MARTIN',
				'prenom_usuel'  => 'Marie',
				'prenom_1'      => 'Marie-Claude',
			),
			'etablissement' => array(
				'numero_voie'      => '5',
				'type_voie'        => 'AVENUE',
				'libelle_voie'     => 'DES CHAMPS',
				'code_postal'      => '69000',
				'libelle_commune'  => 'LYON',
				'siret'            => '12345678901234',
			),
		);

		$result = $this->formatter->format_entrepreneur_individuel( $ei_data );

		$this->assertStringContainsString( 'MARTIN Marie', $result );
	}

	/**
	 * Test de format_entrepreneur_individuel avec enseigne
	 */
	public function test_format_entrepreneur_individuel_with_enseigne() {
		$ei_data = array(
			'siret' => '12345678901234',
			'unite_legale' => array(
				'nom'      => 'DUPONT',
				'prenom_1' => 'Jean',
			),
			'etablissement' => array(
				'numero_voie'       => '5',
				'type_voie'         => 'AVENUE',
				'libelle_voie'      => 'DES CHAMPS',
				'code_postal'       => '69000',
				'libelle_commune'   => 'LYON',
				'siret'             => '12345678901234',
				'enseigne_1'        => 'PLOMBERIE DUPONT',
			),
		);

		$result = $this->formatter->format_entrepreneur_individuel( $ei_data );

		$this->assertStringContainsString( 'PLOMBERIE DUPONT', $result );
	}

	/**
	 * Test de format_fallback
	 */
	public function test_format_fallback() {
		$result = $this->formatter->format_fallback( $this->company_data );

		$this->assertStringContainsString( 'TEST COMPANY SARL', $result );
		$this->assertStringContainsString( '732 829 320 00074', $result );
	}

	/**
	 * Test de format_fallback sans denomination
	 */
	public function test_format_fallback_no_denomination() {
		$data = array(
			'siret' => '12345678901234',
			'unite_legale' => array(
				'nom'      => 'DUPONT',
				'prenom_1' => 'Jean',
			),
			'etablissement_siege' => array(
				'numero_voie'      => '5',
				'libelle_commune'  => 'LYON',
			),
		);

		$result = $this->formatter->format_fallback( $data );

		$this->assertStringContainsString( 'Jean DUPONT', $result );
	}
}

