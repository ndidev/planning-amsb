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
  <section class="bandeau-info" style:margin-left={tv ? "0px" : "90px"}>
    <div
      class="ligne-bandeau-info"
      style:background-color={bgColor}
      style:color={couleurTexte}
    >
      Côte Cesson : {coteCesson.toFixed(2)} m
    </div>
  </section>
{/if}

<style>
  .bandeau-info {
    position: sticky;
    top: 0px;
    z-index: 1;
    font-size: 1.2em;
  }

  .ligne-bandeau-info {
    padding: 5px;
  }

  @media screen and (max-width: 480px) {
    .bandeau-info {
      margin-left: 65px;
      font-size: 0.8em;
    }
  }
</style>
