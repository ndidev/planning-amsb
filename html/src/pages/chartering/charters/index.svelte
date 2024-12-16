<!-- routify:options title="Planning AMSB - Affrètement maritime" -->
<script lang="ts">
  import { onDestroy, getContext, setContext } from "svelte";
  import { params } from "@roxi/routify";

  import { Chargement, BandeauInfo, SseConnection } from "@app/components";
  import { FilterBanner, filter, LigneCharter } from "./components";

  import type { Stores } from "@app/types";

  const { charteringCharters } = getContext<Stores>("stores");

  let charters: typeof $charteringCharters;

  const archives = "archives" in $params;

  setContext("archives", archives);

  const unsubscribeFilter = filter.subscribe((value) => {
    const params = value.toSearchParams();

    if (archives) params.append("archives", "");

    charteringCharters.setSearchParams(params);
  });

  const unsubscribeCharters = charteringCharters.subscribe((value) => {
    charters = value;
  });

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
