<!-- 
  @component
  
  Ligne de configuration d'une ligne du bandeau d'information.

  Usage :
  ```tsx
  <ConfigBandeauInfo ligneInfo: ConfigBandeauInfo={ligneInfo}>
  ```
 -->
<script lang="ts">
  import { onMount } from "svelte";

  import Notiflix from "notiflix";
  import autosize from "autosize";

  import { MaterialButton } from "@app/components";

  import { configBandeauInfo } from "@app/stores";

  import { notiflixOptions } from "@app/utils";

  import type { ConfigBandeauInfo } from "@app/types";

  export let ligneInfo: ConfigBandeauInfo;
  let ligneInfoInitial = structuredClone(ligneInfo);

  let isNew: boolean = ligneInfo.id < 1;

  let modificationEnCours = isNew; // Modification en cours par défaut si nouveau compte uniquement;

  let ligne: HTMLLIElement;

  $: ligneInfo.message = ligneInfo.message.slice(0, 255);

  /**
   * Valider l'ajout.
   */
  async function validerAjout() {
    try {
      Notiflix.Block.dots([ligne], notiflixOptions.texts.ajout);
      ligne.style.minHeight = "initial";

      await configBandeauInfo.create(ligneInfo);

      Notiflix.Notify.success("La ligne d'information a été ajoutée");
    } catch (erreur) {
      Notiflix.Notify.failure(erreur.message);
      Notiflix.Block.remove([ligne]);
    }
  }

  /**
   * Annuler l'ajout.
   */
  function annulerAjout() {
    configBandeauInfo.cancel(ligneInfo.id);
  }

  /**
   * Valider les modifications.
   */
  async function validerModification() {
    try {
      Notiflix.Block.dots([ligne], notiflixOptions.texts.modification);
      ligne.style.minHeight = "initial";

      await configBandeauInfo.update(ligneInfo);

      Notiflix.Notify.success("La ligne d'information a été modifiée");
      ligneInfoInitial = structuredClone(ligneInfo);
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
    ligneInfo = structuredClone(ligneInfoInitial);
    modificationEnCours = false;
  }

  /**
   * Supprimer une ligne.
   */
  function supprimerLigne() {
    // Demande de confirmation
    Notiflix.Confirm.show(
      "Suppression ligne d'information",
      `Voulez-vous vraiment supprimer la ligne d'information ?`,
      "Supprimer",
      "Annuler",
      async function () {
        try {
          Notiflix.Block.dots([ligne], notiflixOptions.texts.suppression);
          ligne.style.minHeight = "initial";

          await configBandeauInfo.delete(ligneInfo.id);

          Notiflix.Notify.success("La ligne d'information a été supprimée");
        } catch (erreur) {
          console.error(erreur);
          Notiflix.Notify.failure(erreur.message);
          Notiflix.Block.remove([ligne]);
        }
      },
      null,
      notiflixOptions.themes.red
    );
  }

  onMount(() => {
    // Si changement d'une ligne, activation de la classe "modificationEnCours"
    ligne
      .querySelectorAll<HTMLInputElement | HTMLTextAreaElement>(
        "input, textarea"
      )
      .forEach((input) => {
        input.onchange = () => (modificationEnCours = true);
        input.oninput = () => (modificationEnCours = true);
      });

    autosize(ligne.querySelector("textarea"));
  });
</script>

<li class="ligne pure-form" class:modificationEnCours bind:this={ligne}>
  <div class="bloc pure-u-1 pure-u-lg-6-24">
    <!-- Active PC -->
    <span class="champ pure-u-7-24">
      <label class="pure-checkbox"
        >PC
        <input type="checkbox" bind:checked={ligneInfo.pc} />
      </label>
    </span>
    <!-- Active TV -->
    <span class="champ pure-u-7-24">
      <label class="pure-checkbox"
        >TV
        <input type="checkbox" bind:checked={ligneInfo.tv} />
      </label>
    </span>
    <!-- Couleur -->
    <span class="champ pure-u-7-24">
      <input type="color" class="couleur" bind:value={ligneInfo.couleur} />
    </span>
  </div>
  <!-- Message -->
  <span class="champ pure-u-1 pure-u-lg-16-24">
    <textarea
      class="message"
      bind:value={ligneInfo.message}
      rows="1"
      maxlength="255"
    />
  </span>
  <!-- Boutons -->
  <span class="actions">
    {#if !isNew && !modificationEnCours}
      <MaterialButton preset="supprimer" on:click={supprimerLigne} />
    {/if}
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
</li>

<style>
  .ligne .champ {
    margin-left: 1%;
  }

  input[type="color"] {
    margin: 0.5em 0;
  }

  textarea {
    width: 100%;
    padding: 5px;
  }

  @media screen and (max-width: 480px) {
    .message {
      margin-top: 5px;
    }
  }
</style>
