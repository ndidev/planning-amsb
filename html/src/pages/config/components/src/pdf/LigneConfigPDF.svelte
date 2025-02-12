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

  import {
    Label,
    Input,
    Textarea,
    Checkbox,
    Button,
    Modal,
  } from "flowbite-svelte";
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

  let showModal = false;
  let modalAction: "view" | "send";
  let startDate = new DateUtils(new Date())
    .getPreviousWorkingDay(config.jours_avant)
    .toLocaleISODateString();
  let endDate = new DateUtils(new Date())
    .getNextWorkingDay(config.jours_apres)
    .toLocaleISODateString();

  /**
   * Afficher les explications pour la liste d'e-mails.
   */
  function showEmailsHelp() {
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
  async function viewPdf(startDate: string, endDate: string) {
    if (!validerFormulaire(ligne)) return;

    try {
      Notiflix.Block.dots([ligne], notiflixOptions.texts.telechargement);
      ligne.style.minHeight = "initial";

      const blob: Blob = await fetcher("config/pdf/generer", {
        accept: "blob",
        searchParams: {
          config: config.id.toString(),
          date_debut: startDate,
          date_fin: endDate,
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
  }

  /**
   * Envoyer le PDF par e-mail.
   */
  async function sendPdf(startDate: string, endDate: string) {
    if (!validerFormulaire(ligne)) return;

    try {
      Notiflix.Block.dots([ligne], notiflixOptions.texts.envoi);
      ligne.style.minHeight = "initial";

      await fetcher("config/pdf/generer", {
        requestInit: {
          method: "POST",
          body: JSON.stringify({
            config: config.id.toString(),
            date_debut: startDate,
            date_fin: endDate,
          }),
        },
      });

      Notiflix.Notify.success("Le PDF a été envoyé avec succès");
    } catch (err) {
      Notiflix.Notify.failure(err.message);
    } finally {
      Notiflix.Block.remove([ligne]);
    }
  }

  /**
   * Valider l'ajout.
   */
  async function createConfig() {
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
  function cancelCreate() {
    configPdf.cancel(config.id);
  }

  /**
   * Valider les modifications.
   */
  async function updateConfig() {
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
  function cancelUpdate() {
    config = structuredClone(configInitial);
    modificationEnCours = false;
    ligne.querySelectorAll("input").forEach((input) => {
      input.setCustomValidity("");
    });
  }

  /**
   * Supprimer une ligne.
   */
  function deleteConfig() {
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
        on:click={showEmailsHelp}
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
        on:click={isNew ? createConfig : updateConfig}
      />
      <LucideButton
        preset="cancel"
        on:click={isNew ? cancelCreate : cancelUpdate}
      />
    {:else}
      <LucideButton
        icon={EyeIcon}
        title="Visualiser le PDF"
        on:click={() => {
          modalAction = "view";
          showModal = true;
        }}
      />
      <LucideButton
        icon={SendIcon}
        title="Envoyer le PDF"
        on:click={() => {
          modalAction = "send";
          showModal = true;
        }}
      />
      <LucideButton preset="delete" on:click={deleteConfig} />
    {/if}
  </div>
</ConfigLine>

<Modal
  title={modalAction === "view" ? "Visualiser le PDF" : "Envoyer le PDF"}
  bind:open={showModal}
  outsideclose
  autoclose
  dismissable={false}
  size="xs"
>
  <div>
    <Label for="start-date">Date début :</Label>
    <Input type="date" id="start-date" bind:value={startDate} max={endDate} />
  </div>
  <div>
    <Label for="end-date">Date fin :</Label>
    <Input type="date" id="end-date" bind:value={endDate} min={startDate} />
  </div>
  <div class="text-center">
    <Button
      on:click={() => {
        switch (modalAction) {
          case "view":
            viewPdf(startDate, endDate);
            break;
          case "send":
            sendPdf(startDate, endDate);
            break;
        }
      }}>{modalAction === "view" ? "Visualiser" : "Envoyer"}</Button
    >
    <Button on:click={() => (showModal = false)} color="dark">Annuler</Button>
  </div>
</Modal>
