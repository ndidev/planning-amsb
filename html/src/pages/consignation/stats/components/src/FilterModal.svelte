<!-- 
  @component
  
  Bandeau filtre pour le planning vrac.

  Usage :
  ```tsx
  <FilterBanner />
  ```
 -->
<script lang="ts" context="module">
  import { writable } from "svelte/store";

  import { DateUtils, Filter } from "@app/utils";

  import type { ShippingFilter } from "@app/types";

  const emptyFilter: ShippingFilter = {
    startDate: "2018-01-01",
    endDate: "",
    ships: [],
    shipOwners: [],
    cargoes: [],
    strictCargoes: true,
    customers: [],
    lastPorts: [],
    nextPorts: [],
  };

  const filterName = "shipping-stats-filter";

  export const filter = writable(
    new Filter<ShippingFilter>(
      JSON.parse(sessionStorage.getItem(filterName)) ||
        structuredClone(emptyFilter)
    )
  );
</script>

<script lang="ts">
  import { onMount } from "svelte";

  import {
    Modal,
    Label,
    Input,
    Button,
    Tooltip,
    Checkbox,
  } from "flowbite-svelte";
  import { FilterIcon, FilterXIcon } from "lucide-svelte";

  import { Svelecte, LucideButton } from "@app/components";

  import { fetcher } from "@app/utils";

  import { ports, tiers } from "@app/stores";

  let filterData = { ...$filter.data };

  let open = false;

  let shipsList: string[];
  let cargoesList: string[];
  let customersList: string[];

  $: filterIsActive = Object.entries({ ...$filter.data }).reduce(
    (acc, [key, value]) =>
      (Array.isArray(value) ? value.length > 0 : value !== emptyFilter[key]) ||
      acc,
    false
  );

  let filterTooltip = "Chargement...";
  $: if (filterData && $tiers) {
    filterTooltip = makeFilterTooltip();
  }

  function makeFilterTooltip() {
    if (!filterIsActive) {
      return "Aucun filtre activé";
    }

    const filterTooltip = [];

    if (filterData.startDate !== emptyFilter.startDate) {
      const formattedDate = new DateUtils(filterData.startDate).format().short;
      filterTooltip.push(`Du ${formattedDate}`);
    }

    if (filterData.endDate !== emptyFilter.endDate) {
      const formattedDate = new DateUtils(filterData.endDate).format().short;
      filterTooltip.push(`Au ${formattedDate}`);
    }

    if (filterData.ships?.length) {
      filterTooltip.push(`Navires : ${filterData.ships.join(", ")}`);
    }

    if (filterData.shipOwners?.length) {
      filterTooltip.push(
        `Armateurs : ${filterData.shipOwners
          .map((armateurId) => $tiers.get(armateurId)?.nom_court)
          .join(", ")}`
      );
    }

    if (filterData.cargoes?.length) {
      filterTooltip.push(
        `Marchandises ${filterData.strictCargoes ? "(strict)" : ""}: ${filterData.cargoes.join(", ")}`
      );
    }

    if (filterData.customers?.length) {
      filterTooltip.push(`Clients : ${filterData.customers.join(", ")}`);
    }

    if (filterData.lastPorts?.length) {
      filterTooltip.push(
        `Provenances : ${filterData.lastPorts
          .map(
            (locode) =>
              $ports.find((port) => locode === port.locode)?.nom_affichage
          )
          .join(", ")}`
      );
    }

    if (filterData.nextPorts?.length) {
      filterTooltip.push(
        `Destinations : ${filterData.nextPorts
          .map(
            (locode) =>
              $ports.find((port) => locode === port.locode)?.nom_affichage
          )
          .join(", ")}`
      );
    }

    if (filterTooltip.length === 0) {
      filterIsActive = false;
      return "Aucun filtre activé";
    }

    return filterTooltip.join("<br/>");
  }

  function addCreatedCargoOption(
    event: CustomEvent<{ text: string; value: string }>
  ) {
    const newOption = event.detail.value;

    if (!cargoesList.includes(newOption)) {
      cargoesList.push(newOption);
      filterData.strictCargoes = false;
      cargoesList.sort((a, b) => a.localeCompare(b));
      cargoesList = cargoesList;
    }
  }

  async function applyFilter() {
    sessionStorage.setItem(filterName, JSON.stringify(filterData));

    filterData = filterData; // Enable reactive tooltip
    filter.set(new Filter(filterData));
  }

  function removeFilter() {
    sessionStorage.removeItem(filterName);

    filterData = structuredClone(emptyFilter);
    filter.set(new Filter(filterData));
  }

  onMount(async () => {
    shipsList = await fetcher<string[]>("consignation/navires");

    cargoesList = await fetcher<string[]>("consignation/marchandises");
    filterData.cargoes.forEach((cargo) => {
      if (!cargoesList.includes(cargo)) {
        cargoesList.push(cargo);
      }
    });
    cargoesList.sort((a, b) => a.localeCompare(b));

    customersList = await fetcher<string[]>("consignation/clients");
  });
