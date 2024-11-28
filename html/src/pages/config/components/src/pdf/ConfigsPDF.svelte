<!-- 
  @component
  
  Ensemble des configurations PDF.

  Usage :
  ```tsx
  <ConfigsPDF />
  ```
 -->
<script lang="ts">
  import { TitreSousSection, ConfigLine, LigneConfigPDF } from "../../";
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
      <div id="pdf-{module}" class="mb-4">
        {#each [...$configPdf[module].values()] as config (config.id)}
          <LigneConfigPDF {config} />
        {:else}
          <ConfigLine>Aucun PDF configuré.</ConfigLine>
        {/each}
      </div>
    {/if}
  {/each}
{/if}

<style>
</style>
