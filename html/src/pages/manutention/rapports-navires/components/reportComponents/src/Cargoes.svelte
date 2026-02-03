<script lang="ts">
  import {
    Table,
    TableHead,
    TableHeadCell,
    TableBody,
    TableBodyRow,
    TableBodyCell,
  } from "flowbite-svelte";
  // import { ArrowUpIcon, ArrowDownIcon } from "lucide-svelte";
  import ArrowUpIcon from "lucide-svelte/icons/arrow-up";
  import ArrowDownIcon from "lucide-svelte/icons/arrow-down";

  import { SectionTitle } from "../";

  import { NumberUtils } from "@app/utils";

  import type { StevedoringShipReport } from "@app/types";

  export let report: StevedoringShipReport;
  export let subreport: StevedoringShipReport["subreports"][number];

  let tableBodyCellPrintClass =
    "font-medium px-6 py-4 print:px-3 print:py-1 print:font-normal";
</script>

<div>
  <SectionTitle>Marchandises</SectionTitle>

  <div class="print:text-sm">
    {#if subreport.cargoIds.length > 0}
      <div>
        <Table>
          <TableHead>
            <TableHeadCell>Marchandise (Client)</TableHeadCell>
            <TableHeadCell>BL</TableHeadCell>
            <TableHeadCell>Outturn</TableHeadCell>
            <TableHeadCell>Différence</TableHeadCell>
          </TableHead>

          <TableBody>
            {#each subreport.cargoIds as cargoId}
              {@const cargo = report.cargoEntries.find(
                (entry) => entry.id === cargoId,
              )}
              <TableBodyRow>
                <!-- Marchandise + Client -->
                <TableBodyCell tdClass={tableBodyCellPrintClass}>
                  <span
                    title={cargo.operation.charAt(0).toLocaleUpperCase() +
                      cargo.operation.slice(1)}
                    class="*:align-text-top"
                  >
                    {#if cargo.operation === "import"}
                      <ArrowDownIcon size="1em" />
                    {:else if cargo.operation === "export"}
                      <ArrowUpIcon size="1em" />
                    {/if}
                  </span>
                  <span class="font-bold">{cargo.cargoName}</span>
                  <span>({cargo.customer})</span>
                </TableBodyCell>

                <!-- BL -->
                <TableBodyCell tdClass={tableBodyCellPrintClass}>
                  {#if cargo.blTonnage}
                    <div>{NumberUtils.formatTonnage(cargo.blTonnage)}</div>
                  {/if}

                  {#if cargo.blVolume}
                    <div>
                      {NumberUtils.formatVolume(cargo.blVolume)}
                    </div>
                  {/if}

                  {#if cargo.blUnits}
                    <div>{NumberUtils.formatUnits(cargo.blUnits)}</div>
                  {/if}
                </TableBodyCell>

                <!-- Outturn -->
                <TableBodyCell tdClass={tableBodyCellPrintClass}>
                  {#if cargo.outturnTonnage}
                    <div>
                      {NumberUtils.formatTonnage(cargo.outturnTonnage)}
                    </div>
                  {/if}

                  {#if cargo.outturnVolume}
                    <div>
                      {NumberUtils.formatVolume(cargo.outturnVolume)}
                    </div>
                  {/if}

                  {#if cargo.outturnUnits}
                    <div>{NumberUtils.formatUnits(cargo.outturnUnits)}</div>
                  {/if}
                </TableBodyCell>

                <!-- Différence -->
                <TableBodyCell tdClass={tableBodyCellPrintClass}>
                  {#if cargo.outturnTonnage && cargo.blTonnage}
                    <div
                      class={NumberUtils.getQuantityColor(
                        cargo.tonnageDifference,
                      )}
                    >
                      {NumberUtils.formatTonnage(cargo.tonnageDifference, true)}
                    </div>
                  {/if}

                  {#if cargo.outturnVolume && cargo.blVolume}
                    <div
                      class={NumberUtils.getQuantityColor(
                        cargo.volumeDifference,
                      )}
                    >
                      {NumberUtils.formatVolume(cargo.volumeDifference, true)}
                    </div>
                  {/if}

                  {#if cargo.outturnUnits && cargo.blUnits}
                    <div
                      class={NumberUtils.getQuantityColor(
                        cargo.unitsDifference,
                      )}
                    >
                      {NumberUtils.formatUnits(cargo.unitsDifference, true)}
                    </div>
                  {/if}
                </TableBodyCell>
              </TableBodyRow>
            {/each}
          </TableBody>

          {#if subreport.cargoIds.length > 1}
            <tfoot>
              <tr class="font-semibold text-gray-900 bg-gray-50 text-sm">
                <th scope="row" class="py-3 px-6">Total</th>

                <!-- BL -->
                <td class="py-3 px-6">
                  {#if subreport.cargoTotals.bl.tonnage}
                    <div>
                      {NumberUtils.formatTonnage(
                        subreport.cargoTotals.bl.tonnage,
                      )}
                    </div>
                  {/if}

                  {#if subreport.cargoTotals.bl.volume}
                    <div>
                      {NumberUtils.formatVolume(
                        subreport.cargoTotals.bl.volume,
                      )}
                    </div>
                  {/if}

                  {#if subreport.cargoTotals.bl.units}
                    <div>
                      {NumberUtils.formatUnits(subreport.cargoTotals.bl.units)}
                    </div>
                  {/if}
                </td>

                <!-- Outturn -->
                <td class="py-3 px-6">
                  {#if subreport.cargoTotals.bl.tonnage}
                    <div>
                      {NumberUtils.formatTonnage(
                        subreport.cargoTotals.outturn.tonnage,
                      )}
                    </div>
                  {/if}

                  {#if subreport.cargoTotals.bl.volume}
                    <div>
                      {NumberUtils.formatVolume(
                        subreport.cargoTotals.outturn.volume,
                      )}
                    </div>
                  {/if}

                  {#if subreport.cargoTotals.bl.units}
                    <div>
                      {NumberUtils.formatUnits(
                        subreport.cargoTotals.outturn.units,
                      )}
                    </div>
                  {/if}
                </td>

                <!-- Différence -->
                <td class="py-3 px-6">
                  {#if subreport.cargoTotals.bl.tonnage}
                    <div
                      class={NumberUtils.getQuantityColor(
                        subreport.cargoTotals.difference.tonnage,
                      )}
                    >
                      {NumberUtils.formatTonnage(
                        subreport.cargoTotals.difference.tonnage,
                        true,
                      )}
                    </div>
                  {/if}

                  {#if subreport.cargoTotals.bl.volume}
                    <div
                      class={NumberUtils.getQuantityColor(
                        subreport.cargoTotals.difference.volume,
                      )}
                    >
                      {NumberUtils.formatVolume(
                        subreport.cargoTotals.difference.volume,
                        true,
                      )}
                    </div>
                  {/if}

                  {#if subreport.cargoTotals.bl.units}
                    <div
                      class={NumberUtils.getQuantityColor(
                        subreport.cargoTotals.difference.units,
                      )}
                    >
                      {NumberUtils.formatUnits(
                        subreport.cargoTotals.difference.units,
                        true,
                      )}
                    </div>
                  {/if}
                </td>
              </tr>
            </tfoot>
          {/if}
        </Table>
      </div>
    {:else}
      <div class="ms-2 italic">Aucune marchandise</div>
    {/if}
  </div>
</div>
