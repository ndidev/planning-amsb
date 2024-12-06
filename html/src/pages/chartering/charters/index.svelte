<!-- routify:options title="Planning AMSB - Affrètement maritime" -->
<script lang="ts">
  import { onDestroy, getContext, setContext } from "svelte";
  import { writable } from "svelte/store";
  import { params } from "@roxi/routify";

  import { Chargement, BandeauInfo, SseConnection } from "@app/components";
  import { FilterBanner, LigneCharter } from "./components";

  import { Filtre } from "@app/utils";
  import type { Stores, CharteringFilter } from "@app/types";

  const { charteringCharters } = getContext<Stores>("stores");

  const emptyFilter: CharteringFilter = {
    date_debut: "",
    date_fin: "",
    affreteur: [],
    armateur: [],
    courtier: [],
    statut: [],
  };

  const filterName = "chartering-planning-filter";

  // Stores Filtre et affrètements
  let filter = new Filtre<CharteringFilter>(
    JSON.parse(sessionStorage.getItem(filterName)) ||
      structuredClone(emptyFilter)
  );

  const filterStore = writable(filter);

  let charters: typeof $charteringCharters;

  const archives = "archives" in $params;

  setContext("archives", archives);

  const unsubscribeFilter = filterStore.subscribe((value) => {
    const params = value.toSearchParams();

    if (archives) params.append("archives", "");

    charteringCharters.setSearchParams(params);
  });

  const unsubscribeCharters = charteringCharters.subscribe((value) => {
    charters = value;
  });

  setContext("filter", { emptyFilter, filterStore, filterName });

  onDestroy(() => {
    unsubscribeCharters();
    unsubscribeFilter();
  });
</script>

<!-- routify:options query-params-is-page -->
<!-- routify:options guard="chartering" -->

<SseConnection
  subscriptions={["chartering/charters", "tiers", "config/bandeau-info"]}
/>

<div class="sticky top-0 z-[1] ml-16 lg:ml-24">
  <BandeauInfo module="chartering" pc />

  <FilterBanner />
</div>

<main class="divide-y">
  {#if charters}
    {#each [...charters.values()] as charter (charter.id)}
      <LigneCharter {charter} />
    {/each}
  {:else}
    <Chargement />
  {/if}
</main>
