<!--
  @component

  This component displays a single staff member.

  Usage:
  ```tsx
  <StaffCard
    staff={staff} // The staff member to display
  />
  ```

-->
<script lang="ts">
  import { Card } from "flowbite-svelte";

  import { StaffDrawer } from "../";

  import type { StevedoringStaff } from "@app/types";

  export let staff: StevedoringStaff;

  let drawerHidden = true;
</script>

<Card size="xs">
  <div>
    <button
      on:click={() => (drawerHidden = false)}
      class="hover:underline"
      class:opacity-50={!staff.isActive}>{staff.fullname}</button
    >
  </div>

  {#if staff.tempWorkAgency}
    <div>{staff.tempWorkAgency}</div>
  {/if}
</Card>

<StaffDrawer {staff} bind:hidden={drawerHidden} />
