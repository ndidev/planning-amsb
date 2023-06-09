<!-- routify:options title="Planning AMSB - Administration" -->
<script lang="ts">
  import { LigneCompteUtilisateur } from "./components";
  import { Chargement, ConnexionSSE } from "@app/components";
  import { adminUsers } from "@app/stores";

  $: comptes = [...$adminUsers.values()].sort((a, b) =>
    a.login < b.login ? -1 : 1
  );
</script>

<!-- routify:options guard="admin" -->

<ConnexionSSE subscriptions={["admin/users"]} />

<main class="formulaire">
  <h1>Administration</h1>

  <div class="ajouter-compte">
    <button on:click={() => adminUsers.new()}>Ajouter un compte</button>
  </div>

  {#if $adminUsers.size === 0}
    <Chargement />
  {:else}
    <ul>
      {#each comptes as compte (compte.uid)}
        <LigneCompteUtilisateur id={compte.uid} />
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
