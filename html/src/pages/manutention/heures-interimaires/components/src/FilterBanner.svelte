<!-- 
  @component
  
  Bandeau filtre pour les heures hors navires.

  Usage :
  ```tsx
  <FilterBanner />
  ```
 -->
<script lang="ts" context="module">
  import { writable } from "svelte/store";

  import { Filter, parseJSON } from "@app/utils";

  import type { StevedoringStaff } from "@app/types";

  type TempWorkHoursFilter = {
    startDate?: string;
    endDate?: string;
    staff?: StevedoringStaff["id"][];
    agency?: string[];
  };

  const emptyFilter: TempWorkHoursFilter = {
    startDate: new Date().toISOString().split("T")[0],
    endDate: "",
    staff: [],
    agency: [],
  };

  const filterName = "stevedoring-temp-work-hours-filter";

  export const filter = writable(
    new Filter<TempWorkHoursFilter>(
      parseJSON(sessionStorage.getItem(filterName)) ||
        structuredClone(emptyFilter)
    )
  );
</script>

<script lang="ts">
  import { Label, Input, Button } from "flowbite-svelte";

  import { Svelecte } from "@app/components";

  import { stevedoringStaff } from "@app/stores";

  let filterData = { ...$filter.data };

  let agenciesList: string[] = [];

  $: if ($stevedoringStaff) {
    agenciesList = [
      ...new Set(
        [...$stevedoringStaff.values()]
          .filter((staff) => staff.type === "interim")
          .map((staff) => staff.tempWorkAgency)
          .sort()
      ).values(),
    ];
  }

  /**
   * Enregistrer le filtre.
   */
  async function applyFilter() {
    sessionStorage.setItem(filterName, JSON.stringify(filterData));

    filter.set(new Filter(filterData));
  }

  /**
   * Supprimer le filtre.
   */
  function removeFilter() {
    sessionStorage.removeItem(filterName);

    filterData = structuredClone(emptyFilter);
    filter.set(new Filter(filterData));
  }
</script>

<div class="flex flex-col lg:flex-row lg:items-end gap-2 lg:gap-4">
  <!-- Dates -->
  <div>
    <Label for="startDate">Du</Label>
    <Input
      type="date"
      id="startDate"
      bind:value={filterData.startDate}
      max={filterData.endDate}
    />
  </div>

  <div>
    <Label for="endDate">Au</Label>
    <Input
      type="date"
      id="endDate"
      bind:value={filterData.endDate}
      min={filterData.startDate}
    />
  </div>

  <!-- Filtre personnel -->
  <div class="flex-auto">
    <Label for="staff-filter">Personnel</Label>
    <Svelecte
      type="interimaires"
      inputId="staff-filter"
      name="staff-filter"
      bind:value={filterData.staff}
      placeholder="Personnel"
      multiple
    />
  </div>

  <!-- Filtre agence -->
  <div class="flex-auto">
    <Label for="agency-filter">Agence</Label>
    <Svelecte
      inputId="agency-filter"
      name="agency-filter"
      options={agenciesList}
      bind:value={filterData.agency}
      placeholder="Agence"
      multiple
    />
  </div>

  <!-- Boutons filtre -->
  <div class="w-full lg:w-max">
    <Button type="submit" class="w-full" on:click={applyFilter}>Filtrer</Button>
  </div>

  <div class="w-full lg:w-max">
    <Button type="reset" class="w-full" on:click={removeFilter}>
      Supprimer le filtre
    </Button>
  </div>
</div>
