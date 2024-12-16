<!-- 
  @component
  
  Bandeau filtre pour le planning bois.

  Usage :
  ```tsx
  <FilterBanner />
  ```
 -->
<script lang="ts" context="module">
  import { writable } from "svelte/store";

  import { Filter } from "@app/utils";

  import type { RdvBois } from "@app/types";

  type TimberFilter = {
    date_debut?: string;
    date_fin?: string;
    fournisseur?: RdvBois["fournisseur"][];
    client?: RdvBois["client"][];
    chargement?: RdvBois["chargement"][];
    livraison?: RdvBois["livraison"][];
    transporteur?: RdvBois["transporteur"][];
    affreteur?: RdvBois["affreteur"][];
  };

  const emptyFilter: TimberFilter = {
    date_debut: "",
    date_fin: "",
    fournisseur: [],
    client: [],
    chargement: [],
    livraison: [],
    transporteur: [],
    affreteur: [],
  };

  const filterName = "timber-stats-filter";

  export const filter = writable(
    new Filter<TimberFilter>(
      JSON.parse(sessionStorage.getItem(filterName)) ||
        structuredClone(emptyFilter)
    )
  );
</script>

<script lang="ts">
  import { Label, Input, Button } from "flowbite-svelte";

  import { Svelecte } from "@app/components";
  import { device } from "@app/utils";

  let filterData = { ...$filter.data };

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
    class="items-end gap-2 lg:grid-flow-col lg:grid-cols-[max-content_repeat(3,1fr)_max-content] lg:grid-rows-2 lg:gap-4"
    style:display={filterIsDisplayed ? "grid" : "none"}
  >
    <!-- Date début -->
    <div>
      <Label for="date_debut">Du</Label>
      <Input
        type="date"
        id="date_debut"
        name="date_debut"
        bind:value={filterData.date_debut}
      />
    </div>

    <!-- Date fin -->
    <div>
      <Label for="date_fin">Au</Label>
      <Input
        type="date"
        id="date_fin"
        name="date_fin"
        bind:value={filterData.date_fin}
      />
    </div>

    <!-- Filtre fournisseur -->
    <div>
      <Label for="filtre_fournisseur">Fournisseur</Label>
      <Svelecte
        inputId="filtre_fournisseur"
        type="tiers"
        role="bois_fournisseur"
        includeInactive
        bind:value={filterData.fournisseur}
        placeholder="Fournisseur"
        multiple
      />
    </div>

    <!-- Filtre client -->
    <div>
      <Label for="filtre_client">Client</Label>
      <Svelecte
        inputId="filtre_client"
        type="tiers"
        role="bois_client"
        includeInactive
        bind:value={filterData.client}
        placeholder="Client"
        multiple
      />
    </div>

    <!-- Filtre chargement -->
    <div class="">
      <Label for="filtre_chargement">Chargement</Label>
      <Svelecte
        inputId="filtre_chargement"
        type="tiers"
        role="bois_client"
        includeInactive
        bind:value={filterData.chargement}
        placeholder="Chargement"
        multiple
      />
    </div>

    <!-- Filtre livraison -->
    <div>
      <Label for="filtre_livraison">Livraison</Label>
      <Svelecte
        inputId="filtre_livraison"
        type="tiers"
        role="bois_client"
        includeInactive
        bind:value={filterData.livraison}
        placeholder="Livraison"
        multiple
      />
    </div>

    <!-- Filtre transporteur -->
    <div>
      <Label for="filtre_transporteur">Transporteur</Label>
      <Svelecte
        inputId="filtre_transporteur"
        type="tiers"
        role="bois_transporteur"
        includeInactive
        bind:value={filterData.transporteur}
        placeholder="Transporteur"
        multiple
      />
    </div>

    <!-- Filtre affréteur -->
    <div>
      <Label for="filtre_affreteur">Affréteur</Label>
      <Svelecte
        inputId="filtre_affreteur"
        type="tiers"
        role="bois_affreteur"
        includeInactive
        bind:value={filterData.affreteur}
        placeholder="Affréteur"
        multiple
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
