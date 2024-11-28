<!-- 
  @component
  
  Ligne de date d'attente du planning bois.

  Usage :
  ```tsx
  <LigneDateAttente
    camions: CamionsParDate />
  ```
 -->
<script lang="ts">
  import type { CamionsParDate } from "@app/types";

  export let camions: CamionsParDate = {
    total: 0,
  };
</script>

<div class="mb-2 mt-5 w-full first:mt-12 lg:mb-4 lg:mt-8 lg:text-xl">
  <div class="inline-block text-green-400">En attente de confirmation</div>

  <div class="camions-stats inline-block">
    <div class="total">
      <span class="camions">
        {camions.total}
      </span>
      <span class="tooltip-camions">Total</span>
    </div>
  </div>
</div>

<style>
  /* CAMIONS */

  .camions-stats {
    margin-left: 20px;
    border: 1px solid black;
    border-radius: 6px;
  }

  .camions-stats .total {
    margin: 0 5px;
    font-weight: bold;
  }

  .camions-stats .total,
  .camions-stats .total .tooltip-camions {
    color: var(--couleur-total);
  }

  .camions-stats .total .tooltip-camions {
    border-color: var(--couleur-total);
  }

  .camions-stats .total .tooltip-camions::after {
    border-color: var(--couleur-total) transparent transparent transparent;
  }

  /* Décompte camions + tooltip */
  .camions-stats .total {
    position: relative;
    display: inline-block;
  }

  .tooltip-camions {
    visibility: hidden;
    background-color: white;
    text-align: center;
    padding: 5px 0;
    border-radius: 6px;
    border-width: 1px;
    border-style: solid;

    /* Position the tooltip text - see examples below! */
    position: absolute;
    z-index: 1;
    width: 100px;
    bottom: 130%;
    left: 50%;
    margin-left: -50px; /* Use half of the width (120/2 = 60), to center the tooltip */
  }

  .tooltip-camions::after /* Flêche du tooltip */ {
    content: " ";
    position: absolute;
    top: 100%; /* At the bottom of the tooltip */
    left: 50%;
    margin-left: -5px;
    border-width: 5px;
    border-style: solid;
  }

  .camions-stats .total:hover .tooltip-camions {
    visibility: visible;
  }
</style>
