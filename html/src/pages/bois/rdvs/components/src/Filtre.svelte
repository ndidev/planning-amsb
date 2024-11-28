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
  import { Filtre, device } from "@app/utils";

  import type { FiltreBois } from "@app/types";

  const filtre = getContext<Writable<Filtre<FiltreBois>>>("filtre");

  let _filtre = { ...$filtre.data };

  $: filtreActif =
    Object.values({ ...$filtre.data }).filter((value) =>
      Array.isArray(value) ? (value.length > 0 ? value : undefined) : value
    ).length > 0;
  $: filterIsDisplayed = filtreActif && $device.is("desktop");

  /**
   * Enregistrer le filtre.
   */
  async function applyFilter(e: Event) {
    e.preventDefault();

    sessionStorage.setItem("filtre-planning-bois", JSON.stringify(_filtre));

    filtre.set(new Filtre(_filtre));
  }

  /**
   * Supprimer le filtre.
   */
  function removeFilter() {
    sessionStorage.removeItem("filtre-planning-bois");

    filtre.set(new Filtre({}));
    _filtre = {};
  }
</script>

<div
  class="w-[90%]"
  style:background={filtreActif ? "hsl(0, 100%, 92%)" : "white"}
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
    <!-- Dates -->
    <div>
      <Label for="date_debut">Du</Label>
      <Input
        type="date"
        id="date_debut"
        name="date_debut"
        bind:value={_filtre.date_debut}
      />
    </div>

    <div>
      <Label for="date_fin">Au</Label>
      <Input
        type="date"
        id="date_fin"
        name="date_fin"
        bind:value={_filtre.date_fin}
      />
    </div>

    <!-- Filtre fournisseur -->
    <div>
      <Label for="filtre_fournisseur">Fournisseur</Label>
      <Svelecte
        inputId="filtre_fournisseur"
        type="tiers"
        role="bois_fournisseur"
        bind:value={_filtre.fournisseur}
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
        bind:value={_filtre.client}
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
        bind:value={_filtre.chargement}
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
        bind:value={_filtre.livraison}
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
        bind:value={_filtre.transporteur}
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
        bind:value={_filtre.affreteur}
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
