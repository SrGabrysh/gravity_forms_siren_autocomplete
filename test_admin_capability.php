<?php
/**
 * Test des capacités utilisateur pour l'admin
 * Exécuter: wp eval-file test_admin_capability.php
 */

echo "\n========== TEST CAPACITÉS UTILISATEUR ==========\n\n";

// Test utilisateur courant (contexte WP-CLI = aucun utilisateur)
$user = wp_get_current_user();
echo "User ID: " . $user->ID . "\n";
echo "Login: " . $user->user_login . "\n";
echo "Roles: " . implode(", ", $user->roles) . "\n";

// Test avec un admin
$admins = get_users(array('role' => 'administrator', 'number' => 1));
if (!empty($admins)) {
    $admin = $admins[0];
    echo "\n--- Test avec premier admin ---\n";
    echo "Admin ID: " . $admin->ID . "\n";
    echo "Admin Login: " . $admin->user_login . "\n";
    
    // Simuler l'utilisateur
    wp_set_current_user($admin->ID);
    
    echo "Has gravityforms_edit_forms: " . (current_user_can('gravityforms_edit_forms') ? "YES" : "NO") . "\n";
    echo "Has manage_options: " . (current_user_can('manage_options') ? "YES" : "NO") . "\n";
}

// Test du menu
echo "\n========== TEST MENU ADMIN ==========\n\n";

global $menu, $submenu;

echo "Recherche des menus Gravity Forms:\n";
foreach ($submenu as $parent => $items) {
    if (strpos($parent, 'gf_') !== false) {
        echo "\nParent: $parent\n";
        foreach ($items as $item) {
            echo "  - {$item[0]} (capability: {$item[1]}, slug: {$item[2]})\n";
        }
    }
}

// Vérifier notre menu spécifiquement
echo "\nRecherche du menu gf-siren-settings:\n";
$found = false;
foreach ($submenu as $parent => $items) {
    foreach ($items as $item) {
        if (strpos($item[2], 'gf-siren') !== false) {
            $found = true;
            echo "✅ Menu trouvé!\n";
            echo "  Parent: $parent\n";
            echo "  Titre: {$item[0]}\n";
            echo "  Capability requise: {$item[1]}\n";
            echo "  Slug: {$item[2]}\n";
        }
    }
}

if (!$found) {
    echo "❌ Menu gf-siren-settings non trouvé!\n";
}

echo "\n========== TEST CONSTANTS ==========\n\n";

if (class_exists('GFSirenAutocomplete\Core\Constants')) {
    echo "ADMIN_CAPABILITY: " . \GFSirenAutocomplete\Core\Constants::ADMIN_CAPABILITY . "\n";
    echo "ADMIN_MENU_SLUG: " . \GFSirenAutocomplete\Core\Constants::ADMIN_MENU_SLUG . "\n";
}

echo "\n========== FIN TEST ==========\n\n";

