<!-- routify:options title="Planning AMSB - Affrètement maritime" -->
<script lang="ts">
  import { onDestroy } from "svelte";

  import { Chargement, BandeauInfo, SseConnection } from "@app/components";
  import { FilterModal, filter, LigneCharter } from "./components";

  import { charteringCharters, tiers, configBandeauInfo } from "@app/stores";

  let charters: typeof $charteringCharters;

  const unsubscribeFilter = filter.subscribe((value) => {
    const params = value.toSearchParams();

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

<!-- routify:options guard="chartering" -->

<SseConnection
  subscriptions={[
    charteringCharters.endpoint,
    tiers.endpoint,
    configBandeauInfo.endpoint,
  ]}
/>

<div class="sticky top-0 z-[1] ml-16 lg:ml-24">
  <BandeauInfo module="chartering" pc />

  <FilterModal />
</div>

<main class="divide-y">
  {#if charters}
    {#each [...charters.values()] as charter (charter.id)}
      <LigneCharter {charter} />
    {:else}
      <div class="p-4 text-center">Aucun affrètement trouvé.</div>
    {/each}
  {:else}
    <Chargement />
  {/if}
</main>
