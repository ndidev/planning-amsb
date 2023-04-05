<!-- 
  @component
  
  Ensemble des RDVs rapides.

  Usage :
  ```tsx
  <AjoutsRapides />
  ```
 -->
<script lang="ts">
  import { TitreSousSection, LigneAjoutRapideBois } from "../..";
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
      <ul id="rdv-rapides-{module}">
        {#each [...$configAjoutsRapides[module].values()] as ajoutRapide (ajoutRapide.id)}
          <svelte:component this={components[module]} {ajoutRapide} />
        {:else}
          <li class="ligne-vide">Aucun ajout rapide configuré.</li>
        {/each}
      </ul>
    {/if}
  {/each}
{/if}

<style></style>
