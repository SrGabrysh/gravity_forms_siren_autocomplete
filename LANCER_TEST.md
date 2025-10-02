# 🚀 Lancer le Test d'Intégration

## ✅ Méthode Rapide (Recommandée)

Ouvrez PowerShell dans ce dossier et exécutez :

```powershell
# 1. Définissez votre clé API (remplacez par votre vraie clé)
$env:GF_SIREN_API_KEY = "votre_cle_api_ici"

# 2. Lancez le test
php test_api_integration.php
```

---

## 📋 Exemple Complet

```powershell
# Naviguez vers le dossier du plugin
cd "E:\Mon Drive\00 - Dev\01 - Codes\Sites web\TB-Formation\dev_plugin_wc_qualiopi_steps\Plugins\gravity_forms_siren_autocomplete"

# Définissez la clé API (REMPLACEZ PAR VOTRE VRAIE CLÉ)
$env:GF_SIREN_API_KEY = "FlwM9Symg1SIox2WYRSN2vhRmCCwRXal"

# Lancez le test
php test_api_integration.php
```

---

## ⚙️ Où trouver votre clé API ?

### Option 1 : Depuis votre wp-config.php

Vous avez normalement ajouté cette ligne dans votre `wp-config.php` :

```php
define( 'GF_SIREN_API_KEY', 'votre_cle_api_ici' );
```

Copiez la valeur entre les guillemets.

### Option 2 : Depuis le site https://data.siren-api.fr

1. Connectez-vous à votre compte
2. Allez dans "API Keys" ou "Clés API"
3. Copiez votre clé

---

## 🎯 Résultat Attendu

Si tout fonctionne correctement, vous devriez voir :

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

[... tests suivants ...]

╔════════════════════════════════════════════════════════════╗
║  ✅ TOUS LES TESTS SONT PASSÉS AVEC SUCCÈS !              ║
╚════════════════════════════════════════════════════════════╝

🎉 Le plugin est prêt pour la production !
```

---

## ❌ Problèmes fréquents

### "Clé API non définie"

➡️ Assurez-vous d'avoir exécuté la commande `$env:GF_SIREN_API_KEY = "..."` avant de lancer le test

### "Erreur API 401 - Unauthorized"

➡️ Votre clé API est invalide ou expirée. Vérifiez-la sur https://data.siren-api.fr

### "Erreur de connexion"

➡️ Vérifiez votre connexion Internet

---

## 💡 Astuce

Pour éviter de retaper la clé API à chaque fois, ajoutez-la à votre profil PowerShell :

```powershell
# Ouvrez votre profil PowerShell
notepad $PROFILE

# Ajoutez cette ligne (avec votre vraie clé)
$env:GF_SIREN_API_KEY = "votre_cle_api_ici"

# Sauvegardez et fermez
```

Désormais, votre clé API sera définie automatiquement à chaque ouverture de PowerShell.

---

**Prêt à lancer le test ? Exécutez les commandes ci-dessus ! 🚀**
