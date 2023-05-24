<!-- routify:options title="Planning AMSB - Consignation" -->
<script lang="ts">
  import { getContext } from "svelte";

  import { BandeauInfo, CoteCesson, ConnexionSSE } from "@app/components";
  import { LigneEscale } from "./components";

  import type { Stores } from "@app/types";

  const { currentUser, consignationEscales } = getContext<Stores>("stores");
</script>

{#if $currentUser.canUseApp && $currentUser.canAccess("consignation")}
  <ConnexionSSE
    subscriptions={[
      "consignation/escales",
      "tiers",
      "config/bandeau-info",
      "config/cotes",
    ]}
  />

  <CoteCesson tv />
  <BandeauInfo module="consignation" tv />

  <main>
    {#each [...($consignationEscales?.values() || [])] as escale (escale.id)}
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
