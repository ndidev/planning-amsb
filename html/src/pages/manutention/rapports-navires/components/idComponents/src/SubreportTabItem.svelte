<script lang="ts">
  import {
    Button,
    Dropdown,
    DropdownItem,
    TabItem,
    Tooltip,
  } from "flowbite-svelte";
  import {
    CopyIcon,
    EllipsisVerticalIcon,
    PencilIcon,
    PlusCircleIcon,
    Trash2Icon,
    TriangleAlertIcon,
  } from "lucide-svelte";

  import { LucideButton } from "@app/components";

  import { DateUtils } from "@app/utils";

  import {
    stevedoringEquipments,
    stevedoringStaff,
    consignationEscales,
  } from "@app/stores";

  import type { StevedoringShipReport } from "@app/types";

  export let report: StevedoringShipReport;
  export let subreport: StevedoringShipReport["subreports"][number];
  export let index: number;
  export let open: boolean = false;
  export let dailyEntriesModalOpen: boolean = false;
  export let dailyEntriesSubreport: StevedoringShipReport["subreports"][number];
  export let dailyEntriesDate: string;
  export let openCargoSelectionModal: (
    subreport: StevedoringShipReport["subreports"][number],
    index: number
  ) => void;
  export let deleteSubreport: (
    subreport: StevedoringShipReport["subreports"][number]
  ) => void;

  let addDayButton: Button;

  async function addDay(
    subreport: StevedoringShipReport["subreports"][number]
  ) {
    // Convert property from empty array to empty object
    if (Array.isArray(subreport.entriesByDate)) {
      subreport.entriesByDate = {};
    }

    // Date based on entries
    const maxEntriesDateString = Object.keys(subreport.entriesByDate)
      .sort()
      .pop();
    const maxEntriesDate = maxEntriesDateString
      ? new Date(maxEntriesDateString)
      : null;
    const nextEntriesDate = maxEntriesDate
      ? new DateUtils(maxEntriesDate).getNextWorkingDay().date
      : null;

    // Date based on linked call's ops date
    let opsDate: Date = null;
    if (!nextEntriesDate && report.linkedShippingCallId) {
      opsDate = await _getCallOpsDate();
    }

    const nextDate = nextEntriesDate || opsDate || new Date();
    const nextDateAsString = new DateUtils(nextDate).toLocaleISODateString();

    if (!subreport.entriesByDate[nextDateAsString]) {
      subreport.entriesByDate[nextDateAsString] = {
        cranes: [],
        equipments: [],
        permanentStaff: [],
        tempStaff: [],
        trucking: [],
        otherSubcontracts: [],
      };
    }

    dailyEntriesSubreport = subreport;
    dailyEntriesDate = nextDateAsString;
    dailyEntriesModalOpen = true;

    async function _getCallOpsDate() {
      if (report.linkedShippingCallId) {
        try {
          addDayButton.$set({ disabled: true });

          const call = await consignationEscales.get(
            report.linkedShippingCallId
          );

          if (!call?.ops_date) {
            return null;
          }

          return new Date(call.ops_date);
        } catch (error) {
          return null;
        } finally {
          addDayButton.$set({ disabled: false });
        }
      }
    }
  }

  function editDay(
    subreport: StevedoringShipReport["subreports"][number],
    date: string
  ) {
    dailyEntriesSubreport = subreport;
    dailyEntriesDate = date;
    dailyEntriesModalOpen = true;
  }

  function copyDay(
    subreport: StevedoringShipReport["subreports"][number],
    date: string
  ) {
    const maxEntriesDate = Object.keys(subreport.entriesByDate).sort().pop();
    const nextDate = new DateUtils(maxEntriesDate)
      .getNextWorkingDay()
      .toLocaleISODateString();

    subreport.entriesByDate[nextDate] = structuredClone(
      subreport.entriesByDate[date]
    );

    for (const type in subreport.entriesByDate[nextDate]) {
      subreport.entriesByDate[nextDate][type].forEach((entry) => {
        entry.id = null;
      });
    }

    report.subreports = report.subreports;
  }

  function deleteDay(
    subreport: StevedoringShipReport["subreports"][number],
    date: string
  ) {
    delete subreport.entriesByDate[date];

    report.subreports = report.subreports;
  }
</script>

