<!-- 
  @component
  
  Bandeau filtre pour le planning vrac.

  Usage :
  ```tsx
  <FilterBanner />
  ```
 -->
<script lang="ts" context="module">
  import { writable } from "svelte/store";

  import { DateUtils, Filter } from "@app/utils";

  import type { TimberFilter } from "@app/types";

  const emptyFilter: TimberFilter = {
    date_debut: "2014-01-01",
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
  import { Modal, Label, Input, Button, Tooltip } from "flowbite-svelte";
  import { FilterIcon, FilterXIcon } from "lucide-svelte";

  import { Svelecte, LucideButton } from "@app/components";

  import { tiers } from "@app/stores";

  let filterData = { ...$filter.data };

  let open = false;

  $: filterIsActive = Object.entries({ ...$filter.data }).reduce(
    (acc, [key, value]) =>
      (Array.isArray(value) ? value.length > 0 : value !== emptyFilter[key]) ||
      acc,
    false
  );

  let filterTooltip = "";
  $: if (filterData && $tiers) {
    filterTooltip = makeFilterTooltip();
  }

  function makeFilterTooltip() {
    if (!filterIsActive) {
      return "Aucun filtre activé";
    }

    const filterTooltip = [];

    if (filterData.date_debut !== emptyFilter.date_debut) {
      const formattedDate = new DateUtils(filterData.date_debut).format().short;
      filterTooltip.push(`Du ${formattedDate}`);
    }

    if (filterData.date_fin !== emptyFilter.date_fin) {
      const formattedDate = new DateUtils(filterData.date_fin).format().short;
      filterTooltip.push(`Au ${formattedDate}`);
    }

    if (filterData.fournisseur.length) {
      filterTooltip.push(
        `Fournisseur : ${filterData.fournisseur
          .map((fournisseurId) => $tiers.get(fournisseurId)?.nom_court)
          .join(", ")}`
      );
    }

    if (filterData.client.length) {
      filterTooltip.push(
        `Client : ${filterData.client
          .map(
            (clientId) =>
              $tiers.get(clientId)?.nom_court +
              " " +
              $tiers.get(clientId)?.ville
          )
          .join(", ")}`
      );
    }

    if (filterData.chargement.length) {
      filterTooltip.push(
        `Chargement : ${filterData.chargement
          .map(
            (chargementId) =>
              $tiers.get(chargementId)?.nom_court +
              " " +
              $tiers.get(chargementId)?.ville
          )
          .join(", ")}`
      );
    }

    if (filterData.livraison.length) {
      filterTooltip.push(
        `Livraison : ${filterData.livraison
          .map(
            (livraisonId) =>
              $tiers.get(livraisonId)?.nom_court +
              " " +
              $tiers.get(livraisonId)?.ville
          )
          .join(", ")}`
      );
    }

    if (filterData.transporteur.length) {
      filterTooltip.push(
        `Transporteur : ${filterData.transporteur
          .map((transporteurId) => $tiers.get(transporteurId)?.nom_court)
          .join(", ")}`
      );
    }

    if (filterData.affreteur.length) {
      filterTooltip.push(
        `Affréteur : ${filterData.affreteur
          .map((affreteurId) => $tiers.get(affreteurId)?.nom_court)
          .join(", ")}`
      );
    }

    return filterTooltip.join("<br/>");
  }

  async function applyFilter() {
    sessionStorage.setItem(filterName, JSON.stringify(filterData));

    filterData = filterData; // Enable reactive tooltip
    filter.set(new Filter(filterData));
  }

  function removeFilter() {
    sessionStorage.removeItem(filterName);

    filterData = structuredClone(emptyFilter);
    filter.set(new Filter(filterData));
  }
</script>

<div class="text-center">
  <button on:click={() => (open = !open)}>Afficher le filtre</button>
</div>

<div class="filter-button">
  <LucideButton
    icon={filterIsActive ? FilterXIcon : FilterIcon}
    color={filterIsActive ? "yellow" : "default"}
    staticallyColored
    title="Filtre"
    on:click={() => (open = !open)}
  />
  <Tooltip type="auto" placement="left-end" class="w-max whitespace-pre-line"
    >{@html filterTooltip}</Tooltip
  >
</div>

<Modal
  title="Filtre"
  bind:open
  autoclose
  outsideclose
  on:open={() => (filterData = { ...$filter.data })}
>
  <div class="flex flex-col gap-2">
    <div class="flex flex-col lg:flex-row gap-2">
      <!-- Date début -->
      <div class="w-full">
        <Label for="date_debut">Du</Label>
        <Input
          type="date"
          id="date_debut"
          bind:value={filterData.date_debut}
          max={filterData.date_fin}
        />
      </div>

      <!-- Date fin -->
      <div class="w-full">
        <Label for="date_fin">Au</Label>
        <Input
          type="date"
          id="date_fin"
          bind:value={filterData.date_fin}
          min={filterData.date_debut}
        />
      </div>
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
    <div>
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
    <div class="flex flex-col lg:flex-row gap-2">
      <div class="w-full">
        <Button type="submit" class="w-full" on:click={applyFilter}>
          Filtrer
        </Button>
      </div>

      <div class="w-full">
        <Button
          type="reset"
          class="w-full"
          color="dark"
          on:click={removeFilter}
        >
          Supprimer le filtre
        </Button>
      </div>
    </div>
  </div>
</Modal>

<style>
  .filter-button {
    --size: 50px;

    display: grid;
    place-items: center;
    position: fixed;
    right: 20px;
    bottom: calc(var(--footer-height) + 20px);
    width: var(--size);
    height: var(--size);
    z-index: 3;
    border-radius: 50%;
    background: radial-gradient(
      circle at center,
      white 0,
      white 50%,
      transparent 100%
    );
  }
</style>
