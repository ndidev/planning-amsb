<!-- 
  @component
  
  Ligne de configuration d'un PDF.

  Usage :
  ```tsx
  <LigneConfigPDF config: ConfigPDF={config}>
  ```
 -->
<script lang="ts">
  import { onMount, afterUpdate } from "svelte";

  import { CircleHelpIcon, EyeIcon, SendIcon } from "lucide-svelte";

  import { LucideButton, Svelecte } from "@app/components";

  import { configPdf } from "@app/stores";

  import Notiflix from "notiflix";
  import autosize from "autosize";

  import {
    fetcher,
    DateUtils,
    notiflixOptions,
    validerFormulaire,
  } from "@app/utils";

  import type { ConfigPDF } from "@app/types";

  export let config: ConfigPDF;
  let configInitial: ConfigPDF = structuredClone(config);

  let isNew: boolean = config.id < 1;

  let modificationEnCours: boolean = isNew; // Modification en cours par défaut si nouveau compte uniquement;

  let ligne: HTMLLIElement;

  /**
   * Afficher les explications pour la liste d'e-mails.
   */
  function afficherExplicationsEmails() {
    Notiflix.Report.info(
      "Liste e-mails",
      "Une adresse par ligne.<br />" +
        "La liste d'adresses ne peut pas être vide.<br />" +
        "Pour ne pas envoyer à une adresse sans la supprimer, la faire précéder d'un point d'exclamation \"!\".",
      "Fermer"
    );
  }

  /**
   * Visualiser le PDF.
   */
  function visualiserPdf() {
    if (!validerFormulaire(ligne)) return;

    const date_debut = new DateUtils(new Date())
      .jourOuvrePrecedent(config.jours_avant)
      .toLocaleISODateString();

    const date_fin = new DateUtils(new Date())
      .jourOuvreSuivant(config.jours_apres)
      .toLocaleISODateString();

    Notiflix.Confirm.show(
      "Visualiser le PDF",
      `<div class="pure-form pdf-form-notiflix">
          <div class="pdf-champ-notiflix">
            <label>Date début : <input type="date" class="date_debut" value="${date_debut}"></label>
          </div>
          <div class="pdf-champ-notiflix">
            <label>Date fin : <input type="date" class="date_fin" value="${date_fin}"></label>
          </div>
        </div>`,
      "Visualiser",
      "Annuler",
      async () => {
        try {
          Notiflix.Block.dots([ligne], notiflixOptions.texts.telechargement);
          ligne.style.minHeight = "initial";

          const blob: Blob = await fetcher("config/pdf/generer", {
            accept: "blob",
            searchParams: {
              config: config.id.toString(),
              date_debut:
                document.querySelector<HTMLInputElement>(".date_debut").value,
              date_fin:
                document.querySelector<HTMLInputElement>(".date_fin").value,
            },
          });

          const file = URL.createObjectURL(blob);
          const filename = "planning.pdf";
          const link = document.createElement("a");
          link.href = file;
          link.download = filename;
          link.click();
        } catch (err) {
          Notiflix.Notify.failure(err.message);
        } finally {
          Notiflix.Block.remove([ligne]);
        }
      },
      null,
      notiflixOptions.themes.green
    );
  }

  /**
   * Envoyer le PDF par e-mail.
   */
  function envoyerPdf() {
    if (!validerFormulaire(ligne)) return;

    const date_debut = new DateUtils(new Date())
      .jourOuvrePrecedent(config.jours_avant)
      .toLocaleISODateString();

    const date_fin = new DateUtils(new Date())
      .jourOuvreSuivant(config.jours_apres)
      .toLocaleISODateString();

    Notiflix.Confirm.show(
      "Envoyer le PDF",
      `<div class="pure-form pdf-form-notiflix">
          <div class="pdf-champ-notiflix">
            <label>Date début : <input type="date" class="date_debut" value="${date_debut}"></label>
          </div>
          <div class="pdf-champ-notiflix">
            <label>Date fin : <input type="date" class="date_fin" value="${date_fin}"></label>
          </div>
        </div>`,
      "Envoyer",
      "Annuler",
      async () => {
        try {
          Notiflix.Block.dots([ligne], notiflixOptions.texts.envoi);
          ligne.style.minHeight = "initial";

          type SendingResult = {
            statut: "succes" | "echec";
            message: string;
            module: string;
            fournisseur: number;
            adresses: {
              from: string;
              to: string[];
              cc: [];
              bcc: [];
            };
          };

          const resultat: SendingResult = await fetcher("config/pdf/generer", {
            requestInit: {
              method: "POST",
              body: JSON.stringify({
                config: config.id.toString(),
                date_debut:
                  document.querySelector<HTMLInputElement>(".date_debut").value,
                date_fin:
                  document.querySelector<HTMLInputElement>(".date_fin").value,
              }),
            },
          });

          if (resultat.statut === "succes") {
            Notiflix.Notify.success(resultat.message);
          }

          if (resultat.statut === "echec") {
            throw new Error("Échec d'envoi du PDF");
          }
        } catch (err) {
          Notiflix.Notify.failure(err.message);
        } finally {
          Notiflix.Block.remove([ligne]);
        }
      },
      null,
      notiflixOptions.themes.green
    );
  }

  /**
   * Valider l'ajout.
   */
  async function validerAjout() {
    if (!validerFormulaire(ligne)) return;

    try {
      Notiflix.Block.dots([ligne], notiflixOptions.texts.ajout);
      ligne.style.minHeight = "initial";

      await configPdf.create(config);

      Notiflix.Notify.success("La ligne de configuration a été ajoutée");
    } catch (erreur) {
      Notiflix.Notify.failure(erreur.message);
    } finally {
      Notiflix.Block.remove([ligne]);
    }
  }

  /**
   * Annuler l'ajout.
   */
  function annulerAjout() {
    configPdf.cancel(config.id);
  }

  /**
   * Valider les modifications.
   */
  async function validerModification() {
    if (!validerFormulaire(ligne)) return;

    try {
      Notiflix.Block.dots([ligne], notiflixOptions.texts.modification);
      ligne.style.minHeight = "initial";

      await configPdf.update(config);

      Notiflix.Notify.success("La configuration a été modifiée");
      configInitial = structuredClone(config);
      modificationEnCours = false;
    } catch (erreur) {
      Notiflix.Notify.failure(erreur.message);
    } finally {
      Notiflix.Block.remove([ligne]);
    }
  }

  /**
   * Annuler les modifications.
   */
  function annulerModification() {
    config = structuredClone(configInitial);
    modificationEnCours = false;
    ligne.querySelectorAll("input").forEach((input) => {
      input.setCustomValidity("");
    });
  }

  /**
   * Supprimer une ligne.
   */
  function supprimerLigne() {
    // Demande de confirmation
    Notiflix.Confirm.show(
      "Suppression configuration",
      `Voulez-vous vraiment supprimer la configuration ?`,
      "Supprimer",
      "Annuler",
      async function () {
        try {
          Notiflix.Block.dots([ligne], notiflixOptions.texts.suppression);
          ligne.style.minHeight = "initial";

          await configPdf.delete(config.id);

          Notiflix.Notify.success("La configuration a été supprimée");
        } catch (erreur) {
          Notiflix.Notify.failure(erreur.message);
        } finally {
          Notiflix.Block.remove([ligne]);
        }
      },
      null,
      notiflixOptions.themes.red
    );
  }

  onMount(() => {
    ligne.id = "config_pdf_" + config.id;

    autosize(ligne.querySelector("textarea"));
  });

  afterUpdate(() => {
    // Si changement d'une ligne, activation de la classe "modificationEnCours"
    ligne
      .querySelectorAll<
        HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement
      >("input, textarea, select")
      .forEach((input) => {
        input.onchange = () => (modificationEnCours = true);
        input.oninput = () => (modificationEnCours = true);
      });
  });
