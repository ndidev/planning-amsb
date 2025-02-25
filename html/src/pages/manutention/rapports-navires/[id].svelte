<!-- routify:options title="Planning AMSB - Rapport navire" -->
<script lang="ts">
  import { onMount } from "svelte";
  import { params, goto, redirect } from "@roxi/routify";

  import {
    Button,
    Dropdown,
    DropdownItem,
    Input,
    Label,
    Select,
    Tabs,
    TabItem,
    Textarea,
    Toggle,
    Tooltip,
  } from "flowbite-svelte";
  import {
    CopyIcon,
    EllipsisVerticalIcon,
    LinkIcon,
    PencilIcon,
    PlusCircleIcon,
    Trash2Icon,
    TriangleAlertIcon,
    UnlinkIcon,
    WandSparklesIcon,
  } from "lucide-svelte";
  import Notiflix from "notiflix";

  import {
    CallSelectionModal,
    DailyEntriesModal,
    CargoSelectionModal,
    SubreportTabItem,
  } from "./components/idComponents";

  import {
    PageHeading,
    LucideButton,
    Svelecte,
    NumericInput,
    Chargement,
    BoutonAction,
  } from "@app/components";

  import {
    fetcher,
    DateUtils,
    validerFormulaire,
    notiflixOptions,
    preventFormSubmitOnEnterKeydown,
  } from "@app/utils";

  import {
    stevedoringShipReports,
    stevedoringEquipments,
    stevedoringStaff,
    consignationEscales,
  } from "@app/stores";

  import type { StevedoringShipReport } from "@app/types";

  let form: HTMLFormElement;
  let createReportButton: BoutonAction;
  let updateReportButton: BoutonAction;
  let deleteReportButton: BoutonAction;
  let addDayButton: Button;

  let callSelectionModalOpen: boolean = false;
  let dailyEntriesModalOpen: boolean = false;
  let dailyEntriesSubreport: StevedoringShipReport["subreports"][number];
  let dailyEntriesDate: string;
  let displaySubreportIndex = 0;
  let cargoSelectionModalOpen: boolean = false;
  let cargoSelectionSubreport: StevedoringShipReport["subreports"][number];
  let cargoSelectionSubreportIndex: number;
  let cargoSelectionCreationMode: boolean = false;
  let noSubreportTabOpen: boolean = false;

  /**
   * Identifiant du RDV.
   */
  let id: StevedoringShipReport["id"] = parseInt($params.id);

  let cargoList: string[];
  let listeClients: string[];

  const isNew = $params.id === "new";

  let report: StevedoringShipReport = isNew
    ? stevedoringShipReports.getTemplate()
    : null;

  // Récupérer les données de le rapport
  (async () => {
    try {
      if (id) {
        report = structuredClone(await stevedoringShipReports.get(id));
        if (!report) throw new Error();
      }
    } catch (error) {
      $redirect("./new");
    }

    noSubreportTabOpen = report.subreports.length === 0;
  })();

  function removeLinkWithCall() {
    Notiflix.Confirm.show(
      "Suppression de la liaison",
      "Voulez-vous vraiment supprimer la liaison avec l'escale consignation ?",
      "Supprimer",
      "Annuler",
      async function () {
        report.linkedShippingCallId = null;
      },
      null,
      notiflixOptions.themes.red
    );
  }

  function addCargo() {
    report.cargoEntries = [
      ...report.cargoEntries,
      {
        id: null,
        escale_id: null,
        shipReportId: report.id,
        operation: "import",
        cargoName: "",
        customer: "",
        isApproximate: true,
        blTonnage: null,
        blVolume: null,
        blUnits: null,
        outturnTonnage: null,
        outturnVolume: null,
        outturnUnits: null,
      },
    ];
  }

  function deleteCargo(
    marchandiseASupprimer: StevedoringShipReport["cargoEntries"][number]
  ) {
    report.cargoEntries = report.cargoEntries.filter(
      (marchandise) => marchandise !== marchandiseASupprimer
    );
  }

  function addSubreport(bypassCargoSelection = false) {
    report.subreports = [
      ...report.subreports,
      {
        id: null,
        entriesByDate: {},
        // Copy cargo entries if no subreport exists
        cargoIds:
          report.subreports.length === 0
            ? report.cargoEntries.flatMap((cargo) => cargo.id)
            : [],
      },
    ];

    noSubreportTabOpen = false;
    displaySubreportIndex = report.subreports.length - 1;

    if (!bypassCargoSelection) {
      openCargoSelectionModal(
        report.subreports[displaySubreportIndex],
        displaySubreportIndex,
        true
      );
    }
  }

  function deleteSubreport(
    subreport: StevedoringShipReport["subreports"][number]
  ) {
    // Get current subreport index
    const currentIndex = report.subreports.indexOf(subreport);

    // Delete subreport
    report.subreports = report.subreports.filter(
      (_subreport) => _subreport !== subreport
    );

    if (report.subreports.length === 0) {
      noSubreportTabOpen = true;
      return;
    }

    // Show previous tab or first tab if first tab was deleted
    displaySubreportIndex = Math.max(currentIndex - 1, 0);
  }

  function openCargoSelectionModal(
    subreport: StevedoringShipReport["subreports"][number],
    index: number,
    creationMode = false
  ) {
    cargoSelectionSubreport = subreport;
    cargoSelectionSubreportIndex = index;
    cargoSelectionCreationMode = creationMode;
    cargoSelectionModalOpen = true;
  }

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

  function addStorage() {
    report.storageEntries = [
      ...report.storageEntries,
      {
        id: null,
        cargoId: null,
        storageName: "",
        tonnage: null,
        volume: null,
        units: null,
        comments: "",
      },
    ];
  }

  function deleteStorage(
    storage: StevedoringShipReport["storageEntries"][number]
  ) {
    report.storageEntries = report.storageEntries.filter(
      (_storage) => _storage !== storage
    );
  }

  function checkStorageQuantities() {
    // For each cargo, check that the total stored qualtity does not exceed the corresponding total cargo quantity
    report.cargoEntries.forEach((cargo) => {
      // Tonnage
      const storedTonnage = report.storageEntries
        .filter((storage) => storage.cargoId === cargo.id)
        .reduce(
          (acc, storage) => acc + (parseFloat(String(storage.tonnage)) || 0),
          0
        );

      if (
        storedTonnage >
        parseFloat(String(cargo.outturnTonnage || cargo.blTonnage))
      ) {
        Notiflix.Notify.warning(
          `Le tonnage stocké de la marchandise ${cargo.cargoName} (${cargo.customer}) dépasse le tonnage du navire`
        );
        return false;
      }

      // Volume
      const storedVolume = report.storageEntries
        .filter((storage) => storage.cargoId === cargo.id)
        .reduce((acc, storage) => acc + (storage.volume || 0), 0);

      if (storedVolume > (cargo.outturnVolume || cargo.blVolume)) {
        Notiflix.Notify.warning(
          `Le cubage stocké de la marchandise ${cargo.cargoName} (${cargo.customer}) dépasse le cubage du navire`
        );
        return false;
      }

      // Units
      const storedUnits = report.storageEntries
        .filter((storage) => storage.cargoId === cargo.id)
        .reduce((acc, storage) => acc + (storage.units || 0), 0);

      if (storedUnits > (cargo.outturnUnits || cargo.blUnits)) {
        Notiflix.Notify.warning(
          `Le nombre de colis stocké de la marchandise ${cargo.cargoName} (${cargo.customer}) dépasse le nombre de colis du navire`
        );
        return false;
      }
    });
  }

  async function createReport() {
    if (!validerFormulaire(form)) return;

    createReportButton.$set({ block: true });

    try {
      await stevedoringShipReports.create(report);

      Notiflix.Notify.success("Le rapport a été créé");
      $goto("./");
    } catch (erreur) {
      Notiflix.Notify.failure(erreur.message);
      createReportButton.$set({ block: false });
    }
  }

  async function updateReport() {
    if (!validerFormulaire(form)) return;

    updateReportButton.$set({ block: true });

    try {
      await stevedoringShipReports.update(report);

      Notiflix.Notify.success("Le rapport a été modifié");
      $goto("./");
    } catch (erreur) {
      Notiflix.Notify.failure(erreur.message);
      console.error(erreur);
      updateReportButton.$set({ block: false });
    }
  }

  function deleteReport() {
    if (!id) return;

    deleteReportButton.$set({ block: true });

    // Demande de confirmation
    Notiflix.Confirm.show(
      "Suppression rapport",
      `Voulez-vous vraiment supprimer le rapport ?`,
      "Supprimer",
      "Annuler",
      async function () {
        try {
          await stevedoringShipReports.delete(id);

          Notiflix.Notify.success("Le rapport a été supprimé");
          $goto("./");
        } catch (erreur) {
          Notiflix.Notify.failure(erreur.message);
        }

        deleteReportButton.$set({ block: false });
      },
      () => {
        deleteReportButton.$set({ block: false });
      },
      notiflixOptions.themes.red
    );
  }

  let cargoListFromEntries: { id: number | null; name: string }[] = [];

  $: cargoListFromEntries =
    report?.cargoEntries?.map(({ id, cargoName, customer }) => ({
      id,
      name: cargoName + " (" + customer + ")",
    })) || [];

  onMount(async () => {
    cargoList = await fetcher<string[]>("consignation/marchandises");
    listeClients = await fetcher<string[]>("consignation/clients");
  });
