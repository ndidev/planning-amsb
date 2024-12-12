<!-- 
  @component
  
  Bandeau filtre pour le planning bois.

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

  import type { TimberFilter } from "@app/types";

  type FilterContext = {
    emptyFilter: TimberFilter;
    filterStore: Writable<Filtre<TimberFilter>>;
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

<div
  class="grid items-end gap-2 lg:grid-flow-col lg:grid-cols-[max-content_repeat(3,1fr)_max-content] lg:grid-rows-2 lg:gap-4"
>
  <!-- Dates -->
  <div>
    <Label for="date_debut">Du</Label>
    <Input
      type="date"
      id="date_debut"
      name="date_debut"
      bind:value={filterData.date_debut}
    />
  </div>

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
    <Button type="submit" class="w-full" on:click={applyFilter}>Filtrer</Button>
  </div>

  <div>
    <Button type="reset" class="w-full" on:click={removeFilter}>
      Supprimer le filtre
    </Button>
  </div>
</div>
