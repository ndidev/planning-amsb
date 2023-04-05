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

  import type { ModuleId } from "@app/types";

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
  );
</script>

<section class="bandeau-info" style:--margin-left={tv ? "0px" : "90px"}>
  {#each lignes as ligne}
    <div
      class="ligne-bandeau-info"
      style:background-color={ligne.couleur}
      style:color={luminance.getTextColor(ligne.couleur)}
    >
      {ligne.message}
    </div>
  {/each}
</section>

<style>
  .bandeau-info {
    position: sticky;
    top: 0px;
    z-index: 1;
    margin-left: var(--margin-left, 0px);
    font-size: 1.2em;
  }

  .ligne-bandeau-info {
    padding: 5px;
  }

  @media screen and (max-width: 480px) {
    .bandeau-info {
      margin-left: 65px;
      font-size: 0.8em;
    }
  }
</style>
