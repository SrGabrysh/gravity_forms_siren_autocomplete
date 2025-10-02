# 🔍 DIAGNOSTIC - Accès Page Admin

## Problème
La page `https://tb-formation.fr/wp-admin/admin.php?page=gf-siren-settings` renvoie une erreur 403 Forbidden.

## Tests de diagnostic

### Test 1: Vérifier les capacités de l'utilisateur

```bash
wp user list --fields=ID,user_login,roles
wp user meta get [USER_ID] wp_capabilities
```

### Test 2: Vérifier le menu admin

```php
wp eval 'global $menu, $submenu;
foreach ($submenu as $parent => $items) {
    foreach ($items as $item) {
        if (strpos($item[2], "gf-siren") !== false) {
            echo "Menu trouvé:\n";
            echo "  Parent: $parent\n";
            echo "  Titre: {$item[0]}\n";
            echo "  Capability: {$item[1]}\n";
            echo "  Slug: {$item[2]}\n";
        }
    }
}'
```

### Test 3: Vérifier la constante ADMIN_CAPABILITY

```bash
wp eval 'echo GFSirenAutocomplete\Core\Constants::ADMIN_CAPABILITY;'
```

### Test 4: Vérifier si l'utilisateur a la capability

```bash
wp eval '$user = wp_get_current_user();
echo "User ID: " . $user->ID . "\n";
echo "Login: " . $user->user_login . "\n";
echo "Roles: " . implode(", ", $user->roles) . "\n";
echo "Has gravityforms_edit_forms: " . (current_user_can("gravityforms_edit_forms") ? "YES" : "NO") . "\n";
echo "Has manage_options: " . (current_user_can("manage_options") ? "YES" : "NO") . "\n";'
```

### Test 5: Accéder directement avec admin

Si vous êtes admin WordPress, essayez :
- URL alternative: `https://tb-formation.fr/wp-admin/admin.php?page=gf-siren-settings&debug=1`
- Vérifier les logs d'erreurs PHP: `tail -50 /sites/tb-formation.fr/files/wp-content/debug.log`

## Solution potentielle

Si le problème persiste, modifier `Constants::ADMIN_CAPABILITY` :

```php
// Dans src/Core/Constants.php
const ADMIN_CAPABILITY = 'manage_options'; // Au lieu de 'gravityforms_edit_forms'
```

Ou ajouter une vérification dans `AdminManager::add_admin_menu()` :

```php
$capability = current_user_can('gravityforms_edit_forms') 
    ? 'gravityforms_edit_forms' 
    : 'manage_options';
```

