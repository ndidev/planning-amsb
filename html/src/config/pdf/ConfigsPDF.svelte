<!-- 
  @component
  
  Ensemble des configurations PDF.

  Usage :
  ```tsx
  <ConfigsPDF />
  ```
 -->
<script lang="ts">
  import LigneConfigPDF from "./LigneConfigPDF.svelte";
  import TitreSection from "../TitreSection.svelte";
  import TitreSousSection from "../TitreSousSection.svelte";
  import { Chargement } from "@app/components";

  import { currentUser, configsPdf } from "@app/stores";

  import { sitemap } from "@app/utils";

  $: modulesAffiches = Object.keys($configsPdf || {});

  const modeleConfigPDF: ConfigPDF = {
    id: null,
    module: "",
    fournisseur: null,
    fournisseur_nom: "",
    envoi_auto: false,
    liste_emails: "",
    jours_avant: 0,
    jours_apres: 0,
  };

  /**
   * Ajouter une nouvelle ligne au bandeau d'information.
   *
   * @param module Module de la nouvelle ligne info
   */
  function ajouterLigneConfig(module: string) {
    configsPdf.update((configs) => {
      const nouvelleConfig: ConfigPDF = structuredClone(modeleConfigPDF);
      nouvelleConfig.id = "new_" + Math.floor(Math.random() * 1e10);
      nouvelleConfig.module = module;

      configs[module].push(nouvelleConfig);

      return configs;
    });
  }
</script>

<TitreSection titre={"PDF"} />

{#if !$configsPdf}
  <Chargement />
{:else}
  {#each modulesAffiches as module}
    {#if $currentUser.canEdit(module)}
      <TitreSousSection
        titre={sitemap.get(module).affichage}
        fonctionAjout={() => {
          ajouterLigneConfig(module);
        }}
      />
      <ul id="pdf-{module}">
        {#each $configsPdf[module] as configPdf (configPdf.id)}
          <LigneConfigPDF {configPdf} />
        {:else}
          <li class="ligne-vide">Aucun PDF configuré.</li>
        {/each}
      </ul>
    {/if}
  {/each}
{/if}

<style>
</style>
