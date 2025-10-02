# Gravity Forms Siren Autocomplete

**Version:** 1.0.3  
**Auteur:** TB-Web  
**Licence:** GPL v2 or later

## 📋 Description

Plugin WordPress qui s'intègre avec Gravity Forms pour :

- ✅ **Vérifier la validité** d'un numéro SIRET
- ✅ **Remplir automatiquement** les champs du formulaire (nom d'entreprise, adresse, etc.)
- ✅ **Générer les mentions légales** formatées selon le type d'entreprise
- ✅ **Mettre en cache** les résultats API pour améliorer les performances

## 🚀 Fonctionnalités

### Frontend

- Bouton "Vérifier le SIRET" ajouté automatiquement dans les formulaires
- Remplissage automatique de 12 champs (SIRET, dénomination, adresse, etc.)
- Génération de mentions légales selon le type d'entreprise (SARL, SAS, EI, etc.)
- Gestion des entreprises inactives avec avertissement visuel
- Détection des modifications manuelles post-vérification

### Administration

- Interface de configuration avec clé API Siren
- Mapping personnalisé des champs par formulaire
- Visualisation des logs avec filtres (niveau, date)
- Test de connexion à l'API
- Gestion du cache avec statistiques

## 📦 Installation

### Prérequis

- **PHP** : 7.4 ou supérieur
- **WordPress** : 5.8 ou supérieur
- **Gravity Forms** : 2.5 ou supérieur
- **Clé API Siren** : [Inscription sur siren-api.fr](https://client.siren-api.fr/)

### Installation manuelle

1. Téléchargez le plugin
2. Décompressez dans `/wp-content/plugins/`
3. Installez les dépendances Composer :
   ```bash
   cd wp-content/plugins/gravity-forms-siren-autocomplete
   composer install --optimize-autoloader --no-dev
   ```
4. Activez le plugin dans l'administration WordPress
5. Configurez la clé API Siren

### Configuration de la clé API

**Méthode recommandée** (wp-config.php) :

```php
define( 'GF_SIREN_API_KEY', 'votre_cle_api_ici' );
```

**Méthode alternative** : Via l'interface d'administration du plugin

## ⚙️ Configuration

### 1. Configuration générale

- **Menu** : Formulaires → Siren Autocomplete
- Définir la clé API Siren
- Configurer la durée du cache (24h par défaut)
- Tester la connexion à l'API

### 2. Mapping des champs

- **Menu** : Formulaires → Siren Autocomplete → Onglet "Mapping des champs"
- Sélectionner le formulaire Gravity Forms
- Associer les champs de l'API aux champs du formulaire

**Champs disponibles** :

- SIRET (obligatoire)
- Dénomination
- Adresse
- Code postal
- Ville
- Forme juridique
- Code APE / Libellé APE
- Date de création
- Statut actif/inactif
- Type d'entreprise
- Mentions légales
- Prénom/Nom du représentant légal

### 3. Création d'un formulaire

1. Créer un formulaire Gravity Forms
2. Ajouter les champs nécessaires (texte, textarea, etc.)
3. Configurer le mapping dans l'administration du plugin
4. Le bouton "Vérifier le SIRET" s'ajoutera automatiquement

## 🧪 Tests

### Tests Unitaires

```bash
composer test:unit
```

### Tests End-to-End

```bash
python tests/E2E/run_tests.py
```

### Couverture de code

```bash
composer test:unit:coverage
```

## 🔧 Développement

### Standards de code

```bash
# Vérifier
composer cs:lint

# Corriger automatiquement
composer cs:fix
```

### Architecture

Le plugin suit une architecture modulaire :

- `src/Core/` : Noyau (Logger, Constants, Plugin)
- `src/Helpers/` : Utilitaires (DataHelper, SecurityHelper)
- `src/Modules/Siren/` : Gestion API Siren
- `src/Modules/MentionsLegales/` : Formatage des mentions
- `src/Modules/GravityForms/` : Intégration Gravity Forms
- `src/Admin/` : Interface d'administration

### Principes respectés

- **KISS** : Keep It Simple, Stupid
- **SRP** : Single Responsibility Principle
- **DRY** : Don't Repeat Yourself
- **OCP** : Open/Closed Principle
- **YAGNI** : You Aren't Gonna Need It

### Limites de lignes

- Fichier PHP : MAX 300 lignes
- Classe PHP : MAX 250 lignes
- Méthode : MAX 50 lignes

## 📚 Documentation

### Hooks disponibles

**Filtres** :

```php
// Modifier le mapping des champs
apply_filters( 'gf_siren_field_mapping', $mapping, $form_id );

// Modifier les mentions légales générées
apply_filters( 'gf_siren_mentions_legales', $mentions, $company_data, $representant_data );

// Modifier le texte du bouton de vérification
apply_filters( 'gf_siren_button_text', $button_text, $form_id );
```

### Logs

Les logs sont accessibles via : **Formulaires → Logs Siren**

Niveaux disponibles :

- **INFO** : Vérifications réussies, cache utilisé
- **WARNING** : Entreprise inactive, modifications manuelles
- **ERROR** : Erreurs API, SIRET introuvable, timeout

## 🐛 Dépannage

### Le bouton "Vérifier" n'apparaît pas

- Vérifier que Gravity Forms est actif
- Vérifier que le mapping est configuré pour ce formulaire
- Vérifier que le champ SIRET est bien mappé

### Erreur "Clé API manquante"

- Vérifier la constante `GF_SIREN_API_KEY` dans wp-config.php
- OU configurer la clé dans l'interface admin

### Erreur "Aucune entreprise trouvée"

- Vérifier que le SIRET contient exactement 14 chiffres
- Vérifier que l'entreprise existe bien dans la base Siren

## 📄 Licence

GPL v2 or later. Voir [LICENSE](LICENSE.txt) pour plus de détails.

## 🤝 Support

Pour tout problème ou suggestion :

- **Email** : contact@tb-web.fr
- **GitHub** : [Issues](https://github.com/SrGabrysh/gravity-forms-siren-autocomplete/issues)

## 📝 Changelog

### Version 1.0.0 (01/10/2025)

- 🎉 Version initiale du plugin
- ✅ Vérification SIRET via API Siren
- ✅ Remplissage automatique des champs
- ✅ Génération des mentions légales (3 types d'entreprise)
- ✅ Système de cache (WordPress Transients)
- ✅ Interface d'administration complète
- ✅ Système de logs avec visualisation
- ✅ Support multi-formulaires
- ✅ Tests unitaires et E2E

---

**Dernière mise à jour** : 01 Octobre 2025  
**Développé par** : TB-Web

---

Dernière mise à jour : 2025-10-02 09:02:01
