<!-- 
  @component
  
  Affichage de la côte de Cesson.

  La valeur de la côte est modifiable par l'utilisateur via "Configuration".

  Usage :
  ```tsx
  <CoteCesson />
  ```
 -->
<script lang="ts">
  import { configCotes } from "@app/stores";

  import { luminance } from "@app/utils";

  import type { Cote } from "@app/types";

  export let tv: boolean = false;

  $: coteCesson = $configCotes?.find((cote: Cote) => cote.cote === "cesson")
    .valeur as number;

  $: bgColor = calculerCouleurLigne(coteCesson);
  $: couleurTexte = luminance.getTextColor(bgColor);

  /**
   * Calcule la couleur d'arrière-plan en fonction de la valeur de la côte.
   *
   * @param cote Valeur de la côte
   *
   * @returns Couleur d'arrière-plan au format HSL
   */
  function calculerCouleurLigne(cote: number): string {
    const valeur = cote;

    // Couleur d'arrière-plan
    // De vert à rouge en fonction de la valeur
    // Côtes min et max pour l'échelle de couleurs
    const min = 4.8; // Vert
    const max = 6; // Rouge

    // Teinte (hue) entre 0 et 120
    // avec 0 = vert (côte 4m ou moins) et 120 = rouge (cote 6m ou plus)
    // (inversé par rapport au cercle chromatique normal)
    const hue =
      Math.abs(1 - (Math.min(Math.max(valeur, min), max) - min) / (max - min)) *
      120;
    const saturation = "90%";
    const lightness = "50%";

    return `hsl(${hue}, ${saturation}, ${lightness})`;
  }
</script>

{#if coteCesson}
  <div class="text-xs lg:ml-0 lg:text-xl">
    <div
      class="p-1 lg:p-2"
      style:background-color={bgColor}
      style:color={couleurTexte}
    >
      Côte Cesson : {coteCesson.toFixed(2)} m
    </div>
  </div>
{/if}
