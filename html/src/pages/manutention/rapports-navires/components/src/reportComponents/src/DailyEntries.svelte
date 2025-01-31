<script lang="ts">
  import { onMount } from "svelte";

  import { Accordion, AccordionItem } from "flowbite-svelte";

  import { SectionTitle } from "..";

  import { DateUtils, NumberUtils } from "@app/utils";

  import { stevedoringEquipments, stevedoringStaff } from "@app/stores";

  import type { StevedoringEquipment, StevedoringShipReport } from "@app/types";

  export let report: StevedoringShipReport;

  const dates = Object.keys(report.entriesByDate);
</script>

<!-- Screen view -->
<div class="print:hidden">
  <SectionTitle>Jours</SectionTitle>

  {#if Object.values(report.entriesByDate).flat().length > 0}
    <div class="ms-2">
      <Accordion multiple flush>
        {#each dates as date}
          {@const dateEntries = report.entriesByDate[date]}
          <AccordionItem>
            <span slot="header">{new DateUtils(date).format().long}</span>

            <!-- Grues -->
            <div class="ms-2">
              <div class="font-bold">Grues</div>
              <ul class="ms-2">
                {#each dateEntries.cranes as entry}
                  {@const crane = stevedoringEquipments.get(entry.equipmentId)}
                  <li>
                    {#await crane}
                      <span
                        class="animate-pulse bg-gray-200 rounded-lg w-1/4 h-6"
                      ></span>
                    {:then crane}
                      <span
                        >{crane.brand}
                        {crane.model}
                        {crane.internalNumber}</span
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
                        >{NumberUtils.stringifyTime(entry.hoursWorked)}</span
                      >
                    {/if}

                    {#if entry.cost}
                      <span class="ms-3"
                        >{NumberUtils.formatCost(entry.cost)}</span
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
                        >{NumberUtils.stringifyTime(entry.hoursWorked)}</span
                      >
                    {/if}

                    {#if entry.cost}
                      <span class="ms-3"
                        >{NumberUtils.formatCost(entry.cost)}</span
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

<!-- Print view -->
<table class="details hidden text-sm print:block">
  <!-- <tr><td class="mb-2 font-bold underline">Grues</td></tr>
  <tr
    ><td class="entries-table">
      {#if craneEntries.length > 0}
        <table>
          <thead>
            <tr>
              <th rowspan="2" class="first-column">Grue</th>
              {#each dates as date}
                <th colspan="2">{new DateUtils(date).format().short}</th>
              {/each}
            </tr>
            <tr>
              {#each dates as date}
                <th>Heures</th>
                <th>Durée</th>
              {/each}
            </tr>
          </thead>
          <tbody>
            {#each craneList as crane}
              <tr>
                <td class="text-left"
                  >{crane.brand} {crane.model} {crane.internalNumber}</td
                >
                {#each dates as date}
                  {@const entry = getEntryForDate("cranes", crane.id, date)}
                  <td></td>
                  <td>{entry.hoursWorked}</td>
                {/each}
              </tr>
            {/each}
          </tbody>
          <tfoot>
            <tr>
              <td>Total</td>
              {#each dates as date}
                <td></td>
                <td
                  >{report.entriesByDate[date].cranes.reduce(
                    (acc, { hoursWorked }) => acc + hoursWorked,
                    0
                  )}</td
                >
              {/each}
            </tr>
          </tfoot>
        </table>
      {:else}
        <em>Aucune grue</em>
      {/if}
    </td></tr
  >

 <tr><td class="table-title">Engins</td></tr>
  <tr
    ><td class="entries-table">
      {#if equipmentEntries.entries.length > 0}
        <table>
          <thead>
            <tr>
              <th rowspan="2" class="first-column">Engin</th>
              {#each dates as date}
                <th colspan="2">{new DateUtils(date).format().short}</th>
              {/each}
            </tr>
            <tr>
              {#each dates as date}
                <th>Heures</th>
                <th>Durée</th>
              {/each}
            </tr>
          </thead>
          <tbody>
            {#each equipmentEntries.entries as equipment, entriesByDate}
              <tr>
                <td class="text-left">{equipment}</td>
                {#each dates as date}
                  {@const entry = entriesByDate[date]}
                  <td></td>
                  <td>{entry.hoursWorked}</td>
                {/each}
              </tr>
            {/each}
          </tbody>
          <tfoot>
            <tr>
              <td>Total</td>
              {#each dates as date}
                <td></td>
                <td>{equipmentEntries.totals.byDay[date].hoursWorked}</td>
              {/each}
            </tr>
          </tfoot>
        </table>
      {:else}
        <p><em>Aucun engin</em></p>
      {/if}
    </td></tr
  >

  <tr><td class="table-title">Personnel CDI</td></tr>
  <tr
    ><td class="entries-table">
      {#if permanentStaffEntries.entries.length > 0}
        <table>
          <thead>
            <tr>
              <th rowspan="2" class="first-column">Nom</th>
              {#each dates as date}
                <th colspan="2">{new DateUtils(date).format().short}</th>
              {/each}
            </tr>
            <tr>
              {#each dates as date}
                <th>Heures</th>
                <th>Durée</th>
              {/each}
            </tr>
          </thead>
          <tbody>
            {#each permanentStaffEntries.entries as staff, entriesByDate}
              <tr>
                <td class="text-left">{staff}</td>
                {#each dates as date}
                  {@const entry = entriesByDate[date]}
                  <td></td>
                  <td>{entry.hoursWorked}</td>
                {/each}
              </tr>
            {/each}
          </tbody>
          <tfoot>
            <tr>
              <td>Total</td>
              {#each dates as date}
                <td></td>
                <td>{permanentStaffEntries.totals.byDay[date].hoursWorked}</td>
              {/each}
            </tr>
          </tfoot>
        </table>
      {:else}
        <p><em>Aucun personnel CDI</em></p>
      {/if}
    </td></tr
  >

  <tr><td class="table-title">S/T Grues / Engins</td></tr>
  <tr
    ><td class="entries-table">
      {#if otherSubcontractsEntries.entries.length > 0}
        <table>
          <thead>
            <tr>
              <th rowspan="2" class="first-column">Prestataire</th>
              {#each dates as date}
                <th colspan="2">{new DateUtils(date).format().short}</th>
              {/each}
            </tr>
            <tr>
              {#each dates as date}
                <th>Heures</th>
                <th>Coût</th>
              {/each}
            </tr>
          </thead>
          <tbody>
            {#each otherSubcontractsEntries.entries as subcontractorName, entriesByDate}
              <tr>
                <td class="text-left">{subcontractorName}</td>
                {#each dates as date}
                  {@const entry = entriesByDate[date]}
                  <td>{entry.hoursWorked || ""}</td>
                  <td>{entry.cost ? NumberUtils.formatCost(entry.cost) : ""}</td
                  >
                {/each}
              </tr>
            {/each}
          </tbody>
          <tfoot>
            <tr>
              <td>Total</td>
              {#each dates as date}
                {@const cost = otherSubcontractsEntries.totals.byDay[date].cost}
                <td
                  >{otherSubcontractsEntries.totals.byDay[date].hoursWorked ||
                    ""}</td
                >
                <td>{cost ? NumberUtils.formatCost(cost) : ""}</td>
              {/each}
            </tr>
          </tfoot>
        </table>
      {:else}
        <p><em>Aucune sous-traitance</em></p>
      {/if}
    </td></tr
  >

  <tr><td class="table-title">Brouettage</td></tr>
  <tr
    ><td class="entries-table">
      {#if truckingEntries.entries.length > 0}
        <table>
          <thead>
            <tr>
              <th rowspan="2" class="first-column">Prestataire</th>
              {#each dates as date}
                <th colspan="2">{new DateUtils(date).format().short}</th>
              {/each}
            </tr>
            <tr>
              {#each dates as date}
                <th>Heures</th>
                <th>Coût</th>
              {/each}
            </tr>
          </thead>
          <tbody>
            {#each truckingEntries.entries as subcontractorName, entriesByDate}
              <tr>
                <td class="text-left">{subcontractorName}</td>
                {#each dates as date}
                  {@const entry = entriesByDate[date]}
                  <td>{entry.hoursWorked || ""}</td>
                  <td>{entry.cost ? NumberUtils.formatCost(entry.cost) : ""}</td
                  >
                {/each}
              </tr>
            {/each}
          </tbody>
          <tfoot>
            <tr>
              <td>Total</td>
              {#each dates as date}
                {@const cost = truckingEntries.totals.byDay[date].cost}
                <td>{truckingEntries.totals.byDay[date].hoursWorked || ""}</td>
                <td>{cost ? NumberUtils.formatCost(cost) : ""}</td>
              {/each}
            </tr>
          </tfoot>
        </table>
      {:else}
        <p><em>Aucun brouettage</em></p>
      {/if}
    </td></tr
  >

  <tr><td class="table-title">Personnel Intérimaire</td></tr>
  <tr
    ><td class="entries-table">
      {#if tempStaffEntries.entries.length > 0}
        <table>
          <thead>
            <tr>
              <th rowspan="2" class="first-column">Nom</th>
              {#each dates as date}
                <th colspan="2">{new DateUtils(date).format().short}</th>
              {/each}
            </tr>
            <tr>
              {#each dates as date}
                <th>Heures</th>
                <th>Durée</th>
              {/each}
            </tr>
          </thead>
          <tbody>
            {#each tempStaffEntries.entries as staff, entriesByDate}
              <tr>
                <td class="first-column">{staff}</td>
                {#each dates as date}
                  {@const entry = entriesByDate[date]}
                  <td></td>
                  <td>{entry.hoursWorked}</td>
                {/each}
              </tr>
            {/each}
          </tbody>
          <tfoot>
            <tr>
              <td class="first-column">Total</td>
              {#each dates as date}
                <td></td>
                <td>{tempStaffEntries.totals.byDay[date].hoursWorked}</td>
              {/each}
            </tr>
          </tfoot>
        </table>
      {:else}
        <p><em>Aucun personnel intérimaire</em></p>
      {/if}
    </td></tr
  > -->
</table>

<style>
</style>
