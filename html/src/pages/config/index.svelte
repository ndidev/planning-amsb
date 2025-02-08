<!-- routify:options title="Planning AMSB - Configuration" -->
<script lang="ts">
  import { Heading, Select } from "flowbite-svelte";

  import {
    BandeauInfo,
    ConfigsPDF,
    AjoutsRapides,
    Agence,
    Cotes,
    Marees,
  } from "./components";

  import {
    currentUser,
    configBandeauInfo,
    configPdf,
    configAjoutsRapides,
    marees,
  } from "@app/stores";

  import { PageHeading, SseConnection } from "@app/components";

  import type { ModuleId } from "@app/types";

  const configs = [
    {
      id: "bandeau-info",
      affichage: "Bandeau d'informations",
      component: BandeauInfo,
    },
    {
      id: "pdf",
      affichage: "PDF",
      component: ConfigsPDF,
    },
    {
      id: "ajouts-rapides",
      affichage: "Ajouts rapides",
      component: AjoutsRapides,
    },
    {
      id: "agence",
      affichage: "Agence",
      component: Agence,
    },
    {
      id: "cotes",
      affichage: "Côtes (consignation)",
      module: "consignation" as ModuleId,
      component: Cotes,
    },
    {
      id: "marees",
      affichage: "Marées",
      module: "consignation" as ModuleId,
      component: Marees,
    },
  ];

  let selected = configs[0];

  // Si l'utilisateur voit son autorisation modifiée en temps réel,
  // afficher l'écran par défaut (bandeau info = non soumis à un module)
  $: {
    if (selected.module && !$currentUser.canEdit(selected.module)) {
      selected = configs[0];
    }
  }
</script>

<!-- routify:options guard="config" -->

<SseConnection
  subscriptions={[
    configBandeauInfo.endpoint,
    configPdf.endpoint,
    configAjoutsRapides.endpoint,
    "config/agence",
    "config/cotes",
    marees.endpoint,
  ]}
/>

<main class="w-7/12 mx-auto">
  <PageHeading>Configuration</PageHeading>

  <div id="select" class="text-center mb-8">
    <Select bind:value={selected} placeholder="">
      {#each configs as config}
        {#if !config.module || (config.module && $currentUser.canEdit(config.module))}
          <option value={config}>
            {config.affichage}
          </option>
        {/if}
      {/each}
    </Select>
  </div>

  <Heading tag="h2" class="mb-4">{selected.affichage}</Heading>

  <svelte:component this={selected.component} />
</main>
