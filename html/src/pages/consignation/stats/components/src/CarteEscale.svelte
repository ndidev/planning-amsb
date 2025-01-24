<script lang="ts">
  import { DateUtils } from "@app/utils";
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
  {/if}cargoName

  <ul class="mt-customer
    {#each escale.marchandises as marchandise}
      <li class="ml-4">
        <div>
          <span>{marchandise.marchandise}</span>
          <span class="ml-2">{marchandise.client}</span>

          {#if marchandise.tonnage_outturn}
            <span class="ml-4">
              {marchandise.tonnage_outturn.toLocaleString("fr-FR", {
                minimumFractionDigits: 3,
              }) + " MT"}
            </span>
          {/if}

          {#if marchandise.cubage_outturn}
            <span class="ml-4 italic">
              {marchandise.cubage_outturn.toLocaleString("fr-FR", {
                minimumFractionDigits: 3,
              }) + " m3"}
            </span>
          {/if}

          {#if marchandise.nombre_outturn}
            <span class="ml-4">
              {marchandise.nombre_outturn.toLocaleString("fr-FR") + " colis"}
            </span>
          {/if}
        </div>
      </li>
    {:else}
      <li>Aucune marchandise</li>
    {/each}
  </ul>
</li>
