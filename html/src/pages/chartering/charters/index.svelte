<!-- routify:options title="Planning AMSB - Affrètement maritime" -->
<script lang="ts">
  import { onMount, onDestroy, getContext, setContext } from "svelte";
  import { writable } from "svelte/store";
  import { params } from "@roxi/routify";

  import { Chargement, BandeauInfo } from "@app/components";
  import { Filtre as BandeauFiltre, LigneCharter } from "./components";

  import { demarrerConnexionSSE, Filtre } from "@app/utils";
  import type { Stores, FiltreCharter } from "@app/types";

  let source: EventSource;

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
    const params = value.toParams();

    if (archives) params.append("archives", "");

    charteringCharters.setParams(params);
  });

  const unsubscribeCharters = charteringCharters.subscribe((value) => {
    charters = value;
  });

  setContext("filtre", storeFiltre);

  onMount(async () => {
    source = await demarrerConnexionSSE([
      "chartering/charters",
      "tiers",
      "config/bandeau-info",
    ]);
  });

  onDestroy(() => {
    source.close();
    unsubscribeCharters();
    unsubscribeFiltre();
  });
</script>

<!-- routify:options query-params-is-page -->
<!-- routify:options guard="chartering" -->

<BandeauInfo module="chartering" pc />

<div class="filtre">
  <BandeauFiltre />
</div>

<main>
  {#if charters}
    {#each [...charters.values()] as charter (charter.id)}
      <LigneCharter {charter} />
    {/each}
  {:else}
    <Chargement />
  {/if}
</main>

<style>
  .filtre {
    position: sticky;
    top: 0;
    left: 0;
    margin-left: 100px;
    z-index: 2;
  }
</style>
