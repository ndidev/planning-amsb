<!-- 
  @component
  
  Bandeau d'information.

  Affiche les lignes d'information configurée dans la partie "Configuration".

  Usage :
  ```tsx
  <BandeauInfo
    module: string="module"
    pc?: boolean={true}
    tv?: boolean={false}
  />
  ```
 -->
<script lang="ts">
  import { configBandeauInfo } from "@app/stores";

  import { luminance } from "@app/utils";

  import type { ModuleId, ConfigBandeauInfo } from "@app/types";

  /**
   * Module des lignes à afficher.
   */
  export let module: ModuleId;

  /**
   * Afficher les lignes actives sur PC.
   */
  export let pc = false;

  /**
   * Afficher les lignes actives sur TV.
   */
  export let tv = false;

  $: lignes = [...$configBandeauInfo[module].values()].filter(
    (ligne) =>
      (pc === true && ligne.pc === true) || (tv === true && ligne.tv === true)
  ) as ConfigBandeauInfo[];
</script>

<div class="text-xs lg:ml-0 lg:text-xl">
  {#each lignes as ligne}
    <div
      class="p-1 lg:p-2"
      style:background-color={ligne.couleur}
      style:color={luminance.getTextColor(ligne.couleur)}
    >
      {@html ligne.message.replace(/\r\n|\r|\n/g, "<br/>")}
    </div>
  {/each}
</div>
