<script lang="ts">
  import { Label, Input } from "flowbite-svelte";

  import { LucideButton } from "@app/components";

  import type { QualiteVrac } from "@app/types";

  export let qualite: QualiteVrac;
  export let supprimerQualite: (qualite: QualiteVrac) => void;
  export let nombreRdv = 0;

  $: isNew = qualite.id === null;
</script>

<li class="p-2 my-1 flex flex-col lg:flex-row gap-3" class:new={isNew}>
  <div>
    <Label for={"nom_" + qualite.id}>Nom</Label>
    <Input
      type="text"
      id={"nom_" + qualite.id}
      name="nom"
      maxlength={255}
      data-nom="Nom de la qualité"
      bind:value={qualite.nom}
      required
    />
  </div>

  <div>
    <Label>Couleur</Label>
    <Input
      type="color"
      name="couleur"
      bind:value={qualite.couleur}
      class="min-h-10 w-full lg:w-20 p-1"
      required
    />
  </div>

  <div class="ms-3 self-center">
    <LucideButton
      preset="delete"
      title={nombreRdv > 0
        ? `La qualité est concernée par ${nombreRdv} rdv. Impossible de la supprimer.`
        : "Supprimer la qualité"}
      disabled={nombreRdv > 0}
      on:click={() => supprimerQualite(qualite)}
    />
  </div>
</li>

<style>
  .new {
    border-radius: 10px;
    background-color: antiquewhite;
  }
</style>
