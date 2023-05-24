<!-- routify:options title="Planning AMSB - Configuration" -->
<script lang="ts">
  import {
    BandeauInfo,
    ConfigsPDF,
    AjoutsRapides,
    Agence,
    Cotes,
    Marees,
  } from "./components";

  import { currentUser } from "@app/stores";

  import { ConnexionSSE } from "@app/components";

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
</script>

<!-- routify:options guard="config" -->

<ConnexionSSE
  subscriptions={[
    "config/bandeau-info",
    "config/pdf",
    "config/ajouts-rapides",
    "config/agence",
    "config/cotes",
    "marees",
  ]}
/>

<main>
  <h1>Configuration</h1>

  <div id="select">
    <select bind:value={selected}>
      {#each configs as config}
        {#if !config.module || (config.module && $currentUser.canEdit(config.module))}
          <option value={config}>
            {config.affichage}
          </option>
        {/if}
      {/each}
    </select>
  </div>

  <h2 class="titre-section">{selected.affichage}</h2>

  <svelte:component this={selected.component} />
</main>

<style>
  main {
    width: 90%;
    margin: auto;
  }

  /* En-têtes */

  h1 {
    margin: 20px 0;
    text-align: center;
  }

  .titre-section {
    margin: 20px 10% 5px 10%;
    color: #333;
  }

  #select {
    text-align: center;
  }

  :global(.ligne) {
    width: 80%;
    margin: 5px auto;
    padding: 10px;
    border-radius: 5px;
    border: 2px solid #ddd;
    background-color: #eee;
    display: flex;
    align-items: center;
  }

  :global(.ligne-vide) {
    width: 80%;
    margin: 5px auto;
    padding: 10px;
    border-radius: 5px;
    display: flex;
    align-items: center;
  }

  :global(.modificationEnCours) {
    background-color: lightyellow;
  }

  :global(.modificationEnCours .valider-annuler) {
    display: inline;
  }

  :global(.modificationEnCours .actions) {
    display: none;
  }

  :global(.valider-annuler) {
    margin-left: auto;
    color: rgba(0, 0, 0, 0.3);
    display: none;
  }

  :global(.actions) {
    margin-left: auto;
    color: rgba(0, 0, 0, 0.3);
    display: inline;
  }

  :global(.help),
  :global(.actions i),
  :global(.valider-annuler i) {
    cursor: pointer;
  }

  :global(.visualiser:hover),
  :global(.envoyer:hover),
  :global(.valider:hover),
  :global(.annuler:hover) {
    color: rgba(0, 0, 0, 0.8);
  }

  @media screen and (max-width: 480px) {
    :global(.ligne) {
      flex-direction: column;
      width: 100%;
    }

    :global(input[type="text"]),
    :global(input:not([type])) {
      width: 100%;
    }
  }
</style>
