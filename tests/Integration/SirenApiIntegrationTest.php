<?php
/**
 * Test d'intégration API Siren
 * 
 * Ce test valide toute la chaîne de traitement avec un appel réel à l'API Siren.
 * ATTENTION : Nécessite une clé API valide et une connexion Internet.
 *
 * @package GFSirenAutocomplete
 * @group integration
 */

namespace GFSirenAutocomplete\Tests\Integration;

use PHPUnit\Framework\TestCase;
use GFSirenAutocomplete\Modules\Siren\SirenValidator;
use GFSirenAutocomplete\Modules\Siren\SirenClient;
use GFSirenAutocomplete\Modules\Siren\SirenCache;
use GFSirenAutocomplete\Modules\Siren\SirenManager;
use GFSirenAutocomplete\Modules\MentionsLegales\MentionsFormatter;
use GFSirenAutocomplete\Modules\MentionsLegales\MentionsHelper;
use GFSirenAutocomplete\Core\Constants;

/**
 * Test d'intégration pour l'API Siren
 */
class SirenApiIntegrationTest extends TestCase {

	/**
	 * SIRET de test (entreprise réelle)
	 */
	private const TEST_SIRET = '89498206500019';

	/**
	 * Instance du gestionnaire Siren
	 *
	 * @var SirenManager
	 */
	private $manager;

	/**
	 * Instance du formatter de mentions
	 *
	 * @var MentionsFormatter
	 */
	private $formatter;

	/**
	 * Configuration avant chaque test
	 */
	protected function setUp(): void {
		// Vérifier que la clé API est définie
		$this->checkApiKey();

		$validator = new SirenValidator();
		$client    = new SirenClient();
		$cache     = new SirenCache();

		$this->manager   = new SirenManager( $validator, $client, $cache );
		$this->formatter = new MentionsFormatter();
	}

	/**
	 * Vérifie que la clé API est disponible
	 */
	private function checkApiKey() {
		$api_key = $this->getApiKey();
		
		if ( empty( $api_key ) ) {
			$this->markTestSkipped(
				'Clé API Siren non trouvée. ' .
				'Définissez GF_SIREN_API_KEY dans les variables d\'environnement ou créez un fichier .env.test'
			);
		}
	}

