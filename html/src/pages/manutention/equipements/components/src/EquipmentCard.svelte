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

<Card size="xs">
  <div class:opacity-50={!equipment.isActive}>
    <div>
      <button on:click={() => (drawerHidden = false)} class="hover:underline"
        >{equipment.model} {equipment.internalNumber}</button
      >
    </div>

    <div>{equipment.type}</div>
  </div>
</Card>

<EquipmentDrawer {equipment} bind:hidden={drawerHidden} />
