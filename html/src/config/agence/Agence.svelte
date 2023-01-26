<!-- 
  @component
  
  Ensemble des informations des services de l'agence.

  Usage :
  ```tsx
  <Agence />
  ```
 -->
<script lang="ts">
  import Service from "./Service.svelte";
  import { Chargement } from "@app/components";
  import TitreSection from "../TitreSection.svelte";
  import TitreSousSection from "../TitreSousSection.svelte";

  import { servicesAgence } from "@app/stores";
</script>

<TitreSection titre={"Agence"} />

{#if !$servicesAgence}
  <Chargement />
{:else}
  {#each $servicesAgence as service}
    <div>
      <TitreSousSection titre={service.affichage} />
      <Service {service} />
    </div>
  {/each}
{/if}

<style></style>
