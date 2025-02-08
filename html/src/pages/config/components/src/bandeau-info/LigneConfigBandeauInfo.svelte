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

  import { Input, Textarea, Checkbox } from "flowbite-svelte";
  import Notiflix from "notiflix";
  import autosize from "autosize";

  import ConfigLine from "../ConfigLine.svelte";
  import { LucideButton } from "@app/components";

  import { configBandeauInfo } from "@app/stores";

  import { notiflixOptions } from "@app/utils";

  import type { ConfigBandeauInfo } from "@app/types";

  export let ligneInfo: ConfigBandeauInfo;
  let ligneInfoInitial = structuredClone(ligneInfo);

  let isNew: boolean = ligneInfo.id < 1;

  let modificationEnCours = isNew; // Modification en cours par défaut si nouveau compte uniquement;

  let ligne: HTMLDivElement;

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
    } finally {
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
    autosize(ligne.querySelector("textarea"));
  });
</script>

<ConfigLine bind:modificationEnCours bind:ligne>
  <!-- Active PC -->
  <Checkbox bind:checked={ligneInfo.pc} class="inline-block basis-2/24"
    >PC</Checkbox
  >

  <!-- Active TV -->
  <Checkbox bind:checked={ligneInfo.tv} class="inline-block basis-2/24"
    >TV</Checkbox
  >

  <!-- Couleur -->
  <input
    type="color"
    class="inline-block basis-2/24 min-h-7 rounded-lg"
    bind:value={ligneInfo.couleur}
  />

  <!-- Message -->
  <Textarea
    class="basis-full lg:basis-14/24"
    bind:value={ligneInfo.message}
    rows={1}
    maxlength={255}
    required
  />

  <!-- Boutons -->
  <div slot="actions">
    {#if !modificationEnCours}
      {#if !isNew}
        <LucideButton preset="delete" on:click={supprimerLigne} />
      {/if}
    {:else}
      <LucideButton
        preset="confirm"
        on:click={isNew ? validerAjout : validerModification}
      />
      <LucideButton
        preset="cancel"
        on:click={isNew ? annulerAjout : annulerModification}
      />
    {/if}
  </div>
</ConfigLine>
