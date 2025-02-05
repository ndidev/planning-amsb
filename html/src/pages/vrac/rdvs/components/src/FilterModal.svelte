<!-- 
  @component
  
  Modal filtre pour le planning vrac.

  Usage :
  ```tsx
  <FilterModal />
  ```
 -->
<script lang="ts" context="module">
  import { writable } from "svelte/store";

  import { DateUtils, Filter, parseJSON } from "@app/utils";

  import type { BulkPlanningFilter } from "@app/types";

  const emptyFilter: BulkPlanningFilter = {
    date_debut: new Date().toISOString().split("T")[0],
    date_fin: "",
    produit: [],
    qualite: [],
    client: [],
    transporteur: [],
    archives: false,
  };

  const filterName = "bulk-planning-filter";

  export const filter = writable(
    new Filter<BulkPlanningFilter>(
      parseJSON(sessionStorage.getItem(filterName)) ||
        structuredClone(emptyFilter)
    )
  );
</script>

<script lang="ts">
  import {
    Modal,
    Label,
    Input,
    Button,
    Toggle,
    Tooltip,
  } from "flowbite-svelte";
  import { FilterIcon, FilterXIcon } from "lucide-svelte";

  import { Svelecte, LucideButton } from "@app/components";

  import { vracProduits, tiers } from "@app/stores";

  let filterData = { ...$filter.data };

  let open = false;

  $: filterIsActive = Object.entries({ ...$filter.data }).reduce(
    (acc, [key, value]) =>
      (Array.isArray(value) ? value.length > 0 : value !== emptyFilter[key]) ||
      acc,
    false
  );

  let qualityOptions: { id: number; name: string }[] = [];
  $: {
    // Filter qualities
    qualityOptions = filterData.produit.length
      ? [
          ...new Set(
            filterData.produit
              .flatMap((productId) => {
                const product = $vracProduits?.get(productId);

                if (!product) return [];

                return product.qualites.map((quality) => ({
                  id: quality.id,
                  name: product.nom + " " + quality.nom,
                }));
              })
              .sort((a, b) => a.name.localeCompare(b.name))
          ),
        ]
      : [];

    // Remove selected qualities if the product is not selected
    const availableQualityIds = filterData.produit.flatMap(
      (productId) =>
        $vracProduits?.get(productId)?.qualites.map((quality) => quality.id) ||
        []
    );
    filterData.qualite = filterData.qualite.filter((qualityId) =>
      availableQualityIds.includes(qualityId)
    );
  }

  let filterTooltip = "";
  $: if (filterData && $vracProduits && $tiers) {
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

    if (filterData.produit.length) {
      const listString = filterData.produit
        .map((productId) => $vracProduits?.get(productId)?.nom)
        .join(", ");
      filterTooltip.push(`Produit : ${listString}`);
    }

    if (filterData.qualite.length) {
      filterTooltip.push(
        `Qualité : ${filterData.qualite
          .map(
            (qualityId) =>
              qualityOptions.find((quality) => quality.id === qualityId)?.name
          )
          .join(", ")}`
      );
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
          .map((clientId) => $tiers.get(clientId)?.nom_court)
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

    if (filterData.archives) {
      filterTooltip.push("Archives");
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

    <!-- Filtre produit -->
    <div>
      <Label for="filtre_produit">Produit</Label>
      <Svelecte
        inputId="filtre_produit"
        options={[...($vracProduits?.values() || [])]}
        valueField="id"
        labelField="nom"
        includeInactive
        bind:value={filterData.produit}
        placeholder="Produit"
        multiple
      />
    </div>

    <!-- Filtre qualité -->
    <div class:disabled={qualityOptions.length === 0}>
      <Label for="filtre_qualite">Qualité</Label>
      <Svelecte
        inputId="filtre_qualite"
        name="filtre_qualite"
        options={qualityOptions}
        valueField="id"
        labelField="name"
        bind:value={filterData.qualite}
        placeholder="Qualité"
        multiple
      />
    </div>

    <!-- Filtre fournisseur -->
    <div>
      <Label for="filtre_fournisseur">Fournisseur</Label>
      <Svelecte
        inputId="filtre_fournisseur"
        type="tiers"
        role="vrac_fournisseur"
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
        role="vrac_client"
        includeInactive
        bind:value={filterData.client}
        placeholder="Client"
        multiple
      />
    </div>

    <!-- Filtre transporteur -->
    <div>
      <Label for="filtre_transporteur">Transporteur</Label>
      <Svelecte
        inputId="filtre_transporteur"
        type="tiers"
        role="vrac_transporteur"
        includeInactive
        bind:value={filterData.transporteur}
        placeholder="Transporteur"
        multiple
      />
    </div>

    <!-- Filtre archives -->
    <div class="my-4">
      <Toggle bind:checked={filterData.archives}>Archives</Toggle>
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

  .disabled {
    opacity: 0.5;
    pointer-events: none;
    cursor: not-allowed;
  }
</style>
