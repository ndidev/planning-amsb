<!-- 
  @component
  
  Bandeau filtre pour le planning affrètement maritime.

  Usage :
  ```tsx
  <FilterBanner />
  ```
 -->
<script lang="ts" context="module">
  import { writable } from "svelte/store";

  import { Filter } from "@app/utils";

  import type { Charter } from "@app/types";

  type CharteringFilter = {
    date_debut?: string;
    date_fin?: string;
    affreteur?: Charter["affreteur"][];
    armateur?: Charter["armateur"][];
    courtier?: Charter["courtier"][];
    statut?: Charter["statut"][];
  };

  const emptyFilter: CharteringFilter = {
    date_debut: "",
    date_fin: "",
    affreteur: [],
    armateur: [],
    courtier: [],
    statut: [],
  };

  const filterName = "chartering-planning-filter";

  export const filter = writable(
    new Filter<CharteringFilter>(
      JSON.parse(sessionStorage.getItem(filterName)) ||
        structuredClone(emptyFilter)
    )
  );
</script>

<script lang="ts">
  import { Label, Input, Button, Select } from "flowbite-svelte";

  import { Svelecte } from "@app/components";

  import { device } from "@app/utils";

  let filterData = { ...$filter.data };

  $: filtreIsActive =
    Object.values({ ...$filter.data }).filter((value) =>
      Array.isArray(value) ? (value.length > 0 ? value : undefined) : value
    ).length > 0;
  $: filterIsDisplayed = filtreIsActive && $device.is("desktop");

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

<!-- Filtre par date/client -->
<div
  class="w-[90%]"
  style:background={filtreIsActive ? "hsl(0, 100%, 92%)" : "white"}
>
  <button
    class="my-4 w-full cursor-pointer border-b-[1px] border-b-gray-300"
    on:click={() => (filterIsDisplayed = !filterIsDisplayed)}
    title={`${filterIsDisplayed ? "Masquer" : "Afficher"} le filtre`}
  >
    {filterIsDisplayed ? "Masquer" : "Afficher"} le filtre
  </button>

  <div
    class="items-end gap-2 lg:grid-flow-col lg:grid-cols-[max-content_repeat(2,1fr)_max-content] lg:grid-rows-2 lg:gap-4"
    style:display={filterIsDisplayed ? "grid" : "none"}
  >
    <!-- Date début -->
    <div>
      <Label for="date_debut">Du</Label>
      <Input
        type="date"
        id="date_debut"
        bind:value={filterData.date_debut}
        max={filterData.date_fin}
      />
    </div>

    <!-- Date fin -->
    <div>
      <Label for="date_fin">Au</Label>
      <Input
        type="date"
        id="date_fin"
        bind:value={filterData.date_fin}
        min={filterData.date_debut}
      />
    </div>

    <!-- Filtre affréteur -->
    <div>
      <Label for="filtre_affreteur">Affréteur</Label>
      <Svelecte
        inputId="filtre_affreteur"
        type="tiers"
        role="maritime_affreteur"
        includeInactive
        bind:value={filterData.affreteur}
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
        bind:value={filterData.armateur}
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
        bind:value={filterData.courtier}
        placeholder="Courtier"
        multiple
      />
    </div>

    <!-- Filtre statut -->
    <div>
      <Label for="filtre_statut">Statut</Label>
      <Select id="filtre_statut" bind:value={filterData.statut}>
        <option value="">Tous</option>
        <option value="0">Plannifié (pas confirmé)</option>
        <option value="1">Confirmé par l'affréteur</option>
        <option value="2">Affrété</option>
        <option value="3">Chargement effectué</option>
        <option value="4">Voyage terminé</option>
      </Select>
    </div>

    <!-- Boutons filtre -->
    <div>
      <Button type="submit" class="w-full" on:click={applyFilter}>
        Filtrer
      </Button>
    </div>

    <div>
      <Button type="reset" class="w-full" on:click={removeFilter}>
        Supprimer le filtre
      </Button>
    </div>
  </div>
</div>