</script>

<!-- routify:options param-is-page -->
<!-- routify:options guard="manutention/edit" -->

<main class="w-18/24 md:w-17/24 lg:w-16/24 xl:w-15/24 mx-auto">
  <PageHeading>Rapport navire</PageHeading>

  {#if !report}
    <Chargement />
  {:else}
    <!-- Link call button -->
    <div class="text-center mb-4">
      {#if !report.linkedShippingCallId}
        {#if !report.id}
          <Button on:click={() => (callSelectionModalOpen = true)}
            ><WandSparklesIcon size={16} />
            <span class="ms-2">Créer à partir d'une escale consignation</span
            ></Button
          >
          <CallSelectionModal
            bind:open={callSelectionModalOpen}
            bind:report
            type="create"
            on:linked={() => {
              // Add a subreport by default
              if (report.subreports.length === 0) {
                addSubreport(true);
              }
            }}
          />
        {:else}
          <Button on:click={() => (callSelectionModalOpen = true)}
            ><LinkIcon size={16} />
            <span class="ms-2">Lier à une escale consignation</span></Button
          >
          <CallSelectionModal
            bind:open={callSelectionModalOpen}
            bind:report
            type="link"
          />
        {/if}
      {:else}
        <Button color="red" on:click={removeLinkWithCall}
          ><UnlinkIcon size={16} />
          <span class="ms-2"
            >Supprimer la liaison avec l'escale consignation</span
          ></Button
        >
      {/if}
    </div>

    <form
      class="flex flex-col gap-3 mb-4"
      bind:this={form}
      use:preventFormSubmitOnEnterKeydown
    >
      <div class="flex flex-col lg:flex-row gap-3 mb-4">
        <!-- Infos générales -->
        <div class="flex flex-col gap-3 mb-4 w-full lg:w-5/12">
          <!-- Navire -->
          <div>
            <Label for="ship">Navire</Label>
            <Input
              id="ship"
              bind:value={report.ship}
              placeholder="Nom du navire"
              maxlength={255}
              name="Navire"
              on:blur={() => (report.ship = report.ship.trim().toUpperCase())}
              required
            />
          </div>

          <!-- Port et quai -->
          <div class="flex flex-col lg:flex-row gap-2">
            <div>
              <Label for="port">Port</Label>
              <Input
                list="port-list"
                id="port"
                placeholder="Port"
                name="Port"
                bind:value={report.port}
              />
            </div>

            <div>
              <Label for="berth">Quai</Label>
              <Input
                list="berth-list"
                id="berth"
                placeholder="Quai"
                name="Quai"
                bind:value={report.berth}
              />
            </div>
          </div>

          <!-- Commentaire -->
          <div>
            <Label for="comments">Commentaire</Label>
            <Textarea
              id="comments"
              rows={3}
              cols={30}
              bind:value={report.comments}
            />
          </div>

          <!-- Instructions de facturation -->
          <div>
            <Label for="invoiceInstructions">Instructions de facturation</Label>
            <Textarea
              id="invoiceInstructions"
              rows={3}
              cols={30}
              bind:value={report.invoiceInstructions}
            />
          </div>

          <!-- Archive -->
          <div>
            <Toggle bind:checked={report.isArchive}>Archivé</Toggle>
          </div>
        </div>

        <!-- Marchandises -->
        <div class="flex flex-col gap-3 mb-4 w-full lg:w-7/12">
          <div>
            <span class="font-bold text-2xl me-2">Marchandises</span>
            <Button on:click={addCargo} color="light" class="mb-4" size="sm">
              <PlusCircleIcon size={16} />
              <span class="ms-2">Ajouter une marchandise</span>
            </Button>
          </div>
          <div>
            <ul>
              {#each report.cargoEntries as cargo, i ((cargo.id ||= Math.random()))}
                <li
                  class="my-1 flex flex-col items-end gap-2 rounded-lg border-[1px] border-gray-300 p-2 lg:flex-row"
                >
                  <div class="flex w-full flex-col gap-1 lg:w-1/2">
                    <div>
                      <Label for="marchandise_{i}">Marchandise*</Label>
                      <Svelecte
                        inputId="marchandise_{i}"
                        name="Marchandise {i + 1}"
                        options={cargoList}
                        virtualList
                        allowEditing
                        creatable
                        creatablePrefix=""
                        keepCreated
                        placeholder="Marchandise"
                        bind:value={cargo.cargoName}
                        required
                      />
                    </div>
                    <div>
                      <Label for="client_{i}">Client*</Label>
                      <Svelecte
                        inputId="client_{i}"
                        name="Client {i + 1}"
                        options={listeClients}
                        virtualList
                        allowEditing
                        creatable
                        creatablePrefix=""
                        keepCreated
                        placeholder="Client"
                        bind:value={cargo.customer}
                        required
                      />
                    </div>
                    <div>
                      <Label>
                        Opération*
                        <Select
                          class="operation"
                          bind:value={cargo.operation}
                          placeholder=""
                          required
                        >
                          <option value="import">Import</option>
                          <option value="export">Export</option>
                        </Select>
                      </Label>
                    </div>
                  </div>

                  <div class="flex w-full flex-col gap-1 lg:w-fit">
                    <div class="font-bold">BL</div>
                    <div>
                      <Label for="bl-tonnage-{i}">Tonnage</Label>
                      <NumericInput
                        id="bl-tonnage-{i}"
                        format="+3"
                        bind:value={cargo.blTonnage}
                      />
                    </div>
                    <div>
                      <Label for="bl-volume-{i}">Cubage</Label>
                      <NumericInput
                        id="bl-volume-{i}"
                        format="+3"
                        bind:value={cargo.blVolume}
                      />
                    </div>
                    <div>
                      <Label for="bl-units-{i}">Colis</Label>
                      <NumericInput
                        id="bl-units-{i}"
                        format="+0"
                        bind:value={cargo.blUnits}
                      />
                    </div>
                  </div>

                  <div class="flex w-full flex-col gap-1 lg:w-fit">
                    <div class="font-bold">
                      Outturn
                      <LucideButton
                        preset="copy"
                        title="Copier les données BL vers Outturn"
                        size="1em"
                        on:click={() => {
                          cargo.outturnTonnage = cargo.blTonnage;
                          cargo.outturnVolume = cargo.blVolume;
                          cargo.outturnUnits = cargo.blUnits;
                        }}
                      />
                    </div>
                    <div>
                      <Label for="outturn-tonnage-{i}">Tonnage</Label>
                      <NumericInput
                        id="outturn-tonnage-{i}"
                        format="+3"
                        bind:value={cargo.outturnTonnage}
                      />
                    </div>
                    <div>
                      <Label for="outturn-volume-{i}">Cubage</Label>
                      <NumericInput
                        id="outturn-volume-{i}"
                        format="+3"
                        bind:value={cargo.outturnVolume}
                      />
                    </div>
                    <div>
                      <Label for="outturn-units-{i}">Colis</Label>
                      <NumericInput
                        id="outturn-units-{i}"
                        format="+0"
                        bind:value={cargo.outturnUnits}
                      />
                    </div>
                  </div>

                  <div class="w-min self-center">
                    <LucideButton
                      preset="delete"
                      title="Supprimer la marchandise"
                      on:click={() => deleteCargo(cargo)}
                    />
                  </div>
                </li>
              {:else}
                <li class="ms-4 italic">Pas de marchandise</li>
              {/each}
            </ul>
          </div>
        </div>
      </div>

      <!-- Stockage -->
      <div>
        <div>
          <span class="font-bold text-2xl me-2">Stockage</span>
          <Button on:click={addStorage} color="light" class="mb-4" size="sm">
            <PlusCircleIcon size={16} />
            <span class="ms-2">Ajouter du stockage</span>
          </Button>
        </div>
        <div>
          <ul>
            {#each report.storageEntries as storage, i ((storage.id ||= Math.random()))}
              <li
                class="my-1 flex flex-col items-end gap-2 rounded-lg border-[1px] border-gray-300 p-2 lg:flex-row"
              >
                <div class="w-full lg:w-2/6">
                  <Label for="cargo-{i}">
                    Marchandise
                    {#if storage.cargoId}
                      <LucideButton
                        preset="copy"
                        title="Copier les données outturn vers stockage"
                        size="1em"
                        on:click={() => {
                          const cargo = report.cargoEntries.find(
                            (cargo) => cargo.id === storage.cargoId
                          );
                          storage.tonnage =
                            cargo.outturnTonnage -
                            report.storageEntries
                              .filter(
                                ({ id, cargoId }) =>
                                  cargoId === storage.cargoId &&
                                  id !== storage.id
                              )
                              .flatMap(({ tonnage }) => tonnage)
                              .reduce((a, b) => a + b, 0);
                          storage.volume =
                            cargo.outturnVolume -
                            report.storageEntries
                              .filter(
                                ({ id, cargoId }) =>
                                  cargoId === storage.cargoId &&
                                  id !== storage.id
                              )
                              .flatMap(({ volume }) => volume)
                              .reduce((a, b) => a + b, 0);
                          storage.units =
                            cargo.outturnUnits -
                            report.storageEntries
                              .filter(
                                ({ id, cargoId }) =>
                                  cargoId === storage.cargoId &&
                                  id !== storage.id
                              )
                              .flatMap(({ units }) => units)
                              .reduce((a, b) => a + b, 0);
                          checkStorageQuantities();
                        }}
                      />
                    {/if}
                  </Label>
                  <Svelecte
                    inputId="cargo-{i}"
                    name="Marchandise {i + 1}"
                    options={cargoListFromEntries}
                    placeholder="Marchandise"
                    bind:value={storage.cargoId}
                    required
                  />
                </div>

                <div class="w-full lg:w-1/6">
                  <Label for="storage-name-{i}">Magasin</Label>
                  <Input
                    type="text"
                    id="storage-name-{i}"
                    name="Magasin {i + 1}"
                    placeholder="Magasin"
                    bind:value={storage.storageName}
                    required
                    list="storage-names"
                  />
                  <datalist id="storage-names">
                    <option value="Agro 1"></option>
                    <option value="Agro 2"></option>
                    <option value="Agro 3"></option>
                    <option value="Agro 4"></option>
                    <option value="Cesson 1"></option>
                    <option value="Cesson 2"></option>
                    <option value="Cesson 3"></option>
                    <option value="Cesson 4"></option>
                    <option value="Quai de travers"></option>
                    <option value="SDV"></option>
                    <option value="Sifab"></option>
                    <option value="Presqu'île"></option>
                  </datalist>
                </div>

                <div class="w-full lg:w-1/6">
                  <Label for="stored-tonnage-{i}">Tonnage</Label>
                  <NumericInput
                    id="stored-tonnage-{i}"
                    format="+3"
                    bind:value={storage.tonnage}
                    on:blur={checkStorageQuantities}
                  />
                </div>

                <div class="w-full lg:w-1/6">
                  <Label for="stored-volume-{i}">Cubage</Label>
                  <NumericInput
                    id="stored-volume-{i}"
                    format="+3"
                    bind:value={storage.volume}
                    on:blur={checkStorageQuantities}
                  />
                </div>

                <div class="w-full lg:w-1/6">
                  <Label for="stored-units-{i}">Colis</Label>
                  <NumericInput
                    id="stored-units-{i}"
                    format="+0"
                    bind:value={storage.units}
                    on:blur={checkStorageQuantities}
                  />
                </div>

                <div class="w-min self-center">
                  <LucideButton
                    preset="delete"
                    title="Supprimer la marchandise"
                    on:click={() => deleteStorage(storage)}
                  />
                </div>
              </li>
            {:else}
              <p class="ms-4 italic">Pas de stockage</p>
            {/each}
          </ul>
        </div>
      </div>

      <!-- Détail journalier -->
      <DailyEntriesModal
        bind:open={dailyEntriesModalOpen}
        bind:subreport={dailyEntriesSubreport}
        bind:report
        bind:date={dailyEntriesDate}
      />
      <CargoSelectionModal
        bind:open={cargoSelectionModalOpen}
        bind:report
        bind:subreport={cargoSelectionSubreport}
        index={cargoSelectionSubreportIndex}
        {deleteSubreport}
        creationMode={cargoSelectionCreationMode}
      />
      <div>
        <Tabs contentClass="dark:bg-gray-800">
          {#each report.subreports as subreport, index}
            <SubreportTabItem
              bind:report
              {subreport}
              {index}
              open={index === displaySubreportIndex}
              bind:dailyEntriesModalOpen
              bind:dailyEntriesSubreport
              bind:dailyEntriesDate
              {openCargoSelectionModal}
              {deleteSubreport}
            />
          {/each}

          {#if report.cargoEntries.length > 0}
            <TabItem
              on:click={() => addSubreport()}
              open={noSubreportTabOpen}
              disabled={report.cargoEntries.length ===
                report.subreports
                  .map((subreport) => subreport.cargoIds.length)
                  .reduce((a, b) => a + b, 0)}
            >
              <span slot="title" id="add-subreport-title"
                >Ajouter un sous-rapport
                {#if report.cargoEntries.length > report.subreports
                    .map((subreport) => subreport.cargoIds.length)
                    .reduce((a, b) => a + b, 0)}
                  <span
                    title="Toutes les marchandises ne sont pas associées"
                    class="text-red-500 dark:text-red-300"
                  >
                    <TriangleAlertIcon size={16} />
                    <Tooltip type="auto" triggeredBy="#add-subreport-title">
                      <div class="text-red-500 dark:text-red-300">
                        Les marchandises suivantes ne sont pas associées à un
                        sous-rapport :
                        {#each report.cargoEntries as cargo}
                          {#if !report.subreports
                            .flatMap((subreport) => subreport.cargoIds)
                            .some((id) => id === cargo.id)}
                            <div>{cargo.cargoName} ({cargo.customer})</div>
                          {/if}
                        {/each}
                      </div>
                    </Tooltip>
                  </span>
                {/if}
              </span>
              <div class="ms-4 mt-2 italic">Aucun sous-rapport</div>
            </TabItem>
          {/if}
        </Tabs>
      </div>
    </form>

    <!-- Validation/Annulation/Suppression -->
    <div class="text-center">
      {#if isNew}
        <!-- Bouton "Ajouter" -->
        <BoutonAction
          preset="ajouter"
          on:click={createReport}
          bind:this={createReportButton}
        />
      {:else}
        <!-- Bouton "Modifier" -->
        <BoutonAction
          preset="modifier"
          on:click={updateReport}
          bind:this={updateReportButton}
        />
        <!-- Bouton "Supprimer" -->
        <BoutonAction
          preset="supprimer"
          on:click={deleteReport}
          bind:this={deleteReportButton}
        />
      {/if}

      <!-- Bouton "Annuler" -->
      <BoutonAction preset="annuler" on:click={() => $goto("./")} />
    </div>
  {/if}
</main>

<datalist id="port-list">
  <option value="Le Légué" />
  <option value="Tréguier" />
</datalist>

<datalist id="berth-list">
  <option value="Bassin" />
  <option value="Bassin 2" />
  <option value="Bassin 4/5" />
  <option value="Cesson" />
  <option value="Cesson 1" />
  <option value="Cesson 2" />
  <option value="Quai Garnier" />
  <option value="Quai Guindy" />
</datalist>
