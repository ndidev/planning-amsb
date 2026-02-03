<script lang="ts">
  import { Tooltip } from "flowbite-svelte";
  // import { TriangleAlertIcon } from "lucide-svelte";
  import TriangleAlertIcon from "lucide-svelte/icons/triangle-alert";

  import { ShipReportDrawer } from "..";

  import { DateUtils } from "@app/utils";

  import type { StevedoringShipReport } from "@app/types";

  export let report: StevedoringShipReport;

  let drawerHidden = true;

  let allCargoesAreInSubreports =
    report.subreports.reduce(
      (acc, subreport) => acc + subreport.cargoIds.length,
      0,
    ) === report.cargoEntries.length;
</script>

<div class="mb-4 rounded-lg bg-white shadow-lg">
  <button
    on:click={() => (drawerHidden = false)}
    class="h-full w-full p-4 text-left hover:cursor-pointer hover:bg-gray-50"
  >
    <div class="text-lg font-bold">{report.ship}</div>
    <div>
      {report.startDate
        ? new DateUtils(report.startDate).format().long
        : "Pas de date de début"} &gt; {report.endDate
        ? new DateUtils(report.endDate).format().long
        : "Pas de date de fin"}
    </div>
    <div>{report.port} {report.berth}</div>
    <div>
      {report.cargoEntries.map((entry) => entry.cargoName).join(", ")}
      {#if !allCargoesAreInSubreports}
        <TriangleAlertIcon class="text-red-500" />
        <Tooltip type="auto">
          Toutes les marchandises n'ont pas été réparties dans des sous-rapports
        </Tooltip>
      {/if}
    </div>
  </button>
</div>

<ShipReportDrawer {report} bind:hidden={drawerHidden} />
