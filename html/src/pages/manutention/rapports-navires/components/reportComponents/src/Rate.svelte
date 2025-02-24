<script lang="ts">
  import { SectionTitle } from "../";

  import { NumberUtils } from "@app/utils";

  import type { StevedoringShipReport } from "@app/types";

  export let subreport: StevedoringShipReport["subreports"][number];

  let totalCraneHours = Object.values(subreport.entriesByDate)
    .flatMap(({ cranes }) => cranes)
    .reduce((acc, curr) => acc + curr.hoursWorked, 0);

  let rate = {
    tonnage: subreport.cargoTotals.outturn.tonnage
      ? subreport.cargoTotals.outturn.tonnage / totalCraneHours
      : null,
    volume: subreport.cargoTotals.outturn.volume
      ? subreport.cargoTotals.outturn.volume / totalCraneHours
      : null,
    units: subreport.cargoTotals.outturn.units
      ? subreport.cargoTotals.outturn.units / totalCraneHours
      : null,
  };
</script>

<div>
  <SectionTitle>Cadence</SectionTitle>

  <div>
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
