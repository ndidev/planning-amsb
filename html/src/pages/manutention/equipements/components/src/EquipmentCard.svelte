<!--
  @component

  This component displays a single equipment.

  Usage:
  ```tsx
  <EquipmentCard
    equipment={equipment} // The equipment to display
  />
  ```

-->
<script lang="ts">
  import { Card } from "flowbite-svelte";

  import { EquipmentDrawer } from "../";

  import type { StevedoringEquipment } from "@app/types";
  // import { StevedoringEquipment } from "@app/entities";

  export let equipment: StevedoringEquipment;

  let drawerHidden = true;
</script>

<Card
  size="xs"
  class="hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer"
  on:click={() => (drawerHidden = false)}
>
  <div
    class:opacity-50={!equipment.isActive}
    class="text-gray-700 dark:text-gray-400"
  >
    <div class="font-semibold">
      {equipment.model}
      {equipment.internalNumber}
      {#if equipment.year}
        <span>({equipment.year})</span>
      {/if}
    </div>

    <div>{equipment.type}</div>
  </div>
</Card>

<EquipmentDrawer {equipment} bind:hidden={drawerHidden} />
