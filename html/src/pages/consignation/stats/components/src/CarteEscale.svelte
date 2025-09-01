<script lang="ts">
  import { DateUtils, NumberUtils } from "@app/utils";
  import type { EscaleConsignation } from "@app/types";

  export let escale: EscaleConsignation;
</script>

<li class="my-4 rounded-lg border-2 border-gray-300 p-4">
  <div class="mb-1 font-bold">
    <a class="hover:underline" href="/consignation/escales/{escale.id}"
      >{escale.navire}</a
    >
  </div>

  {#if escale.ops_date && escale.etc_date}
    <div>
      {new DateUtils(escale.ops_date).format().long} &gt; {new DateUtils(
        escale.etc_date
      ).format().long}
    </div>
  {/if}

  <ul class="mt-2">
    {#each escale.marchandises as cargo}
      <li class="ml-4">
        <div>
          <span>{cargo.cargoName}</span>
          <span class="ml-2">{cargo.customer}</span>

          {#if cargo.outturnTonnage}
            <span class="ml-4">
              {NumberUtils.formatTonnage(cargo.outturnTonnage)}
            </span>
          {/if}

          {#if cargo.outturnVolume}
            <span class="ml-4 italic">
              {NumberUtils.formatVolume(cargo.outturnVolume)}
            </span>
          {/if}

          {#if cargo.outturnUnits}
            <span class="ml-4">
              {NumberUtils.formatUnits(cargo.outturnUnits)}
            </span>
          {/if}
        </div>
      </li>
    {:else}
      <li>Aucune marchandise</li>
    {/each}
  </ul>
</li>
