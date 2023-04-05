<!-- 
  @component
  
  Ensemble des configurations PDF.

  Usage :
  ```tsx
  <ConfigsPDF />
  ```
 -->
<script lang="ts">
  import { TitreSousSection, LigneConfigPDF } from "../../";
  import { Chargement } from "@app/components";

  import { currentUser, configPdf } from "@app/stores";

  import { sitemap } from "@app/utils";

  import type { ConfigPDF } from "@app/types";

  $: modulesAffiches = Object.keys($configPdf) as ConfigPDF["module"][];
</script>

{#if !$configPdf}
  <Chargement />
{:else}
  {#each modulesAffiches as module}
    {#if $currentUser.canEdit(module)}
      <TitreSousSection
        titre={sitemap.get(module).affichage}
        fonctionAjout={() => {
          configPdf.new(module);
        }}
      />
      <ul id="pdf-{module}">
        {#each [...$configPdf[module].values()] as config (config.id)}
          <LigneConfigPDF {config} />
        {:else}
          <li class="ligne-vide">Aucun PDF configuré.</li>
        {/each}
      </ul>
    {/if}
  {/each}
{/if}

<style>
</style>
