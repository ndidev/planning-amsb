<!-- routify:options title="Planning AMSB - Escale consignation" -->
<script lang="ts">
  import { onMount } from "svelte";
  import { params, goto, redirect } from "@roxi/routify";

  import {
    Label,
    Input,
    Textarea,
    Checkbox,
    Select,
    Button,
  } from "flowbite-svelte";
  // import { PlusCircleIcon } from "lucide-svelte";
  import PlusCircleIcon from "lucide-svelte/icons/plus-circle";
  import Notiflix from "notiflix";

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
    validerFormulaire,
    notiflixOptions,
    preventFormSubmitOnEnterKeydown,
  } from "@app/utils";

  import { consignationEscales } from "@app/stores";

  import type { EscaleConsignation } from "@app/types";

  let form: HTMLFormElement;
  let createCallButton: BoutonAction;
  let updateCallButton: BoutonAction;
  let deleteCallButton: BoutonAction;

  /**
   * Identifiant du RDV.
   */
  let id: EscaleConsignation["id"] = parseInt($params.id);

  let listeMarchandises: string[];
  let listeClients: string[];

  const isNew = $params.id === "new";

  let escale: EscaleConsignation = isNew
    ? consignationEscales.getTemplate()
    : null;

  // Récupérer les données de l'escale
  (async () => {
    try {
      if (id) {
        escale = structuredClone(await consignationEscales.get(id));
        if (!escale) throw new Error();
      }
    } catch (error) {
      $redirect("./new");
    }
  })();

  const ETX = [
    {
      etx: "eta",
      acronym: "ETA",
      abbr: "Estimated Time of Arrival",
    },
    {
      etx: "nor",
      acronym: "NOR",
      abbr: "Notice Of Readiness",
    },
    {
      etx: "pob",
      acronym: "POB",
      abbr: "Pilot On Board",
    },
    {
      etx: "etb",
      acronym: "ETB",
      abbr: "Estimated Time of Berthing",
    },
    {
      etx: "ops",
      acronym: "Ops",
      abbr: "Début ops",
    },
    {
      etx: "etc",
      acronym: "ETC",
      abbr: "Estimated Time of Completion",
    },
    {
      etx: "etd",
      acronym: "ETD",
      abbr: "Estimated Time of Departure",
    },
  ] as const;

  /**
   * Ajouter une ligne marchandise.
   */
  function addCargo() {
    escale.marchandises = [
      ...escale.marchandises,
      {
        id: null,
        escale_id: null,
        shipReportId: null,
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

  /**
   * Supprimer une marchandise.
   */
  function deleteCargo(
    marchandiseASupprimer: EscaleConsignation["marchandises"][0],
  ) {
    escale.marchandises = escale.marchandises.filter(
      (marchandise) => marchandise !== marchandiseASupprimer,
    );
  }

  /**
   * Formattage heure ETX (majuscules + rajout auto ':' heure)
   */
  function formatTime(etx: (typeof ETX)[number]["etx"]) {
    let originalTime = escale[`${etx}_heure`].trim().toUpperCase();
    let formattedTime = originalTime;
    let regexp_HHMM = /^((([01][0-9]|2[0-3])([:H]?)[0-5][0-9])|24([:H]?)00)\b/;

    if (regexp_HHMM.test(originalTime)) {
      let separateur =
        originalTime.charAt(2) === ":" || originalTime.charAt(2) === "H"
          ? 3
          : 2;
      formattedTime =
        originalTime.substring(0, 2) +
        ":" +
        originalTime.substring(separateur, originalTime.length);
    }
    escale[`${etx}_heure`] = formattedTime;
  }

  /**
   * N° voyage automatique
   * Laisser APRÈS le formattage de la date
   */
  async function calculerNumeroVoyage() {
    const navire = escale.navire;
    const eta_annee = escale.eta_date.substring(0, 4);

    if (navire === "" || navire === "TBN") {
      // Si navire non nommé, pas de numéro de voyage
      escale.voyage = "";
    } else {
      // Récupération du dernier numéro de voyage pour ce navire
      const { voyage } = await fetcher<{ voyage: string }>(
        "consignation/voyage",
        {
          searchParams: {
            navire: escale.navire,
            id: id ? id.toString() : "",
          },
        },
      );

      // Nouveau voyage par défaut (navire jamais venu)
      const nouveau_voyage = {
        annee: new Date().getFullYear(),
        numero: 1,
      };

      if (voyage) {
        // Le navire est déjà venu
        const dernier_voyage = {
          annee: parseInt(voyage.substring(0, 4)),
          numero: parseInt(voyage.substring(5)) || 0,
        };

        nouveau_voyage.annee = parseInt(eta_annee) || new Date().getFullYear();

        nouveau_voyage.numero =
          nouveau_voyage.annee === dernier_voyage.annee
            ? dernier_voyage.numero + 1 // Le navire est déjà venu cette année
            : 1; // Le navire n'est pas venu cette année
      }

      escale.voyage = nouveau_voyage.annee + "/" + nouveau_voyage.numero;
    }
  }

  /**
   * Ajouter l'escale.
   */
  async function createCall() {
    if (!validerFormulaire(form)) return;

    createCallButton.$set({ block: true });

    try {
      await consignationEscales.create(escale);

      Notiflix.Notify.success("L'escale a été créée");
      $goto("./");
    } catch (erreur) {
      Notiflix.Notify.failure(erreur.message);
      createCallButton.$set({ block: false });
    }
  }

  /**
   * Modifier l'escale.
   */
  async function updateCall() {
    if (!validerFormulaire(form)) return;

    updateCallButton.$set({ block: true });

    try {
      await consignationEscales.update(escale);

      Notiflix.Notify.success("L'escale a été modifiée");
      $goto("./");
    } catch (erreur) {
      Notiflix.Notify.failure(erreur.message);
      console.error(erreur);
      updateCallButton.$set({ block: false });
    }
  }

  /**
   * Supprimer l'escale.
   */
  function deleteCall() {
    if (!id) return;

    deleteCallButton.$set({ block: true });

    // Demande de confirmation
    Notiflix.Confirm.show(
      "Suppression escale",
      `Voulez-vous vraiment supprimer l'escale ?`,
      "Supprimer",
      "Annuler",
      async function () {
        try {
          await consignationEscales.delete(id);

          Notiflix.Notify.success("L'escale a été supprimée");
          $goto("./");
        } catch (erreur) {
          Notiflix.Notify.failure(erreur.message);
        }

        deleteCallButton.$set({ block: false });
      },
      () => {
        deleteCallButton.$set({ block: false });
      },
      notiflixOptions.themes.red,
    );
  }

  onMount(async () => {
    listeMarchandises = await fetcher<string[]>("consignation/marchandises");
    listeClients = await fetcher<string[]>("consignation/clients");
  });
</script>

<!-- routify:options param-is-page -->
<!-- routify:options guard="consignation/edit" -->

<main class="w-18/24 md:w-17/24 lg:w-16/24 xl:w-15/24 mx-auto">
  <PageHeading>Escale</PageHeading>

  {#if !escale}
    <Chargement />
  {:else}
    <form
      class="flex flex-col lg:flex-row gap-3 mb-4"
      bind:this={form}
      use:preventFormSubmitOnEnterKeydown
    >
      <div class="flex flex-col gap-3 mb-4 w-full lg:w-5/12">
        <!-- Navire -->
        <div>
          <Label for="navire">Navire</Label>
          <Input
            id="navire"
            bind:value={escale.navire}
            placeholder="Nom du navire"
            maxlength={255}
            data-nom="Navire"
            on:blur={() => (escale.navire = escale.navire.trim().toUpperCase())}
            on:change={calculerNumeroVoyage}
          />
        </div>

        <!-- N° voyage -->
        <div>
          <Label for="voyage">N° voyage</Label>
          <Input
            id="voyage"
            placeholder="N° voyage"
            maxlength={20}
            data-nom="Voyage"
            pattern={`20\\d{2}\/\\d{1,}`}
            bind:value={escale.voyage}
            class="w-full lg:w-max"
          />
        </div>

        <!-- Armateur -->
        <div>
          <Label for="armateur">Armateur</Label>
          <Svelecte
            type="tiers"
            role="maritime_armateur"
            inputId="armateur"
            placeholder="Armateur"
            bind:value={escale.armateur}
          />
        </div>

        <!-- ETX -->
        {#each ETX as { etx, acronym, abbr }}
          <div>
            <Label for="{etx}_date"><abbr title={abbr}>{acronym}</abbr></Label>
            <Input
              type="date"
              class="inline-block w-full md:w-max mb-1 md:mb-0"
              id="{etx}_date"
              placeholder="Date {acronym}"
              data-nom="Date {acronym}"
              bind:value={escale[`${etx}_date`]}
            />
            <Input
              class="inline-block w-full md:w-32"
              id="{etx}_heure"
              maxlength={10}
              placeholder="Heure"
              data-nom="Heure {acronym}"
              bind:value={escale[`${etx}_heure`]}
              on:change={() => formatTime(etx)}
            />
          </div>
        {/each}

        <div class="flex flex-col gap-1 md:flex-row">
          <!-- TE arrivée -->
          <div>
            <Label for="te_arrivee">TE arrivée</Label>
            <NumericInput
              id="te_arrivee"
              format="+2"
              bind:value={escale.te_arrivee}
              placeholder="TE arrivée"
            />
          </div>

          <!-- TE départ -->
          <div>
            <Label for="te_depart">TE départ</Label>
            <NumericInput
              id="te_depart"
              format="+2"
              bind:value={escale.te_depart}
              placeholder="TE départ"
            />
          </div>
        </div>

        <!-- Port et quai d'escale -->
        <div>
          <Label for="call_port">Port d'escale</Label>
          <Input
            list="call_port_list"
            id="call_port"
            placeholder="Port d'escale"
            data-nom="Port d'escale"
            bind:value={escale.call_port}
            class="inline-block w-full md:w-min mb-1 md:mb-0 lg:w-11/24"
          />
          <Input
            list="quai_list"
            placeholder="Quai d'escale"
            data-nom="Quai d'escale"
            bind:value={escale.quai}
            class="inline-block w-full md:w-min lg:w-11/24"
          />
        </div>

        <!-- Port de provenance -->
        <div>
          <Label for="last_port">Port de provenance</Label>
          <Svelecte
            inputId="last_port"
            type="port"
            bind:value={escale.last_port}
            placeholder="Port de provenance"
          />
        </div>

        <!-- Port de destination -->
        <div>
          <Label for="next_port">Port de destination</Label>
          <Svelecte
            inputId="next_port"
            type="port"
            bind:value={escale.next_port}
            placeholder="Port de destination"
          />
        </div>

        <!-- Commentaire -->
        <div>
          <Label for="commentaire">Commentaire</Label>
          <Textarea
            class="escale_commentaire"
            id="commentaire"
            rows={3}
            cols={30}
            data-nom="Commentaire"
            bind:value={escale.commentaire}
          />
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
            {#each escale.marchandises as cargo, i ((cargo.id ||= Math.random()))}
              <li
                class="my-1 flex flex-col items-end gap-2 rounded-lg border-[1px] border-gray-300 p-2 lg:flex-row"
              >
                <div class="flex w-full flex-col gap-1 lg:w-1/2">
                  <div>
                    <Label for="marchandise_{i}">Marchandise*</Label>
                    <Svelecte
                      inputId="marchandise_{i}"
                      name="Marchandise {i + 1}"
                      options={listeMarchandises}
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
                      <Select class="operation" bind:value={cargo.operation}>
                        <option value="import">Import</option>
                        <option value="export">Export</option>
                      </Select>
                    </Label>
                  </div>
                </div>

                <div class="flex w-full flex-col gap-1 lg:w-fit">
                  <div class="font-bold">
                    BL (<Checkbox
                      class="environ checkbox-environ inline ml-1"
                      bind:checked={cargo.isApproximate}
                      >environ)
                    </Checkbox>
                  </div>
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
                    <!-- <Input bind:value={cargo.outturnTonnage} /> -->
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
            {/each}
          </ul>
        </div>
      </div>
    </form>

    <!-- Validation/Annulation/Suppression -->
    <div class="text-center">
      {#if isNew}
        <!-- Bouton "Ajouter" -->
        <BoutonAction
          preset="ajouter"
          on:click={createCall}
          bind:this={createCallButton}
        />
      {:else}
        <!-- Bouton "Modifier" -->
        <BoutonAction
          preset="modifier"
          on:click={updateCall}
          bind:this={updateCallButton}
        />
        <!-- Bouton "Supprimer" -->
        <BoutonAction
          preset="supprimer"
          on:click={deleteCall}
          bind:this={deleteCallButton}
        />
      {/if}

      <!-- Bouton "Annuler" -->
      <BoutonAction preset="annuler" on:click={() => $goto("./")} />
    </div>
  {/if}
</main>

<datalist id="call_port_list">
  <option value="Le Légué" />
  <option value="Tréguier" />
</datalist>

<datalist id="quai_list">
  <option value="Bassin" />
  <option value="Bassin 2" />
  <option value="Bassin 4/5" />
  <option value="Cesson" />
  <option value="Cesson 1" />
  <option value="Cesson 2" />
  <option value="Quai Garnier" />
  <option value="Quai Guindy" />
</datalist>
