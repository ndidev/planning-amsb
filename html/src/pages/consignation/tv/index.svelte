<!-- routify:options title="Planning AMSB - Consignation" -->
<script lang="ts">
  import { BandeauInfo, CoteCesson, SseConnection } from "@app/components";
  import { LigneEscale } from "./components";

  import { currentUser, consignationEscales } from "@app/stores";
</script>

{#if $currentUser.canUseApp && $currentUser.canAccess("consignation")}
  <SseConnection
    subscriptions={[
      "consignation/escales",
      "tiers",
      "config/bandeau-info",
      "config/cotes",
    ]}
  />

  <CoteCesson />
  <BandeauInfo module="consignation" tv />

  <main class="divide-y">
    {#each [...($consignationEscales?.values() || [])] as escale (escale.id)}
      <LigneEscale {escale} />
    {/each}
  </main>
{/if}

<style>
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
