/**
 * JavaScript Frontend - Gravity Forms Siren Autocomplete
 *
 * @package GFSirenAutocomplete
 */

(function ($) {
  "use strict";

  const GFSirenFrontend = {
    /**
     * Initialisation
     */
    init: function () {
      this.bindEvents();
    },

    /**
     * Attache les événements
     */
    bindEvents: function () {
      $(document).on(
        "click",
        ".gf-siren-verify-button",
        this.handleVerifyClick.bind(this)
      );
      $(document).on(
        "change",
        'input[id^="input_"]',
        this.detectManualEdit.bind(this)
      );
    },

    /**
     * Gère le clic sur le bouton "Vérifier"
     */
    handleVerifyClick: function (e) {
      e.preventDefault();

      const $button = $(e.currentTarget);
      const formId = $button.data("form-id");
      const fieldId = $button.data("field-id");
      const nonce = $button.data("nonce");

      const $container = $button.closest(".gf-siren-verify-container");
      const $loader = $container.find(".gf-siren-loader");
      const $message = $container.find(".gf-siren-message");

      // Récupérer la valeur du SIRET
      const siretValue = $(
        "#input_" + formId + "_" + String(fieldId).replace(".", "_")
      ).val();

      if (!siretValue || siretValue.trim() === "") {
        this.showMessage($message, gfSirenData.messages.error_invalid, "error");
        return;
      }

      // Récupérer les données du représentant (si mappées)
      const prenomValue = this.getRepresentantField(formId, "prenom");
      const nomValue = this.getRepresentantField(formId, "nom");

      // Désactiver le bouton et afficher le loader
      $button.prop("disabled", true);
      $loader.show();
      $message.empty();

      // Appel AJAX
      $.ajax({
        url: gfSirenData.ajax_url,
        type: "POST",
        data: {
          action: "gf_siren_verify",
          nonce: nonce,
          form_id: formId,
          siret: siretValue,
          prenom: prenomValue,
          nom: nomValue,
        },
        success: (response) => {
          if (response.success && response.data) {
            this.fillFormFields(formId, response.data.data);
            this.showMessage(
              $message,
              response.data.message,
              response.data.est_actif ? "success" : "warning"
            );

            // Marquer le formulaire comme vérifié
            this.markAsVerified(formId, siretValue);

            // Avertissement si entreprise inactive
            if (!response.data.est_actif) {
              this.showMessage(
                $message,
                gfSirenData.messages.warning_inactive,
                "warning",
                true
              );
            }
          } else {
            this.showMessage(
              $message,
              response.data.message || gfSirenData.messages.error_api,
              "error"
            );
          }
        },
        error: (xhr) => {
          let errorMsg = gfSirenData.messages.error_api;

          if (xhr.status === 404) {
            errorMsg = gfSirenData.messages.error_not_found;
          } else if (xhr.status === 408 || xhr.statusText === "timeout") {
            errorMsg = gfSirenData.messages.error_timeout;
          }

          this.showMessage($message, errorMsg, "error");
        },
        complete: () => {
          $button.prop("disabled", false);
          $loader.hide();
        },
      });
    },

    /**
     * Remplit les champs du formulaire avec les données
     */
    fillFormFields: function (formId, data) {
      $.each(data, (fieldId, value) => {
        const $field = $("#input_" + formId + "_" + fieldId.replace(".", "_"));
        if ($field.length) {
          $field.val(value).trigger("change");
          $field.addClass("gf-siren-auto-filled");
        }
      });
    },

    /**
     * Récupère la valeur d'un champ représentant
     */
    getRepresentantField: function (formId, type) {
      // Cette fonction suppose que les champs sont mappés
      // En production, il faudrait récupérer le mapping depuis PHP
      const $field = $('input[name*="' + type + '"]').first();
      return $field.length ? $field.val() : "";
    },

    /**
     * Affiche un message
     */
    showMessage: function ($container, message, type, append = false) {
      const cssClass = "gf-siren-message-" + type;
      const icon = type === "success" ? "✓" : type === "warning" ? "⚠" : "✗";

      const $msg = $("<div>")
        .addClass("gf-siren-message-box " + cssClass)
        .html('<span class="icon">' + icon + "</span> " + message);

      if (append) {
        $container.append($msg);
      } else {
        $container.html($msg);
      }
    },

    /**
     * Marque le formulaire comme vérifié
     */
    markAsVerified: function (formId, siret) {
      // Ajouter des champs cachés pour la validation côté serveur
      const $form = $("#gform_" + formId);

      // Supprimer les anciens champs cachés
      $form.find('input[name^="gf_siren_verified_"]').remove();

      // Ajouter les nouveaux
      $form.append(
        $("<input>").attr({
          type: "hidden",
          name: "gf_siren_verified_" + formId,
          value: "1",
        })
      );

      $form.append(
        $("<input>").attr({
          type: "hidden",
          name: "gf_siren_verified_siret_" + formId,
          value: siret,
        })
      );
    },

    /**
     * Détecte les modifications manuelles
     */
    detectManualEdit: function (e) {
      const $field = $(e.currentTarget);

      if ($field.hasClass("gf-siren-auto-filled")) {
        $field.removeClass("gf-siren-auto-filled");
        $field.addClass("gf-siren-manually-edited");

        // Afficher un avertissement
        const $warning = $('<small class="gf-siren-edit-warning">').text(
          gfSirenData.messages.warning_modified
        );

        if (!$field.next(".gf-siren-edit-warning").length) {
          $field.after($warning);
        }
      }
    },
  };

  // Initialisation au chargement du DOM
  $(document).ready(() => {
    GFSirenFrontend.init();
  });
})(jQuery);
