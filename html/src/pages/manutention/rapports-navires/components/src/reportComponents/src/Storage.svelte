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

  let storageByCargo = report.storageEntries.reduce((acc, curr) => {
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
  {#if report.storageEntries.length > 0}
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
          <tr class="font-semibold text-gray-900 bg-gray-50">
            <th scope="row" class="py-3 px-6 text-base">Total</th>

            <td class="py-3 px-6"></td>

            <td class="py-3 px-6">
              {report.storageTotals.tonnage
                ? NumberUtils.formatTonnage(report.storageTotals.tonnage)
                : ""}
            </td>

            <td class="py-3 px-6">
              {report.storageTotals.volume
                ? NumberUtils.formatVolume(report.storageTotals.volume)
                : ""}
            </td>

            <td class="py-3 px-6">
              {report.storageTotals.units
                ? NumberUtils.formatUnits(report.storageTotals.units)
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
