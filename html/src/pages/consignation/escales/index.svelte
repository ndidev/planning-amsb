<!-- routify:options title="Planning AMSB - Consignation" -->
<script lang="ts">
  import { onDestroy } from "svelte";

  import {
    BandeauInfo,
    CoteCesson,
    Chargement,
    SseConnection,
  } from "@app/components";
  import { LigneEscale, FilterModal, filter } from "./components";

  import { consignationEscales, tiers, configBandeauInfo } from "@app/stores";

  import type { EscaleConsignation } from "@app/types";

  let escales: EscaleConsignation[];

  let callsReady = consignationEscales.getReadyState();

  const unsubscribeFilter = filter.subscribe((value) => {
    const params = value.toSearchParams();
    consignationEscales.setSearchParams(params);

    callsReady = consignationEscales.getReadyState();
  });

  const unsubscribeEscales = consignationEscales.subscribe((value) => {
    if (!value) return;

    escales = [...value.values()].sort((a, b) => {
      return (
        (a.eta_date ?? "9").localeCompare(b.eta_date ?? "9") ||
        a.eta_heure.localeCompare(b.eta_heure) ||
        (a.etb_date ?? "9").localeCompare(b.etb_date ?? "9") ||
        a.etb_heure.localeCompare(b.etb_heure)
      );
    });
  });

  onDestroy(() => {
    unsubscribeFilter();
    unsubscribeEscales();
  });
</script>

<!-- routify:options guard="consignation" -->

<SseConnection
  subscriptions={[
    consignationEscales.endpoint,
    tiers.endpoint,
    configBandeauInfo.endpoint,
    "config/cotes",
  ]}
/>

<div class="sticky top-0 z-[1] ml-16 lg:ml-24">
  <CoteCesson />
  <BandeauInfo module="consignation" pc />
</div>

<FilterModal />

<main class="w-full flex-auto">
  {#await callsReady}
    <Chargement />
  {:then}
    <div class="divide-y">
      {#each escales as escale (escale.id)}
        <LigneEscale {escale} />
      {:else}
        <div class="p-4 text-center">Aucune escale trouvée.</div>
      {/each}
    </div>
  {:catch error}
    <div class="p-4 text-center text-red-500">Erreur de chargement</div>
  {/await}
</main>
