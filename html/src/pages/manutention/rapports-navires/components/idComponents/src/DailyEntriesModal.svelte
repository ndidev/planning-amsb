<script lang="ts">
  import { onMount, tick } from "svelte";

  import { Modal, Input, Label, Button, Checkbox } from "flowbite-svelte";
  // import { PlusCircleIcon, Trash2Icon } from "lucide-svelte";
  import PlusCircleIcon from "lucide-svelte/icons/plus-circle";
  import Trash2Icon from "lucide-svelte/icons/trash-2";
  import Notiflix from "notiflix";

  import { Svelecte, NumericInput, BoutonAction } from "@app/components";

  import { DateUtils, validerFormulaire, fetcher } from "@app/utils";

  import type {
    StevedoringShipReport,
    StevedoringShipReportEquipmentEntry,
    StevedoringShipReportStaffEntry,
  } from "@app/types";

  let form: HTMLFormElement;

  export let open: boolean = false;
  export let date: string;
  export let subreport: StevedoringShipReport["subreports"][number];
  export let report: StevedoringShipReport;

  let dateFromInput: string;
  let dateEntries: StevedoringShipReport["subreports"][number]["entriesByDate"][string];
  let subcontractorTruckingList: string[] = [];
  let subcontractorOtherList: string[] = [];

  type EntriesKey =
    keyof StevedoringShipReport["subreports"][number]["entriesByDate"][string];
  type EntryType<T extends EntriesKey> =
    StevedoringShipReport["subreports"][number]["entriesByDate"][string][T][number];
  type Entry = EntryType<EntriesKey>;

  let selectedItems: {
    categories: Partial<typeof dateEntries>;
    allSelected: boolean;
    someSelected: boolean;
  } = {
    categories: {},
    get allSelected() {
      return (
        Object.values(this.categories).flatMap((items) => items).length ===
          Object.values(dateEntries).flatMap((items) => items as Entry[])
            .length &&
        Object.values(dateEntries).flatMap((items) => items as Entry[]).length >
          0
      );
    },
    get someSelected() {
      return (
        Object.values(this.categories).flatMap((items) => items).length > 0 &&
        Object.values(this.categories).flatMap((items) => items).length <
          Object.values(dateEntries).flatMap((items) => items as Entry[]).length
      );
    },
  };

  const flowbiteCheckboxStyle =
    "w-4 h-4 bg-gray-100 border-gray-300 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-600 dark:border-gray-500 rounded text-primary-600 focus:ring-primary-500 dark:focus:ring-primary-600";

  const checkboxes = {
    toggleAll(e: Event) {
      const checkbox = e.target as HTMLInputElement;
      Object.keys(selectedItems.categories).forEach((key) => {
        selectedItems.categories[key] = checkbox.checked
          ? [...dateEntries[key]]
          : [];
      });
    },

    toggleCategory(e: Event, category: EntriesKey) {
      const checkbox = e.target as HTMLInputElement;
      selectedItems.categories[category as string] = checkbox.checked
        ? [...dateEntries[category]]
        : [];
    },
  };

  async function getSubcontractorsData() {
    type SubcontractorsData = {
      trucking: string[];
      other: string[];
    };

    try {
      const subcontractorsData: SubcontractorsData = await fetcher(
        "manutention/rapports-navires/sous-traitants",
      );

      subcontractorTruckingList = subcontractorsData.trucking;
      subcontractorOtherList = subcontractorsData.other;
    } catch (error) {
      // Silently fail
    }
  }

  async function addCrane() {
    const newEntry = {
      id: null,
      equipmentId: null,
      date: null,
      hoursHint:
        dateEntries.cranes[dateEntries.cranes.length - 1]?.hoursHint || "",
      hoursWorked:
        dateEntries.cranes[dateEntries.cranes.length - 1]?.hoursWorked || 0,
      comments: "",
    };

    dateEntries.cranes = [...dateEntries.cranes, newEntry];

    if (document.querySelector<HTMLInputElement>("#cranes-toggleAll").checked) {
      selectedItems.categories.cranes = [
        ...selectedItems.categories.cranes,
        newEntry,
      ];
    }

    await tick();

    form
      .querySelector<HTMLInputElement>(
        `#cranes-${dateEntries.cranes.length - 1}-equipmentId`,
      )
      .focus();
  }

  async function addEquipment() {
    const newEntry = {
      id: null,
      equipmentId: null,
      date: null,
      hoursHint:
        dateEntries.equipments[dateEntries.equipments.length - 1]?.hoursHint ||
        dateEntries.cranes[dateEntries.cranes.length - 1]?.hoursHint ||
        "",
      hoursWorked:
        dateEntries.equipments[dateEntries.equipments.length - 1]
          ?.hoursWorked ||
        Math.max(
          ...dateEntries.cranes.map(({ hoursWorked }) => hoursWorked),
          0,
        ) ||
        0,
      comments: "",
    };

    dateEntries.equipments = [...dateEntries.equipments, newEntry];

    if (
      document.querySelector<HTMLInputElement>("#equipments-toggleAll").checked
    ) {
      selectedItems.categories.equipments = [
        ...selectedItems.categories.equipments,
        newEntry,
      ];
    }

    await tick();

    form
      .querySelector<HTMLInputElement>(
        `#equipments-${dateEntries.equipments.length - 1}-equipmentId`,
      )
      .focus();
  }

  async function addPermanentStaff() {
    const newEntry = {
      id: null,
      staffId: null,
      date: null,
      hoursHint:
        dateEntries.permanentStaff[dateEntries.permanentStaff.length - 1]
          ?.hoursHint ||
        dateEntries.cranes[dateEntries.cranes.length - 1]?.hoursHint ||
        "",
      hoursWorked:
        dateEntries.permanentStaff[dateEntries.permanentStaff.length - 1]
          ?.hoursWorked ||
        Math.max(
          ...dateEntries.cranes.map(({ hoursWorked }) => hoursWorked),
          0,
        ) ||
        0,
      comments: "",
    };

    dateEntries.permanentStaff = [...dateEntries.permanentStaff, newEntry];

    if (
      document.querySelector<HTMLInputElement>("#permanentStaff-toggleAll")
        .checked
    ) {
      selectedItems.categories.permanentStaff = [
        ...selectedItems.categories.permanentStaff,
        newEntry,
      ];
    }

    await tick();

    form
      .querySelector<HTMLInputElement>(
        `#permanentStaff-${dateEntries.permanentStaff.length - 1}-staffId`,
      )
      .focus();
  }

  async function addTempStaff() {
    const newEntry = {
      id: null,
      staffId: null,
      date: null,
      hoursHint:
        dateEntries.tempStaff[dateEntries.tempStaff.length - 1]?.hoursHint ||
        dateEntries.cranes[dateEntries.cranes.length - 1]?.hoursHint ||
        "",
      hoursWorked:
        dateEntries.tempStaff[dateEntries.tempStaff.length - 1]?.hoursWorked ||
        Math.max(
          ...dateEntries.cranes.map(({ hoursWorked }) => hoursWorked),
          0,
        ) ||
        0,
      comments: "",
    };

    dateEntries.tempStaff = [...dateEntries.tempStaff, newEntry];

    if (
      document.querySelector<HTMLInputElement>("#tempStaff-toggleAll").checked
    ) {
      selectedItems.categories.tempStaff = [
        ...selectedItems.categories.tempStaff,
        newEntry,
      ];
    }

    await tick();

    form
      .querySelector<HTMLInputElement>(
        `#tempStaff-${dateEntries.tempStaff.length - 1}-staffId`,
      )
      .focus();
  }

  async function addSubcontractTrucking() {
    const newEntry = {
      id: null,
      subcontractorName: "",
      date: null,
      hoursWorked: null,
      cost: null,
      comments: "",
    };

    dateEntries.trucking = [...dateEntries.trucking, newEntry];

    if (
      document.querySelector<HTMLInputElement>("#trucking-toggleAll").checked
    ) {
      selectedItems.categories.trucking = [
        ...selectedItems.categories.trucking,
        newEntry,
      ];
    }

    await tick();

    form
      .querySelector<HTMLInputElement>(
        `#trucking-${dateEntries.trucking.length - 1}-subcontractorName`,
      )
      .focus();
  }

  async function addSubcontractOther() {
    const newEntry = {
      id: null,
      subcontractorName: "",
      date: null,
      hoursWorked: null,
      cost: null,
      comments: "",
    };

    dateEntries.otherSubcontracts = [
      ...dateEntries.otherSubcontracts,
      newEntry,
    ];

    if (
      document.querySelector<HTMLInputElement>("#otherSubcontracts-toggleAll")
        .checked
    ) {
      selectedItems.categories.otherSubcontracts = [
        ...selectedItems.categories.otherSubcontracts,
        newEntry,
      ];
    }

    await tick();

    form
      .querySelector<HTMLInputElement>(
        `#otherSubcontracts-${dateEntries.otherSubcontracts.length - 1}-subcontractorName`,
      )
      .focus();
  }

  function inferHoursWorked(
    entry:
      | StevedoringShipReportStaffEntry
      | StevedoringShipReportEquipmentEntry,
  ) {
    if (entry.hoursHint === "") return;

    const regexp = new RegExp(
      /(?<start>\d{1,2}[h:]?\d{0,2})\s*-?\s*(?<end>\d{1,2}[h:]?\d{0,2})/,
      "gi",
    );

    const match = regexp.exec(entry.hoursHint);

    if (!match) return;

    const start = _parseHours(match.groups.start);
    const end = _parseHours(match.groups.end);

    if (!start || !end) return;

    const formattedValue =
      String(Math.floor(start)).padStart(2, "0") +
      "h" +
      String(Math.floor((start % 1) * 60)).padStart(2, "0") +
      "-" +
      String(Math.floor(end)).padStart(2, "0") +
      "h" +
      String(Math.floor((end % 1) * 60)).padStart(2, "0");

    entry.hoursHint = formattedValue;

    // Do not overwrite the hours worked if it has already been set
    if (entry.hoursWorked) return;

    let hoursWorked = end - start;

    if (hoursWorked < 0) return;

    // Remove the lunch break if the hours worked are more than 5
    if (hoursWorked > 5) {
      const lunchBreak = 1.5;
      hoursWorked -= lunchBreak;
    }

    return hoursWorked;

    function _parseHours(hours: string): number {
      let hoursPart = 0;
      let minutesPart = 0;

      if (/[h:]/i.test(hours)) {
        [hoursPart, minutesPart] = hours.split(/[h:]/i).map(Number);
      } else if (hours.length <= 2) {
        hoursPart = Number(hours);
      } else {
        hoursPart = Number(hours.substring(0, hours.length - 2));
        minutesPart = Number(hours.substring(hours.length - 2));
      }

      if (hoursPart > 23 || minutesPart > 59) return;

      return hoursPart + (minutesPart || 0) / 60;
    }
  }

  function deleteEntry<T extends EntriesKey>(key: T, entry: EntryType<T>) {
    dateEntries[key] = dateEntries[key].filter(
      (item: EntryType<T>) => item !== entry,
    ) as (typeof dateEntries)[T];

    selectedItems.categories[key] = selectedItems.categories[key].filter(
      (item: EntryType<T>) => item !== entry,
    ) as (typeof dateEntries)[T];
  }

  function updateMultipleValues(
    property: string,
    source: Entry,
    force: boolean = true,
  ) {
    // If the source isn't selected, return
    if (
      !Object.values(selectedItems.categories)
        .flatMap((items) => items as Entry[])
        .includes(source)
    ) {
      return;
    }

    Object.values(selectedItems.categories).forEach((items) => {
      items.forEach((target: Entry) => {
        if (
          target !== source &&
          property in target &&
          (!target[property] || force)
        ) {
          // If the value is being updated from a "normal" entry
          // and the target is in trucking or otherSubcontracts
          // do not update it
          if (
            ![...dateEntries.trucking, ...dateEntries.otherSubcontracts].find(
              (item) => item === source,
            ) &&
            [...dateEntries.trucking, ...dateEntries.otherSubcontracts].find(
              (item) => item === target,
            )
          ) {
            return;
          }

          target[property] = source[property];
        }
      });
    });

    dateEntries = dateEntries;
  }

  async function updateEntries() {
    if (!validerFormulaire(form)) return;

    // If the date has changed, check that is does not interfere with existing entries
    if (dateFromInput !== date) {
      const dateAlreadyExists = Object.keys(subreport.entriesByDate).includes(
        dateFromInput,
      );

      if (dateAlreadyExists) {
        Notiflix.Notify.failure(
          `Il existe déjà des entrées pour le ${new DateUtils(dateFromInput).format().long}`,
        );

        return;
      }
    }

    // Update the entries
    subreport.entriesByDate[dateFromInput] = dateEntries;

    // Remove the old entries if the date has changed
    if (dateFromInput !== date) {
      delete subreport.entriesByDate[date];
    }

    // If the date entries are empty, delete the date from the report
    if (
      Object.values(subreport.entriesByDate[dateFromInput]).flatMap(
        (items) => items as Entry[],
      ).length === 0
    ) {
      delete subreport.entriesByDate[dateFromInput];
    }

    report.subreports = report.subreports;

    open = false;
  }

  function cancelUpdate() {
    // If the date entries are empty, delete the date from the report
    if (
      Object.values(subreport.entriesByDate[date]).flatMap(
        (items) => items as Entry[],
      ).length === 0
    ) {
      delete subreport.entriesByDate[date];
    }

    subreport.entriesByDate = subreport.entriesByDate;

    open = false;
  }

  onMount(async () => {
    getSubcontractorsData();
  });
