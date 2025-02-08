<script lang="ts">
  import { LigneDate, LigneRdv } from "../";

  import type { RdvVrac } from "@app/types";

  export let date: string;
  export let appointments: RdvVrac[] = [];
  export let navires: string[] = [];
  export let springTides = false;
</script>

<div class="w-11/12 mx-auto">
  <LigneDate {date} maree={springTides} {navires} />

  <div class="divide-y">
    {#each appointments as appointment (appointment.id)}
      <LigneRdv {appointment} />
    {/each}
  </div>
</div>
