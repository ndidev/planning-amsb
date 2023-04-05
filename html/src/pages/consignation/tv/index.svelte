<!-- routify:options title="Planning AMSB - Consignation" -->
<script lang="ts">
  import { onMount, onDestroy, getContext } from "svelte";

  import { BandeauInfo, CoteCesson } from "@app/components";
  import { LigneEscale } from "./components";

  import { demarrerConnexionSSE } from "@app/utils";
  import type { Stores } from "@app/types";

  let source: EventSource;

  const { currentUser, consignationEscales } = getContext<Stores>("stores");

  let escales: typeof $consignationEscales;

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

{#if $currentUser.canUseApp && $currentUser.canAccess("consignation")}
  <CoteCesson tv />
  <BandeauInfo module="consignation" tv />

  <main>
    {#each [...(escales?.values() || [])] as escale (escale.id)}
      <LigneEscale {escale} />
    {/each}
  </main>
{/if}

<style>
  @import "/src/css/commun.css";

  * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
  }

  /* body {
    width: 100vw;
    overflow-x: hidden;
    font-family: sans-serif;
  } */

  ::-webkit-scrollbar {
    display: none;
  }
</style>
