<script lang="ts">
  import { Modal, Input, Label, Button, Select } from "flowbite-svelte";
  import { PlusCircleIcon, Trash2Icon } from "lucide-svelte";
  import Notiflix from "notiflix";

  import { Svelecte, NumericInput, BoutonAction } from "@app/components";

  import { DateUtils, validerFormulaire } from "@app/utils";

  import type { StevedoringShipReport } from "@app/types";

  let form: HTMLFormElement;

  export let open: boolean = false;
  export let date: string;
  export let report: StevedoringShipReport;

  let dateFromInput: string;
  let dateEntries: StevedoringShipReport["entriesByDate"][string];
  let subcontractorList: { names: string[]; types: string[] } = {
    names: [],
    types: [],
  };

  function addCrane() {
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
  }

  function addEquipment() {
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
  }

  function addPermanentStaff() {
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
  }

  function addTempStaff() {
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
  }

  function addSubcontractTrucking() {
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
  }

  function addSubcontractOther() {
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
</script>

<Modal
  title={new DateUtils(date).format().long}
  bind:open
  dismissable={false}
  size="lg"
  on:open={async () => {
    dateFromInput = date;
    dateEntries = structuredClone(report.entriesByDate[date]) || {
      permanentStaff: [],
      tempStaff: [],
      cranes: [],
      equipments: [],
      trucking: [],
      otherSubcontracts: [],
    };
    // subcontractorList = await fetcher(
    //   "manutention/rapports-navires/sous-traitants"
    // );
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
              <Label for="crane-name-{i}">Grue</Label>
              <Svelecte
                type="cranes"
                inputId="crane-name-{i}"
                placeholder="Grue"
                name="Grue {i + 1}"
                bind:value={craneEntry.equipmentId}
                required
              />
            </div>

            <div class="w-full lg:w-auto">
              <Label for="crane-hours-worked-{i}">Heures</Label>
              <NumericInput
                id="crane-hours-worked-{i}"
                name="Grue {i + 1} - Heures"
                format="+2"
                max={24}
                bind:value={craneEntry.hoursWorked}
                placeholder="Heures"
                required
              />
            </div>

            <div class="w-full lg:w-2/5">
              <Label for="crane-comments-{i}">Commentaires</Label>
              <Input
                type="text"
                id="crane-comments-{i}"
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
              <Label for="equipment-name-{i}">Équipement</Label>
              <Svelecte
                type="equipments"
                inputId="equipment-name-{i}"
                placeholder="Équipement"
                name="Équipement {i + 1}"
                bind:value={equipmentEntry.equipmentId}
                required
              />
            </div>

            <div class="w-full lg:w-auto">
              <Label for="equipment-hours-worked-{i}">Heures</Label>
              <NumericInput
                id="equipment-hours-worked-{i}"
                name="Équipement {i + 1} - Heures"
                format="+2"
                max={24}
                bind:value={equipmentEntry.hoursWorked}
                placeholder="Heures"
                required
              />
            </div>

            <div class="w-full lg:w-2/5">
              <Label for="equipment-comments-{i}">Commentaires</Label>
              <Input
                type="text"
                id="equipment-comments-{i}"
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
              <Label for="permanent-staff-name-{i}">Personnel</Label>
              <Svelecte
                type="mensuels"
                inputId="permanent-staff-name-{i}"
                name="Personnel {i + 1} - Nom"
                placeholder="Personnel"
                bind:value={staffEntry.staffId}
                required
              />
            </div>

            <div class="w-full lg:w-auto">
              <Label for="permanent-staf-hours-worked-{i}">Heures</Label>
              <NumericInput
                id="permanent-staf-hours-worked-{i}"
                name="Personnel {i + 1} - Heures"
                format="+2"
                max={24}
                bind:value={staffEntry.hoursWorked}
                placeholder="Heures"
                required
              />
            </div>

            <div class="w-full lg:w-2/5">
              <Label for="permanent-staf-comments-{i}">Commentaires</Label>
              <Input
                type="text"
                id="permanent-staf-comments-{i}"
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
              <Label for="temp-staff-name-{i}">Intérimaire</Label>
              <Svelecte
                type="interimaires"
                inputId="temp-staff-name-{i}"
                name="Intérimaire {i + 1} - Nom"
                placeholder="Intérimaire"
                bind:value={staffEntry.staffId}
                required
              />
            </div>

            <div class="w-full lg:w-auto">
              <Label for="temp-staff-hours-worked-{i}">Heures</Label>
              <NumericInput
                id="temp-staff-hours-worked-{i}"
                name="Intérimaire {i + 1} - Heures"
                format="+2"
                max={24}
                bind:value={staffEntry.hoursWorked}
                placeholder="Heures"
                required
              />
            </div>

            <div class="w-full lg:w-2/5">
              <Label for="temp-staff-comments-{i}">Commentaires</Label>
              <Input
                type="text"
                id="temp-staff-comments-{i}"
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
              <Label for="subcontractorName-{i}">Prestataire</Label>
              <!-- TODO: Svelecte avec noms sous-traitants existants -->
              <!-- <Svelecte
                   options={subcontractorTruckingList.names}
                   inputId="subcontractorName-{i}"
                   name="Sous-traitance {i +1} - Prestataire"
                   placeholder="Prestataire"
                   name="Prestataire {i + 1}"
                   bind:value={subcontractEntry.subcontractorName}
                   creatable
                   creatablePrefix=""
                   keepCreated
                   allowEditing
                   required
                 /> -->
              <Input
                type="text"
                id="subcontractorName-{i}"
                name="Sous-traitance {i + 1} - Prestataire"
                bind:value={subcontractEntry.subcontractorName}
                placeholder="Prestataire"
                required
              />
            </div>

            <div class="w-full lg:w-auto">
              <Label for="hours-worked-{i}">Heures</Label>
              <NumericInput
                id="hours-worked-{i}"
                name="Sous-traitance {i + 1} - Heures"
                format="+2"
                max={24}
                bind:value={subcontractEntry.hoursWorked}
                placeholder="Heures"
                required={subcontractEntry.cost === null}
              />
            </div>

            <div class="w-full lg:w-auto">
              <Label for="cost-{i}">Coût</Label>
              <NumericInput
                id="cost-{i}"
                name="Sous-traitance {i + 1} - Coût"
                format="2"
                bind:value={subcontractEntry.cost}
                placeholder="Coût"
                required={subcontractEntry.hoursWorked === null}
              />
            </div>

            <div class="w-full lg:w-2/5">
              <Label for="comments-{i}">Commentaires</Label>
              <Input
                type="text"
                id="comments-{i}"
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
              <Label for="subcontractorName-{i}">Prestataire</Label>
              <!-- <Svelecte
                options={subcontractorList.names}
                inputId="subcontractorName-{i}"
                name="Sous-traitance {i +1} - Prestataire"
                placeholder="Prestataire"
                name="Prestataire {i + 1}"
                bind:value={subcontractEntry.subcontractorName}
                required
              /> -->
              <Input
                type="text"
                id="subcontractorName-{i}"
                name="Sous-traitance {i + 1} - Prestataire"
                bind:value={subcontractEntry.subcontractorName}
                placeholder="Prestataire"
                required
              />
            </div>

            <div class="w-full lg:w-auto">
              <Label for="hours-worked-{i}">Heures</Label>
              <NumericInput
                id="hours-worked-{i}"
                name="Sous-traitance {i + 1} - Heures"
                format="+2"
                max={24}
                bind:value={subcontractEntry.hoursWorked}
                placeholder="Heures"
                required={subcontractEntry.cost === null}
              />
            </div>

            <div class="w-full lg:w-auto">
              <Label for="cost-{i}">Coût</Label>
              <NumericInput
                id="cost-{i}"
                name="Sous-traitance {i + 1} - Coût"
                format="2"
                bind:value={subcontractEntry.cost}
                placeholder="Coût"
                required={subcontractEntry.hoursWorked === null}
              />
            </div>

            <div class="w-full lg:w-2/5">
              <Label for="comments-{i}">Commentaires</Label>
              <Input
                type="text"
                id="comments-{i}"
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
