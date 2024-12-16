<!-- 
  @component
  
  Bandeau filtre pour le planning consignation.

  Usage :
  ```tsx
  <FilterBanner />
  ```
 -->
<script lang="ts" context="module">
  import { writable } from "svelte/store";

  import { Filter } from "@app/utils";

  import type { EscaleConsignation } from "@app/types";

  type ShippingFilter = {
    date_debut?: string;
    date_fin?: string;
    navire?: EscaleConsignation["navire"][];
    marchandise?: EscaleConsignation["marchandises"][number]["marchandise"];
    client?: EscaleConsignation["marchandises"][number]["client"];
    armateur?: EscaleConsignation["armateur"][];
    last_port?: EscaleConsignation["last_port"][];
    next_port?: EscaleConsignation["next_port"][];
  };

  const emptyFilter: ShippingFilter = {
    date_debut: "",
    date_fin: "",
    navire: [],
    marchandise: "",
    client: "",
    armateur: [],
    last_port: [],
    next_port: [],
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

  import { Label, Input, Button } from "flowbite-svelte";

  import { Svelecte } from "@app/components";
  import { fetcher, device } from "@app/utils";

  let filterData = { ...$filter.data };

  let listeNavires: string[] = [];
  let listeMarchandises: string[] = [];
  let listeClients: string[] = [];

  $: filterIsActive =
    Object.values({ ...$filter.data }).filter((value) =>
      Array.isArray(value) ? (value.length > 0 ? value : undefined) : value
    ).length > 0;
  $: filterIsDisplayed = $device.is("desktop");

  /**
   * Enregistrer le filtre.
   */
  async function applyFilter(e: Event) {
    e.preventDefault();

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

  onMount(async () => {
    listeNavires = await fetcher<string[]>("consignation/navires");
    listeMarchandises = await fetcher<string[]>("consignation/marchandises");
    listeClients = await fetcher<string[]>("consignation/clients");
  });
</script>

<div
  class="w-full"
  style:background={filterIsActive && !filterIsDisplayed
    ? "hsl(0, 100%, 92%)"
    : "white"}
>
  <button
    class="my-4 w-full cursor-pointer border-b-[1px] border-b-gray-300"
    on:click={() => (filterIsDisplayed = !filterIsDisplayed)}
    title={`${filterIsDisplayed ? "Masquer" : "Afficher"} le filtre`}
  >
    {filterIsDisplayed ? "Masquer" : "Afficher"} le filtre
  </button>

  <form
    class="grid items-end gap-2 lg:grid-flow-col lg:grid-cols-[max-content_repeat(3,1fr)_max-content] lg:grid-rows-2 lg:gap-4"
    style:display={filterIsDisplayed ? "grid" : "none"}
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
      <Button
        type="submit"
        name="filtrer"
        class="w-full"
        on:click={applyFilter}
      >
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
  </form>
</div>
