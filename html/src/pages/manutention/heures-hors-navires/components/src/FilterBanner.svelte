<!-- 
  @component
  
  Bandeau filtre pour les heures hors navires.

  Usage :
  ```tsx
  <Filtre />
  ```
 -->
<script lang="ts">
  import { getContext } from "svelte";
  import type { Writable } from "svelte/store";

  import { Label, Input, Button } from "flowbite-svelte";

  import { Svelecte } from "@app/components";
  import { Filtre } from "@app/utils";

  import type { StevedoringDispatchFilter } from "@app/types";

  type FilterContext = {
    emptyFilter: StevedoringDispatchFilter;
    filterStore: Writable<Filtre<StevedoringDispatchFilter>>;
    filterName: string;
  };

  const { emptyFilter, filterStore, filterName } =
    getContext<FilterContext>("filter");

  let filterData = { ...$filterStore.data };

  /**
   * Enregistrer le filtre.
   */
  async function applyFilter() {
    sessionStorage.setItem(filterName, JSON.stringify(filterData));

    filterStore.set(new Filtre(filterData));
  }

  /**
   * Supprimer le filtre.
   */
  function removeFilter() {
    sessionStorage.removeItem(filterName);

    filterData = structuredClone(emptyFilter);
    filterStore.set(new Filtre(filterData));
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
