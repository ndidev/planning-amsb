<script lang="ts">
  import { onMount, tick } from "svelte";

  import { Modal, Input, Label, Button } from "flowbite-svelte";
  import { PlusCircleIcon, Trash2Icon } from "lucide-svelte";
  import Notiflix from "notiflix";

  import { Svelecte, NumericInput, BoutonAction } from "@app/components";

  import { DateUtils, validerFormulaire, fetcher } from "@app/utils";

  import type { StevedoringShipReport } from "@app/types";

  let form: HTMLFormElement;

  export let open: boolean = false;
  export let date: string;
  export let report: StevedoringShipReport;

  let dateFromInput: string;
  let dateEntries: StevedoringShipReport["entriesByDate"][string];
  let subcontractorTruckingList: string[] = [];
  let subcontractorOtherList: string[] = [];

  async function getSubcontractorsData() {
    type SubcontractorsData = {
      trucking: string[];
      other: string[];
    };

    try {
      const subcontractorsData: SubcontractorsData = await fetcher(
        "manutention/rapports-navires/sous-traitants"
      );

      subcontractorTruckingList = subcontractorsData.trucking;
      subcontractorOtherList = subcontractorsData.other;
    } catch (error) {
      // Silently fail
    }
  }

  async function addCrane() {
    dateEntries.cranes = [
      ...dateEntries.cranes,
      {
        id: null,
        equipmentId: null,
        date: null,
        hoursWorked: 0,
        comments: "",
      },
    ];

    await tick();

    form
      .querySelector<HTMLInputElement>(
        `#cranes-${dateEntries.cranes.length - 1}-equipmentId`
      )
      .focus();
  }

  async function addEquipment() {
    dateEntries.equipments = [
      ...dateEntries.equipments,
      {
        id: null,
        equipmentId: null,
        date: null,
        hoursWorked: 0,
        comments: "",
      },
    ];

    await tick();

    form
      .querySelector<HTMLInputElement>(
        `#equipments-${dateEntries.equipments.length - 1}-equipmentId`
      )
      .focus();
  }

  async function addPermanentStaff() {
    dateEntries.permanentStaff = [
      ...dateEntries.permanentStaff,
      {
        id: null,
        staffId: null,
        date: null,
        hoursWorked: 0,
        comments: "",
      },
    ];

    await tick();

    form
      .querySelector<HTMLInputElement>(
        `#permanentStaff-${dateEntries.permanentStaff.length - 1}-staffId`
      )
      .focus();
  }

  async function addTempStaff() {
    dateEntries.tempStaff = [
      ...dateEntries.tempStaff,
      {
        id: null,
        staffId: null,
        date: null,
        hoursWorked: 0,
        comments: "",
      },
    ];

    await tick();

    form
      .querySelector<HTMLInputElement>(
        `#tempStaff-${dateEntries.tempStaff.length - 1}-staffId`
      )
      .focus();
  }

  async function addSubcontractTrucking() {
    dateEntries.trucking = [
      ...dateEntries.trucking,
      {
        id: null,
        subcontractorName: "",
        date: null,
        hoursWorked: null,
        cost: null,
        comments: "",
      },
    ];

    await tick();

    form
      .querySelector<HTMLInputElement>(
        `#trucking-${dateEntries.trucking.length - 1}-subcontractorName`
      )
      .focus();
  }

  async function addSubcontractOther() {
    dateEntries.otherSubcontracts = [
      ...dateEntries.otherSubcontracts,
      {
        id: null,
        subcontractorName: "",
        date: null,
        hoursWorked: null,
        cost: null,
        comments: "",
      },
    ];

    await tick();

    form
      .querySelector<HTMLInputElement>(
        `#otherSubcontracts-${dateEntries.otherSubcontracts.length - 1}-subcontractorName`
      )
      .focus();
  }

  async function updateEntries() {
    if (!validerFormulaire(form)) return;

    // If the date has changed, check that is does not interfere with existing entries
    if (dateFromInput !== date) {
      const dateAlreadyExists = Object.keys(report.entriesByDate).includes(
        dateFromInput
      );

      if (dateAlreadyExists) {
        Notiflix.Notify.failure(
          `Il existe déjà des entrées pour le ${new DateUtils(dateFromInput).format().long}`
        );

        return;
      }
    }

    // Update the entries
    report.entriesByDate[dateFromInput] = dateEntries;

    // Remove the old entries if the date has changed
    if (dateFromInput !== date) {
      delete report.entriesByDate[date];
    }

    open = false;
  }

  function cancelUpdate() {
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
    dateEntries = structuredClone(report.entriesByDate[date]) || {
      permanentStaff: [],
      tempStaff: [],
      cranes: [],
      equipments: [],
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

    <!-- Grues -->
    <div>
      <div class="mb-2">
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
        {#each dateEntries.cranes as craneEntry, i}
          <div class="flex flex-col lg:flex-row gap-2 items-center">
            <div class="w-full lg:w-2/5">
              <Label for="cranes-{i}-equipmentId">Grue</Label>
              <Svelecte
                type="cranes"
                inputId="cranes-{i}-equipmentId"
                name="Grue {i + 1}"
                placeholder="Grue"
                bind:value={craneEntry.equipmentId}
                required
              />
            </div>

            <div class="w-full lg:w-auto">
              <Label for="cranes-{i}-hoursWorked">Heures</Label>
              <NumericInput
                id="cranes-{i}-hoursWorked"
                name="Grue {i + 1} - Heures"
                format="+2"
                max={24}
                bind:value={craneEntry.hoursWorked}
                placeholder="Heures"
                required
              />
            </div>

            <div class="w-full lg:w-2/5">
              <Label for="cranes-{i}-comments">Commentaires</Label>
              <Input
                type="text"
                id="cranes-{i}-comments"
                bind:value={craneEntry.comments}
              />
            </div>

            <div class="flex-auto">
              <Button
                on:click={() => {
                  dateEntries.cranes = dateEntries.cranes.filter(
                    (entry) => entry !== craneEntry
                  );
                }}
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
      <div class="mb-2">
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
        {#each dateEntries.equipments as equipmentEntry, i}
          <div class="flex flex-col lg:flex-row gap-2 items-center">
            <div class="w-full lg:w-2/5">
              <Label for="equipments-{i}-equipmentId">Équipement</Label>
              <Svelecte
                type="equipments"
                inputId="equipments-{i}-equipmentId"
                placeholder="Équipement"
                name="Équipement {i + 1}"
                bind:value={equipmentEntry.equipmentId}
                required
              />
            </div>

            <div class="w-full lg:w-auto">
              <Label for="equipments-{i}-hoursWorked">Heures</Label>
              <NumericInput
                id="equipments-{i}-hoursWorked"
                name="Équipement {i + 1} - Heures"
                format="+2"
                max={24}
                bind:value={equipmentEntry.hoursWorked}
                placeholder="Heures"
                required
              />
            </div>

            <div class="w-full lg:w-2/5">
              <Label for="equipments-{i}-comments">Commentaires</Label>
              <Input
                type="text"
                id="equipments-{i}-comments"
                bind:value={equipmentEntry.comments}
              />
            </div>

            <div class="flex-auto">
              <Button
                on:click={() => {
                  dateEntries.equipments = dateEntries.equipments.filter(
                    (entry) => entry !== equipmentEntry
                  );
                }}
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
      <div class="mb-2">
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
        {#each dateEntries.permanentStaff as staffEntry, i}
          <div class="flex flex-col lg:flex-row gap-2 items-center">
            <div class="w-full lg:w-2/5">
              <Label for="permanentStaff-{i}-staffId">Personnel</Label>
              <Svelecte
                type="mensuels"
                inputId="permanentStaff-{i}-staffId"
                name="Personnel {i + 1} - Nom"
                placeholder="Personnel"
                bind:value={staffEntry.staffId}
                required
              />
            </div>

            <div class="w-full lg:w-auto">
              <Label for="permanentStaff-{i}-hoursWorked">Heures</Label>
              <NumericInput
                id="permanentStaff-{i}-hoursWorked"
                name="Personnel {i + 1} - Heures"
                format="+2"
                max={24}
                bind:value={staffEntry.hoursWorked}
                placeholder="Heures"
                required
              />
            </div>

            <div class="w-full lg:w-2/5">
              <Label for="permanentStaff-{i}-comments">Commentaires</Label>
              <Input
                type="text"
                id="permanentStaff.comments.{i}"
                bind:value={staffEntry.comments}
              />
            </div>

            <div class="flex-auto">
              <Button
                on:click={() => {
                  dateEntries.permanentStaff =
                    dateEntries.permanentStaff.filter(
                      (entry) => entry !== staffEntry
                    );
                }}
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
      <div class="mb-2">
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
        {#each dateEntries.tempStaff as staffEntry, i}
          <div class="flex flex-col lg:flex-row gap-2 items-center">
            <div class="w-full lg:w-2/5">
              <Label for="tempStaff-{i}-staffId">Intérimaire</Label>
              <Svelecte
                type="interimaires"
                inputId="tempStaff-{i}-staffId"
                name="Intérimaire {i + 1} - Nom"
                placeholder="Intérimaire"
                bind:value={staffEntry.staffId}
                required
              />
            </div>

            <div class="w-full lg:w-auto">
              <Label for="tempStaff-{i}-hoursWorked">Heures</Label>
              <NumericInput
                id="tempStaff-{i}-hoursWorked"
                name="Intérimaire {i + 1} - Heures"
                format="+2"
                max={24}
                bind:value={staffEntry.hoursWorked}
                placeholder="Heures"
                required
              />
            </div>

            <div class="w-full lg:w-2/5">
              <Label for="tempStaff-{i}-comments">Commentaires</Label>
              <Input
                type="text"
                id="tempStaff-{i}-comments"
                bind:value={staffEntry.comments}
              />
            </div>

            <div class="flex-auto">
              <Button
                on:click={() => {
                  dateEntries.tempStaff = dateEntries.tempStaff.filter(
                    (entry) => entry !== staffEntry
                  );
                }}
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
      <div class="mb-2">
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
        {#each dateEntries.trucking as subcontractEntry, i}
          <div class="flex flex-col lg:flex-row gap-2 items-center">
            <div class="w-full lg:w-2/5">
              <Label for="trucking-{i}-subcontractorName">Prestataire</Label>
              <Svelecte
                options={subcontractorTruckingList}
                inputId="trucking-{i}-subcontractorName"
                name="Brouettage {i + 1} - Prestataire"
                placeholder="Prestataire"
                bind:value={subcontractEntry.subcontractorName}
                creatable
                creatablePrefix=""
                keepCreated
                allowEditing
                required
              />
            </div>

            <div class="w-full lg:w-auto">
              <Label for="trucking-{i}-hoursWorked">Heures</Label>
              <NumericInput
                id="trucking-{i}-hoursWorked"
                name="Brouettage {i + 1} - Heures"
                format="+2"
                max={24}
                bind:value={subcontractEntry.hoursWorked}
                placeholder="Heures"
                required={subcontractEntry.cost === null}
              />
            </div>

            <div class="w-full lg:w-auto">
              <Label for="trucking-{i}-cost">Coût (€)</Label>
              <NumericInput
                id="trucking-{i}-cost"
                name="Brouettage {i + 1} - Coût"
                format="2"
                bind:value={subcontractEntry.cost}
                placeholder="Coût"
                required={subcontractEntry.hoursWorked === null}
              />
            </div>

            <div class="w-full lg:w-2/5">
              <Label for="trucking-{i}-comments">Commentaires</Label>
              <Input
                type="text"
                id="trucking-{i}-comments"
                bind:value={subcontractEntry.comments}
              />
            </div>

            <div class="flex-auto">
              <Button
                on:click={() => {
                  dateEntries.trucking = dateEntries.trucking.filter(
                    (entry) => entry !== subcontractEntry
                  );
                }}
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
      <div class="mb-2">
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
        {#each dateEntries.otherSubcontracts as subcontractEntry, i}
          <div class="flex flex-col lg:flex-row gap-2 items-center">
            <div class="w-full lg:w-2/5">
              <Label for="otherSubcontracts-{i}-subcontractorName"
                >Prestataire</Label
              >
              <Svelecte
                options={subcontractorOtherList}
                inputId="otherSubcontracts-{i}-subcontractorName"
                name="Sous-traitance {i + 1} - Prestataire"
                placeholder="Prestataire"
                bind:value={subcontractEntry.subcontractorName}
                creatable
                creatablePrefix=""
                keepCreated
                allowEditing
                required
              />
            </div>

            <div class="w-full lg:w-auto">
              <Label for="otherSubcontracts-{i}-hoursWorked">Heures</Label>
              <NumericInput
                id="otherSubcontracts-{i}-hoursWorked"
                name="Sous-traitance {i + 1} - Heures"
                format="+2"
                max={24}
                bind:value={subcontractEntry.hoursWorked}
                placeholder="Heures"
                required={subcontractEntry.cost === null}
              />
            </div>

            <div class="w-full lg:w-auto">
              <Label for="otherSubcontracts-{i}-cost">Coût (€)</Label>
              <NumericInput
                id="otherSubcontracts-{i}-cost"
                name="Sous-traitance {i + 1} - Coût"
                format="2"
                bind:value={subcontractEntry.cost}
                placeholder="Coût"
                required={subcontractEntry.hoursWorked === null}
              />
            </div>

            <div class="w-full lg:w-2/5">
              <Label for="otherSubcontracts-{i}-comments">Commentaires</Label>
              <Input
                type="text"
                id="otherSubcontracts-{i}-comments"
                bind:value={subcontractEntry.comments}
              />
            </div>

            <div class="flex-auto">
              <Button
                on:click={() => {
                  dateEntries.otherSubcontracts =
                    dateEntries.otherSubcontracts.filter(
                      (entry) => entry !== subcontractEntry
                    );
                }}
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
