# Guide d'installation et configuration - Formulaire ID: 1

## 📋 Vue d'ensemble

Ce guide vous explique comment installer et configurer le plugin **Gravity Forms Siren Autocomplete** pour votre formulaire "Test de positionnement Révélation Digitale" (ID: 1).

---

## 🎯 Configuration de votre formulaire

### Champs mappés

| Rôle                    | Type de champ GF    | ID    | Utilisation           |
| ----------------------- | ------------------- | ----- | --------------------- |
| **SIRET**               | Text                | `1`   | Saisie utilisateur ✏️ |
| **Bouton Vérifier**     | HTML                | `11`  | Bouton + messages 🔘  |
| **Nom entreprise**      | Text                | `12`  | Pré-rempli ✅         |
| **Adresse**             | Address (composite) | `8`   | Pré-rempli ✅         |
| ├─ Rue                  | Address sous-champ  | `8.1` | Pré-rempli ✅         |
| ├─ Ville                | Address sous-champ  | `8.3` | Pré-rempli ✅         |
| ├─ Code postal          | Address sous-champ  | `8.5` | Pré-rempli ✅         |
| └─ Pays                 | Address sous-champ  | `8.6` | Pré-rempli ✅         |
| **Mentions légales**    | Textarea            | `13`  | Pré-rempli ✅         |
| **Prénom représentant** | Name sous-champ     | `7.3` | Lecture 📖            |
| **Nom représentant**    | Name sous-champ     | `7.6` | Lecture 📖            |

---

## 🚀 Étapes d'installation

### Étape 1 : Installer le plugin

1. **Copier le dossier du plugin** dans `wp-content/plugins/`

   ```
   wp-content/plugins/gravity_forms_siren_autocomplete/
   ```

2. **Activer le plugin** dans WordPress Admin
   - Aller dans **Extensions** → **Extensions installées**
   - Trouver "Gravity Forms Siren Autocomplete"
   - Cliquer sur **Activer**

### Étape 2 : Configurer la clé API

1. Aller dans **Formulaires** → **Siren Autocomplete** → **Réglages**

2. Entrer votre **clé API Siren** dans le champ prévu

   ```
   Clé API Siren : [Votre clé API]
   ```

3. Cliquer sur **Tester la connexion** pour vérifier

4. Sauvegarder

### Étape 3 : Installer la configuration du formulaire

#### Option A : Via WP-CLI (Recommandé)

```bash
# Naviguer vers le dossier WordPress
cd path/to/wordpress

# Installer la configuration
wp eval-file wp-content/plugins/gravity_forms_siren_autocomplete/config_form_1.php
```

#### Option B : Via l'admin WordPress

1. Aller dans **Formulaires** → **Siren Autocomplete** → **Configuration**

2. Cliquer sur **Installer la configuration du formulaire ID: 1**

3. Vérifier que le message de succès s'affiche

#### Option C : Manuellement via le code

Ajouter ce code dans `functions.php` de votre thème :

```php
<?php
// Charger la configuration du formulaire ID: 1
require_once WP_PLUGIN_DIR . '/gravity_forms_siren_autocomplete/config_form_1.php';

// Installer la configuration au premier chargement
if ( ! get_option( 'gf_siren_form_1_configured' ) ) {
    gf_siren_install_form_1_config();
    update_option( 'gf_siren_form_1_configured', true );
}
```

### Étape 4 : Configurer le champ HTML (ID: 11)

1. Aller dans **Formulaires** → **Formulaires** → **Test de positionnement Révélation Digitale**

2. **Éditer le champ HTML (ID: 11)**

3. **Copier-coller** le contenu suivant dans le champ HTML :

