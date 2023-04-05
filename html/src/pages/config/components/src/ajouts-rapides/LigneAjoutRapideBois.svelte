<!-- 
  @component
  
  Ligne de configuration d'un RDV rapide bois.

  Usage :
  ```tsx
  <LigneAjoutRapideBois ajoutRapide: AjoutRapideBois={ajoutRapide}>
  ```
 -->
<script lang="ts">
  import { afterUpdate } from "svelte";

  import { Svelecte, MaterialButton } from "@app/components";

  import { configAjoutsRapides } from "@app/stores";

  import Notiflix from "notiflix";

  import { fetcher, notiflixOptions, validerFormulaire } from "@app/utils";

  import type { AjoutRapideBois } from "@app/types";

  export let ajoutRapide: AjoutRapideBois;
  let ajoutRapideInitial = structuredClone(ajoutRapide);

  let isNew: boolean = ajoutRapide.id < 1;

  let modificationEnCours: boolean = isNew; // Modification en cours par défaut si nouveau compte uniquement;

  let ligne: HTMLLIElement;

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

          await configAjoutsRapides.delete(ajoutRapide.id);

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

  afterUpdate(() => {
    // Si changement d'une ligne, activation de la classe "modificationEnCours"
    ligne
      .querySelectorAll<HTMLInputElement | HTMLSelectElement>("input, select")
      .forEach((input) => {
        input.onchange = () => {
          modificationEnCours = true;
        };
        input.oninput = () => (modificationEnCours = true);
      });
  });
</script>

<li class="ligne" bind:this={ligne} class:modificationEnCours>
  <!-- Fournisseur -->
  <div>
    <label for={"fournisseur_" + ajoutRapide.id}>Fournisseur</label>
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
    <label for={"client_" + ajoutRapide.id}>Client</label>
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
    <label for={"chargement_" + ajoutRapide.id}>Chargement</label>
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
    <label for={"livraison_" + ajoutRapide.id}>Livraison</label>
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
    <label for={"transporteur_" + ajoutRapide.id}>Transporteur</label>
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
    <label for={"affreteur_" + ajoutRapide.id}>Affréteur</label>
    <Svelecte
      inputId={"affreteur_" + ajoutRapide.id}
      type="tiers"
      role="bois_affreteur"
      name="Affréteur"
      bind:value={ajoutRapide.affreteur}
      required
    />
  </div>

  <!-- Boutons -->
  <div class="boutons-icone">
    <span class="actions">
      <MaterialButton preset="supprimer" on:click={supprimerLigne} />
    </span>
    <span class="valider-annuler">
      <MaterialButton
        icon="done"
        title="Valider"
        on:click={isNew ? validerAjout : validerModification}
      />
      <MaterialButton
        icon="close"
        title="Annuler"
        on:click={isNew ? annulerAjout : annulerModification}
      />
    </span>
  </div>
</li>

<style>
  .ligne :global(.svelecte-control) {
    width: 100%;
  }

  /* Mobile */
  @media screen and (max-width: 767px) {
    .ligne {
      display: flex;
      flex-direction: column;
    }

    .ligne > div:not(.boutons-icone) {
      width: 100%;
    }

    .boutons-icone {
      width: auto;
      margin: 10px auto;
    }
  }

  /* Desktop */
  @media screen and (min-width: 768px) {
    .ligne {
      display: grid;
      grid-template-areas:
        "a b c g"
        "d e f g";
      grid-template-columns: repeat(3, 1fr) 50px;
      column-gap: 10px;
      row-gap: 5px;
    }

    .boutons-icone {
      grid-area: g;
      align-self: center;
      justify-self: center;
      text-align: center;
    }
  }
</style>
