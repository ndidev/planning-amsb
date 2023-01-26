<!-- 
  @component
  
  Page de gestion des marées.

  Usage :
  ```tsx
  <Marees />
  ```
 -->
<script lang="ts">
  import LigneMarees from "./LigneMarees.svelte";
  import TitreSection from "../TitreSection.svelte";
  import TitreSousSection from "../TitreSousSection.svelte";
  import { Chargement } from "@app/components";

  import { mareesAnnees as annees } from "@app/stores";

  import Notiflix from "notiflix";

  import { env } from "@app/utils";

  let fileInput: HTMLInputElement;
  let ajouterBtn: HTMLButtonElement;

  /**
   * Ajouter des marées.
   */
  async function ajouterMarees() {
    ajouterBtn.disabled = true;

    const [csv] = fileInput.files;

    if (!csv) {
      Notiflix.Notify.failure("Veuillez sélectionner un fichier");
      return;
    }

    const formData = new FormData();
    formData.append("csv", csv);

    const url = new URL(env.api);
    url.pathname += "marees";

    try {
      const reponse = await fetch(url, {
        method: "POST",
        body: formData,
      });

      if (!reponse.ok) {
        throw new Error(`${reponse.status} : ${reponse.statusText}`);
      }

      Notiflix.Notify.success("Les marées ont été ajoutées");
    } catch (erreur) {
      Notiflix.Notify.failure(erreur.message);
    } finally {
      ajouterBtn.disabled = false;
    }
  }
</script>

<TitreSection titre="Marées" />

<div>
  <TitreSousSection titre="Années existantes" />
  {#if !$annees}
    <Chargement />
  {:else}
    <ul id="marees-existantes">
      {#each $annees as annee}
        <LigneMarees {annee} />
      {:else}
        <li class="ligne-vide">Aucune donnée de marées trouvées</li>
      {/each}
    </ul>
  {/if}
</div>

<div>
  <TitreSousSection titre="Ajouter des marées" />
  <div class="ajouter-marees">
    <div class="ligne-explicative">
      Intégrer l'onglet "SQL CSV" du fichier des marées (Z:\Commun\TE Marées\{`{année}`}),
      enregistré au format ".csv"
    </div>
    <div>
      <input
        type="file"
        accept=".csv,text/csv"
        bind:this={fileInput}
        on:change={(ajouterBtn.disabled = false)}
      />
      <button
        class="pure-button pure-button-primary"
        on:click|preventDefault={ajouterMarees}
        bind:this={ajouterBtn}
        disabled
      >
        Ajouter les marées
      </button>
    </div>
  </div>
</div>

<style>
  .ajouter-marees {
    margin-left: 10%;
  }

  .ligne-explicative {
    margin-top: 10px;
    margin-bottom: 10px;
  }
</style>
