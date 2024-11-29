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
    consignationEscales.setSearchParams({ archives: "true" });
  } else {
    consignationEscales.setSearchParams({});
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

<div class="sticky top-0 z-[1] ml-16 lg:ml-24">
  <CoteCesson />
  <BandeauInfo module="consignation" pc />
</div>

<main class="w-full mb-8">
  {#if escales}
    {#each [...escales.values()] as escale (escale.id)}
      <LigneEscale {escale} />
    {/each}
  {:else}
    <Chargement />
  {/if}
</main>
