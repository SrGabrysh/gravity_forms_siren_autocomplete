# 🧪 Tests d'Intégration - API Siren

## 📖 Description

Les tests d'intégration vérifient le bon fonctionnement du plugin avec l'API Siren réelle. Contrairement aux tests unitaires qui testent des fonctions isolées, ces tests valident **toute la chaîne de traitement** :

1. ✅ **Validation** : Nettoyage et validation du SIRET
2. ✅ **API Client** : Appel réel à l'API Siren (https://data.siren-api.fr)
3. ✅ **Cache** : Mise en cache des résultats
4. ✅ **Orchestration** : Coordination entre les modules
5. ✅ **Formatage** : Génération des mentions légales

---

## ⚠️ Prérequis

### 🔑 Clé API Siren (obligatoire)

Ces tests nécessitent une **clé API Siren valide**. Pour obtenir votre clé :

1. Rendez-vous sur https://data.siren-api.fr
2. Créez un compte
3. Récupérez votre clé API

### 🌐 Connexion Internet

Les tests effectuent des **appels HTTP réels** à l'API Siren, une connexion Internet est donc nécessaire.

---

## 🚀 Méthode 1 : Script standalone (recommandé)

Le script `test_api_integration.php` est un test autonome, simple à exécuter et très visuel.

### Configuration

Définissez votre clé API comme variable d'environnement :

#### Windows (PowerShell)

```powershell
$env:GF_SIREN_API_KEY = "votre_cle_api_ici"
php test_api_integration.php
```

#### Linux/Mac (Bash)

```bash
export GF_SIREN_API_KEY="votre_cle_api_ici"
php test_api_integration.php
```

#### Modification directe du fichier

Vous pouvez aussi modifier la ligne 20 du fichier `test_api_integration.php` :

```php
define( 'GF_SIREN_API_KEY', getenv( 'GF_SIREN_API_KEY' ) ?: 'VOTRE_CLE_API_ICI' );
```

### Exécution

```bash
cd Plugins/gravity_forms_siren_autocomplete
php test_api_integration.php
```

### Sortie attendue

```
╔════════════════════════════════════════════════════════════╗
║  TEST D'INTÉGRATION - API SIREN                            ║
║  Plugin: Gravity Forms Siren Autocomplete                  ║
╚════════════════════════════════════════════════════════════╝

============================================================
  CONFIGURATION
============================================================
SIRET de test: 89498206500019
Clé API: ****ef45
✓ Configuration chargée
✓ Composants initialisés

============================================================
  TEST 1 : Validation du SIRET
============================================================
  SIRET nettoyé: 89498206500019
✓ Format de SIRET valide
  SIREN extrait: 894982065
✓ SIREN extrait avec succès

============================================================
  TEST 2 : Appel à l'API Siren
============================================================
✓ Données récupérées en 345.67 ms
✓ Structure des données valide

============================================================
  TEST 3 : Informations de l'entreprise
============================================================
  Dénomination: EXEMPLE SOCIETE SARL
  SIREN: 894 982 065
  SIRET: 894 982 065 00019
  Adresse: 10 RUE DE LA PAIX, 75001 PARIS
  Forme juridique: SARL
  Statut: ACTIVE
✓ Informations extraites avec succès

============================================================
  TEST 4 : Type d'entreprise
============================================================
  Type déterminé: PERSONNE_MORALE
✓ Type d'entreprise identifié

============================================================
  TEST 5 : Mentions légales
============================================================
✓ Mentions générées (456 caractères)

--- MENTIONS LÉGALES ---

[Mentions légales complètes affichées ici]

--- FIN DES MENTIONS ---

============================================================
  TEST 6 : Système de cache
============================================================
  Premier appel (API): 345.67 ms
  Deuxième appel (cache): 2.45 ms
✓ Cache accélère les requêtes de 99.3%
✓ Données en cache identiques aux données API

============================================================
  RÉSUMÉ DES TESTS
============================================================

╔════════════════════════════════════════════════════════════╗
║  ✅ TOUS LES TESTS SONT PASSÉS AVEC SUCCÈS                ║
╚════════════════════════════════════════════════════════════╝

Le plugin est capable de :
  ✓ Valider et nettoyer un SIRET
  ✓ Appeler l'API Siren avec succès
  ✓ Extraire et structurer les données
  ✓ Déterminer le type d'entreprise
  ✓ Générer des mentions légales conformes
  ✓ Mettre en cache les résultats

🎉 Le plugin est prêt pour la production !
```

---

## 🧪 Méthode 2 : PHPUnit (avancé)

Vous pouvez aussi exécuter les tests via PHPUnit.

### Configuration

```bash
export GF_SIREN_API_KEY="votre_cle_api_ici"
```

### Exécution

