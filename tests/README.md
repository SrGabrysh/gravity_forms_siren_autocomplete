# 🧪 Tests Unitaires - Gravity Forms Siren Autocomplete

## 📊 État actuel des tests

**✅ 68 tests | 114 assertions | 0 échec | 0 erreur**

Tous les tests unitaires passent avec succès ! 🎉

---

## 🎯 Modules testés

### ✅ **Helpers** (26 tests)

#### **DataHelper** (16 tests)

- ✅ Nettoyage et formatage SIRET/SIREN
- ✅ Formatage de dates
- ✅ Extraction de valeurs dans les tableaux
- ✅ Sanitization et masquage des clés API
- ✅ Encodage/décodage JSON
- ✅ Vérification de valeurs vides
- ✅ Sanitization de tableaux

#### **SecurityHelper** (10 tests)

- ✅ Sanitization de différents types d'entrées (texte, textarea, email, URL)
- ✅ Échappement de sorties (HTML, attributs, JS)
- ✅ Validation de clés API

### ✅ **Siren Module** (19 tests)

#### **SirenValidator** (19 tests)

- ✅ Nettoyage de SIRET
- ✅ Validation de format SIRET
- ✅ Extraction de SIREN depuis SIRET
- ✅ Vérification du statut actif d'une entreprise
- ✅ Détermination du type d'entreprise (Personne Morale / Entrepreneur Individuel)
- ✅ Validation complète avec messages d'erreur

### ✅ **Mentions Légales Module** (23 tests)

#### **MentionsHelper** (13 tests)

- ✅ Récupération de forme juridique
- ✅ Formatage d'adresses complètes
- ✅ Extraction d'adresses sans CP/Ville
- ✅ Extraction de code postal et ville
- ✅ Détermination du titre de représentant
- ✅ Vérification de société à capital
- ✅ Récupération d'enseigne

#### **MentionsFormatter** (10 tests)

- ✅ Formatage pour sociétés à capital (SARL, SAS, SA)
- ✅ Formatage pour personnes morales
- ✅ Formatage pour entrepreneurs individuels
- ✅ Gestion des enseignes
- ✅ Formatage fallback

---

## 🚀 Commandes de test

### Exécuter tous les tests

```bash
composer test
```

Ou directement :

```bash
vendor/bin/phpunit
```

### Avec affichage détaillé

```bash
vendor/bin/phpunit --testdox --colors=always
```

### Avec rapport de couverture (nécessite Xdebug ou PCOV)

```bash
vendor/bin/phpunit --coverage-text
```

---

## 📁 Structure des tests

```
tests/
├── bootstrap.php              # Configuration et mocks WordPress
├── Unit/                      # Tests unitaires
│   ├── Helpers/              # Tests des utilitaires
│   │   ├── DataHelperTest.php
│   │   └── SecurityHelperTest.php
│   └── Modules/              # Tests des modules métier
│       ├── Siren/
│       │   └── SirenValidatorTest.php
│       └── MentionsLegales/
│           ├── MentionsHelperTest.php
│           └── MentionsFormatterTest.php
└── E2E/                       # Tests End-to-End (à venir)
    └── scenarios/
```

---

## 🎨 Philosophie des tests unitaires

Les tests unitaires de ce plugin suivent ces principes :

### ✅ **Isolation**

- Chaque test vérifie **une seule fonction ou méthode**
- Pas de dépendances externes (API, base de données, etc.)
- Utilisation de mocks pour les fonctions WordPress

### ✅ **Rapidité**

- Temps d'exécution total : **< 1 seconde**
- Aucun appel réseau
- Aucune opération I/O lourde

### ✅ **Fiabilité**

- Tests déterministes (résultats identiques à chaque exécution)
- Pas d'effets de bord
- Couverture des cas limites et erreurs

### ✅ **Lisibilité**

- Noms de tests descriptifs (format : `test_fonction_cas_attendu`)
- Un test = une assertion principale
- Commentaires explicatifs quand nécessaire

---

## 🔧 Configuration

### Fichiers de configuration

#### `phpunit.xml`

- Configuration PHPUnit
- Définition des suites de tests
- Exclusions de couverture

#### `tests/bootstrap.php`

- Chargement de l'autoloader Composer
- Définition de constantes WordPress
- Mocks des fonctions WordPress essentielles

### Fonctions WordPress mockées

Pour permettre l'exécution isolée des tests, les fonctions WordPress suivantes sont mockées :

```php
esc_html()
esc_attr()
esc_url()
esc_url_raw()
esc_js()
esc_textarea()
sanitize_text_field()
sanitize_textarea_field()
sanitize_email()
sanitize_key()
wp_kses_post()
wp_json_encode()
__()
esc_html__()
_n()
date_i18n()
```

---

## 📈 Ajout de nouveaux tests

### Template de test unitaire

```php
<?php

namespace GFSirenAutocomplete\Tests\Unit\VotreModule;

use PHPUnit\Framework\TestCase;
use GFSirenAutocomplete\VotreModule\VotreClasse;

class VotreClasseTest extends TestCase {

	private $instance;

	protected function setUp(): void {
		$this->instance = new VotreClasse();
	}

	public function test_votre_methode_cas_nominal() {
		// Arrange
		$input = 'valeur_test';

		// Act
		$result = $this->instance->votreMethode( $input );

		// Assert
		$this->assertEquals( 'valeur_attendue', $result );
	}

	public function test_votre_methode_cas_erreur() {
		// Test du cas d'erreur
		$this->expectException( \Exception::class );
		$this->instance->votreMethode( null );
	}
}
```

### Bonnes pratiques

1. **Nommer clairement** : `test_nom_de_la_methode_cas_teste`
2. **Tester les cas limites** : valeurs nulles, chaînes vides, tableaux vides
3. **Tester les erreurs** : exceptions attendues, validations
4. **Utiliser setUp()** : pour initialiser les instances communes
5. **Un test = un concept** : ne pas mélanger plusieurs validations

---

## 🐛 Debugging

### Afficher les détails d'un test spécifique

```bash
vendor/bin/phpunit --filter test_votre_test_specifique --testdox
```

### Afficher les assertions

```bash
vendor/bin/phpunit --verbose
```

### Mode debug avec variables

Ajoutez dans vos tests :

```php
var_dump( $result );
$this->assertTrue( false, print_r( $data, true ) );
```

---

## 📚 Ressources

- [Documentation PHPUnit](https://phpunit.de/documentation.html)
- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)
- [Test Driven Development (TDD)](https://en.wikipedia.org/wiki/Test-driven_development)

---

## ✨ Prochaines étapes

- [ ] Ajouter des tests pour `SirenClient` (avec mocks HTTP)
- [ ] Ajouter des tests pour `SirenManager`
- [ ] Ajouter des tests pour `MentionsManager`
- [ ] Implémenter les tests E2E
- [ ] Atteindre 80%+ de couverture de code

---

**Mis à jour le** : 2025-10-01  
**Version plugin** : 1.0.0  
**PHPUnit** : 9.6.29  
**PHP** : 8.3.8
