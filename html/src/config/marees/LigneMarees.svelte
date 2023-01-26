<!-- 
  @component
  
  Ligne de configuration d'une ligne du bandeau d'information.

  Usage :
  ```tsx
  <LigneMaree annee: String="2023">
  ```
 -->
<script lang="ts">
  import { onMount } from "svelte";

  import { MaterialButton } from "@app/components";

  import { marees } from "@app/stores";

  import Notiflix from "notiflix";

  import { env } from "@app/utils";

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
        const url = new URL(env.api);
        url.pathname += `marees/${annee}`;

        try {
          Notiflix.Block.dots(`#${ligne.id}`);

          const reponse = await fetch(url, {
            method: "DELETE",
          });

          if (!reponse.ok) {
            throw new Error(`${reponse.status}, ${reponse.statusText}`);
          }

          Notiflix.Notify.success(
            `Les marées de l'année ${annee} ont été supprimées`
          );
          ligne.remove();
        } catch (erreur) {
          Notiflix.Notify.failure(erreur.message);
          Notiflix.Block.remove(`#${ligne.id}`);
        }
      },
      null,
      {
        titleColor: "#ff5549",
        okButtonColor: "#f8f8f8",
        okButtonBackground: "#ff5549",
        cancelButtonColor: "#f8f8f8",
        cancelButtonBackground: "#a9a9a9",
      }
    );
  }

  onMount(() => {
    ligne.id = "config_marees_" + annee;
  });
</script>

<li class="ligne" bind:this={ligne}>
  {annee}
  <MaterialButton icon="delete" title="Supprimer" on:click={supprimerMarees} />
</li>

<style>
</style>
