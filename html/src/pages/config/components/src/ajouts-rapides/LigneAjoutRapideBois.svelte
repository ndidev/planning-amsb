<!-- 
  @component
  
  Ligne de configuration d'un RDV rapide bois.

  Usage :
  ```tsx
  <LigneAjoutRapideBois ajoutRapide: AjoutRapideBois={ajoutRapide}>
  ```
 -->
<script lang="ts">
  import { Label } from "flowbite-svelte";

  import { ConfigLine } from "../../";
  import { Svelecte, LucideButton } from "@app/components";

  import { configAjoutsRapides } from "@app/stores";

  import Notiflix from "notiflix";

  import { notiflixOptions, validerFormulaire } from "@app/utils";

  import type { AjoutRapideBois } from "@app/types";

  export let ajoutRapide: AjoutRapideBois;
  let ajoutRapideInitial = structuredClone(ajoutRapide);

  let isNew: boolean = ajoutRapide.id < 1;

  let modificationEnCours: boolean = isNew; // Modification en cours par défaut si nouveau compte uniquement;

  let ligne: HTMLDivElement;

  /**
   * Valider l'ajout.
   */
  async function validerAjout() {
    if (!validerFormulaire(ligne)) return;

    try {
      Notiflix.Block.dots([ligne], notiflixOptions.texts.ajout);
      ligne.style.minHeight = "initial";

      await configAjoutsRapides.create(ajoutRapide);

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
    configAjoutsRapides.cancel(ajoutRapide.id);
  }

  /**
   * Valider les modifications.
   */
  async function validerModification() {
    if (!validerFormulaire(ligne)) return;

    try {
      Notiflix.Block.dots([ligne], notiflixOptions.texts.modification);
      ligne.style.minHeight = "initial";

      await configAjoutsRapides.update(ajoutRapide);

      Notiflix.Notify.success("La configuration a été modifiée");
      ajoutRapideInitial = structuredClone(ajoutRapide);
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
    ajoutRapide = structuredClone(ajoutRapideInitial);
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

          await configAjoutsRapides.delete(ajoutRapide);

          Notiflix.Notify.success("La configuration a été supprimée");
        } catch (erreur) {
          Notiflix.Notify.failure(erreur.message);
          Notiflix.Block.remove([ligne]);
        }
      },
      null,
      notiflixOptions.themes.red
    );
  }
</script>

<ConfigLine bind:modificationEnCours bind:ligne>
  <div class="ligne basis-11/12">
    <!-- Fournisseur -->
    <div>
      <Label for={"fournisseur_" + ajoutRapide.id}>Fournisseur</Label>
      <Svelecte
        inputId={"fournisseur_" + ajoutRapide.id}
        type="tiers"
        role="bois_fournisseur"
        name="Fournisseur"
        bind:value={ajoutRapide.fournisseur}
        required
      />
    </div>

    <!-- Client -->
    <div>
      <Label for={"client_" + ajoutRapide.id}>Client</Label>
      <Svelecte
        inputId={"client_" + ajoutRapide.id}
        type="tiers"
        role="bois_client"
        name="Client"
        bind:value={ajoutRapide.client}
        required
      />
    </div>

    <!-- Chargement -->
    <div>
      <Label for={"chargement_" + ajoutRapide.id}>Chargement</Label>
      <Svelecte
        inputId={"chargement_" + ajoutRapide.id}
        type="tiers"
        role="bois_client"
        name="Chargement"
        bind:value={ajoutRapide.chargement}
        required
      />
    </div>

    <!-- Livraison -->
    <div>
      <Label for={"livraison_" + ajoutRapide.id}>Livraison</Label>
      <Svelecte
        inputId={"livraison_" + ajoutRapide.id}
        type="tiers"
        role="bois_client"
        name="Livraison"
        bind:value={ajoutRapide.livraison}
        required
      />
    </div>

    <!-- Transporteur -->
    <div class="pure-control-group">
      <Label for={"transporteur_" + ajoutRapide.id}>Transporteur</Label>
      <Svelecte
        inputId={"transporteur_" + ajoutRapide.id}
        type="tiers"
        role="bois_transporteur"
        name="Transporteur"
        bind:value={ajoutRapide.transporteur}
        required
      />
    </div>

    <!-- Affréteur -->
    <div>
      <Label for={"affreteur_" + ajoutRapide.id}>Affréteur</Label>
      <Svelecte
        inputId={"affreteur_" + ajoutRapide.id}
        type="tiers"
        role="bois_affreteur"
        name="Affréteur"
        bind:value={ajoutRapide.affreteur}
        required
      />
    </div>
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
      <LucideButton preset="delete" on:click={supprimerLigne} />
    {/if}
  </div>
</ConfigLine>

<style>
  /* Mobile */
  @media screen and (max-width: 767px) {
    .ligne {
      display: flex;
      flex-direction: column;
    }

    .ligne > div:not(.boutons-icone) {
      width: 100%;
    }
  }

  /* Desktop */
  @media screen and (min-width: 768px) {
    .ligne {
      display: grid;
      grid-template-areas:
        "a b c"
        "d e f";
      grid-template-columns: repeat(3, 1fr);
      column-gap: 10px;
      row-gap: 5px;
    }
  }
</style>
