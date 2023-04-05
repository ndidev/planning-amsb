<!-- 
  @component
  
  Ensemble des informations des services de l'agence.

  Usage :
  ```tsx
  <Agence />
  ```
 -->
<script lang="ts">
  import { TitreSousSection, Service } from "../../";
  import { Chargement } from "@app/components";

  import { configAgence } from "@app/stores";
</script>

{#if !$configAgence}
  <Chargement />
{:else}
  {#each $configAgence as service}
    <div>
      <TitreSousSection titre={service.affichage} />
      <Service {service} />
    </div>
  {/each}
{/if}

<style></style>