</script>

<div class="text-center">
  <button on:click={() => (open = !open)}>Afficher le filtre</button>
</div>

<div class="filter-button">
  <LucideButton
    icon={filterIsActive ? FilterXIcon : FilterIcon}
    color={filterIsActive ? "yellow" : "default"}
    staticallyColored
    title="Filtre"
    on:click={() => (open = !open)}
  />
  <Tooltip type="auto" placement="left-end" class="w-max whitespace-pre-line"
    >{@html filterTooltip}</Tooltip
  >
</div>

<Modal
  title="Filtre"
  bind:open
  autoclose
  outsideclose
  on:open={() => (filterData = { ...$filter.data })}
>
  <div class="flex flex-col gap-2">
    <div class="flex flex-col lg:flex-row gap-2">
      <!-- Date début -->
      <div class="w-full">
        <Label for="startDate">Du</Label>
        <Input
          type="date"
          id="startDate"
          bind:value={filterData.startDate}
          max={filterData.endDate}
        />
      </div>

      <!-- Date fin -->
      <div class="w-full">
        <Label for="endDate">Au</Label>
        <Input
          type="date"
          id="endDate"
          bind:value={filterData.endDate}
          min={filterData.startDate}
        />
      </div>
    </div>

    <!-- Filtre navires -->
    <div>
      <Label for="ships">Navires</Label>
      <Svelecte
        inputId="ships"
        options={shipsList}
        bind:value={filterData.ships}
        placeholder="Navires"
        multiple
      />
    </div>

    <!-- Filtre marchandises -->
    <div class="flex flex-row gap-2">
      <div class="flex-grow">
        <Label for="cargoes">Marchandises</Label>
        <Svelecte
          inputId="cargoes"
          bind:options={cargoesList}
          includeInactive
          bind:value={filterData.cargoes}
          placeholder="Marchandises"
          creatable
          creatablePrefix=""
          keepCreated
          allowEditing
          on:createoption={addCreatedCargoOption}
          multiple
        />
      </div>
      <div class="flex-shrink self-center">
        <Checkbox bind:checked={filterData.strictCargoes}>Strict</Checkbox>
      </div>
    </div>

    <!-- Filtre clients -->
    <div>
      <Label for="customers">Clients</Label>
      <Svelecte
        inputId="customers"
        options={customersList}
        includeInactive
        bind:value={filterData.customers}
        placeholder="Clients"
        multiple
      />
    </div>

    <!-- Filtre armateurs -->
    <div>
      <Label for="shipOwners">Armateurs</Label>
      <Svelecte
        inputId="shipOwners"
        type="tiers"
        role="maritime_armateur"
        includeInactive
        bind:value={filterData.shipOwners}
        placeholder="Armateurs"
        multiple
      />
    </div>

    <!-- Filtre provenances -->
    <div>
      <Label for="lastPort">Provenances</Label>
      <Svelecte
        inputId="lastPort"
        type="port"
        bind:value={filterData.lastPorts}
        placeholder="Provenances"
        multiple
      />
    </div>

    <!-- Filtre destinations -->
    <div>
      <Label for="nextPort">Destinations</Label>
      <Svelecte
        inputId="nextPort"
        type="port"
        bind:value={filterData.nextPorts}
        placeholder="Destinations"
        multiple
      />
    </div>

    <!-- Boutons filtre -->
    <div class="flex flex-col lg:flex-row gap-2">
      <div class="w-full">
        <Button type="submit" class="w-full" on:click={applyFilter}>
          Filtrer
        </Button>
      </div>

      <div class="w-full">
        <Button
          type="reset"
          class="w-full"
          color="dark"
          on:click={removeFilter}
        >
          Supprimer le filtre
        </Button>
      </div>
    </div>
  </div>
</Modal>

<style>
  .filter-button {
    --size: 50px;

    display: grid;
    place-items: center;
    position: fixed;
    right: 20px;
    bottom: calc(var(--footer-height) + 20px);
    width: var(--size);
    height: var(--size);
    z-index: 3;
    border-radius: 50%;
    background: radial-gradient(
      circle at center,
      white 0,
      white 50%,
      transparent 100%
    );
  }
</style>
