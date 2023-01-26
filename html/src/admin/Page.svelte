<script lang="ts">
  import { onMount, onDestroy } from "svelte";

  import LigneCompteUtilisateur from "./LigneCompteUtilisateur.svelte";
  import { Chargement, Menu } from "@app/components";

  import { comptesUtilisateurs, currentUser } from "@app/stores";

  import { AccountStatus } from "@app/auth";
  import { demarrerConnexionSSE } from "@app/utils";

  let source: EventSource;

  const modeleCompte: CompteUtilisateur = {
    uid: null,
    login: "",
    nom: "",
    statut: AccountStatus.PENDING,
    roles: {
      admin: 0,
      bois: 0,
      chartering: 0,
      config: 0,
      consignation: 0,
      tiers: 0,
      vrac: 0,
    },
    commentaire: "",
    last_connection: "",
    historique: "",
    self: false,
  };

  function ajouterCompte() {
    comptesUtilisateurs.update((comptes) => {
      const nouveauCompte = structuredClone(modeleCompte);
      nouveauCompte.uid = "new_" + Math.floor(Math.random() * 1e10);
      return [nouveauCompte, ...comptes];
    });
  }

  onMount(() => {
    source = demarrerConnexionSSE(["admin/users"]);
  });

  onDestroy(() => {
    source.close();
  });
</script>

{#if $currentUser.statut === AccountStatus.ACTIVE && $currentUser.isAdmin}
  <Menu />

  <main class="formulaire">
    <h1>Administration</h1>

    {#if !$comptesUtilisateurs}
      <Chargement />
    {:else}
      <div class="ajouter-compte">
        <button on:click={ajouterCompte}>Ajouter un compte</button>
      </div>

      <ul>
        {#each $comptesUtilisateurs as compte (compte.uid)}
          <LigneCompteUtilisateur {compte} />
        {/each}
      </ul>
    {/if}
  </main>
{:else}
  {(location.href = "/")}
{/if}

<style>
  @import "/src/css/commun.css";
  @import "/src/css/formulaire.css";

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
