<!-- 
  @component
  
  Ligne de configuration d'une côte.

  Usage :
  ```tsx
  <LigneCote cote: Cote={cote}>
  ```
 -->
<script lang="ts">
  import { MaterialButton, InputDecimal } from "@app/components";

  import Notiflix from "notiflix";

  import { fetcher, notiflixOptions } from "@app/utils";

  import type { Cote } from "@app/types";

  export let cote: Cote;
  let coteInitial = structuredClone(cote);

  let modificationEnCours = false;

  let ligne: HTMLDivElement;

  /**
   * Validation des modifications
   */
  async function validerModification() {
    try {
      Notiflix.Block.dots([ligne]);
      ligne.style.minHeight = "initial";

      cote.valeur = parseFloat(String(cote.valeur));

      await fetcher(`config/cotes/${cote.cote}`, {
        requestInit: {
          method: "PUT",
          body: JSON.stringify(cote),
        },
      });

      Notiflix.Notify.success("Les informations ont été modifiées");
      coteInitial = cote;
      modificationEnCours = false;
    } catch (erreur) {
      Notiflix.Notify.failure(erreur.message);
    } finally {
      Notiflix.Block.remove([ligne]);
    }
  }

  /**
   * Annulation des modifications
   */
  function annulerModification() {
    cote = structuredClone(coteInitial);
    modificationEnCours = false;
  }
</script>

<div class="ligne pure-form" class:modificationEnCours bind:this={ligne}>
  <div class="bloc pure-u-1 pure-u-lg-20-24">
    <!-- Valeur -->
    <div class="pure-control-group">
      <label for={"config_cotes_" + cote.cote}>Valeur : </label>
      <InputDecimal
        id={"config_cotes_" + cote.cote}
        format="+2"
        bind:value={cote.valeur}
        on:input={() => (modificationEnCours = true)}
      />
    </div>
  </div>

  <!-- Boutons -->
  <span class="valider-annuler">
    <MaterialButton
      icon="done"
      title="Valider"
      on:click={validerModification}
    />
    <MaterialButton
      icon="close"
      title="Annuler"
      on:click={annulerModification}
    />
  </span>
</div>

<style>
  /* Mobile */
  @media screen and (max-width: 767px) {
    .ligne {
      text-align: left;
      flex-direction: column;
      width: 100%;
    }

    .ligne :global(input) {
      width: 100%;
    }
  }
</style>
