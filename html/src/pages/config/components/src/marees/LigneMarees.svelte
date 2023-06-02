<!-- 
  @component
  
  Ligne de configuration d'une ligne du bandeau d'information.

  Usage :
  ```tsx
  <LigneMaree annee: String="2023">
  ```
 -->
<script lang="ts">
  import { MaterialButton } from "@app/components";

  import Notiflix from "notiflix";

  import { marees } from "@app/stores";

  import { fetcher, notiflixOptions } from "@app/utils";

  /**
   * Année de la ligne.
   */
  export let annee: string;

  let ligne: HTMLLIElement;

  /**
   * Supprimer les marées d'une année.
   */
  function supprimerMarees() {
    Notiflix.Confirm.show(
      "Suppression des marées",
      `Voulez-vous vraiment supprimer les marées de l'année ${annee} ?`,
      "Supprimer",
      "Annuler",
      async function () {
        try {
          Notiflix.Block.dots([ligne], notiflixOptions.texts.suppression);
          ligne.style.minHeight = "initial";

          await marees().delete(Number(annee));

          Notiflix.Notify.success(
            `Les marées de l'année ${annee} ont été supprimées`
          );
        } catch (erreur) {
          Notiflix.Notify.failure(erreur.message);
          console.error(erreur);
          Notiflix.Block.remove([ligne]);
        }
      },
      null,
      notiflixOptions.themes.red
    );
  }
</script>

<li class="ligne" bind:this={ligne}>
  <span class="annee">{annee}</span>

  <MaterialButton preset="supprimer" on:click={supprimerMarees} />
</li>

<style>
  .annee {
    margin-right: 10px;
  }
</style>
