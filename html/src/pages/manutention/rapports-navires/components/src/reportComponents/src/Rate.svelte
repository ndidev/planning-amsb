<script lang="ts">
  import { SectionTitle } from "../";

  import { NumberUtils } from "@app/utils";

  import type { StevedoringShipReport } from "@app/types";

  export let report: StevedoringShipReport;

  let totalCraneHours = Object.values(report.entriesByDate)
    .flatMap(({ cranes }) => cranes)
    .reduce((acc, curr) => acc + curr.hoursWorked, 0);

  let rate = {
    tonnage: report.cargoTotals.outturn.tonnage
      ? report.cargoTotals.outturn.tonnage / totalCraneHours
      : null,
    volume: report.cargoTotals.outturn.volume
      ? report.cargoTotals.outturn.volume / totalCraneHours
      : null,
    units: report.cargoTotals.outturn.units
      ? report.cargoTotals.outturn.units / totalCraneHours
      : null,
  };
</script>

<div>
  <SectionTitle>Cadence</SectionTitle>

  <div class="print:text-sm">
    {#if Object.values(rate).some((value) => value)}
      <!-- Tonnage -->
      {#if rate.tonnage}
        <div class="ms-2">
          {NumberUtils.formatTonnageRate(rate.tonnage)}
        </div>
      {/if}

      <!-- Volume -->
      {#if rate.volume}
        <div class="ms-2">
          {NumberUtils.formatVolumeRate(rate.volume)}
        </div>
      {/if}

      <!-- Unités -->
      {#if rate.units}
        <div class="ms-2">
          {NumberUtils.formatUnitsRate(rate.units)}
        </div>
      {/if}
    {:else}
      <div class="ms-2 italic">Cadence non disponible</div>
    {/if}
  </div>
</div>
