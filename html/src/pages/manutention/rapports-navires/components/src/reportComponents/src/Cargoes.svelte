<script lang="ts">
  import {
    Table,
    TableHead,
    TableHeadCell,
    TableBody,
    TableBodyRow,
    TableBodyCell,
  } from "flowbite-svelte";
  import { ArrowUpIcon, ArrowDownIcon } from "lucide-svelte";

  import { SectionTitle } from "../";

  import { NumberUtils } from "@app/utils";

  import type { StevedoringShipReport } from "@app/types";

  export let report: StevedoringShipReport;

  let tableBodyCellPrintClass =
    "font-medium px-6 py-4 print:px-3 print:py-1 print:font-normal";
</script>

<div>
  <SectionTitle>Marchandises</SectionTitle>

  <div class="print:text-sm">
    {#if report.cargoEntries.length > 0}
      <div>
        <Table>
          <TableHead>
            <TableHeadCell>Marchandise (Client)</TableHeadCell>
            <TableHeadCell>BL</TableHeadCell>
            <TableHeadCell>Outturn</TableHeadCell>
            <TableHeadCell>Différence</TableHeadCell>
          </TableHead>

          <TableBody>
            {#each report.cargoEntries as cargo}
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
                        cargo.tonnageDifference
                      )}
                    >
                      {NumberUtils.formatTonnage(cargo.tonnageDifference, true)}
                    </div>
                  {/if}

                  {#if cargo.outturnVolume && cargo.blVolume}
                    <div
                      class={NumberUtils.getQuantityColor(
                        cargo.volumeDifference
                      )}
                    >
                      {NumberUtils.formatVolume(cargo.volumeDifference, true)}
                    </div>
                  {/if}

                  {#if cargo.outturnUnits && cargo.blUnits}
                    <div
                      class={NumberUtils.getQuantityColor(
                        cargo.unitsDifference
                      )}
                    >
                      {NumberUtils.formatUnits(cargo.unitsDifference, true)}
                    </div>
                  {/if}
                </TableBodyCell>
              </TableBodyRow>
            {/each}
          </TableBody>

          {#if report.cargoEntries.length > 1}
            <tfoot>
              <tr class="font-semibold text-gray-900 bg-gray-50 text-sm">
                <th scope="row" class="py-3 px-6">Total</th>

                <!-- BL -->
                <td class="py-3 px-6">
                  {#if report.cargoTotals.bl.tonnage}
                    <div>
                      {NumberUtils.formatTonnage(report.cargoTotals.bl.tonnage)}
                    </div>
                  {/if}

                  {#if report.cargoTotals.bl.volume}
                    <div>
                      {NumberUtils.formatVolume(report.cargoTotals.bl.volume)}
                    </div>
                  {/if}

                  {#if report.cargoTotals.bl.units}
                    <div>
                      {NumberUtils.formatUnits(report.cargoTotals.bl.units)}
                    </div>
                  {/if}
                </td>

                <!-- Outturn -->
                <td class="py-3 px-6">
                  {#if report.cargoTotals.bl.tonnage}
                    <div>
                      {NumberUtils.formatTonnage(
                        report.cargoTotals.outturn.tonnage
                      )}
                    </div>
                  {/if}

                  {#if report.cargoTotals.bl.volume}
                    <div>
                      {NumberUtils.formatVolume(
                        report.cargoTotals.outturn.volume
                      )}
                    </div>
                  {/if}

                  {#if report.cargoTotals.bl.units}
                    <div>
                      {NumberUtils.formatUnits(
                        report.cargoTotals.outturn.units
                      )}
                    </div>
                  {/if}
                </td>

                <!-- Différence -->
                <td class="py-3 px-6">
                  {#if report.cargoTotals.bl.tonnage}
                    <div
                      class={NumberUtils.getQuantityColor(
                        report.cargoTotals.difference.tonnage
                      )}
                    >
                      {NumberUtils.formatTonnage(
                        report.cargoTotals.difference.tonnage,
                        true
                      )}
                    </div>
                  {/if}

                  {#if report.cargoTotals.bl.volume}
                    <div
                      class={NumberUtils.getQuantityColor(
                        report.cargoTotals.difference.volume
                      )}
                    >
                      {NumberUtils.formatVolume(
                        report.cargoTotals.difference.volume,
                        true
                      )}
                    </div>
                  {/if}

                  {#if report.cargoTotals.bl.units}
                    <div
                      class={NumberUtils.getQuantityColor(
                        report.cargoTotals.difference.units
                      )}
                    >
                      {NumberUtils.formatUnits(
                        report.cargoTotals.difference.units,
                        true
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
