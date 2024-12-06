<!-- 
  @component
  
  Bandeau filtre pour le planning consignation.

  Usage :
  ```tsx
  <Filtre />
  ```
 -->
<script lang="ts">
  import { onMount, getContext } from "svelte";
  import type { Writable } from "svelte/store";

  import { Label, Input, Button } from "flowbite-svelte";

  import { Svelecte } from "@app/components";
  import { Filtre, fetcher } from "@app/utils";

  import type { ShippingFilter } from "@app/types";

  type FilterContext = {
    emptyFilter: ShippingFilter;
    filterStore: Writable<Filtre<ShippingFilter>>;
    filterName: string;
  };

  const { emptyFilter, filterStore, filterName } =
    getContext<FilterContext>("filter");

  let filterData = { ...$filterStore.data };

  let listeNavires: string[] = [];
  let listeMarchandises: string[] = [];
  let listeClients: string[] = [];

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

  onMount(async () => {
    listeNavires = await fetcher<string[]>("consignation/navires");
    listeMarchandises = await fetcher<string[]>("consignation/marchandises");
    listeClients = await fetcher<string[]>("consignation/clients");
  });
</script>

<div
  class="grid items-end gap-2 lg:grid-flow-col lg:grid-cols-[max-content_repeat(3,1fr)_max-content] lg:grid-rows-2 lg:gap-4"
>
  <!-- Dates -->
  <div>
    <Label for="date_debut">Du</Label>
    <Input type="date" id="date_debut" bind:value={filterData.date_debut} />
  </div>

  <div>
    <Label for="date_fin">Au</Label>
    <Input type="date" id="date_fin" bind:value={filterData.date_fin} />
  </div>

  <!-- Filtre navire -->
  <div>
    <Label for="filtre_navire">Navires</Label>
    <Svelecte
      inputId="filtre_navire"
      options={listeNavires}
      bind:value={filterData.navire}
      placeholder="Navires"
      multiple
      virtualList
    />
  </div>

  <!-- Filtre armateur -->
  <div>
    <Label for="filtre_armateur">Armateurs</Label>
    <Svelecte
      inputId="filtre_armateur"
      type="tiers"
      role="maritime_armateur"
      bind:value={filterData.armateur}
      placeholder="Armateurs"
      multiple
    />
  </div>

  <!-- Filtre marchandises -->
  <div>
    <Label for="filtre_marchandise">Marchandises</Label>
    <Svelecte
      inputId="filtre_marchandise"
      options={listeMarchandises}
      bind:value={filterData.marchandise}
      placeholder="Marchandises"
      multiple
      virtualList
    />
  </div>

  <!-- Filtre clients -->
  <div>
    <Label for="filtre_client">Clients</Label>
    <Svelecte
      inputId="filtre_client"
      options={listeClients}
      bind:value={filterData.client}
      placeholder="Clients"
      multiple
      virtualList
    />
  </div>

  <!-- Filtre port précédent -->
  <div>
    <Label for="filtre_last_port">Ports précédents</Label>
    <Svelecte
      inputId="filtre_last_port"
      type="port"
      bind:value={filterData.last_port}
      placeholder="Ports précédents"
      multiple
      virtualList
    />
  </div>

  <!-- Filtre port suivant -->
  <div>
    <Label for="filtre_next_port">Ports suivants</Label>
    <Svelecte
      inputId="filtre_next_port"
      type="port"
      bind:value={filterData.next_port}
      placeholder="Ports suivants"
      multiple
      virtualList
    />
  </div>

  <!-- Boutons filtre -->
  <div>
    <Button type="submit" name="filtrer" class="w-full" on:click={applyFilter}>
      Filtrer
    </Button>
  </div>

  <div>
    <Button
      type="reset"
      name="supprimer_filtre"
      class="w-full"
      on:click={removeFilter}
    >
      Supprimer le filtre
    </Button>
  </div>
</div>