	/**
	 * Récupère la clé API depuis l'environnement
	 *
	 * @return string|null
	 */
	private function getApiKey() {
		// Essayer depuis variable d'environnement
		$api_key = getenv( 'GF_SIREN_API_KEY' );
		if ( ! empty( $api_key ) ) {
			return $api_key;
		}

		// Essayer depuis constante (si définie dans bootstrap)
		if ( defined( 'GF_SIREN_API_KEY' ) ) {
			return GF_SIREN_API_KEY;
		}

		// Essayer de charger depuis .env.test
		$env_file = __DIR__ . '/../../.env.test';
		if ( file_exists( $env_file ) ) {
			$lines = file( $env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
			foreach ( $lines as $line ) {
				if ( strpos( trim( $line ), '#' ) === 0 ) {
					continue; // Ignorer les commentaires
				}
				if ( strpos( $line, 'GF_SIREN_API_KEY=' ) === 0 ) {
					return trim( str_replace( 'GF_SIREN_API_KEY=', '', $line ) );
				}
			}
		}

		return null;
	}

	/**
	 * Test 1 : Récupération des données complètes d'une entreprise
	 *
	 * @group integration
	 */
	public function test_get_company_data_with_real_siret() {
		echo "\n[TEST 1] Récupération des données pour SIRET : " . self::TEST_SIRET . "\n";

		// Act
		$company_data = $this->manager->getCompanyData( self::TEST_SIRET );

		// Assert - Vérifier la structure des données
		$this->assertIsArray( $company_data, 'Les données doivent être un tableau' );
		$this->assertArrayHasKey( 'etablissement', $company_data, 'Doit contenir les données établissement' );
		$this->assertArrayHasKey( 'unite_legale', $company_data, 'Doit contenir les données unité légale' );

		// Afficher les données récupérées
		echo "✓ Données établissement récupérées\n";
		echo "✓ Données unité légale récupérées\n";

		// Vérifier les informations de l'établissement
		$etablissement = $company_data['etablissement'];
		$this->assertArrayHasKey( 'siret', $etablissement );
		$this->assertEquals( self::TEST_SIRET, $etablissement['siret'] );
		echo "✓ SIRET vérifié : " . $etablissement['siret'] . "\n";

		// Vérifier les informations de l'unité légale
		$unite_legale = $company_data['unite_legale'];
		$this->assertArrayHasKey( 'siren', $unite_legale );
		echo "✓ SIREN : " . $unite_legale['siren'] . "\n";

		// Afficher les informations principales
		if ( isset( $unite_legale['denomination'] ) ) {
			echo "✓ Dénomination : " . $unite_legale['denomination'] . "\n";
		} elseif ( isset( $unite_legale['nom'] ) ) {
			$prenom = $unite_legale['prenom_usuel'] ?? $unite_legale['prenom_1'] ?? '';
			echo "✓ Nom : " . $prenom . ' ' . $unite_legale['nom'] . "\n";
		}

		if ( isset( $etablissement['libelle_commune'] ) ) {
			echo "✓ Ville : " . $etablissement['libelle_commune'] . "\n";
		}

		return $company_data;
	}

	/**
	 * Test 2 : Vérification du type d'entreprise
	 *
	 * @depends test_get_company_data_with_real_siret
	 * @group integration
	 */
	public function test_determine_company_type( $company_data ) {
		echo "\n[TEST 2] Détermination du type d'entreprise\n";

		$validator = new SirenValidator();
		$type      = $validator->determine_entreprise_type( $company_data['unite_legale'] );

		$this->assertNotEquals( Constants::ENTREPRISE_TYPE_INCONNU, $type, 'Le type d\'entreprise doit être déterminé' );
		$this->assertContains(
			$type,
			[ Constants::ENTREPRISE_TYPE_PERSONNE_MORALE, Constants::ENTREPRISE_TYPE_ENTREPRENEUR_INDIVIDUEL ],
			'Le type doit être soit Personne Morale soit Entrepreneur Individuel'
		);

		echo "✓ Type d'entreprise déterminé : " . $type . "\n";

		return [ 'company_data' => $company_data, 'type' => $type ];
	}

	/**
	 * Test 3 : Génération des mentions légales
	 *
	 * @depends test_determine_company_type
	 * @group integration
	 */
	public function test_generate_legal_mentions( $data ) {
		echo "\n[TEST 3] Génération des mentions légales\n";

		$company_data = $data['company_data'];
		$type         = $data['type'];

		// Générer les mentions selon le type
		if ( Constants::ENTREPRISE_TYPE_PERSONNE_MORALE === $type ) {
			$mentions = $this->formatter->format_personne_morale( $company_data );
		} elseif ( Constants::ENTREPRISE_TYPE_ENTREPRENEUR_INDIVIDUEL === $type ) {
			$mentions = $this->formatter->format_entrepreneur_individuel( $company_data );
		} else {
			$mentions = $this->formatter->format_fallback( $company_data );
		}

		// Vérifications
		$this->assertIsString( $mentions, 'Les mentions doivent être une chaîne' );
		$this->assertNotEmpty( $mentions, 'Les mentions ne doivent pas être vides' );
		$this->assertGreaterThan( 50, strlen( $mentions ), 'Les mentions doivent contenir au moins 50 caractères' );

		// Vérifier que les informations clés sont présentes
		$siren_formatted = MentionsHelper::format_siren( $company_data['unite_legale']['siren'] );
		$this->assertStringContainsString( $siren_formatted, $mentions, 'Les mentions doivent contenir le SIREN' );

		echo "✓ Mentions légales générées (" . strlen( $mentions ) . " caractères)\n";
		echo "\n--- MENTIONS LÉGALES GÉNÉRÉES ---\n";
		echo $mentions . "\n";
		echo "--- FIN DES MENTIONS ---\n";

		return $mentions;
	}

	/**
	 * Test 4 : Vérification du cache
	 *
	 * @group integration
	 */
	public function test_cache_is_working() {
		echo "\n[TEST 4] Vérification du système de cache\n";

		// Premier appel (doit utiliser l'API)
		$start_time_1  = microtime( true );
		$company_data1 = $this->manager->getCompanyData( self::TEST_SIRET );
		$duration_1    = ( microtime( true ) - $start_time_1 ) * 1000;

		echo "✓ Premier appel (API) : " . round( $duration_1, 2 ) . " ms\n";

		// Deuxième appel (doit utiliser le cache)
		$start_time_2  = microtime( true );
		$company_data2 = $this->manager->getCompanyData( self::TEST_SIRET );
		$duration_2    = ( microtime( true ) - $start_time_2 ) * 1000;

		echo "✓ Deuxième appel (cache) : " . round( $duration_2, 2 ) . " ms\n";

		// Le deuxième appel doit être plus rapide (grâce au cache)
		$this->assertLessThan( $duration_1, $duration_2, 'Le deuxième appel doit être plus rapide (cache)' );
		echo "✓ Le cache accélère les requêtes de " . round( ( 1 - $duration_2 / $duration_1 ) * 100, 1 ) . "%\n";

		// Les données doivent être identiques
		$this->assertEquals( $company_data1, $company_data2, 'Les données doivent être identiques' );
		echo "✓ Les données en cache sont identiques aux données API\n";
	}

	/**
	 * Test 5 : Validation d'un SIRET invalide
	 *
	 * @group integration
	 */
	public function test_invalid_siret_throws_exception() {
		echo "\n[TEST 5] Test avec un SIRET invalide\n";

		$this->expectException( \Exception::class );
		$this->manager->getCompanyData( '12345' );

		echo "✓ Une exception est bien levée pour un SIRET invalide\n";
	}

	/**
	 * Test 6 : SIRET inexistant (doit retourner une erreur API)
	 *
	 * @group integration
	 */
	public function test_nonexistent_siret() {
		echo "\n[TEST 6] Test avec un SIRET inexistant\n";

		$fake_siret = '00000000000000'; // SIRET valide en format mais inexistant

		try {
			$this->manager->getCompanyData( $fake_siret );
			$this->fail( 'Une exception aurait dû être levée pour un SIRET inexistant' );
		} catch ( \Exception $e ) {
			$this->assertStringContainsString( 'trouvée', $e->getMessage(), 'Le message d\'erreur doit indiquer que l\'entreprise n\'est pas trouvée' );
			echo "✓ Erreur correctement levée : " . $e->getMessage() . "\n";
		}
	}

	/**
	 * Test 7 : Vérification du statut actif de l'entreprise
	 *
	 * @group integration
	 */
	public function test_company_active_status() {
		echo "\n[TEST 7] Vérification du statut actif\n";

		$company_data = $this->manager->getCompanyData( self::TEST_SIRET );
		$validator    = new SirenValidator();
		$is_active    = $validator->is_active( $company_data['unite_legale'] );

		$this->assertIsBool( $is_active, 'Le statut actif doit être un booléen' );
		
		if ( $is_active ) {
			echo "✓ Entreprise ACTIVE\n";
		} else {
			echo "⚠ Entreprise INACTIVE\n";
		}
	}

	/**
	 * Nettoyage après tous les tests
	 */
	public static function tearDownAfterClass(): void {
		echo "\n========================================\n";
		echo "✅ Tests d'intégration terminés\n";
		echo "========================================\n";
	}
}

