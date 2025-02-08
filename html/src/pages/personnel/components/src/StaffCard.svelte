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

  import { StaffDrawer } from "..";

  import type { StevedoringStaff } from "@app/types";

  export let staff: StevedoringStaff;

  let drawerHidden = true;
</script>

<Card
  size="xs"
  class="hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer"
  on:click={() => (drawerHidden = false)}
>
  <div
    class:opacity-50={!staff.isActive}
    class="text-gray-700 dark:text-gray-400"
  >
    {staff.fullname}
  </div>

  {#if staff.tempWorkAgency}
    <div>{staff.tempWorkAgency}</div>
  {/if}
</Card>

<StaffDrawer {staff} bind:hidden={drawerHidden} />