```bash
cd Plugins/gravity_forms_siren_autocomplete

# Tous les tests d'intégration
vendor/bin/phpunit --group integration

# Un seul test spécifique
vendor/bin/phpunit --filter test_get_company_data_with_real_siret

# Avec affichage détaillé
vendor/bin/phpunit --group integration --testdox --colors=always
```

### Exclure les tests d'intégration des tests unitaires

Les tests d'intégration sont marqués avec `@group integration`. Pour lancer uniquement les tests unitaires (sans appels API) :

```bash
vendor/bin/phpunit --exclude-group integration
```

---

## 🎯 Tests disponibles

### Test 1 : `test_get_company_data_with_real_siret`

Récupère les données complètes d'une entreprise via l'API Siren.

**Vérifie :**

- Structure de la réponse API
- Présence des données établissement et unité légale
- Correspondance du SIRET

### Test 2 : `test_determine_company_type`

Détermine si l'entreprise est une Personne Morale ou un Entrepreneur Individuel.

**Vérifie :**

- Type correctement identifié
- Pas de type INCONNU

### Test 3 : `test_generate_legal_mentions`

Génère les mentions légales formatées.

**Vérifie :**

- Mentions non vides
- Longueur minimale
- Présence du SIREN formaté

### Test 4 : `test_cache_is_working`

Valide le système de cache.

**Vérifie :**

- Deuxième appel plus rapide
- Données identiques

### Test 5 : `test_invalid_siret_throws_exception`

Teste le comportement avec un SIRET invalide.

**Vérifie :**

- Exception levée
- Message d'erreur approprié

### Test 6 : `test_nonexistent_siret`

Teste avec un SIRET inexistant.

**Vérifie :**

- Erreur API 404
- Message d'erreur clair

### Test 7 : `test_company_active_status`

Vérifie le statut actif/inactif de l'entreprise.

**Vérifie :**

- Statut correctement déterminé
- Valeur booléenne

---

## 🔍 SIRET de test

Par défaut, les tests utilisent le SIRET : **89498206500019**

Vous pouvez modifier ce SIRET dans :

- **Script standalone** : Ligne 72 de `test_api_integration.php`
- **PHPUnit** : Constante `TEST_SIRET` dans `SirenApiIntegrationTest.php` (ligne 31)

---

## 🐛 Résolution des problèmes

### ❌ "Clé API non définie"

**Solution :**

```bash
export GF_SIREN_API_KEY="votre_cle_api"
```

Ou modifiez directement le fichier de test.

### ❌ "Erreur API 401 - Unauthorized"

**Cause :** Clé API invalide ou expirée.

**Solution :**

- Vérifiez votre clé sur https://data.siren-api.fr
- Régénérez une nouvelle clé si nécessaire

### ❌ "Erreur API 429 - Too Many Requests"

**Cause :** Quota d'API dépassé.

**Solution :**

- Attendez quelques minutes
- Vérifiez votre plan API

### ❌ "Erreur de connexion"

**Cause :** Pas d'accès Internet ou API indisponible.

**Solution :**

- Vérifiez votre connexion Internet
- Vérifiez le statut de l'API : https://data.siren-api.fr/status

### ❌ "Aucune entreprise trouvée (404)"

**Cause :** Le SIRET n'existe pas dans la base Siren.

**Solution :**

- Vérifiez que le SIRET est correct
- Utilisez un SIRET d'entreprise réelle et active

---

## 📊 Temps d'exécution

Les tests d'intégration sont **plus lents** que les tests unitaires car ils effectuent des appels réseau :

- **Premier appel API** : 200-500 ms (selon votre connexion)
- **Appels suivants (cache)** : 1-5 ms
- **Durée totale** : ~3-5 secondes

---

## 🔒 Sécurité

⚠️ **IMPORTANT** : Ne commitez JAMAIS votre clé API dans Git !

### Fichiers à ne pas commiter

Les fichiers suivants sont déjà dans `.gitignore` :

- `.env`
- `.env.test`
- `.env.local`

### Bonne pratique

Utilisez toujours les variables d'environnement pour stocker les clés API :

```bash
# Dans votre .bashrc ou .zshrc
export GF_SIREN_API_KEY="votre_cle_api"
```

---

## 📈 Prochaines étapes

Après avoir validé les tests d'intégration :

1. ✅ Le plugin peut communiquer avec l'API Siren
2. ✅ Le cache fonctionne correctement
3. ✅ Les mentions légales sont générées
4. ⏳ Configuration du mapping des champs Gravity Forms
5. ⏳ Tests en conditions réelles avec WordPress

---

## 📚 Ressources

- [API Siren Documentation](https://data.siren-api.fr/documentation)
- [Plugin Documentation](../../README.md)
- [Tests Unitaires](../Unit/README.md)

---

**Mis à jour le** : 2025-10-01  
**Version** : 1.0.0
