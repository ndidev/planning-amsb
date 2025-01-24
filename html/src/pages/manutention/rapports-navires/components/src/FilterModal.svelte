<!-- 
  @component
  
  Modal filtre pour les rapports navires.

  Usage :
  ```tsx
  <FilterModal />
  ```
 -->
<script lang="ts" context="module">
  import { writable } from "svelte/store";

  import { DateUtils, Filter } from "@app/utils";

  import type {
    StevedoringShipReportFilter,
    StevedoringShipReportFilterData,
  } from "@app/types";

  const emptyFilter: StevedoringShipReportFilter = {
    startDate: "",
    endDate: "",
    isArchive: false,
    ships: [],
    ports: [],
    berths: [],
    cargoes: [],
    strictCargoes: true,
    customers: [],
    storageNames: [],
  };

  const filterName = "ship-reports-filter";

  export const filter = writable(
    new Filter<StevedoringShipReportFilter>(
      JSON.parse(sessionStorage.getItem(filterName)) ||
        structuredClone(emptyFilter)
    )
  );
</script>

<script lang="ts">
  import {
    Modal,
    Label,
    Input,
    Button,
    Checkbox,
    Tooltip,
    Toggle,
  } from "flowbite-svelte";
  import { FilterIcon, FilterXIcon } from "lucide-svelte";

  import { Svelecte, LucideButton } from "@app/components";

  import { fetcher } from "@app/utils";

  import { tiers } from "@app/stores";
  import { onMount } from "svelte";

  let filterData = { ...$filter.data };

  let open = false;

  let shipsList: string[];
  let portsList: string[];
  let berthsList: string[];
  let cargoesList: string[];
  let customersList: string[];
  let storageNamesList: string[];

  $: filterIsActive = Object.entries({ ...$filter.data }).reduce(
    (acc, [key, value]) =>
      (Array.isArray(value) ? value.length > 0 : value !== emptyFilter[key]) ||
      acc,
    false
  );

  let filterTooltip = "";
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

    if (filterData.isArchive) {
      filterTooltip.push("Archives");
    }

    if (filterData.ships?.length) {
      filterTooltip.push(`Navires : ${filterData.ships.join(", ")}`);
    }

    if (filterData.ports?.length) {
      filterTooltip.push(`Ports : ${filterData.ports.join(", ")}`);
    }

    if (filterData.berths?.length) {
      filterTooltip.push(`Quais : ${filterData.berths.join(", ")}`);
    }

    if (filterData.cargoes?.length) {
      filterTooltip.push(
        `Marchandises ${filterData.strictCargoes ? "(strict)" : ""}: ${filterData.cargoes.join(", ")}`
      );
    }

    if (filterData.customers?.length) {
      filterTooltip.push(`Clients : ${filterData.customers.join(", ")}`);
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
    ({
      ships: shipsList,
      ports: portsList,
      berths: berthsList,
      cargoes: cargoesList,
      customers: customersList,
      storageNames: storageNamesList,
    } = await fetcher<StevedoringShipReportFilterData>(
      "manutention/rapports-navires/filter-data"
    ));
  });
</script>

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

    <!-- Filtre archives -->
    <div class="my-4">
      <Toggle bind:checked={filterData.isArchive}>Archives</Toggle>
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

    <!-- Filtre ports -->
    <div>
      <Label for="ports">Ports</Label>
      <Svelecte
        inputId="ports"
        options={portsList}
        bind:value={filterData.ports}
        placeholder="Ports"
        multiple
      />
    </div>

    <!-- Filtre quais -->
    <div>
      <Label for="berths">Quais</Label>
      <Svelecte
        inputId="berths"
        options={berthsList}
        bind:value={filterData.berths}
        placeholder="Quais"
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
