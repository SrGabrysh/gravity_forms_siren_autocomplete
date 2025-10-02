/**
 * JavaScript Admin - Gravity Forms Siren Autocomplete
 *
 * @package GFSirenAutocomplete
 */

(function ($) {
  "use strict";

  const GFSirenAdmin = {
    /**
     * Initialisation
     */
    init: function () {
      this.bindEvents();
      this.initTabs();
      this.autoLoadExistingMapping();
    },

    /**
     * Attache les événements
     */
    bindEvents: function () {
      // Test de l'API
      $(document).on(
        "click",
        "#gf-siren-test-api",
        this.testApiConnection.bind(this)
      );

      // Vidage du cache
      $(document).on(
        "click",
        "#gf-siren-clear-cache",
        this.clearCache.bind(this)
      );

      // Sélection de formulaire pour le mapping
      $(document).on("change", "#form_select", this.loadFormFields.bind(this));
    },

    /**
     * Initialise les onglets
     */
    initTabs: function () {
      $(".nav-tab").on("click", function (e) {
        e.preventDefault();

        const tabId = $(this).attr("href");

        // Activer l'onglet
        $(".nav-tab").removeClass("nav-tab-active");
        $(this).addClass("nav-tab-active");

        // Afficher le contenu
        $(".tab-content").hide();
        $(tabId).show();
      });
    },

    /**
     * Charge automatiquement le mapping existant (ex: formulaire 1)
     */
    autoLoadExistingMapping: function () {
      // Vérifier si un formulaire avec un mapping existe (ex: formulaire 1)
      const $formSelect = $("#form_select");
      
      // Si le select de formulaire a au moins 2 options (l'option vide + un formulaire)
      if ($formSelect.find("option").length > 1) {
        // Sélectionner le premier formulaire disponible (index 1)
        const firstFormId = $formSelect.find("option").eq(1).val();
        
        if (firstFormId) {
          $formSelect.val(firstFormId);
          // Afficher le conteneur des champs de mapping
          $("#gf-siren-mapping-fields").show();
          // Afficher un loader dans les selects
          $(".gf-field-select")
            .html('<option value="">Chargement...</option>')
            .prop("disabled", true);
          // Charger les champs via AJAX
          this.populateFieldSelects(firstFormId);
        }
      }
    },

    /**
     * Teste la connexion à l'API
     */
    testApiConnection: function (e) {
      e.preventDefault();

      const $button = $(e.currentTarget);
      const $result = $("#gf-siren-api-test-result");

      $button.prop("disabled", true).text("Test en cours...");
      $result.html("");

      $.ajax({
        url: gfSirenAdmin.ajax_url,
        type: "POST",
        data: {
          action: "gf_siren_test_api",
          nonce: gfSirenAdmin.nonce,
        },
        success: (response) => {
          if (response.success) {
            $result.html(
              '<span style="color:green;">✓ ' +
                gfSirenAdmin.messages.test_success +
                "</span>"
            );
          } else {
            $result.html(
              '<span style="color:red;">✗ ' +
                (response.data.message || gfSirenAdmin.messages.test_error) +
                "</span>"
            );
          }
        },
        error: () => {
          $result.html(
            '<span style="color:red;">✗ ' +
              gfSirenAdmin.messages.test_error +
              "</span>"
          );
        },
        complete: () => {
          $button.prop("disabled", false).text("Tester la connexion");
        },
      });
    },

    /**
     * Vide le cache
     */
    clearCache: function (e) {
      e.preventDefault();

      if (!confirm(gfSirenAdmin.messages.confirm_delete)) {
        return;
      }

      const $button = $(e.currentTarget);
      const $result = $("#gf-siren-cache-result");

      $button.prop("disabled", true).text("Vidage en cours...");
      $result.html("");

      $.ajax({
        url: gfSirenAdmin.ajax_url,
        type: "POST",
        data: {
          action: "gf_siren_clear_cache",
          nonce: gfSirenAdmin.nonce,
        },
        success: (response) => {
          if (response.success) {
            $result.html(
              '<span style="color:green;">✓ ' +
                response.data.message +
                "</span>"
            );
          } else {
            $result.html(
              '<span style="color:red;">✗ Erreur lors du vidage du cache</span>'
            );
          }
        },
        error: () => {
          $result.html(
            '<span style="color:red;">✗ Erreur lors du vidage du cache</span>'
          );
        },
        complete: () => {
          $button.prop("disabled", false).text("Vider le cache maintenant");
        },
      });
    },

    /**
     * Charge les champs d'un formulaire pour le mapping
     */
    loadFormFields: function (e) {
      const formId = $(e.currentTarget).val();

      if (!formId) {
        $("#gf-siren-mapping-fields").hide();
        return;
      }

      // Afficher un loader
      $("#gf-siren-mapping-fields").show();
      $(".gf-field-select")
        .html('<option value="">Chargement...</option>')
        .prop("disabled", true);

      // Charger les champs via AJAX
      this.populateFieldSelects(formId);
    },

    /**
     * Remplit les selects avec les champs du formulaire
     */
    populateFieldSelects: function (formId) {
      $.ajax({
        url: gfSirenAdmin.ajax_url,
        type: "POST",
        data: {
          action: "gf_siren_load_form_fields",
          nonce: gfSirenAdmin.nonce,
          form_id: formId,
        },
        success: (response) => {
          if (response.success) {
            const fields = response.data.fields;
            const mapping = response.data.mapping;

            // Remplir tous les selects avec les champs disponibles
            $(".gf-field-select").each(function () {
              const $select = $(this);
              const fieldKey = $select
                .attr("name")
                .match(/\[mapping\]\[(.+)\]/)[1];

              // Vider et réactiver le select
              $select
                .html('<option value="">-- Non mappé --</option>')
                .prop("disabled", false);

              // Ajouter les options
              fields.forEach((field) => {
                $select.append(
                  $("<option>", {
                    value: field.id,
                    text: field.label + " (ID: " + field.id + ")",
                  })
                );
              });

              // Pré-sélectionner la valeur existante du mapping
              if (mapping && mapping[fieldKey]) {
                $select.val(mapping[fieldKey]);
              }
            });
          } else {
            alert(
              "Erreur lors du chargement des champs: " +
                (response.data.message || "Erreur inconnue")
            );
            $(".gf-field-select")
              .html('<option value="">Erreur de chargement</option>')
              .prop("disabled", false);
          }
        },
        error: () => {
          alert("Erreur lors du chargement des champs du formulaire.");
          $(".gf-field-select")
            .html('<option value="">Erreur de chargement</option>')
            .prop("disabled", false);
        },
      });
    },
  };

  // Initialisation au chargement du DOM
  $(document).ready(() => {
    GFSirenAdmin.init();
  });
})(jQuery);
