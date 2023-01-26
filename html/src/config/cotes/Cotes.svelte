<!-- 
  @component
  
  Ensemble des côtes du port.

  Usage :
  ```tsx
  <Cotes />
  ```
 -->
<script lang="ts">
  import LigneCote from "./LigneCote.svelte";
  import { Chargement } from "@app/components";
  import TitreSection from "../TitreSection.svelte";
  import TitreSousSection from "../TitreSousSection.svelte";

  import { cotes } from "@app/stores";
</script>

<TitreSection titre={"Côtes (consignation)"} />

{#if !$cotes}
  <Chargement />
{:else}
  {#each $cotes as cote}
    <div>
      <TitreSousSection titre={cote.affichage} />
      <LigneCote {cote} />
    </div>
  {/each}
{/if}

<style></style>
