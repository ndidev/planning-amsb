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

  import type { FiltreConsignation } from "@app/types";

  const filtre = getContext<Writable<Filtre<FiltreConsignation>>>("filtre");

  let _filtre = { ...$filtre.data };

  let listeNavires: string[] = [];
  let listeMarchandises: string[] = [];
  let listeClients: string[] = [];

  /**
   * Enregistrer le filtre.
   */
  async function applyFilter() {
    sessionStorage.setItem(
      "filtre-stats-consignation",
      JSON.stringify(_filtre)
    );

    filtre.set(new Filtre(_filtre));
  }

  /**
   * Supprimer le filtre.
   */
  function removeFilter() {
    sessionStorage.removeItem("filtre-stats-consignation");

    filtre.set(new Filtre({}));
    _filtre = {};
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
    <Input type="date" id="date_debut" bind:value={_filtre.date_debut} />
  </div>

  <div>
    <Label for="date_fin">Au</Label>
    <Input type="date" id="date_fin" bind:value={_filtre.date_fin} />
  </div>

  <!-- Filtre navire -->
  <div>
    <Label for="filtre_navire">Navires</Label>
    <Svelecte
      inputId="filtre_navire"
      options={listeNavires}
      bind:value={_filtre.navire}
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
      bind:value={_filtre.armateur}
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
      bind:value={_filtre.marchandise}
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
      bind:value={_filtre.client}
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
      bind:value={_filtre.last_port}
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
      bind:value={_filtre.next_port}
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
