<!-- routify:options query-params-is-page -->
<script lang="ts">
  import { onMount, onDestroy, getContext, setContext } from "svelte";
  import { params, goto } from "@roxi/routify";

  import { BandeauInfo, CoteCesson, Chargement } from "@app/components";
  import { LigneEscale } from "./components";

  import { demarrerConnexionSSE } from "@app/utils";
  import type { Stores } from "@app/types";

  let source: EventSource;

  const { currentUser, consignationEscales } = getContext<Stores>("stores");

  let escales: typeof $consignationEscales;

  const archives = "archives" in $params;

  setContext("archives", archives);

  if (archives) {
    consignationEscales.setParams({ archives: "true" });
  } else {
    consignationEscales.setParams({});
  }

  const unsubscribeEscales = consignationEscales.subscribe((value) => {
    escales = value;
  });

  onMount(async () => {
    source = await demarrerConnexionSSE([
      "consignation/escales",
      "tiers",
      "config/bandeau-info",
      "config/cotes",
    ]);
  });

  onDestroy(() => {
    source.close();
    unsubscribeEscales();
  });
</script>

<!-- routify:options title="Planning AMSB - Consignation" -->

{#if $currentUser.canAccess("consignation")}
  <CoteCesson />
  <BandeauInfo module="consignation" pc />

  <main>
    {#if escales}
      {#each [...escales.values()] as escale (escale.id)}
        <LigneEscale {escale} />
      {/each}
    {:else}
      <Chargement />
    {/if}
  </main>
{:else}
  {$goto("/")}
{/if}

<style>
  main {
    margin-bottom: 2rem;
  }
</style>
