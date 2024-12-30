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
    {#each [] as escale (escale.id)}
      <LigneEscale {escale} />
    {:else}
      <div class="grid w-full h-[90svh] place-items-center">
        <div class="text-3xl">Aucun navire à l'horizon...</div>
      </div>
    {/each}
  </main>
{/if}

<style>
  ::-webkit-scrollbar {
    display: none;
  }
</style>
