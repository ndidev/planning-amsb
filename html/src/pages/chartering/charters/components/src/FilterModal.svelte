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

  import { DateUtils, Filter, parseJSON } from "@app/utils";

  import type { CharteringFilter } from "@app/types";

  const emptyFilter: CharteringFilter = {
    startDate: "",
    endDate: "",
    charterers: [],
    shipOwners: [],
    brokers: [],
    status: [],
    archives: false,
  };

  const filterName = "chartering-planning-filter";

  export const filter = writable(
    new Filter<CharteringFilter>(
      parseJSON(sessionStorage.getItem(filterName)) ||
        structuredClone(emptyFilter),
    ),
  );
</script>

<script lang="ts">
  import {
    Modal,
    Label,
    Input,
    Button,
    Tooltip,
    Select,
    MultiSelect,
    Toggle,
  } from "flowbite-svelte";
  // import { FilterIcon, FilterXIcon } from "lucide-svelte";
  import FilterIcon from "lucide-svelte/icons/filter";
  import FilterXIcon from "lucide-svelte/icons/filter-x";

  import { Svelecte, LucideButton } from "@app/components";

  import { tiers } from "@app/stores";

  let filterData = { ...$filter.data };

  let open = false;

  $: filterIsActive = Object.entries({ ...$filter.data }).reduce(
    (acc, [key, value]) =>
      (Array.isArray(value) ? value.length > 0 : value !== emptyFilter[key]) ||
      acc,
    false,
  );

  let filterTooltip = "Chargement...";
  $: if (filterData && $tiers) {
    filterTooltip = makeFilterTooltip();
  }

  const statusList = [
    { value: "0", name: "Plannifié (pas confirmé)" },
    { value: "1", name: "Confirmé par l'affréteur" },
    { value: "2", name: "Affrété" },
    { value: "3", name: "Chargement effectué" },
    { value: "4", name: "Voyage terminé" },
  ];

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

    if (filterData.charterers?.length) {
      filterTooltip.push(
        `Affréteurs : ${filterData.charterers
          .map((chartererId) => $tiers.get(chartererId)?.nom_court)
          .join(", ")}`,
      );
    }

    if (filterData.shipOwners?.length) {
      filterTooltip.push(
        `Armateurs : ${filterData.shipOwners
          .map((shipOwnerId) => $tiers.get(shipOwnerId)?.nom_court)
          .join(", ")}`,
      );
    }

    if (filterData.brokers?.length) {
      filterTooltip.push(
        `Courtiers : ${filterData.brokers
          .map((brokerId) => $tiers.get(brokerId)?.nom_court)
          .join(", ")}`,
      );
    }

    if (filterData.status?.length) {
      filterTooltip.push(
        `Statuts : ${filterData.status
          .map(
            (status) =>
              statusList.find((s) => s.value === status.toString())?.name,
          )
          .join(", ")}`,
      );
    }

    if (filterData.archives) {
      filterTooltip.push("Archives");
    }

    if (filterTooltip.length === 0) {
      filterIsActive = false;
      return "Aucun filtre activé";
    }

    return filterTooltip.join("<br/>");
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

    <!-- Filtre affréteur -->
    <div>
      <Label for="filtre_affreteur">Affréteur</Label>
      <Svelecte
        inputId="filtre_affreteur"
        type="tiers"
        role="maritime_affreteur"
        includeInactive
        bind:value={filterData.charterers}
        placeholder="Affréteur"
        multiple
      />
    </div>

    <!-- Filtre armateur -->
    <div>
      <Label for="filtre_armateur">Armateur</Label>
      <Svelecte
        inputId="filtre_armateur"
        type="tiers"
        role="maritime_armateur"
        includeInactive
        bind:value={filterData.shipOwners}
        placeholder="Armateur"
        multiple
      />
    </div>

    <!-- Filtre courtier -->
    <div>
      <Label for="filtre_courtier">Courtier</Label>
      <Svelecte
        inputId="filtre_courtier"
        type="tiers"
        role="maritime_courtier"
        includeInactive
        bind:value={filterData.brokers}
        placeholder="Courtier"
        multiple
      />
    </div>

    <!-- Filtre statut -->
    <div>
      <Label for="filtre_statut">Statut</Label>
      <MultiSelect
        id="filtre_statut"
        items={statusList}
        bind:value={filterData.status}
        placeholder="Statut"
      />
    </div>

    <!-- Filtre archives -->
    <div class="my-4">
      <Toggle bind:checked={filterData.archives}>Archives</Toggle>
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
