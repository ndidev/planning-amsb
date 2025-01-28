<script lang="ts">
  import { sineIn } from "svelte/easing";
  import { goto } from "@roxi/routify";

  import {
    Drawer,
    CloseButton,
    Accordion,
    AccordionItem,
    Table,
    TableHead,
    TableHeadCell,
    TableBody,
    TableBodyRow,
    TableBodyCell,
  } from "flowbite-svelte";
  import { PencilIcon, ArrowUpIcon, ArrowDownIcon } from "lucide-svelte";

  import { LucideButton } from "@app/components";
  import { DateUtils, NumberUtils } from "@app/utils";

  import {
    currentUser,
    stevedoringEquipments,
    stevedoringStaff,
  } from "@app/stores";

  import type { StevedoringShipReport } from "@app/types";

  export let report: StevedoringShipReport;

  export let hidden = true;

  let transitionParams = {
    x: 320,
    duration: 200,
    easing: sineIn,
  };

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

<Drawer
  placement="right"
  bgOpacity="bg-opacity-10"
  width="w-3/4 lg:w-2/3"
  transitionType="fly"
  {transitionParams}
  bind:hidden
>
  <CloseButton
    on:click={() => (hidden = true)}
    class="absolute top-0 right-0 m-4"
  />
  <div class="flex flex-col gap-2 lg:gap-6 mt-8">
    <!-- Nom -->
    <div class="text-3xl *:align-baseline">
      <span class="font-bold">{report.ship}</span>

      {#if $currentUser.canEdit("manutention")}
        <LucideButton
          preset="edit"
          icon={PencilIcon}
          on:click={$goto(`/manutention/rapports-navires/${report.id}`)}
          align="baseline"
        />
      {/if}
    </div>

    <!-- Port et quai -->
    <div>
      <div>
        <span class="font-bold">Port :</span>
        {report.port}
      </div>
      <div>
        <span class="font-bold">Quai :</span>
        {report.berth}
      </div>
    </div>

    <!-- Commentaires -->
    <div>
      <div class="text-lg font-bold">Constats & Commentaires</div>
      {#if report.comments}
        <div class="ms-2">
          {@html report.comments.replace(/\r\n|\r|\n/g, "<br/>")}
        </div>
      {:else}
        <div class="ms-2 italic">Aucun commentaire</div>
      {/if}
    </div>

    <!-- Instructions de facturation -->
    <div>
      <div class="text-lg font-bold">Instructions de facturation</div>
      {#if report.invoiceInstructions}
        <div class="ms-2">
          {@html report.invoiceInstructions.replace(/\r\n|\r|\n/g, "<br/>")}
        </div>
      {:else}
        <div class="ms-2 italic">Aucune instruction de facturation</div>
      {/if}
    </div>

    <!-- Clients -->
    <div>
      <div class="text-lg font-bold">Clients</div>
      {#if report.customers.length > 0}
        <div class="ms-2">
          {report.customers.join(", ")}
        </div>
      {:else}
        <div class="ms-2 italic">Aucun client</div>
      {/if}
    </div>

    <!-- Cadence -->
    <div>
      <div class="text-lg font-bold">Cadence</div>
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

    <!-- Marchandises -->
    <div>
      <div class="text-lg font-bold">Marchandises</div>
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
                  <TableBodyCell>
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
                  <TableBodyCell>
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
                  <TableBodyCell>
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
                  <TableBodyCell>
                    {#if cargo.outturnTonnage && cargo.blTonnage}
                      <div
                        class={NumberUtils.getQuantityColor(
                          cargo.tonnageDifference
                        )}
                      >
                        {NumberUtils.formatTonnage(
                          cargo.tonnageDifference,
                          true
                        )}
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
                <tr class="font-semibold text-gray-900 bg-gray-50">
                  <th scope="row" class="py-3 px-6 text-base">Total</th>

                  <!-- BL -->
                  <td class="py-3 px-6">
                    {#if report.cargoTotals.bl.tonnage}
                      <div>
                        {NumberUtils.formatTonnage(
                          report.cargoTotals.bl.tonnage
                        )}
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

    <!-- Stockage -->
    <div>
      <div class="text-lg font-bold">Stockage</div>
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

    <!-- Jours -->
    <div>
      <div class="text-lg font-bold">Jours</div>
      {#if Object.values(report.entriesByDate).flat().length > 0}
        <div class="ms-2">
          <Accordion multiple flush>
            {#each Object.keys(report.entriesByDate) as date}
              {@const dateEntries = report.entriesByDate[date]}
              <AccordionItem>
                <span slot="header">{new DateUtils(date).format().long}</span>

                <!-- Grues -->
                <div class="ms-2">
                  <div class="font-bold">Grues</div>
                  <ul class="ms-2">
                    {#each dateEntries.cranes as entry}
                      {@const equipment = stevedoringEquipments.get(
                        entry.equipmentId
                      )}
                      <li>
                        {#await equipment}
                          <span
                            class="animate-pulse bg-gray-200 rounded-lg w-1/4 h-6"
                          ></span>
                        {:then equipment}
                          <span
                            >{equipment.brand}
                            {equipment.model}
                            {equipment.internalNumber}</span
                          >
                        {/await}

                        <span class="ms-3"
                          >{NumberUtils.stringifyTime(entry.hoursWorked)}</span
                        >

                        <span class="ms-3 italic">{entry.comments}</span>
                      </li>
                    {:else}
                      <li class="italic">Aucune grue</li>
                    {/each}
                  </ul>
                </div>

                <!-- Équipements -->
                <div class="ms-2">
                  <div class="font-bold">Équipements</div>
                  <ul class="ms-2">
                    {#each dateEntries.equipments as entry}
                      {@const equipment = stevedoringEquipments.get(
                        entry.equipmentId
                      )}
                      <li>
                        {#await equipment}
                          <span
                            class="animate-pulse bg-gray-200 rounded-lg w-1/4 h-6"
                          ></span>
                        {:then equipment}
                          <span
                            >{equipment.brand}
                            {equipment.model}
                            {equipment.internalNumber}</span
                          >
                        {/await}

                        <span class="ms-3"
                          >{NumberUtils.stringifyTime(entry.hoursWorked)}</span
                        >

                        <span class="ms-3 italic">{entry.comments}</span>
                      </li>
                    {:else}
                      <li class="italic">Aucun équipement</li>
                    {/each}
                  </ul>
                </div>

                <!-- Personnel -->
                <div class="ms-2">
                  <div class="font-bold">Personnel</div>
                  <ul class="ms-2">
                    {#each dateEntries.permanentStaff as entry}
                      {@const staff = stevedoringStaff.get(entry.staffId)}
                      <li>
                        {#await staff}
                          <span
                            class="animate-pulse bg-gray-200 rounded-lg w-1/4 h-6"
                          ></span>
                        {:then staff}
                          <span>{staff.fullname}</span>
                        {/await}

                        <span class="ms-3"
                          >{NumberUtils.stringifyTime(entry.hoursWorked)}</span
                        >

                        <span class="ms-3 italic">{entry.comments}</span>
                      </li>
                    {:else}
                      <li class="italic">Aucun membre du personnel</li>
                    {/each}
                  </ul>
                </div>

                <!-- Intérimaires -->
                <div class="ms-2">
                  <div class="font-bold">Intérimaires</div>
                  <ul class="ms-2">
                    {#each dateEntries.tempStaff as entry}
                      {@const staff = stevedoringStaff.get(entry.staffId)}
                      <li>
                        {#await staff}
                          <span
                            class="animate-pulse bg-gray-200 rounded-lg w-1/4 h-6"
                          ></span>
                        {:then staff}
                          <span>{staff.fullname}</span>
                        {/await}

                        <span class="ms-3"
                          >{NumberUtils.stringifyTime(entry.hoursWorked)}</span
                        >

                        <span class="ms-3 italic">{entry.comments}</span>
                      </li>
                    {:else}
                      <li class="italic">Aucun intérimaire</li>
                    {/each}
                  </ul>
                </div>

                <!-- Brouettage -->
                <div class="ms-2">
                  <div class="font-bold">Brouettage</div>
                  <ul class="ms-2">
                    {#each dateEntries.trucking as entry}
                      <li>
                        <span>{entry.subcontractorName}</span>

                        {#if entry.hoursWorked}
                          <span class="ms-3"
                            >{NumberUtils.stringifyTime(
                              entry.hoursWorked
                            )}</span
                          >
                        {/if}

                        {#if entry.cost}
                          <span class="ms-3"
                            >{new Intl.NumberFormat("fr-FR", {
                              style: "currency",
                              currency: "EUR",
                            }).format(entry.cost)}</span
                          >
                        {/if}

                        <span class="ms-3 italic">{entry.comments}</span>
                      </li>
                    {:else}
                      <li class="italic">Aucun brouettage</li>
                    {/each}
                  </ul>
                </div>

                <!-- Autres sous-traitances -->
                <div class="ms-2">
                  <div class="font-bold">Autres sous-traitances</div>
                  <ul class="ms-2">
                    {#each dateEntries.otherSubcontracts as entry}
                      <li>
                        <span>{entry.subcontractorName}</span>

                        {#if entry.hoursWorked}
                          <span class="ms-3"
                            >{NumberUtils.stringifyTime(
                              entry.hoursWorked
                            )}</span
                          >
                        {/if}

                        {#if entry.cost}
                          <span class="ms-3"
                            >{new Intl.NumberFormat("fr-FR", {
                              style: "currency",
                              currency: "EUR",
                            }).format(entry.cost)}</span
                          >
                        {/if}

                        <span class="ms-3 italic">{entry.comments}</span>
                      </li>
                    {:else}
                      <li class="italic">Aucune sous-traitance</li>
                    {/each}
                  </ul>
                </div>
              </AccordionItem>
            {/each}
          </Accordion>
        </div>
      {:else}
        <div class="ms-2 italic">Aucune opération enregistrée</div>
      {/if}
    </div>
  </div>
</Drawer>
