<!-- 
  @component
  
  Ligne de date du planning bois.

  Usage :
  ```tsx
  <LigneDate
    date: string={"yyyy-mm-dd"}
    camions: CamionsParDate />
  ```
 -->
<script lang="ts">
  import type { CamionsParDate } from "@app/types";

  export let date: string;
  export let camions: CamionsParDate = {
    total: 0,
    attendus: 0,
    sur_parc: 0,
    charges: 0,
  };

  const formattedDate = new Date(date).toLocaleDateString("fr-FR", {
    weekday: "long",
    year: "numeric",
    month: "long",
    day: "numeric",
  });
</script>

<div>
  <div class="date">{formattedDate}</div>

  <div class="camions-stats">
    <div class="total">
      <span class="camions">
        {camions.total}
      </span>
      <span class="tooltip-camions">Total</span>
    </div>

    <div class="attendus">
      <span class="camions">
        {camions.attendus}
      </span>
      <span class="tooltip-camions">Attendus</span>
    </div>

    <div class="sur_parc">
      <span class="camions">
        {camions.sur_parc}
      </span>
      <span class="tooltip-camions">Sur parc</span>
    </div>

    <div class="charges">
      <span class="camions">
        {camions.charges}
      </span>
      <span class="tooltip-camions">Chargés</span>
    </div>
  </div>
</div>

<style>
  .date {
    color: rgb(88, 200, 95);
    margin-top: 30px;
    margin-bottom: 15px;
  }

  /* CAMIONS */

  .date,
  .camions-stats {
    display: inline-block;
  }

  .camions-stats {
    margin-left: 20px;
    border: 1px solid black;
    border-radius: 6px;
  }

  .camions-stats .total,
  .camions-stats .attendus,
  .camions-stats .sur_parc,
  .camions-stats .charges {
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

  .camions-stats .attendus,
  .camions-stats .attendus .tooltip-camions {
    color: var(--couleur-attendus);
  }

  .camions-stats .attendus .tooltip-camions {
    border-color: var(--couleur-attendus);
  }

  .camions-stats .attendus .tooltip-camions::after {
    border-color: var(--couleur-attendus) transparent transparent transparent;
  }

  .camions-stats .sur_parc,
  .camions-stats .sur_parc .tooltip-camions {
    color: var(--couleur-parc);
  }

  .camions-stats .sur_parc .tooltip-camions {
    border-color: var(--couleur-parc);
  }

  .camions-stats .sur_parc .tooltip-camions::after {
    border-color: var(--couleur-parc) transparent transparent transparent;
  }

  .camions-stats .charges,
  .camions-stats .charges .tooltip-camions {
    color: var(--couleur-charges);
  }

  .camions-stats .charges .tooltip-camions {
    border-color: var(--couleur-charges);
  }

  .camions-stats .charges .tooltip-camions::after {
    border-color: var(--couleur-charges) transparent transparent transparent;
  }

  /* Décompte camions + tooltip */
  .camions-stats .total,
  .camions-stats .attendus,
  .camions-stats .sur_parc,
  .camions-stats .charges {
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

  .camions-stats .total:hover .tooltip-camions,
  .camions-stats .attendus:hover .tooltip-camions,
  .camions-stats .sur_parc:hover .tooltip-camions,
  .camions-stats .charges:hover .tooltip-camions {
    visibility: visible;
  }
</style>
