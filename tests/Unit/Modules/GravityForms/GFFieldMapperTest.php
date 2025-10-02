<?php
/**
 * Tests unitaires pour GFFieldMapper
 *
 * @package GFSirenAutocomplete
 */

namespace GFSirenAutocomplete\Tests\Unit\Modules\GravityForms;

use PHPUnit\Framework\TestCase;
use GFSirenAutocomplete\Modules\GravityForms\GFFieldMapper;

/**
 * Classe de test pour GFFieldMapper
 */
class GFFieldMapperTest extends TestCase {

	/**
	 * Instance du mapper
	 *
	 * @var GFFieldMapper
	 */
	private $mapper;

	/**
	 * Configuration Setup
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->mapper = new GFFieldMapper();
	}

	/**
	 * Test du mapping des champs simples
	 */
	public function test_map_data_to_fields_simple_fields() {
		$company_data = array(
			'siret'        => '89498206500019',
			'denomination' => 'ESTHESUD',
			'unite_legale' => array(
				'denomination' => 'ESTHESUD',
			),
			'etablissement_siege' => array(
				'numero_voie'      => '13',
				'type_voie'        => 'Avenue',
				'libelle_voie'     => 'des Palmiers',
				'code_postal'      => '66700',
				'libelle_commune'  => 'Argelès-sur-Mer',
			),
		);

		$mapping = array(
			'siret'        => '1',
			'denomination' => '12',
			'adresse'      => '8.1',
			'code_postal'  => '8.5',
			'ville'        => '8.3',
		);

		$mentions_legales = 'ESTHESUD, dont le siège social...';

		$result = $this->mapper->map_data_to_fields( $company_data, $mapping, $mentions_legales );

		// Vérifier les champs simples
		$this->assertArrayHasKey( '1', $result );
		$this->assertEquals( '89498206500019', $result['1'] );

		$this->assertArrayHasKey( '12', $result );
		$this->assertEquals( 'ESTHESUD', $result['12'] );
	}

	/**
	 * Test du mapping des champs composites Address
	 */
	public function test_map_data_to_fields_address_composite() {
		$company_data = array(
			'siret'        => '89498206500019',
			'denomination' => 'ESTHESUD',
			'unite_legale' => array(
				'denomination' => 'ESTHESUD',
			),
			'etablissement_siege' => array(
				'numero_voie'      => '13',
				'type_voie'        => 'Avenue',
				'libelle_voie'     => 'des Palmiers',
				'code_postal'      => '66700',
				'libelle_commune'  => 'Argelès-sur-Mer',
			),
		);

		$mapping = array(
			'adresse'      => '8.1',  // Champ Address - Rue
			'code_postal'  => '8.5',  // Champ Address - Code postal
			'ville'        => '8.3',  // Champ Address - Ville
		);

		$result = $this->mapper->map_data_to_fields( $company_data, $mapping, '' );

		// Vérifier les sous-champs Address
		$this->assertArrayHasKey( '8.1', $result );
		$this->assertStringContainsString( '13', $result['8.1'] );
		$this->assertStringContainsString( 'Avenue', $result['8.1'] );
		$this->assertStringContainsString( 'des Palmiers', $result['8.1'] );

		$this->assertArrayHasKey( '8.5', $result );
		$this->assertEquals( '66700', $result['8.5'] );

		$this->assertArrayHasKey( '8.3', $result );
		$this->assertEquals( 'Argelès-sur-Mer', $result['8.3'] );
	}

	/**
	 * Test du mapping avec mentions légales
	 */
	public function test_map_data_to_fields_with_mentions_legales() {
		$company_data = array(
			'siret'        => '89498206500019',
			'denomination' => 'ESTHESUD',
			'unite_legale' => array(
				'denomination' => 'ESTHESUD',
			),
			'etablissement_siege' => array(),
		);

		$mapping = array(
			'siret'            => '1',
			'denomination'     => '12',
			'mentions_legales' => '13',
		);

		$mentions_legales = 'ESTHESUD, dont le siège social est situé au 13 Avenue des Palmiers...';

		$result = $this->mapper->map_data_to_fields( $company_data, $mapping, $mentions_legales );

		// Vérifier les mentions légales
		$this->assertArrayHasKey( '13', $result );
		$this->assertEquals( $mentions_legales, $result['13'] );
	}

	/**
	 * Test de la récupération du mapping pour un formulaire
	 */
	public function test_get_field_mapping_for_form_1() {
		// Simuler les options WordPress
		$test_mapping = array(
			'siret'        => '1',
			'denomination' => '12',
			'adresse'      => '8.1',
			'ville'        => '8.3',
			'code_postal'  => '8.5',
			'prenom'       => '7.3',
			'nom'          => '7.6',
		);

		// Simuler save
		$this->mapper->save_field_mapping( 1, $test_mapping );

		// Récupérer
		$mapping = $this->mapper->get_field_mapping( 1 );

		$this->assertIsArray( $mapping );
		$this->assertArrayHasKey( 'siret', $mapping );
		$this->assertEquals( '1', $mapping['siret'] );

		// Vérifier les champs composites
		$this->assertArrayHasKey( 'adresse', $mapping );
		$this->assertEquals( '8.1', $mapping['adresse'] );

		$this->assertArrayHasKey( 'prenom', $mapping );
		$this->assertEquals( '7.3', $mapping['prenom'] );

		$this->assertArrayHasKey( 'nom', $mapping );
		$this->assertEquals( '7.6', $mapping['nom'] );
	}

