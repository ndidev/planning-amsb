<!-- 
  @component
  
  Ensemble des côtes du port.

  Usage :
  ```tsx
  <Cotes />
  ```
 -->
<script lang="ts">
  import { LigneCote, TitreSousSection } from "../../";
  import { Chargement } from "@app/components";

  import { configCotes } from "@app/stores";
</script>

{#if !$configCotes}
  <Chargement />
{:else}
  {#each $configCotes as cote}
    <div>
      <TitreSousSection titre={cote.affichage} />
      <LigneCote {cote} />
    </div>
  {/each}
{/if}

<style></style>
