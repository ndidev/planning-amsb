<!-- 
  @component
  
  Titre de section (`<h3>`) + bouton d'ajout de ligne (optionnel).

  Usage :
  ```tsx
  <TitreSousSection
    titre: string="Sous section"
    fonctionAjout: Function=() => {}
  />
  ```
 -->
<script lang="ts">
  import { Heading } from "flowbite-svelte";
  // import { PlusIcon } from "lucide-svelte";
  import PlusIcon from "lucide-svelte/icons/plus";

  /**
   * Titre de la sous-section.
   */
  export let titre: string = "Sous section";

  /**
   * Fonction à exécuter pour ajouter une ligne.
   */
  export let fonctionAjout: Function = undefined;
</script>

<div class="border-b-[1px] border-gray-500">
  <Heading tag="h3" class="inline">{titre}</Heading>

  {#if fonctionAjout}
    <button
      title="Ajouter une ligne"
      class="text-base font-normal bg-transparent ml-2 border-none cursor-pointer"
      on:click={() => fonctionAjout()}
    >
      Ajouter une ligne
      <i class="icon align-middle" title="Ajouter une ligne"
        ><PlusIcon class="inline" /></i
      >
    </button>
  {/if}
</div>
