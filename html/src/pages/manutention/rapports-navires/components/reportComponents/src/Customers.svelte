<script lang="ts">
  import SectionTitle from "./SectionTitle.svelte";

  import type { StevedoringShipReport } from "@app/types";

  export let report: StevedoringShipReport;
</script>

<div>
  <SectionTitle>Clients</SectionTitle>
  {#if report.customers.length > 0}
    <div class="ms-2">
      {report.customers.join(", ")}
    </div>
  {:else}
    <div class="ms-2 italic">Aucun client</div>
  {/if}
</div>
