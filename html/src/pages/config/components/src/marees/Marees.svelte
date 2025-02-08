<!-- 
  @component
  
  Page de gestion des marées.

  Usage :
  ```tsx
  <Marees />
  ```
 -->
<script lang="ts">
  import { Fileupload, Button, Spinner } from "flowbite-svelte";

  import { LigneMarees, ConfigLine, TitreSousSection } from "../../";
  import { Chargement } from "@app/components";

  import { marees, mareesAnnees as annees } from "@app/stores";

  import Notiflix from "notiflix";

  let selectedFiles: FileList | undefined;
  let uploadButtonDisabled = true;
  let uploadSubmitting = false;

  $: uploadButtonDisabled = !selectedFiles;

  /**
   * Ajouter des marées.
   */
  async function uploadTides() {
    if (!selectedFiles) {
      return;
    }

    const csv = selectedFiles[0];

    if (!csv) {
      Notiflix.Notify.failure("Veuillez sélectionner un fichier");
      return;
    }

    const formData = new FormData();
    formData.append("csv", csv);

    try {
      uploadButtonDisabled = true;
      uploadSubmitting = true;

      await marees().create(formData);

      Notiflix.Notify.success("Les marées ont été ajoutées");
    } catch (erreur) {
      Notiflix.Notify.failure(erreur.message);
      console.error(erreur);
    } finally {
      selectedFiles = undefined;
      uploadButtonDisabled = false;
      uploadSubmitting = false;
    }
  }
</script>

<div class="mb-4">
  <TitreSousSection titre="Années existantes" />
  {#if !$annees}
    <Chargement />
  {:else}
    <div id="marees-existantes">
      {#each [...$annees] as annee (annee)}
        <LigneMarees {annee} />
      {:else}
        <ConfigLine>Aucune donnée de marées trouvées</ConfigLine>
      {/each}
    </div>
  {/if}
</div>

<div class="mb-4">
  <TitreSousSection titre="Ajouter des marées" />
  <div class="ajouter-marees">
    <div class="my-3">
      Intégrer l'onglet "SQL CSV" du fichier des marées (Z:\Commun\TE Marées\{`{année}`}),
      enregistré au format ".csv"
    </div>
    <div>
      <Fileupload accept=".csv,text/csv" bind:files={selectedFiles} clearable />
      <Button
        color="primary"
        class="mt-3"
        on:click={uploadTides}
        disabled={uploadButtonDisabled}
      >
        {#if uploadSubmitting}
          <Spinner size={5} class="me-4" /> Chargement...
        {:else}
          Ajouter les marées
        {/if}
      </Button>
    </div>
  </div>
</div>
