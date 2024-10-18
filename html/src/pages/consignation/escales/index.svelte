<!-- routify:options title="Planning AMSB - Consignation" -->
<script lang="ts">
  import { onDestroy, getContext, setContext } from "svelte";
  import { params } from "@roxi/routify";

  import {
    BandeauInfo,
    CoteCesson,
    Chargement,
    SseConnection,
  } from "@app/components";
  import { LigneEscale } from "./components";

  import type { Stores } from "@app/types";

  const { consignationEscales } = getContext<Stores>("stores");

  const archives = "archives" in $params;

  setContext("archives", archives);

  if (archives) {
    consignationEscales.setParams({ archives: "true" });
  } else {
    consignationEscales.setParams({});
  }

  let escales: typeof $consignationEscales;

  const unsubscribeEscales = consignationEscales.subscribe((value) => {
    escales = value;
  });

  onDestroy(() => {
    unsubscribeEscales();
  });
</script>

<!-- routify:options query-params-is-page -->
<!-- routify:options guard="consignation" -->

<SseConnection
  subscriptions={[
    "consignation/escales",
    "tiers",
    "config/bandeau-info",
    "config/cotes",
  ]}
/>

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

<style>
  main {
    margin-bottom: 2rem;
  }
</style>
