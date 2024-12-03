<!-- routify:options title="Planning AMSB - Affrètement maritime" -->
<script lang="ts">
  import { onDestroy, getContext, setContext } from "svelte";
  import { writable } from "svelte/store";
  import { params } from "@roxi/routify";

  import { Chargement, BandeauInfo, SseConnection } from "@app/components";
  import { Filtre as BandeauFiltre, LigneCharter } from "./components";

  import { Filtre } from "@app/utils";
  import type { Stores, FiltreCharter } from "@app/types";

  const { charteringCharters } = getContext<Stores>("stores");

  // Stores Filtre et affrètements
  let filtre = new Filtre<FiltreCharter>(
    JSON.parse(sessionStorage.getItem("filtre-planning-chartering")) || {}
  );

  const storeFiltre = writable(filtre);

  let charters: typeof $charteringCharters;

  const archives = "archives" in $params;

  setContext("archives", archives);

  const unsubscribeFiltre = storeFiltre.subscribe((value) => {
    const params = value.toSearchParams();

    if (archives) params.append("archives", "");

    charteringCharters.setSearchParams(params);
  });

  const unsubscribeCharters = charteringCharters.subscribe((value) => {
    charters = value;
  });

  setContext("filtre", storeFiltre);

  onDestroy(() => {
    unsubscribeCharters();
    unsubscribeFiltre();
  });
</script>

<!-- routify:options query-params-is-page -->
<!-- routify:options guard="chartering" -->

<SseConnection
  subscriptions={["chartering/charters", "tiers", "config/bandeau-info"]}
/>

<div class="sticky top-0 z-[1] ml-16 lg:ml-24">
  <BandeauInfo module="chartering" pc />

  <BandeauFiltre />
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