</script>

<Modal
  title={new DateUtils(date).format().long}
  bind:open
  dismissable={false}
  size="lg"
  on:open={() => {
    dateFromInput = date;
    dateEntries = structuredClone(subreport.entriesByDate[date]) || {
      cranes: [],
      equipments: [],
      permanentStaff: [],
      tempStaff: [],
      trucking: [],
      otherSubcontracts: [],
    };
    selectedItems.categories = {
      cranes: [],
      equipments: [],
      permanentStaff: [],
      tempStaff: [],
      trucking: [],
      otherSubcontracts: [],
    };
  }}
>
  <form class="flex flex-col gap-8" bind:this={form}>
    <!-- Date -->
    <div>
      <Label for="date">Date</Label>
      <Input
        type="date"
        id="date"
        bind:value={dateFromInput}
        name="Date"
        required
      />
    </div>

    <div>
      <div class="mb-2 flex gap-3 items-center">
        <Checkbox
          on:change={checkboxes.toggleAll}
          checked={selectedItems.allSelected}
          indeterminate={selectedItems.someSelected}
        />
        <span class="text-xl font-bold me-2">Tout sélectionner</span>
      </div>
    </div>

    <!-- Grues -->
    <div>
      <div class="mb-2 flex gap-3 items-center">
        <Checkbox
          on:change={(e) => checkboxes.toggleCategory(e, "cranes")}
          checked={dateEntries.cranes.length > 0 &&
            selectedItems.categories.cranes.length ===
              dateEntries.cranes.length}
          indeterminate={selectedItems.categories.cranes.length > 0 &&
            selectedItems.categories.cranes.length < dateEntries.cranes.length}
          id="cranes-toggleAll"
        />
        <span class="text-xl font-bold me-2">Grues</span>
        <Button
          on:click={addCrane}
          color="light"
          class="w-1/2 lg:w-auto"
          size="xs"
        >
          <PlusCircleIcon size={16} />
          <span class="ms-2">Ajouter une grue</span>
        </Button>
      </div>

      <div class="flex flex-col gap-3">
        {#each dateEntries.cranes as entry, i}
          <div
            class="flex flex-col lg:flex-row gap-2 items-center p-2 rounded-lg"
            class:bg-violet-100={selectedItems.categories.cranes.includes(
              entry,
            )}
          >
            <div>
              <input
                type="checkbox"
                bind:group={selectedItems.categories.cranes}
                value={entry}
                class={flowbiteCheckboxStyle}
              />
            </div>

            <div class="w-full lg:w-2/5">
              <Label for="cranes-{i}-equipmentId">Grue</Label>
              <Svelecte
                type="cranes"
                inputId="cranes-{i}-equipmentId"
                name="Grue {i + 1}"
                placeholder="Grue"
                bind:value={entry.equipmentId}
                required
              />
            </div>

            <div class="w-full lg:w-auto">
              <Label for="cranes-{i}-hoursHint">Heures</Label>
              <Input
                type="text"
                maxlength={20}
                id="cranes-{i}-hoursHint"
                bind:value={entry.hoursHint}
                on:input={() => updateMultipleValues("hoursHint", entry)}
                on:change={() => {
                  entry.hoursWorked = inferHoursWorked(entry);
                  updateMultipleValues("hoursHint", entry);
                  updateMultipleValues("hoursWorked", entry, false);
                }}
              />
            </div>

            <div class="w-full lg:w-auto">
              <Label for="cranes-{i}-hoursWorked">Durée</Label>
              <NumericInput
                id="cranes-{i}-hoursWorked"
                name="Grue {i + 1} - Heures"
                format="+2"
                max={24}
                bind:value={entry.hoursWorked}
                on:new-value={() => updateMultipleValues("hoursWorked", entry)}
                placeholder="Heures"
                required
              />
            </div>

            <div class="w-full lg:w-2/5">
              <Label for="cranes-{i}-comments">Commentaires</Label>
              <Input
                type="text"
                id="cranes-{i}-comments"
                bind:value={entry.comments}
                on:input={() => updateMultipleValues("comments", entry)}
              />
            </div>

            <div class="flex-auto">
              <Button
                on:click={() => deleteEntry("cranes", entry)}
                color="red"
                size="sm"
                title="Supprimer"
              >
                <Trash2Icon size={16} />
              </Button>
            </div>
          </div>
        {:else}
          <p>Aucune grue</p>
        {/each}
      </div>
    </div>

    <!-- Equipments -->
    <div>
      <div class="mb-2 flex gap-3 items-center">
        <Checkbox
          on:change={(e) => checkboxes.toggleCategory(e, "equipments")}
          checked={dateEntries.equipments.length > 0 &&
            selectedItems.categories.equipments.length ===
              dateEntries.equipments.length}
          indeterminate={selectedItems.categories.equipments.length > 0 &&
            selectedItems.categories.equipments.length <
              dateEntries.equipments.length}
          id="equipments-toggleAll"
        />
        <span class="text-xl font-bold me-2">Équipements</span>
        <Button
          on:click={addEquipment}
          color="light"
          class="w-1/2 lg:w-auto"
          size="xs"
        >
          <PlusCircleIcon size={16} />
          <span class="ms-2">Ajouter un équipement</span>
        </Button>
      </div>

      <div class="flex flex-col gap-3">
        {#each dateEntries.equipments as entry, i}
          <div
            class="flex flex-col lg:flex-row gap-2 items-center p-2 rounded-lg"
            class:bg-violet-100={selectedItems.categories.equipments.includes(
              entry,
            )}
          >
            <div>
              <input
                type="checkbox"
                bind:group={selectedItems.categories.equipments}
                value={entry}
                class={flowbiteCheckboxStyle}
              />
            </div>

            <div class="w-full lg:w-2/5">
              <Label for="equipments-{i}-equipmentId">Équipement</Label>
              <Svelecte
                type="equipments"
                inputId="equipments-{i}-equipmentId"
                placeholder="Équipement"
                name="Équipement {i + 1}"
                bind:value={entry.equipmentId}
                required
              />
            </div>

            <div class="w-full lg:w-auto">
              <Label for="equipments-{i}-hoursHint">Heures</Label>
              <Input
                type="text"
                maxlength={20}
                id="equipments-{i}-hoursHint"
                bind:value={entry.hoursHint}
                on:input={() => updateMultipleValues("hoursHint", entry)}
                on:change={() => {
                  entry.hoursWorked = inferHoursWorked(entry);
                  updateMultipleValues("hoursHint", entry);
                  updateMultipleValues("hoursWorked", entry, false);
                }}
              />
            </div>

            <div class="w-full lg:w-auto">
              <Label for="equipments-{i}-hoursWorked">Durée</Label>
              <NumericInput
                id="equipments-{i}-hoursWorked"
                name="Équipement {i + 1} - Heures"
                format="+2"
                max={24}
                bind:value={entry.hoursWorked}
                on:new-value={() => updateMultipleValues("hoursWorked", entry)}
                placeholder="Heures"
                required
              />
            </div>

            <div class="w-full lg:w-2/5">
              <Label for="equipments-{i}-comments">Commentaires</Label>
              <Input
                type="text"
                id="equipments-{i}-comments"
                bind:value={entry.comments}
                on:input={() => updateMultipleValues("comments", entry)}
              />
            </div>

            <div class="flex-auto">
              <Button
                on:click={() => deleteEntry("equipments", entry)}
                color="red"
                size="sm"
                title="Supprimer"
              >
                <Trash2Icon size={16} />
              </Button>
            </div>
          </div>
        {:else}
          <p>Aucun équipement</p>
        {/each}
      </div>
    </div>

    <!-- Mensuels -->
    <div>
      <div class="mb-2 flex gap-3 items-center">
        <Checkbox
          on:change={(e) => checkboxes.toggleCategory(e, "permanentStaff")}
          checked={dateEntries.permanentStaff.length > 0 &&
            selectedItems.categories.permanentStaff.length ===
              dateEntries.permanentStaff.length}
          indeterminate={selectedItems.categories.permanentStaff.length > 0 &&
            selectedItems.categories.permanentStaff.length <
              dateEntries.permanentStaff.length}
          id="permanentStaff-toggleAll"
        />
        <span class="text-xl font-bold me-2">Mensuels</span>
        <Button
          on:click={addPermanentStaff}
          color="light"
          class="w-1/2 lg:w-auto"
          size="xs"
        >
          <PlusCircleIcon size={16} />
          <span class="ms-2">Ajouter un membre du personnel</span>
        </Button>
      </div>

      <div class="flex flex-col gap-3">
        {#each dateEntries.permanentStaff as entry, i}
          <div
            class="flex flex-col lg:flex-row gap-2 items-center p-2 rounded-lg"
            class:bg-violet-100={selectedItems.categories.permanentStaff.includes(
              entry,
            )}
          >
            <div>
              <input
                type="checkbox"
                bind:group={selectedItems.categories.permanentStaff}
                value={entry}
                class={flowbiteCheckboxStyle}
              />
            </div>

            <div class="w-full lg:w-2/5">
              <Label for="permanentStaff-{i}-staffId">Personnel</Label>
              <Svelecte
                type="mensuels"
                inputId="permanentStaff-{i}-staffId"
                name="Personnel {i + 1} - Nom"
                placeholder="Personnel"
                bind:value={entry.staffId}
                required
              />
            </div>

            <div class="w-full lg:w-auto">
              <Label for="permanentStaff-{i}-hoursHint">Heures</Label>
              <Input
                type="text"
                maxlength={20}
                id="permanentStaff-{i}-hoursHint"
                bind:value={entry.hoursHint}
                on:input={() => updateMultipleValues("hoursHint", entry)}
                on:change={() => {
                  entry.hoursWorked = inferHoursWorked(entry);
                  updateMultipleValues("hoursHint", entry);
                  updateMultipleValues("hoursWorked", entry, false);
                }}
              />
            </div>

            <div class="w-full lg:w-auto">
              <Label for="permanentStaff-{i}-hoursWorked">Durée</Label>
              <NumericInput
                id="permanentStaff-{i}-hoursWorked"
                name="Personnel {i + 1} - Heures"
                format="+2"
                max={24}
                bind:value={entry.hoursWorked}
                on:new-value={() => updateMultipleValues("hoursWorked", entry)}
                placeholder="Heures"
                required
              />
            </div>

            <div class="w-full lg:w-2/5">
              <Label for="permanentStaff-{i}-comments">Commentaires</Label>
              <Input
                type="text"
                id="permanentStaff.comments.{i}"
                bind:value={entry.comments}
                on:input={() => updateMultipleValues("comments", entry)}
              />
            </div>

            <div class="flex-auto">
              <Button
                on:click={() => deleteEntry("permanentStaff", entry)}
                color="red"
                size="sm"
                title="Supprimer"
              >
                <Trash2Icon size={16} />
              </Button>
            </div>
          </div>
        {:else}
          <p>Aucun membre du personnel</p>
        {/each}
      </div>
    </div>

    <!-- Intérimaires -->
    <div>
      <div class="mb-2 flex gap-3 items-center">
        <Checkbox
          on:change={(e) => checkboxes.toggleCategory(e, "tempStaff")}
          checked={dateEntries.tempStaff.length > 0 &&
            selectedItems.categories.tempStaff.length ===
              dateEntries.tempStaff.length}
          indeterminate={selectedItems.categories.tempStaff.length > 0 &&
            selectedItems.categories.tempStaff.length <
              dateEntries.tempStaff.length}
          id="tempStaff-toggleAll"
        />
        <span class="text-xl font-bold me-2">Intérimaires</span>
        <Button
          on:click={addTempStaff}
          color="light"
          class="w-1/2 lg:w-auto"
          size="xs"
        >
          <PlusCircleIcon size={16} />
          <span class="ms-2">Ajouter un intérimaire</span>
        </Button>
      </div>

      <div class="flex flex-col gap-3">
        {#each dateEntries.tempStaff as entry, i}
          <div
            class="flex flex-col lg:flex-row gap-2 items-center p-2 rounded-lg"
            class:bg-violet-100={selectedItems.categories.tempStaff.includes(
              entry,
            )}
          >
            <div>
              <input
                type="checkbox"
                bind:group={selectedItems.categories.tempStaff}
                value={entry}
                class={flowbiteCheckboxStyle}
              />
            </div>

            <div class="w-full lg:w-2/5">
              <Label for="tempStaff-{i}-staffId">Intérimaire</Label>
              <Svelecte
                type="interimaires"
                inputId="tempStaff-{i}-staffId"
                name="Intérimaire {i + 1} - Nom"
                placeholder="Intérimaire"
                bind:value={entry.staffId}
                required
              />
            </div>

            <div class="w-full lg:w-auto">
              <Label for="tempStaff-{i}-hoursHint">Heures</Label>
              <Input
                type="text"
                maxlength={20}
                id="tempStaff-{i}-hoursHint"
                bind:value={entry.hoursHint}
                on:input={() => updateMultipleValues("hoursHint", entry)}
                on:change={() => {
                  entry.hoursWorked = inferHoursWorked(entry);
                  updateMultipleValues("hoursHint", entry);
                  updateMultipleValues("hoursWorked", entry, false);
                }}
              />
            </div>

            <div class="w-full lg:w-auto">
              <Label for="tempStaff-{i}-hoursWorked">Durée</Label>
              <NumericInput
                id="tempStaff-{i}-hoursWorked"
                name="Intérimaire {i + 1} - Heures"
                format="+2"
                max={24}
                bind:value={entry.hoursWorked}
                on:new-value={() => updateMultipleValues("hoursWorked", entry)}
                placeholder="Heures"
                required
              />
            </div>

            <div class="w-full lg:w-2/5">
              <Label for="tempStaff-{i}-comments">Commentaires</Label>
              <Input
                type="text"
                id="tempStaff-{i}-comments"
                bind:value={entry.comments}
                on:input={() => updateMultipleValues("comments", entry)}
              />
            </div>

            <div class="flex-auto">
              <Button
                on:click={() => deleteEntry("tempStaff", entry)}
                color="red"
                size="sm"
                title="Supprimer"
              >
                <Trash2Icon size={16} />
              </Button>
            </div>
          </div>
        {:else}
          <p>Aucun membre du personnel</p>
        {/each}
      </div>
    </div>

    <!-- Brouettage -->
    <div>
      <div class="mb-2 flex gap-3 items-center">
        <Checkbox
          on:change={(e) => checkboxes.toggleCategory(e, "trucking")}
          checked={dateEntries.trucking.length > 0 &&
            selectedItems.categories.trucking.length ===
              dateEntries.trucking.length}
          indeterminate={selectedItems.categories.trucking.length > 0 &&
            selectedItems.categories.trucking.length <
              dateEntries.trucking.length}
          id="trucking-toggleAll"
        />
        <span class="text-lg font-bold me-2">Brouettage</span>
        <Button
          on:click={addSubcontractTrucking}
          color="light"
          class="w-1/2 lg:w-auto"
          size="xs"
        >
          <PlusCircleIcon size={16} />
          <span class="ms-2">Ajouter du brouettage</span>
        </Button>
      </div>

      <div class="flex flex-col gap-3">
        {#each dateEntries.trucking as entry, i}
          <div
            class="flex flex-col lg:flex-row gap-2 items-center p-2 rounded-lg"
            class:bg-violet-100={selectedItems.categories.trucking.includes(
              entry,
            )}
          >
            <div>
              <input
                type="checkbox"
                bind:group={selectedItems.categories.trucking}
                value={entry}
                class={flowbiteCheckboxStyle}
              />
            </div>

            <div class="w-full lg:w-2/5">
              <Label for="trucking-{i}-subcontractorName">Prestataire</Label>
              <Svelecte
                options={subcontractorTruckingList}
                inputId="trucking-{i}-subcontractorName"
                name="Brouettage {i + 1} - Prestataire"
                placeholder="Prestataire"
                bind:value={entry.subcontractorName}
                creatable
                creatablePrefix=""
                keepCreated
                allowEditing
                required
              />
            </div>

            <div class="w-full lg:w-auto">
              <Label for="trucking-{i}-hoursWorked">Durée</Label>
              <NumericInput
                id="trucking-{i}-hoursWorked"
                name="Brouettage {i + 1} - Heures"
                format="+2"
                max={24}
                bind:value={entry.hoursWorked}
                on:new-value={() => updateMultipleValues("hoursWorked", entry)}
                placeholder="Heures"
                required={entry.cost === null}
              />
            </div>

            <div class="w-full lg:w-auto">
              <Label for="trucking-{i}-cost">Coût (€)</Label>
              <NumericInput
                id="trucking-{i}-cost"
                name="Brouettage {i + 1} - Coût"
                format="2"
                bind:value={entry.cost}
                placeholder="Coût"
                required={entry.hoursWorked === null}
              />
            </div>

            <div class="w-full lg:w-2/5">
              <Label for="trucking-{i}-comments">Commentaires</Label>
              <Input
                type="text"
                id="trucking-{i}-comments"
                bind:value={entry.comments}
                on:input={() => updateMultipleValues("comments", entry)}
              />
            </div>

            <div class="flex-auto">
              <Button
                on:click={() => deleteEntry("trucking", entry)}
                color="red"
                size="sm"
                title="Supprimer"
              >
                <Trash2Icon size={16} />
              </Button>
            </div>
          </div>
        {:else}
          <p>Aucun brouettage</p>
        {/each}
      </div>
    </div>

    <!-- Autres sous-traitances -->
    <div>
      <div class="mb-2 flex gap-3 items-center">
        <Checkbox
          on:change={(e) => checkboxes.toggleCategory(e, "otherSubcontracts")}
          checked={dateEntries.otherSubcontracts.length > 0 &&
            selectedItems.categories.otherSubcontracts.length ===
              dateEntries.otherSubcontracts.length}
          indeterminate={selectedItems.categories.otherSubcontracts.length >
            0 &&
            selectedItems.categories.otherSubcontracts.length <
              dateEntries.otherSubcontracts.length}
          id="otherSubcontracts-toggleAll"
        />
        <span class="text-lg font-bold me-2">Autres sous-traitances</span>
        <Button
          on:click={addSubcontractOther}
          color="light"
          class="w-1/2 lg:w-auto"
          size="xs"
        >
          <PlusCircleIcon size={16} />
          <span class="ms-2">Ajouter une sous-traitance</span>
        </Button>
      </div>

      <div class="flex flex-col gap-3">
        {#each dateEntries.otherSubcontracts as entry, i}
          <div
            class="flex flex-col lg:flex-row gap-2 items-center p-2 rounded-lg"
            class:bg-violet-100={selectedItems.categories.otherSubcontracts.includes(
              entry,
            )}
          >
            <div>
              <input
                type="checkbox"
                bind:group={selectedItems.categories.otherSubcontracts}
                value={entry}
                class={flowbiteCheckboxStyle}
              />
            </div>

            <div class="w-full lg:w-2/5">
              <Label for="otherSubcontracts-{i}-subcontractorName"
                >Prestataire</Label
              >
              <Svelecte
                options={subcontractorOtherList}
                inputId="otherSubcontracts-{i}-subcontractorName"
                name="Sous-traitance {i + 1} - Prestataire"
                placeholder="Prestataire"
                bind:value={entry.subcontractorName}
                creatable
                creatablePrefix=""
                keepCreated
                allowEditing
                required
              />
            </div>

            <div class="w-full lg:w-auto">
              <Label for="otherSubcontracts-{i}-hoursWorked">Durée</Label>
              <NumericInput
                id="otherSubcontracts-{i}-hoursWorked"
                name="Sous-traitance {i + 1} - Heures"
                format="+2"
                max={24}
                bind:value={entry.hoursWorked}
                on:new-value={() => updateMultipleValues("hoursWorked", entry)}
                placeholder="Heures"
                required={entry.cost === null}
              />
            </div>

            <div class="w-full lg:w-auto">
              <Label for="otherSubcontracts-{i}-cost">Coût (€)</Label>
              <NumericInput
                id="otherSubcontracts-{i}-cost"
                name="Sous-traitance {i + 1} - Coût"
                format="2"
                bind:value={entry.cost}
                placeholder="Coût"
                required={entry.hoursWorked === null}
              />
            </div>

            <div class="w-full lg:w-2/5">
              <Label for="otherSubcontracts-{i}-comments">Commentaires</Label>
              <Input
                type="text"
                id="otherSubcontracts-{i}-comments"
                bind:value={entry.comments}
                on:input={() => updateMultipleValues("comments", entry)}
              />
            </div>

            <div class="flex-auto">
              <Button
                on:click={() => deleteEntry("otherSubcontracts", entry)}
                color="red"
                size="sm"
                title="Supprimer"
              >
                <Trash2Icon size={16} />
              </Button>
            </div>
          </div>
        {:else}
          <p>Aucune autre sous-traitance</p>
        {/each}
      </div>
    </div>
  </form>

  <div class="text-center">
    <!-- Bouton "Modifier" -->
    <BoutonAction preset="modifier" on:click={updateEntries} />

    <!-- Bouton "Annuler" -->
    <BoutonAction preset="annuler" on:click={cancelUpdate} />
  </div>
</Modal>
