<script lang="ts">
  import {
    Accordion,
    AccordionItem,
    Table,
    TableHead,
    TableHeadCell,
    TableBody,
    TableBodyRow,
    TableBodyCell,
  } from "flowbite-svelte";

  import { SectionTitle } from "..";

  import { DateUtils, NumberUtils } from "@app/utils";

  import { stevedoringEquipments, stevedoringStaff } from "@app/stores";

  import type { StevedoringShipReport } from "@app/types";

  export let report: StevedoringShipReport;

  const dates = Object.keys(report.entriesByDate);
</script>

<div>
  <SectionTitle>Détail journalier</SectionTitle>

  {#if Object.values(report.entriesByDate).flat().length > 0}
    <div class="ms-2">
      <Accordion multiple flush>
        {#each dates as date}
          {@const dateEntries = report.entriesByDate[date]}
          <AccordionItem>
            <span slot="header">{new DateUtils(date).format().long}</span>

            <!-- Grues -->
            <div class="ms-2 mb-4">
              <div class="font-bold">Grues</div>

              {#if dateEntries.cranes.length > 0}
                <Table>
                  <TableHead>
                    <TableHeadCell>Grue</TableHeadCell>
                    <TableHeadCell>Heures</TableHeadCell>
                    <TableHeadCell>Durée</TableHeadCell>
                    <TableHeadCell>Commentaires</TableHeadCell>
                  </TableHead>

                  <TableBody>
                    {#each dateEntries.cranes as entry}
                      {@const crane = stevedoringEquipments.get(
                        entry.equipmentId
                      )}
                      <TableBodyRow>
                        <TableBodyCell>
                          {#await crane then crane}
                            {crane.displayName}
                          {/await}
                        </TableBodyCell>
                        <TableBodyCell>
                          {entry.hoursHint}
                        </TableBodyCell>
                        <TableBodyCell>
                          {NumberUtils.stringifyTime(entry.hoursWorked)}
                        </TableBodyCell>
                        <TableBodyCell>
                          {entry.comments}
                        </TableBodyCell>
                      </TableBodyRow>
                    {/each}
                  </TableBody>

                  <tfoot
                    class="font-semibold text-gray-900 dark:text-white bg-gray-50 text-sm"
                  >
                    <tr>
                      <th scope="row" class="py-3 px-6">Total</th>
                      <td></td>
                      <td class="py-3 px-6">
                        {NumberUtils.stringifyTime(
                          dateEntries.cranes.reduce(
                            (acc, entry) => acc + entry.hoursWorked,
                            0
                          )
                        )}
                      </td>
                      <td></td>
                    </tr>
                  </tfoot>
                </Table>
              {:else}
                <div class="ms-2 italic">Aucune grue</div>
              {/if}
            </div>

            <!-- Équipements -->
            <div class="ms-2 mb-4">
              <div class="font-bold">Équipements</div>

              {#if dateEntries.equipments.length > 0}
                <Table>
                  <TableHead>
                    <TableHeadCell>Équipement</TableHeadCell>
                    <TableHeadCell>Heures</TableHeadCell>
                    <TableHeadCell>Durée</TableHeadCell>
                    <TableHeadCell>Commentaires</TableHeadCell>
                  </TableHead>

                  <TableBody>
                    {#each dateEntries.equipments as entry}
                      {@const equipment = stevedoringEquipments.get(
                        entry.equipmentId
                      )}
                      <TableBodyRow>
                        <TableBodyCell>
                          {#await equipment then equipment}
                            {equipment.displayName}
                          {/await}
                        </TableBodyCell>
                        <TableBodyCell>
                          {entry.hoursHint}
                        </TableBodyCell>
                        <TableBodyCell>
                          {NumberUtils.stringifyTime(entry.hoursWorked)}
                        </TableBodyCell>
                        <TableBodyCell>
                          {entry.comments}
                        </TableBodyCell>
                      </TableBodyRow>
                    {/each}
                  </TableBody>

                  <tfoot
                    class="font-semibold text-gray-900 dark:text-white bg-gray-50 text-sm"
                  >
                    <tr>
                      <th scope="row" class="py-3 px-6">Total</th>
                      <td></td>
                      <td class="py-3 px-6">
                        {NumberUtils.stringifyTime(
                          dateEntries.equipments.reduce(
                            (acc, entry) => acc + entry.hoursWorked,
                            0
                          )
                        )}
                      </td>
                      <td></td>
                    </tr>
                  </tfoot>
                </Table>
              {:else}
                <div class="ms-2 italic">Aucun équipement</div>
              {/if}
            </div>

            <!-- Personnel -->
            <div class="ms-2 mb-4">
              <div class="font-bold">Personnel</div>

              {#if dateEntries.permanentStaff.length > 0}
                <Table>
                  <TableHead>
                    <TableHeadCell>Nom</TableHeadCell>
                    <TableHeadCell>Heures</TableHeadCell>
                    <TableHeadCell>Durée</TableHeadCell>
                    <TableHeadCell>Commentaires</TableHeadCell>
                  </TableHead>

                  <TableBody>
                    {#each dateEntries.permanentStaff as entry}
                      {@const staff = stevedoringStaff.get(entry.staffId)}
                      <TableBodyRow>
                        <TableBodyCell>
                          {#await staff then staff}
                            {staff.fullname}
                          {/await}
                        </TableBodyCell>
                        <TableBodyCell>
                          {entry.hoursHint}
                        </TableBodyCell>
                        <TableBodyCell>
                          {NumberUtils.stringifyTime(entry.hoursWorked)}
                        </TableBodyCell>
                        <TableBodyCell>
                          {entry.comments}
                        </TableBodyCell>
                      </TableBodyRow>
                    {/each}
                  </TableBody>

                  <tfoot
                    class="font-semibold text-gray-900 dark:text-white bg-gray-50 text-sm"
                  >
                    <tr>
                      <th scope="row" class="py-3 px-6">Total</th>
                      <td></td>
                      <td class="py-3 px-6">
                        {NumberUtils.stringifyTime(
                          dateEntries.permanentStaff.reduce(
                            (acc, entry) => acc + entry.hoursWorked,
                            0
                          )
                        )}
                      </td>
                      <td></td>
                    </tr>
                  </tfoot>
                </Table>
              {:else}
                <div class="ms-2 italic">Aucun membre du personnel</div>
              {/if}
            </div>

            <!-- Intérimaires -->
            <div class="ms-2 mb-4">
              <div class="font-bold">Intérimaires</div>

              {#if dateEntries.tempStaff.length > 0}
                <Table>
                  <TableHead>
                    <TableHeadCell>Nom</TableHeadCell>
                    <TableHeadCell>Heures</TableHeadCell>
                    <TableHeadCell>Durée</TableHeadCell>
                    <TableHeadCell>Commentaires</TableHeadCell>
                  </TableHead>

                  <TableBody>
                    {#each dateEntries.tempStaff as entry}
                      {@const staff = stevedoringStaff.get(entry.staffId)}
                      <TableBodyRow>
                        <TableBodyCell>
                          {#await staff then staff}
                            {staff.fullname}
                          {/await}
                        </TableBodyCell>
                        <TableBodyCell>
                          {entry.hoursHint}
                        </TableBodyCell>
                        <TableBodyCell>
                          {NumberUtils.stringifyTime(entry.hoursWorked)}
                        </TableBodyCell>
                        <TableBodyCell>
                          {entry.comments}
                        </TableBodyCell>
                      </TableBodyRow>
                    {/each}
                  </TableBody>

                  <tfoot
                    class="font-semibold text-gray-900 dark:text-white bg-gray-50 text-sm"
                  >
                    <tr>
                      <th scope="row" class="py-3 px-6">Total</th>
                      <td></td>
                      <td class="py-3 px-6">
                        {NumberUtils.stringifyTime(
                          dateEntries.tempStaff.reduce(
                            (acc, entry) => acc + entry.hoursWorked,
                            0
                          )
                        )}
                      </td>
                      <td></td>
                    </tr>
                  </tfoot>
                </Table>
              {:else}
                <div class="ms-2 italic">Aucun intérimaire</div>
              {/if}
            </div>

            <!-- Brouettage -->
            <div class="ms-2 mb-4">
              <div class="font-bold">Brouettage</div>

              {#if dateEntries.trucking.length > 0}
                <Table>
                  <TableHead>
                    <TableHeadCell>Prestataire</TableHeadCell>
                    <TableHeadCell>Durée</TableHeadCell>
                    <TableHeadCell>Coût</TableHeadCell>
                    <TableHeadCell>Commentaires</TableHeadCell>
                  </TableHead>

                  <TableBody>
                    {#each dateEntries.trucking as entry}
                      <TableBodyRow>
                        <TableBodyCell>
                          {entry.subcontractorName}
                        </TableBodyCell>
                        <TableBodyCell>
                          {#if entry.hoursWorked}
                            {NumberUtils.stringifyTime(entry.hoursWorked)}
                          {/if}
                        </TableBodyCell>
                        <TableBodyCell>
                          {#if entry.cost}
                            {NumberUtils.formatCost(entry.cost)}
                          {/if}
                        </TableBodyCell>
                        <TableBodyCell>
                          {entry.comments}
                        </TableBodyCell>
                      </TableBodyRow>
                    {/each}
                  </TableBody>

                  <tfoot
                    class="font-semibold text-gray-900 dark:text-white bg-gray-50 text-sm"
                  >
                    <tr>
                      <th scope="row" class="py-3 px-6">Total</th>
                      <td class="py-3 px-6">
                        {NumberUtils.stringifyTime(
                          dateEntries.trucking.reduce(
                            (acc, entry) => acc + entry.hoursWorked,
                            0
                          )
                        )}
                      </td>
                      <td class="py-3 px-6">
                        {NumberUtils.formatCost(
                          dateEntries.trucking.reduce(
                            (acc, entry) => acc + entry.cost,
                            0
                          )
                        )}
                      </td>
                      <td></td>
                    </tr>
                  </tfoot>
                </Table>
              {:else}
                <div class="ms-2 italic">Aucun brouettage</div>
              {/if}
            </div>

            <!-- Autres sous-traitances -->
            <div class="ms-2 mb-4">
              <div class="font-bold">Autres sous-traitances</div>

              {#if dateEntries.otherSubcontracts.length > 0}
                <Table>
                  <TableHead>
                    <TableHeadCell>Prestataire</TableHeadCell>
                    <TableHeadCell>Durée</TableHeadCell>
                    <TableHeadCell>Coût</TableHeadCell>
                    <TableHeadCell>Commentaires</TableHeadCell>
                  </TableHead>

                  <TableBody>
                    {#each dateEntries.otherSubcontracts as entry}
                      <TableBodyRow>
                        <TableBodyCell>
                          {entry.subcontractorName}
                        </TableBodyCell>
                        <TableBodyCell>
                          {#if entry.hoursWorked}
                            {NumberUtils.stringifyTime(entry.hoursWorked)}
                          {/if}
                        </TableBodyCell>
                        <TableBodyCell>
                          {#if entry.cost}
                            {NumberUtils.formatCost(entry.cost)}
                          {/if}
                        </TableBodyCell>
                        <TableBodyCell>
                          {entry.comments}
                        </TableBodyCell>
                      </TableBodyRow>
                    {/each}
                  </TableBody>

                  <tfoot
                    class="font-semibold text-gray-900 dark:text-white bg-gray-50 text-sm"
                  >
                    <tr>
                      <th scope="row" class="py-3 px-6">Total</th>
                      <td class="py-3 px-6">
                        {NumberUtils.stringifyTime(
                          dateEntries.otherSubcontracts.reduce(
                            (acc, entry) => acc + entry.hoursWorked,
                            0
                          )
                        )}
                      </td>
                      <td class="py-3 px-6">
                        {NumberUtils.formatCost(
                          dateEntries.otherSubcontracts.reduce(
                            (acc, entry) => acc + entry.cost,
                            0
                          )
                        )}
                      </td>
                      <td></td>
                    </tr>
                  </tfoot>
                </Table>
              {:else}
                <div class="ms-2 italic">Aucune sous-traitance</div>
              {/if}
            </div>
          </AccordionItem>
        {/each}
      </Accordion>
    </div>
  {:else}
    <div class="ms-2 italic">Aucune opération enregistrée</div>
  {/if}
</div>