<TabItem bind:open>
  <span slot="title" id="subreport-title-{index}">
    Sous-rapport {index + 1}
    {#if subreport.cargoIds.length === 0}
      <span
        title="Aucune marchandise associée"
        class="text-orange-400 dark:text-orange-300"
      >
        <TriangleAlertIcon size={16} />
      </span>
    {/if}
    <LucideButton icon={EllipsisVerticalIcon} size="1em" />
    <Dropdown>
      <DropdownItem on:click={() => openCargoSelectionModal(subreport, index)}
        >Marchandises</DropdownItem
      >
      <DropdownItem on:click={() => deleteSubreport(subreport)}
        >Supprimer</DropdownItem
      >
    </Dropdown>
    <Tooltip type="auto" triggeredBy="#subreport-title-{index}">
      {#each subreport.cargoIds as cargoId}
        {@const cargo = report.cargoEntries.find(
          (cargo) => cargo.id === cargoId
        )}
        <div>{cargo.cargoName} ({cargo.customer})</div>
      {:else}
        <div class="text-orange-400 dark:text-orange-300">
          Aucune marchandise associée
        </div>
      {/each}
    </Tooltip>
  </span>

  <div class="mt-4">
    <span class="text-2xl font-bold me-2">Détail journalier</span>
    <Button
      on:click={() => addDay(subreport)}
      color="light"
      class="w-1/2 lg:w-auto"
      size="sm"
      bind:this={addDayButton}
    >
      <PlusCircleIcon size={16} />
      <span class="ms-2">Ajouter un jour</span>
    </Button>
  </div>

  {#each Object.entries(subreport.entriesByDate).sort() as [date, entries]}
    <div class="p-4 bg-white shadow-lg rounded-lg mb-4">
      <div>
        <span class="font-bold text-lg"
          >{new DateUtils(date).format().long}</span
        >

        <Button
          on:click={() => editDay(subreport, date)}
          color="light"
          class="ms-1"
          size="xs"
        >
          <PencilIcon size={14} />
          <span class="ms-2">Modifier</span>
        </Button>

        <Button
          on:click={() => copyDay(subreport, date)}
          color="light"
          class="ms-1"
          size="xs"
        >
          <CopyIcon size={14} />
          <span class="ms-2">Copier</span>
        </Button>

        <Button
          on:click={() => deleteDay(subreport, date)}
          color="red"
          class="ms-1"
          size="xs"
        >
          <Trash2Icon size={14} />
          <span class="ms-2">Supprimer</span>
        </Button>
      </div>

      <!-- Grues -->
      <div class="ms-2 mt-2">
        <div class="font-bold">Grues</div>

        {#each entries.cranes as entry ((entry.id ||= Math.random()))}
          {@const equipment = stevedoringEquipments.get(entry.equipmentId)}
          <div
            class="my-2 ms-2 flex flex-row flex-wrap items-center gap-1 lg:flex-row lg:flex-nowrap lg:gap-4"
          >
            {#await equipment}
              <div class="animate-pulse bg-gray-200 rounded-lg w-1/4 h-6"></div>
            {:then equipment}
              <div class="whitespace-nowrap">
                {equipment.brand}
                {equipment.model}
                {equipment.internalNumber}
              </div>
            {/await}

            {#if entry.hoursHint}
              <div class="ms-auto lg:ms-0">
                {entry.hoursHint}
              </div>
            {/if}

            <div class="ms-auto lg:ms-0">
              {DateUtils.stringifyTime(entry.hoursWorked)}
            </div>

            <div class="w-full lg:w-auto">{entry.comments}</div>
          </div>
        {:else}
          <p class="ms-2">Aucune grue</p>
        {/each}
      </div>

      <!-- Équipements -->
      <div class="ms-2 mt-2">
        <div class="font-bold">Équipements</div>

        {#each entries.equipments as entry ((entry.id ||= Math.random()))}
          {@const equipment = stevedoringEquipments.get(entry.equipmentId)}
          <div
            class="my-2 ms-2 flex flex-row flex-wrap items-center gap-1 lg:flex-row lg:flex-nowrap lg:gap-4"
          >
            {#await equipment}
              <div class="animate-pulse bg-gray-200 rounded-lg w-1/4 h-6"></div>
            {:then equipment}
              <div class="whitespace-nowrap">
                {equipment.brand}
                {equipment.model}
                {equipment.internalNumber}
              </div>
            {/await}

            {#if entry.hoursHint}
              <div class="ms-auto lg:ms-0">
                {entry.hoursHint}
              </div>
            {/if}

            <div class="ms-auto lg:ms-0">
              {DateUtils.stringifyTime(entry.hoursWorked)}
            </div>

            <div class="w-full lg:w-auto">{entry.comments}</div>
          </div>
        {:else}
          <p class="ms-2">Aucun équipement</p>
        {/each}
      </div>

      <!-- Mensuels -->
      <div class="ms-2 mt-2">
        <div class="font-bold">Mensuels</div>

        {#each entries.permanentStaff as entry ((entry.id ||= Math.random()))}
          {@const staff = stevedoringStaff.get(entry.staffId)}
          <div
            class="my-2 ms-2 flex flex-row flex-wrap items-center gap-1 lg:flex-row lg:flex-nowrap lg:gap-4"
          >
            {#await staff}
              <div class="animate-pulse bg-gray-200 rounded-lg w-1/4 h-6"></div>
            {:then staff}
              <div class="whitespace-nowrap">
                {staff.fullname}
              </div>
            {/await}

            {#if entry.hoursHint}
              <div class="ms-auto lg:ms-0">
                {entry.hoursHint}
              </div>
            {/if}

            <div class="ms-auto lg:ms-0">
              {DateUtils.stringifyTime(entry.hoursWorked)}
            </div>

            <div class="w-full lg:w-auto">{entry.comments}</div>
          </div>
        {:else}
          <p class="ms-2">Aucun membre du personnel</p>
        {/each}
      </div>

      <!-- Intérimaires -->
      <div class="ms-2 mt-2">
        <div class="font-bold">Intérimaires</div>

        {#each entries.tempStaff as entry ((entry.id ||= Math.random()))}
          {@const staff = stevedoringStaff.get(entry.staffId)}
          <div
            class="my-2 ms-2 flex flex-row flex-wrap items-center gap-1 lg:flex-row lg:flex-nowrap lg:gap-4"
          >
            {#await staff}
              <div class="animate-pulse bg-gray-200 rounded-lg w-1/4 h-6"></div>
            {:then staff}
              <div class="whitespace-nowrap">
                {staff.fullname} ({staff.tempWorkAgency})
              </div>
            {/await}

            {#if entry.hoursHint}
              <div class="ms-auto lg:ms-0">
                {entry.hoursHint}
              </div>
            {/if}

            <div class="ms-auto lg:ms-0">
              {DateUtils.stringifyTime(entry.hoursWorked)}
            </div>

            <div class="w-full lg:w-auto">{entry.comments}</div>
          </div>
        {:else}
          <p class="ms-2">Aucun intérimaire</p>
        {/each}
      </div>

      <!-- Brouettage -->
      <div class="ms-2 mt-2">
        <div class="font-bold">Brouettage</div>

        {#each entries.trucking as entry ((entry.id ||= Math.random()))}
          <div
            class="my-2 ms-2 flex flex-row flex-wrap items-center gap-1 lg:flex-row lg:flex-nowrap lg:gap-4"
          >
            <div class="whitespace-nowrap">
              {entry.subcontractorName}
            </div>

            <div class="ms-auto lg:ms-0">
              {DateUtils.stringifyTime(entry.hoursWorked)}
            </div>

            <div class="ms-auto lg:ms-0">
              {new Intl.NumberFormat("fr-FR", {
                style: "currency",
                currency: "EUR",
              }).format(entry.cost)}
            </div>

            <div class="w-full lg:w-auto">{entry.comments}</div>
          </div>
        {:else}
          <p class="ms-2">Aucune sous-traitance</p>
        {/each}
      </div>

      <!-- Autres sous-traitances -->
      <div class="ms-2 mt-2">
        <div class="font-bold">Autres sous-traitances</div>

        {#each entries.otherSubcontracts as entry ((entry.id ||= Math.random()))}
          <div
            class="my-2 ms-2 flex flex-row flex-wrap items-center gap-1 lg:flex-row lg:flex-nowrap lg:gap-4"
          >
            <div class="whitespace-nowrap">
              {entry.subcontractorName}
            </div>

            <div class="ms-auto lg:ms-0">
              {DateUtils.stringifyTime(entry.hoursWorked)}
            </div>

            <div class="ms-auto lg:ms-0">
              {new Intl.NumberFormat("fr-FR", {
                style: "currency",
                currency: "EUR",
              }).format(entry.cost)}
            </div>

            <div class="w-full lg:w-auto">{entry.comments}</div>
          </div>
        {:else}
          <p class="ms-2">Aucune sous-traitance</p>
        {/each}
      </div>
    </div>
  {:else}
    <p class="ms-4 italic">Aucune donnée à afficher</p>
  {/each}
</TabItem>