```html
<div class="gf-siren-verify-container" data-form-id="1" data-field-id="1">
  <!-- Bouton de vérification -->
  <button
    type="button"
    id="gf-verify-siret"
    class="button gform_button gf-siren-verify-button"
    data-form-id="1"
    data-field-id="1"
    data-nonce="<?php echo wp_create_nonce( 'gf_siren_nonce' ); ?>"
    style="margin: 10px 0;"
  >
    🔍 Vérifier le SIRET
  </button>

  <!-- Loader (caché par défaut) -->
  <div class="gf-siren-loader" style="display: none; margin: 10px 0;">
    <span
      class="spinner is-active"
      style="float: none; margin: 0 10px 0 0;"
    ></span>
    <span>⏳ Vérification en cours...</span>
  </div>

  <!-- Zone de message (succès/erreur) -->
  <div id="gf-siren-status" class="gf-siren-message"></div>
</div>

<style>
  .gf-siren-verify-container {
    margin: 15px 0;
  }

  .gf-siren-loader {
    display: inline-flex;
    align-items: center;
    padding: 8px 12px;
    background-color: #f0f0f1;
    border-radius: 4px;
    font-size: 14px;
  }

  .gf-siren-message {
    margin: 10px 0;
  }

  .gf-siren-message-box {
    padding: 12px 15px;
    border-radius: 4px;
    border-left: 4px solid;
    font-size: 14px;
    line-height: 1.5;
  }

  .gf-siren-message-success {
    background-color: #d4edda;
    color: #155724;
    border-color: #28a745;
  }

  .gf-siren-message-error {
    background-color: #f8d7da;
    color: #721c24;
    border-color: #dc3545;
  }

  .gf-siren-message-warning {
    background-color: #fff3cd;
    color: #856404;
    border-color: #ffc107;
  }

  .gf-siren-message-box .icon {
    font-weight: bold;
    margin-right: 8px;
    font-size: 16px;
  }

  .gf-siren-edit-warning {
    display: block;
    margin-top: 5px;
    color: #856404;
    font-size: 13px;
    font-style: italic;
  }

  input.gf-siren-manually-edited {
    border-left: 3px solid #ffc107 !important;
  }
</style>
```

4. **Sauvegarder** le formulaire

---

## ✅ Vérification de l'installation

### Test 1 : Vérifier la configuration

1. Aller dans **Formulaires** → **Siren Autocomplete** → **Réglages**

2. Vérifier que sous **Formulaires configurés**, le formulaire ID: 1 apparaît

3. Vérifier le mapping des champs :
   ```
   ✅ SIRET : Champ 1
   ✅ Nom entreprise : Champ 12
   ✅ Adresse rue : Champ 8.1
   ✅ Ville : Champ 8.3
   ✅ Code postal : Champ 8.5
   ✅ Pays : Champ 8.6
   ✅ Mentions légales : Champ 13
   ✅ Prénom représentant : Champ 7.3
   ✅ Nom représentant : Champ 7.6
   ```

### Test 2 : Tester l'API

1. Dans **Formulaires** → **Siren Autocomplete** → **Réglages**

2. Cliquer sur **Tester la connexion API**

3. Vérifier le message de succès : ✅ "Connexion API réussie"

### Test 3 : Tester le formulaire en frontend

1. Ouvrir le formulaire en frontend (sur votre site)

2. Saisir un SIRET de test : `89498206500019`

3. Remplir le prénom et nom du représentant :

   - Prénom : Gabriel
   - Nom : Duteurtre

4. Cliquer sur **🔍 Vérifier le SIRET**

5. **Vérifier que les champs se remplissent automatiquement** :

   - ✅ Nom entreprise : "ESTHESUD"
   - ✅ Adresse rue : "13 Avenue..."
   - ✅ Ville : "Argelès-sur-Mer"
   - ✅ Code postal : "66700"
   - ✅ Pays : "France"
   - ✅ Mentions légales : "ESTHESUD, dont le siège social..."

6. **Vérifier le message de statut** :
   - ✅ "Entreprise trouvée : ESTHESUD"

---

## 🐛 Dépannage

### Le bouton "Vérifier" ne s'affiche pas

**Causes possibles :**

- Le champ HTML (ID: 11) n'est pas créé
- Le code HTML n'a pas été copié dans le champ

**Solution :**

1. Vérifier que le champ HTML (ID: 11) existe dans le formulaire
2. Vérifier que le code HTML a bien été copié
3. Sauvegarder le formulaire
4. Vider le cache du site si nécessaire