</script>

<!-- <li class="ligne pure-form" class:modificationEnCours bind:this={ligne}> -->
<li class="ligne pure-form" class:modificationEnCours bind:this={ligne}>
  <!-- Fournisseur -->
  <div class="champ pure-u-1 pure-u-lg-5-24">
    <div class="pure-control-group">
      <label for={"founisseur_" + config.id}>Fournisseur</label>
      <Svelecte
        inputId={"founisseur_" + config.id}
        type="tiers"
        role={`${config.module}_fournisseur`}
        bind:value={config.fournisseur}
        name="Fournisseur"
        required
      />
    </div>
  </div>
  <!-- Jours avant -->
  <div class="champ pure-u-1 pure-u-lg-2-24">
    <label
      >Jours avant
      <input
        type="number"
        min="0"
        class="jours_avant"
        bind:value={config.jours_avant}
        required
      />
    </label>
  </div>
  <!-- Jours après -->
  <div class="champ pure-u-1 pure-u-lg-2-24">
    <label
      >Jours après
      <input
        type="number"
        min="0"
        class="jours_apres"
        bind:value={config.jours_apres}
        required
      />
    </label>
  </div>
  <!-- Envoi automatique -->
  <div class="champ pure-u-1 pure-u-lg-2-24">
    <label class="pure-checkbox"
      >Envoi automatique
      <input
        type="checkbox"
        class="envoi_auto"
        bind:checked={config.envoi_auto}
      />
    </label>
  </div>
  <!-- Liste e-mails -->
  <div class="champ pure-u-1 pure-u-lg-9-24">
    <label for={"liste_emails_" + config.id}
      >Liste e-mails <LucideButton
        icon={CircleHelpIcon}
        title="Afficher les explications"
        on:click={afficherExplicationsEmails}
      /></label
    >
    <textarea
      class="liste_emails"
      id={"liste_emails_" + config.id}
      bind:value={config.liste_emails}
      rows="3"
    />
  </div>
  <!-- Boutons -->
  <span class="actions">
    <LucideButton
      icon={EyeIcon}
      title="Visualiser le PDF"
      on:click={visualiserPdf}
    />
    <LucideButton
      icon={SendIcon}
      title="Envoyer le PDF"
      on:click={envoyerPdf}
    />
    <LucideButton preset="delete" on:click={supprimerLigne} />
  </span>
  <span class="valider-annuler">
    <LucideButton
      preset="confirm"
      on:click={isNew ? validerAjout : validerModification}
    />
    <LucideButton
      preset="cancel"
      on:click={isNew ? annulerAjout : annulerModification}
    />
  </span>
</li>

<style>
  .ligne .champ {
    display: flex;
    flex-direction: column;
    margin-left: 1%;
  }

  .ligne:global(.fournisseur) {
    width: 240px;
  }

  .jours_avant,
  .jours_apres {
    width: 80%;
  }

  textarea {
    padding: 5px;
  }

  /* Dates dans Notiflix */
  :global(.pdf-form-notiflix) {
    text-align: right;
  }

  :global(.pdf-champ-notiflix) {
    margin-bottom: 5px;
    margin-right: 30px;
  }

  /* Mobile */
  @media screen and (max-width: 767px) {
    .ligne {
      flex-direction: column;
    }

    .liste_emails {
      margin-top: 5px;
    }

    .actions,
    .valider-annuler {
      margin: 10px auto;
    }
  }
  /* Desktop */
  @media screen and (min-width: 768px) {
    .ligne :global(.svelecte-control) {
      width: 200px;
    }

    .liste_emails {
      margin-top: 0;
    }
  }
</style>
