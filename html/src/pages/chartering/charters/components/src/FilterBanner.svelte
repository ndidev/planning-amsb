<!-- 
  @component
  
  Bandeau filtre pour le planning affrètement maritime.

  Usage :
  ```tsx
  <Filtre />
  ```
 -->
<script lang="ts">
  import { getContext } from "svelte";
  import type { Writable } from "svelte/store";

  import { Label, Input, Button, Select } from "flowbite-svelte";

  import { Svelecte } from "@app/components";

  import { Filtre, device } from "@app/utils";

  import type { CharteringFilter } from "@app/types";

  type FilterContext = {
    emptyFilter: CharteringFilter;
    filterStore: Writable<Filtre<CharteringFilter>>;
    filterName: string;
  };

  const { emptyFilter, filterStore, filterName } =
    getContext<FilterContext>("filter");

  let filterData = { ...$filterStore.data };

  $: filtreIsActive =
    Object.values({ ...$filterStore.data }).filter((value) =>
      Array.isArray(value) ? (value.length > 0 ? value : undefined) : value
    ).length > 0;
  $: filterIsDisplayed = filtreIsActive && $device.is("desktop");

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
      <Input type="date" id="date_debut" bind:value={filterData.date_debut} />
    </div>

    <!-- Date fin -->
    <div>
      <Label for="date_fin">Au</Label>
      <Input type="date" id="date_fin" bind:value={filterData.date_fin} />
    </div>

    <!-- Filtre affréteur -->
    <div>
      <Label for="filtre_affreteur">Affréteur</Label>
      <Svelecte
        inputId="filtre_affreteur"
        type="tiers"
        role="maritime_affreteur"
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
