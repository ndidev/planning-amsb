<!-- 
  @component
  
  Ensemble des lignes du bandeau d'information.

  Usage :
  ```tsx
  <BandeauInfo />
  ```
 -->
<script lang="ts">
  import { LigneConfigBandeauInfo, ConfigLine, TitreSousSection } from "../../";
  import { Chargement } from "@app/components";

  import { currentUser, configBandeauInfo } from "@app/stores";

  import { sitemap } from "@app/utils";
  import type { ConfigBandeauInfo } from "@app/types";

  $: modulesAffiches = Object.keys(
    $configBandeauInfo
  ) as ConfigBandeauInfo["module"][];
</script>

{#if !$configBandeauInfo}
  <Chargement />
{:else}
  {#each modulesAffiches as module}
    {#if $currentUser.canEdit(module)}
      <TitreSousSection
        titre={sitemap.get(module).affichage}
        fonctionAjout={() => {
          configBandeauInfo.new(module);
        }}
      />

      <div id="infos-{module}" class="mb-4">
        {#each [...$configBandeauInfo[module].values()] as ligneInfo (ligneInfo.id)}
          <LigneConfigBandeauInfo {ligneInfo} />
        {:else}
          <ConfigLine>Aucune ligne configurée.</ConfigLine>
        {/each}
      </div>
    {/if}
  {/each}
{/if}
