<script lang="ts">
  import { LucideButton } from "@app/components";

  import type { QualiteVrac } from "@app/types";

  export let qualite: QualiteVrac;
  export let supprimerQualite: (qualite: QualiteVrac) => void;

  $: isNew = qualite.id === null;
</script>

<li class="ligne-qualite" class:new={isNew}>
  <label
    >Nom :
    <input
      type="text"
      name="nom"
      maxlength="255"
      data-nom="Nom de la qualité"
      bind:value={qualite.nom}
      required
    />
  </label>

  <label
    >Couleur :
    <input
      type="color"
      name="couleur"
      bind:value={qualite.couleur}
      class="couleur"
      required
    />
  </label>

  <div class="poubelle">
    <LucideButton
      preset="delete"
      title="Supprimer la qualité"
      on:click={() => supprimerQualite(qualite)}
    />
  </div>
</li>

<style>
  .ligne-qualite {
    padding: 8px;
    margin-block: 2px;
  }

  .poubelle {
    margin-left: 10px;
  }

  .new {
    border-radius: 10px;
    background-color: antiquewhite;
  }
</style>
