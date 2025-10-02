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

      // Dans un cas réel, on chargerait les champs via AJAX
      // Pour simplifier, on affiche juste la section
      $("#gf-siren-mapping-fields").show();

      // Charger les champs du formulaire (appel AJAX à implémenter)
      this.populateFieldSelects(formId);
    },

    /**
     * Remplit les selects avec les champs du formulaire
     */
    populateFieldSelects: function (formId) {
      // Cette fonction devrait charger les champs via AJAX
      // Pour l'instant, elle est laissée vide car elle nécessite
      // l'API Gravity Forms
      console.log("Loading fields for form ID:", formId);
    },
  };

  // Initialisation au chargement du DOM
  $(document).ready(() => {
    GFSirenAdmin.init();
  });
})(jQuery);
