<!-- 
  @component
  
  Ensemble des RDVs rapides.

  Usage :
  ```tsx
  <RdvRapides />
  ```
 -->
<script lang="ts">
  import LigneRdvRapideBois from "./LigneRdvRapideBois.svelte";
  import TitreSection from "../TitreSection.svelte";
  import TitreSousSection from "../TitreSousSection.svelte";
  import { Chargement } from "@app/components";

  import { currentUser, rdvRapides } from "@app/stores";

  import { sitemap } from "@app/utils";

  const modelesRdvRapide = {
    bois: {
      id: null,
      module: "bois",
      fournisseur: null,
      transporteur: null,
      affreteur: null,
      chargement: 1,
      client: null,
      livraison: null,
    },
  };

  const components = {
    bois: LigneRdvRapideBois,
  };

  $: modulesAffiches = Object.keys($rdvRapides || {});

  /**
   * Ajouter une nouvelle ligne au bandeau d'information.
   *
   * @param module Module de la nouvelle ligne info
   */
  function ajouterLigne(module: string) {
    rdvRapides.update((lignes) => {
      const nouvelleLigne = structuredClone(modelesRdvRapide[module]);
      nouvelleLigne.id = "new_" + Math.floor(Math.random() * 1e10);
      nouvelleLigne.module = module;

      lignes[module].push(nouvelleLigne);

      return lignes;
    });
  }
</script>

<TitreSection titre={"RDVs rapides"} />

{#if !$rdvRapides}
  <Chargement />
{:else}
  {#each modulesAffiches as module}
    {#if $currentUser.canEdit(module)}
      <TitreSousSection
        titre={sitemap.get(module).affichage}
        fonctionAjout={() => {
          ajouterLigne(module);
        }}
      />
      <ul id="rdv-rapides-{module}">
        {#each $rdvRapides[module] as rdvRapide (rdvRapide.id)}
          <svelte:component this={components[module]} {rdvRapide} />
        {:else}
          <li class="ligne-vide">Aucun RDV rapide configuré.</li>
        {/each}
      </ul>
    {/if}
  {/each}
{/if}

<style></style>
