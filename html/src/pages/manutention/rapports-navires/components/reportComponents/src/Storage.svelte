<script lang="ts">
  import {
    Table,
    TableHead,
    TableHeadCell,
    TableBody,
    TableBodyRow,
    TableBodyCell,
  } from "flowbite-svelte";

  import { SectionTitle } from "../";

  import { NumberUtils } from "@app/utils";

  import type { StevedoringShipReport } from "@app/types";

  export let report: StevedoringShipReport;
  export let subreport: StevedoringShipReport["subreports"][number];

  let storageByCargo = subreport.storageEntries.reduce((acc, curr) => {
    if (!acc[curr.cargoId]) {
      acc[curr.cargoId] = [];
    }

    acc[curr.cargoId] = [
      ...acc[curr.cargoId],
      {
        storageName: curr.storageName,
        tonnage: curr.tonnage,
        volume: curr.volume,
        units: curr.units,
      },
    ];

    return acc;
  }, {});
</script>

<div>
  <SectionTitle>Stockage</SectionTitle>
  {#if subreport.storageEntries.length > 0}
    <div>
      <Table>
        <TableHead>
          <TableHeadCell>Marchandise (Client)</TableHeadCell>
          <TableHeadCell>Magasin</TableHeadCell>
          <TableHeadCell>Tonnage</TableHeadCell>
          <TableHeadCell>Volume</TableHeadCell>
          <TableHeadCell>Nombre</TableHeadCell>
        </TableHead>

        <TableBody>
          {#each Object.keys(storageByCargo) as cargoId}
            {@const cargo = report.cargoEntries.find(
              ({ id }) => id === Number(cargoId)
            )}
            {#each storageByCargo[cargoId] as storageEntry, i}
              <TableBodyRow>
                {#if i === 0}
                  <TableBodyCell
                    rowspan={storageByCargo[cargoId].length}
                    class="align-top"
                  >
                    <span class="font-bold">{cargo.cargoName}</span>
                    <span>({cargo.customer})</span>
                  </TableBodyCell>
                {/if}

                <TableBodyCell>{storageEntry.storageName}</TableBodyCell>

                {#if storageEntry.tonnage}
                  <TableBodyCell>
                    {NumberUtils.formatTonnage(storageEntry.tonnage)}
                  </TableBodyCell>
                {:else}
                  <TableBodyCell></TableBodyCell>
                {/if}

                {#if storageEntry.volume}
                  <TableBodyCell>
                    {NumberUtils.formatVolume(storageEntry.volume)}
                  </TableBodyCell>
                {:else}
                  <TableBodyCell></TableBodyCell>
                {/if}

                {#if storageEntry.units}
                  <TableBodyCell>
                    {NumberUtils.formatUnits(storageEntry.units)}
                  </TableBodyCell>
                {:else}
                  <TableBodyCell></TableBodyCell>
                {/if}
              </TableBodyRow>
            {/each}
          {/each}
        </TableBody>

        <tfoot>
          <tr class="font-semibold text-gray-900 bg-gray-50 text-sm">
            <th scope="row" class="py-3 px-6">Total</th>

            <td class="py-3 px-6"></td>

            <td class="py-3 px-6">
              {subreport.storageTotals.tonnage
                ? NumberUtils.formatTonnage(subreport.storageTotals.tonnage)
                : ""}
            </td>

            <td class="py-3 px-6">
              {subreport.storageTotals.volume
                ? NumberUtils.formatVolume(subreport.storageTotals.volume)
                : ""}
            </td>

            <td class="py-3 px-6">
              {subreport.storageTotals.units
                ? NumberUtils.formatUnits(subreport.storageTotals.units)
                : ""}
            </td>
          </tr>
        </tfoot>
      </Table>
    </div>
  {:else}
    <div class="ms-2 italic">Aucun stockage</div>
  {/if}
</div>
