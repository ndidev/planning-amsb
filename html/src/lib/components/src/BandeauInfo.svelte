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
  import { lignesBandeauInfo } from "@app/stores";

  import { luminance } from "@app/utils";

  /**
   * Module des lignes à afficher.
   */
  export let module: string;

  /**
   * Afficher les lignes actives sur PC.
   */
  export let pc = false;

  /**
   * Afficher les lignes actives sur TV.
   */
  export let tv = false;

  $: lignes = $lignesBandeauInfo[module]?.filter(
    (ligne: LigneBandeauInfo) =>
      (pc === true && ligne.pc === 1) || (tv === true && ligne.tv === 1)
  ) as LigneBandeauInfo[];
</script>

<section class="bandeau-info">
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
</style>
