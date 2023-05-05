<!-- routify:options title="Planning AMSB - Administration" -->
<script lang="ts">
  import { onMount, onDestroy, getContext } from "svelte";

  import { LigneCompteUtilisateur } from "./components";
  import { Chargement } from "@app/components";

  import { demarrerConnexionSSE } from "@app/utils";

  import type { Stores } from "@app/types";

  const { adminUsers } = getContext<Stores>("stores");

  let source: EventSource;

  onMount(async () => {
    source = await demarrerConnexionSSE(["admin/users"]);
  });

  onDestroy(() => {
    source.close();
  });
</script>

<!-- routify:options guard="admin" -->

<main class="formulaire">
  <h1>Administration</h1>

  <div class="ajouter-compte">
    <button on:click={() => adminUsers.new()}>Ajouter un compte</button>
  </div>

  {#if $adminUsers.size === 0}
    <Chargement />
  {:else}
    <ul>
      {#each [...$adminUsers.values()] as compte (compte.uid)}
        <LigneCompteUtilisateur {compte} />
      {/each}
    </ul>
  {/if}
</main>

<style>
  ul {
    margin-bottom: 50px;
  }

  .ajouter-compte {
    display: flex;
  }

  .ajouter-compte > button {
    align-self: center;
    margin: 10px auto 15px;
    padding: 7px;
    cursor: pointer;
  }
</style>