	/**
	 * Test de la récupération des données du représentant
	 */
	public function test_get_representant_data_from_name_field() {
		// Simuler le mapping avec champs Name composites
		$mapping = array(
			'prenom' => '7.3',
			'nom'    => '7.6',
		);

		$this->mapper->save_field_mapping( 1, $mapping );

		// Simuler une entrée Gravity Forms
		$entry = array(
			'7.3' => 'Gabriel',
			'7.6' => 'Duteurtre',
		);

		$representant_data = $this->mapper->get_representant_data( 1, $entry );

		$this->assertIsArray( $representant_data );
		$this->assertArrayHasKey( 'prenom', $representant_data );
		$this->assertArrayHasKey( 'nom', $representant_data );
		$this->assertEquals( 'Gabriel', $representant_data['prenom'] );
		$this->assertEquals( 'Duteurtre', $representant_data['nom'] );
	}

	/**
	 * Test de form_has_mapping
	 */
	public function test_form_has_mapping() {
		// Formulaire sans mapping
		$this->assertFalse( $this->mapper->form_has_mapping( 999 ) );

		// Ajouter un mapping
		$mapping = array( 'siret' => '1' );
		$this->mapper->save_field_mapping( 1, $mapping );

		// Formulaire avec mapping
		$this->assertTrue( $this->mapper->form_has_mapping( 1 ) );
	}

	/**
	 * Test de get_siret_field_id
	 */
	public function test_get_siret_field_id() {
		// Ajouter un mapping
		$mapping = array( 'siret' => '1' );
		$this->mapper->save_field_mapping( 1, $mapping );

		$siret_field_id = $this->mapper->get_siret_field_id( 1 );

		$this->assertEquals( '1', $siret_field_id );
	}

	/**
	 * Test du mapping complet avec la configuration Form ID: 1
	 */
	public function test_complete_mapping_for_form_1() {
		// Configuration complète du Form ID: 1
		$complete_mapping = array(
			'form_id'          => 1,
			'siret'            => '1',
			'denomination'     => '12',
			'adresse'          => '8.1',
			'ville'            => '8.3',
			'code_postal'      => '8.5',
			'pays'             => '8.6',
			'mentions_legales' => '13',
			'prenom'           => '7.3',
			'nom'              => '7.6',
		);

		$this->mapper->save_field_mapping( 1, $complete_mapping );

		// Données de test complètes
		$company_data = array(
			'siret'        => '89498206500019',
			'denomination' => 'ESTHESUD',
			'unite_legale' => array(
				'denomination' => 'ESTHESUD',
			),
			'etablissement_siege' => array(
				'numero_voie'      => '13',
				'type_voie'        => 'Avenue',
				'libelle_voie'     => 'des Palmiers',
				'code_postal'      => '66700',
				'libelle_commune'  => 'Argelès-sur-Mer',
			),
		);

		$mentions = 'ESTHESUD, dont le siège social est situé au 13 Avenue des Palmiers, 66700 Argelès-sur-Mer...';

		$result = $this->mapper->map_data_to_fields( $company_data, $complete_mapping, $mentions );

		// Vérifier tous les champs
		$this->assertArrayHasKey( '1', $result );      // SIRET
		$this->assertArrayHasKey( '12', $result );     // Nom entreprise
		$this->assertArrayHasKey( '8.1', $result );    // Adresse rue
		$this->assertArrayHasKey( '8.3', $result );    // Ville
		$this->assertArrayHasKey( '8.5', $result );    // Code postal
		$this->assertArrayHasKey( '13', $result );     // Mentions légales

		// Vérifier les valeurs
		$this->assertEquals( '89498206500019', $result['1'] );
		$this->assertEquals( 'ESTHESUD', $result['12'] );
		$this->assertEquals( '66700', $result['8.5'] );
		$this->assertEquals( 'Argelès-sur-Mer', $result['8.3'] );
		$this->assertEquals( $mentions, $result['13'] );
	}

	/**
	 * Test avec des champs manquants dans le mapping
	 */
	public function test_map_data_with_missing_mapping_fields() {
		$company_data = array(
			'siret'        => '89498206500019',
			'denomination' => 'ESTHESUD',
			'unite_legale' => array(
				'denomination' => 'ESTHESUD',
			),
			'etablissement_siege' => array(
				'code_postal'      => '66700',
				'libelle_commune'  => 'Argelès-sur-Mer',
			),
		);

		// Mapping incomplet (manque ville)
		$mapping = array(
			'siret'        => '1',
			'denomination' => '12',
			'code_postal'  => '8.5',
			// 'ville' est manquant
		);

		$result = $this->mapper->map_data_to_fields( $company_data, $mapping, '' );

		// Les champs mappés doivent être présents
		$this->assertArrayHasKey( '1', $result );
		$this->assertArrayHasKey( '12', $result );
		$this->assertArrayHasKey( '8.5', $result );

		// Le champ ville ne doit pas être dans le résultat
		$this->assertArrayNotHasKey( '8.3', $result );
	}
}

