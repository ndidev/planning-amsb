<script lang="ts">
  import SectionTitle from "./SectionTitle.svelte";

  import type { StevedoringShipReport } from "@app/types";

  export let report: StevedoringShipReport;
</script>

<div>
  <SectionTitle>Constats & Commentaires</SectionTitle>
  {#if report.comments}
    <div class="ms-2">
      {@html report.comments.replace(/\r\n|\r|\n/g, "<br/>")}
    </div>
  {:else}
    <div class="ms-2 italic">Aucun commentaire</div>
  {/if}
</div>
