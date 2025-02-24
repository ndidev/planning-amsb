<script lang="ts">
  import SectionTitle from "./SectionTitle.svelte";

  import type { StevedoringShipReport } from "@app/types";

  export let report: StevedoringShipReport;
</script>

<div>
  <SectionTitle>Instructions de facturation</SectionTitle>
  {#if report.invoiceInstructions}
    <div class="ms-2">
      {@html report.invoiceInstructions.replace(/\r\n|\r|\n/g, "<br/>")}
    </div>
  {:else}
    <div class="ms-2 italic">Aucune instruction de facturation</div>
  {/if}
</div>
