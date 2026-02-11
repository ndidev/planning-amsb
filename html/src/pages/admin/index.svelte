<!-- routify:options title="Planning AMSB - Administration" -->
<script lang="ts">
  import { Accordion, Button } from "flowbite-svelte";

  import { LigneCompteUtilisateur } from "./components";
  import { Chargement, PageHeading, SseConnection } from "@app/components";
  import { adminUsers } from "@app/stores/src/adminUsers";

  $: comptes = [...$adminUsers.values()].sort((a, b) =>
    a.login < b.login ? -1 : 1,
  );
</script>

<!-- routify:options guard="admin" -->

<SseConnection subscriptions={[adminUsers.endpoint]} />

<main class="mx-auto w-11/12 lg:w-7/12">
  <PageHeading>Administration</PageHeading>

  <div class="text-center mb-4">
    <Button on:click={() => adminUsers.new()}>Ajouter un compte</Button>
  </div>

  {#if $adminUsers.size === 0}
    <Chargement />
  {:else}
    <Accordion multiple>
      {#each comptes as compte (compte.uid)}
        <LigneCompteUtilisateur id={compte.uid} />
      {/each}
    </Accordion>
  {/if}
</main>
