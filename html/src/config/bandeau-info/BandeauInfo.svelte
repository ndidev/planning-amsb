<!-- 
  @component
  
  Ensemble des lignes du bandeau d'information.

  Usage :
  ```tsx
  <BandeauInfo />
  ```
 -->
<script lang="ts">
  import LigneBandeauInfo from "./LigneBandeauInfo.svelte";
  import TitreSection from "../TitreSection.svelte";
  import TitreSousSection from "../TitreSousSection.svelte";
  import { Chargement } from "@app/components";

  import { currentUser, lignesBandeauInfo } from "@app/stores";

  import { sitemap } from "@app/utils";

  /**
   * @type {LigneBandeauInfo}
   */
  const modeleLigneInfo = {
    id: null,
    module: "",
    pc: 1,
    tv: 0,
    couleur: "#000000",
    message: "",
  };

  $: modulesAffiches = Object.keys($lignesBandeauInfo || {});

  /**
   * Ajouter une nouvelle ligne au bandeau d'information.
   *
   * @param module Module de la nouvelle ligne info
   */
  function ajouterLigneInfo(module: string) {
    lignesBandeauInfo.update((lignes) => {
      const nouvelleLigne: LigneBandeauInfo = structuredClone(modeleLigneInfo);
      nouvelleLigne.id = "new_" + Math.floor(Math.random() * 1e10);
      nouvelleLigne.module = module;

      lignes[module].push(nouvelleLigne);

      return lignes;
    });
  }
</script>

<TitreSection titre={"Bandeau d'informations"} />

{#if !$lignesBandeauInfo}
  <Chargement />
{:else}
  {#each modulesAffiches as module}
    {#if $currentUser.canEdit(module)}
      <TitreSousSection
        titre={sitemap.get(module).affichage}
        fonctionAjout={() => {
          ajouterLigneInfo(module);
        }}
      />

      <ul id="infos-{module}">
        {#each $lignesBandeauInfo[module] as ligneInfo (ligneInfo.id)}
          <LigneBandeauInfo {ligneInfo} />
        {:else}
          <li class="ligne-vide">Aucune ligne configurée.</li>
        {/each}
      </ul>
    {/if}
  {/each}
{/if}

<style>
</style>
