<!-- routify:options title="Planning AMSB - Consignation" -->
<script lang="ts">
  import { BandeauInfo, CoteCesson, SseConnection } from "@app/components";
  import { LigneEscale } from "./components";

  import {
    currentUser,
    consignationEscales,
    tiers,
    configBandeauInfo,
  } from "@app/stores";
</script>

{#if $currentUser.canUseApp && $currentUser.canAccess("consignation")}
  <SseConnection
    subscriptions={[
      consignationEscales.endpoint,
      tiers.endpoint,
      configBandeauInfo.endpoint,
      "config/cotes",
    ]}
  />

  <CoteCesson />
  <BandeauInfo module="consignation" tv />

  <main class="divide-y">
    {#await consignationEscales.getReadyState()}
      <div class="grid w-full h-[90svh] place-items-center">
        <div class="text-3xl">Chargement...</div>
      </div>
    {:then}
      {#each [...$consignationEscales.values()] as call (call.id)}
        <LigneEscale escale={call} />
      {:else}
        <div class="grid w-full h-[90svh] place-items-center">
          <div class="text-3xl">Aucun navire à l'horizon...</div>
        </div>
      {/each}
    {:catch error}
      <div class="grid w-full h-[90svh] place-items-center">
        <div class="text-3xl text-red-500">Erreur de chargement</div>
      </div>
    {/await}
  </main>
{/if}

<style>
  ::-webkit-scrollbar {
    display: none;
  }
</style>
