<!-- 
  @component
  
  Ensemble des RDVs rapides.

  Usage :
  ```tsx
  <AjoutsRapides />
  ```
 -->
<script lang="ts">
  import { TitreSousSection, ConfigLine, LigneAjoutRapideBois } from "../..";
  import { Chargement } from "@app/components";

  import { currentUser, configAjoutsRapides } from "@app/stores";

  import { sitemap } from "@app/utils";

  import type { AjoutRapide } from "@app/types";

  const components = {
    bois: LigneAjoutRapideBois,
  };

  $: modulesAffiches = Object.keys(
    $configAjoutsRapides
  ) as AjoutRapide["module"][];
</script>

{#if !$configAjoutsRapides}
  <Chargement />
{:else}
  {#each modulesAffiches as module}
    {#if $currentUser.canEdit(module)}
      <TitreSousSection
        titre={sitemap.get(module).affichage}
        fonctionAjout={() => {
          configAjoutsRapides.new(module);
        }}
      />
      <div id="rdv-rapides-{module}" class="mb-4">
        {#each [...$configAjoutsRapides[module].values()] as ajoutRapide (ajoutRapide.id)}
          <svelte:component this={components[module]} {ajoutRapide} />
        {:else}
          <ConfigLine>Aucun ajout rapide configuré.</ConfigLine>
        {/each}
      </div>
    {/if}
  {/each}
{/if}

<style></style>
