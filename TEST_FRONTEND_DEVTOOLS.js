/**
 * TESTS FRONTEND À EXÉCUTER DANS LA CONSOLE DU NAVIGATEUR (DevTools)
 * URL: https://tb-formation.fr/test-de-positionnement/
 *
 * Copiez-collez ces tests un par un dans la console Chrome/Firefox
 */

// ========================================
// TEST 1: Vérifier que jQuery est chargé
// ========================================
console.log("=== TEST 1: jQuery ===");
if (typeof jQuery !== "undefined") {
  console.log("✅ jQuery version:", jQuery.fn.jquery);
} else {
  console.log("❌ jQuery non chargé");
}

// ========================================
// TEST 2: Vérifier que le script frontend.js est chargé
// ========================================
console.log("\n=== TEST 2: Script frontend.js ===");
if (typeof GFSirenFrontend !== "undefined") {
  console.log("✅ GFSirenFrontend chargé");
  console.log("Méthodes:", Object.keys(GFSirenFrontend));
} else {
  console.log("❌ GFSirenFrontend non chargé");
}

// ========================================
// TEST 3: Vérifier les données localisées
// ========================================
console.log("\n=== TEST 3: Données gfSirenData ===");
if (typeof gfSirenData !== "undefined") {
  console.log("✅ gfSirenData chargé");
  console.log("AJAX URL:", gfSirenData.ajax_url);
  console.log("Form ID:", gfSirenData.form_id);
  console.log("Nonce:", gfSirenData.nonce);
  console.log("Messages:", gfSirenData.messages);
} else {
  console.log("❌ gfSirenData non chargé");
}

// ========================================
// TEST 4: Vérifier la présence du bouton
// ========================================
console.log("\n=== TEST 4: Bouton Vérifier SIRET ===");
const $button = jQuery(".gf-siren-verify-button");
if ($button.length > 0) {
  console.log("✅ Bouton trouvé");
  console.log("  - Form ID:", $button.data("form-id"));
  console.log("  - Field ID:", $button.data("field-id"));
  console.log("  - Nonce:", $button.data("nonce"));
  console.log("  - Texte:", $button.text());
} else {
  console.log("❌ Bouton non trouvé");
  console.log("  Vérifier si le formulaire ID 1 est présent");
}

// ========================================
// TEST 5: Vérifier le champ SIRET
// ========================================
console.log("\n=== TEST 5: Champ SIRET ===");
const $siretField = jQuery("#input_1_1");
if ($siretField.length > 0) {
  console.log("✅ Champ SIRET trouvé (ID: input_1_1)");
  console.log("  - Type:", $siretField.attr("type"));
  console.log("  - Valeur actuelle:", $siretField.val());
} else {
  console.log("❌ Champ SIRET non trouvé");
}

// ========================================
// TEST 6: Simuler un clic sur le bouton (avec SIRET de test)
// ========================================
console.log("\n=== TEST 6: Simulation du clic ===");
console.log("Pour tester, remplissez d'abord le champ SIRET:");
console.log("jQuery('#input_1_1').val('83317704900017');");
console.log("Puis cliquez sur le bouton:");
console.log("jQuery('.gf-siren-verify-button').click();");
console.log("\nOu exécutez tout d'un coup:");
console.log(
  "jQuery('#input_1_1').val('83317704900017'); jQuery('.gf-siren-verify-button').click();"
);

// ========================================
// TEST 7: Vérifier les événements attachés
// ========================================
console.log("\n=== TEST 7: Événements attachés ===");
const buttonEvents = jQuery._data($button[0], "events");
if (buttonEvents) {
  console.log("✅ Événements sur le bouton:", Object.keys(buttonEvents));
} else {
  console.log("❌ Aucun événement attaché au bouton");
}

// ========================================
// TEST 8: Tester l'appel AJAX manuellement
// ========================================
console.log("\n=== TEST 8: Test AJAX manuel ===");
console.log("Pour tester l'appel AJAX:");
console.log(`
jQuery.ajax({
    url: gfSirenData.ajax_url,
    method: 'POST',
    data: {
        action: 'gf_siren_verify',
        nonce: gfSirenData.nonce,
        form_id: 1,
        siret: '83317704900017',
        prenom: '',
        nom: ''
    },
    success: function(response) {
        console.log('✅ Succès AJAX:', response);
    },
    error: function(xhr, status, error) {
        console.log('❌ Erreur AJAX:', error);
        console.log('Status:', xhr.status);
        console.log('Response:', xhr.responseText);
    }
});
`);

// ========================================
// TEST 9: Vérifier le CSS
// ========================================
console.log("\n=== TEST 9: Styles CSS ===");
const buttonStyle = window.getComputedStyle($button[0]);
if (buttonStyle) {
  console.log("✅ Styles du bouton:");
  console.log("  - Display:", buttonStyle.display);
  console.log("  - Visibility:", buttonStyle.visibility);
  console.log("  - Background:", buttonStyle.backgroundColor);
}

// ========================================
// RÉSUMÉ
// ========================================
console.log("\n" + "=".repeat(50));
console.log("RÉSUMÉ DES TESTS");
console.log("=".repeat(50));
console.log("Copiez les résultats ci-dessus et envoyez-les pour analyse.");
