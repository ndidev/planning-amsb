<!-- 
  @component
  
  Ligne de configuration d'un PDF.

  Usage :
  ```tsx
  <LigneConfigPDF config: ConfigPDF={config}>
  ```
 -->
<script lang="ts">
  import { onMount } from "svelte";

  import { Label, Input, Textarea, Checkbox } from "flowbite-svelte";
  import { CircleHelpIcon, EyeIcon, SendIcon } from "lucide-svelte";

  import { ConfigLine } from "../../";

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

  let ligne: HTMLDivElement;

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
      `<div class="text-right">
          <div class="mb-1 mr-8">
            <label>Date début : <input type="date" class="date_debut" value="${date_debut}"></label>
          </div>
          <div class="mb-1 mr-8">
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
      `<div class="text-right">
          <div class="mb-1 mr-8">
            <label>Date début : <input type="date" value="${date_debut}"></label>
          </div>
          <div class="mb-1 mr-8">
            <label>Date fin : <input type="date" value="${date_fin}"></label>
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
</script>

<ConfigLine bind:modificationEnCours bind:ligne>
  <div class="basis-full lg:basis-10/24">
    <div>
      <!-- Fournisseur -->
      <div class="basis-full mb-2">
        <Label for={"fournisseur_" + config.id}>Fournisseur</Label>
        <Svelecte
          inputId={"fournisseur_" + config.id}
          type="tiers"
          role={`${config.module}_fournisseur`}
          bind:value={config.fournisseur}
          name="Fournisseur"
          required
        />
      </div>
    </div>

    <div class="flex flex-row gap-4">
      <!-- Jours avant -->
      <div class="basis-full lg:basis-1/3">
        <Label for="days-before">Jours avant</Label>
        <Input
          type="number"
          min={0}
          id="days-before"
          class="jours_avant"
          bind:value={config.jours_avant}
          required
        />
      </div>

      <!-- Jours après -->
      <div class="basis-full lg:basis-1/3">
        <Label for="days-after">Jours après</Label>
        <Input
          type="number"
          min={0}
          id="days-after"
          class="jours_apres"
          bind:value={config.jours_apres}
          required
        />
      </div>

      <!-- Envoi automatique -->
      <div class="basis-full lg:basis-1/4">
        <Label>
          Envoi automatique <Checkbox bind:checked={config.envoi_auto} />
        </Label>
      </div>
    </div>
  </div>

  <!-- Liste e-mails -->
  <div class="basis-full lg:basis-9/24 mb-auto">
    <Label for={"liste_emails_" + config.id}
      >Liste e-mails <LucideButton
        icon={CircleHelpIcon}
        size="20px"
        title="Afficher les explications"
        on:click={afficherExplicationsEmails}
      /></Label
    >
    <Textarea
      class="liste_emails"
      id={"liste_emails_" + config.id}
      bind:value={config.liste_emails}
      rows={3}
    />
  </div>

  <!-- Boutons -->
  <div slot="actions">
    {#if modificationEnCours}
      <LucideButton
        preset="confirm"
        on:click={isNew ? validerAjout : validerModification}
      />
      <LucideButton
        preset="cancel"
        on:click={isNew ? annulerAjout : annulerModification}
      />
    {:else}
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
    {/if}
  </div>
</ConfigLine>
