<script lang="ts">
  import { onMount, onDestroy } from "svelte";

  import BandeauInfo from "./bandeau-info/BandeauInfo.svelte";
  import ConfigsPdf from "./pdf/ConfigsPDF.svelte";
  import RdvRapides from "./rdv-rapides/RdvRapides.svelte";
  import Agence from "./agence/Agence.svelte";
  import Cotes from "./cotes/Cotes.svelte";
  import Marees from "./marees/Marees.svelte";
  import { Menu } from "@app/components";

  import { currentUser } from "@app/stores";

  import { AccountStatus } from "@app/auth";
  import { demarrerConnexionSSE } from "@app/utils";

  let source: EventSource;

  const configs = [
    {
      id: "bandeau-info",
      affichage: "Bandeau d'informations",
      component: BandeauInfo,
    },
    {
      id: "pdf",
      affichage: "PDF",
      component: ConfigsPdf,
    },
    {
      id: "rdv-rapides",
      affichage: "RDVs rapides",
      component: RdvRapides,
    },
    {
      id: "agence",
      affichage: "Agence",
      component: Agence,
    },
    {
      id: "cotes",
      affichage: "Côtes",
      module: "consignation",
      component: Cotes,
    },
    {
      id: "marees",
      affichage: "Marées",
      module: "consignation",
      component: Marees,
    },
  ];

  let selected = configs[0];

  onMount(() => {
    source = demarrerConnexionSSE([
      "config/bandeau-info",
      "config/pdf",
      "config/rdvrapides",
      "config/agence",
      "config/cotes",
      "marees",
    ]);
  });

  onDestroy(() => {
    source.close();
  });
</script>

{#if $currentUser.statut === AccountStatus.ACTIVE && $currentUser.canAccess("config")}
  <Menu />

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

    <svelte:component this={selected.component} />
  </main>
{:else}
  {(location.href = "/")}
{/if}

<style>
  @import "../css/commun.css";

  @import "../css/awesomplete.css";
  @import "../css/formulaire.css";

  main {
    width: 90%;
    margin: auto;
  }

  /* En-têtes */

  h1 {
    margin: 20px 0;
    text-align: center;
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

  :global(input[type="text"]),
  :global(input:not([type])) {
    width: initial;
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
