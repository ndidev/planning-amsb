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

  type StevedoringDispatchFilter = {
    startDate?: string;
    endDate?: string;
    staff?: StevedoringStaff["id"][];
  };

  const emptyFilter: StevedoringDispatchFilter = {
    startDate: new Date().toISOString().split("T")[0],
    endDate: "",
    staff: [],
  };

  const filterName = "stevedoring-dispatch-filter";

  export const filter = writable(
    new Filter<StevedoringDispatchFilter>(
      parseJSON(sessionStorage.getItem(filterName)) ||
        structuredClone(emptyFilter)
    )
  );
</script>

<script lang="ts">
  import { Label, Input, Button } from "flowbite-svelte";

  import { Svelecte } from "@app/components";

  let filterData = { ...$filter.data };

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
    <Input type="date" id="startDate" bind:value={filterData.startDate} />
  </div>

  <div>
    <Label for="endDate">Au</Label>
    <Input type="date" id="endDate" bind:value={filterData.endDate} />
  </div>

  <!-- Filtre personnel -->
  <div class="flex-auto">
    <Label for="staff-filter">Personnel</Label>
    <Svelecte
      inputId="staff-filter"
      type="staff"
      includeInactive
      bind:value={filterData.staff}
      placeholder="Personnel"
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