### Le bouton ne réagit pas au clic

**Causes possibles :**

- JavaScript non chargé
- Conflit avec un autre plugin

**Solution :**

1. Ouvrir la console du navigateur (F12)
2. Vérifier les erreurs JavaScript
3. Vérifier que le fichier `frontend.js` est chargé :
   ```
   /wp-content/plugins/gravity_forms_siren_autocomplete/assets/js/frontend.js
   ```
4. Désactiver temporairement les autres plugins pour tester

### Les champs ne se remplissent pas

**Causes possibles :**

- IDs de champs incorrects dans la configuration
- Mapping non installé
- Erreur JavaScript

**Solution :**

1. Vérifier que la configuration est bien installée (Étape 3)
2. Vérifier les IDs des champs dans Gravity Forms
3. Ouvrir la console du navigateur (F12)
4. Vérifier les erreurs
5. Vérifier la réponse AJAX dans l'onglet "Network"

### Erreur "SIRET non trouvé"

**Causes possibles :**

- SIRET invalide
- API Siren indisponible
- Clé API incorrecte

**Solution :**

1. Vérifier que le SIRET contient bien 14 chiffres
2. Tester avec un SIRET connu : `89498206500019`
3. Vérifier la clé API dans les réglages
4. Tester la connexion API

### Erreur "Erreur de connexion"

**Causes possibles :**

- Problème de réseau
- API Siren en maintenance
- Timeout

**Solution :**

1. Vérifier la connexion internet du serveur
2. Réessayer dans quelques minutes
3. Consulter les logs du plugin :
   - **Formulaires** → **Siren Autocomplete** → **Logs**

---

## 📊 Logs et débogage

### Consulter les logs

1. Aller dans **Formulaires** → **Siren Autocomplete** → **Logs**

2. Filtrer par niveau :

   - ℹ️ **INFO** : Actions normales
   - ⚠️ **WARNING** : Avertissements
   - ❌ **ERROR** : Erreurs

3. Rechercher les entrées liées au formulaire ID: 1

### Activer le mode debug

Ajouter dans `wp-config.php` :

```php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
```

Les logs seront dans `wp-content/debug.log`

---

## 🔧 Configuration avancée

### Personnaliser les messages

Éditer le fichier `config_form_1.php` :

```php
$messages_config = array(
    'success'           => 'Entreprise trouvée : {denomination}',
    'error_invalid'     => 'Veuillez saisir un SIRET valide (14 chiffres).',
    'error_not_found'   => 'Aucune entreprise trouvée avec ce SIRET.',
    // ... autres messages
);
```

### Changer le texte du bouton

Éditer le fichier `config_form_1.php` :

```php
$button_html_config = array(
    'button_text'     => '🔍 Vérifier le SIRET', // Modifier ici
    // ...
);
```

Puis réinstaller la configuration (Étape 3).

### Désactiver le plugin pour ce formulaire

```php
$form_mapping_config = array(
    'enable_plugin'   => false, // Passer à false
    // ...
);
```

---

## 📚 Ressources

### Fichiers importants

- **Configuration** : `config_form_1.php`
- **Documentation API** : `code_source/documentation/API Siren.md`
- **README** : `README.md`
- **Tests** : `tests/`

### Support

Pour toute question ou problème :

1. Consulter les logs du plugin
2. Vérifier la configuration
3. Tester avec un SIRET connu
4. Contacter le support

---

## ✅ Checklist finale

Avant de mettre en production, vérifier :

- [ ] Plugin activé
- [ ] Clé API configurée et testée
- [ ] Configuration du formulaire installée
- [ ] Champ HTML (ID: 11) configuré avec le code fourni
- [ ] Test complet avec un SIRET valide
- [ ] Tous les champs se remplissent correctement
- [ ] Messages de succès/erreur affichés
- [ ] Prénom et nom représentant lus correctement
- [ ] Mentions légales générées correctement
- [ ] Pas d'erreur JavaScript dans la console
- [ ] Pas d'erreur dans les logs du plugin

🎉 **Félicitations ! Votre plugin est configuré et prêt à l'emploi !**
