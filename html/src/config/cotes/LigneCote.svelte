<!-- 
  @component
  
  Ligne de configuration d'une côte.

  Usage :
  ```tsx
  <LigneCote cote: Cote={cote}>
  ```
 -->
<script lang="ts">
  import { onMount, onDestroy } from "svelte";

  import { MaterialButton } from "@app/components";

  import Notiflix from "notiflix";

  import { env, formatDecimal } from "@app/utils";

  export let cote: Cote;
  let coteInitial: Cote = structuredClone(cote);

  let modificationEnCours: boolean = false;

  let ligne: HTMLDivElement;

  /**
   * Validation des modifications
   */
  async function validerModification() {
    const url = new URL(env.api);
    url.pathname += `config/cotes/${cote.cote}`;

    try {
      Notiflix.Block.dots(`#${ligne.id}`, "Modification en cours...");

      const reponse = await fetch(url, {
        method: "PUT",
        body: JSON.stringify(cote),
      });

      if (!reponse.ok) {
        throw new Error(`${reponse.status}, ${reponse.statusText}`);
      }

      Notiflix.Notify.success("Les informations ont été modifiées");
      modificationEnCours = false;
      coteInitial = await reponse.json();
    } catch (erreur) {
      Notiflix.Notify.failure(erreur.message);
    } finally {
      Notiflix.Block.remove(`#${ligne.id}`);
    }
  }

  /**
   * Annulation des modifications
   */
  function annulerModification() {
    cote = structuredClone(coteInitial);
    modificationEnCours = false;
  }

  onMount(() => {
    ligne.id = "config_cotes_" + cote.cote;

    // Si changement d'une ligne, activation de la classe "modificationEnCours"
    for (const input of ligne.querySelectorAll("input")) {
      input.oninput = () => (modificationEnCours = true);
    }

    formatDecimal(ligne);
  });
</script>

<div class="ligne pure-form" class:modificationEnCours bind:this={ligne}>
  <div class="bloc pure-u-1 pure-u-lg-20-24">
    <!-- Valeur -->
    <div class="pure-control-group">
      <label for={"config_cotes_" + cote.cote}>Valeur : </label>
      <input
        class="valeur"
        data-nom="Valeur"
        id={"config_cotes_" + cote.cote}
        bind:value={cote.valeur}
        data-decimal="2"
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
  @media screen and (max-width: 480px) {
    .ligne {
      text-align: left;
      flex-direction: column;
      width: 100%;
    }

    input {
      width: 100%;
    }
  }
</style>
