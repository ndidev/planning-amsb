<script lang="ts">
  import { DateUtils } from "@app/utils";
  import type { EscaleConsignation } from "@app/types";

  export let escale: EscaleConsignation;
</script>

<li class="card">
  <div class="navire">
    <a href="/consignation/escales/{escale.id}">{escale.navire}</a>
  </div>
  <div>
    {new DateUtils(escale.ops_date).format().long} &gt; {new DateUtils(
      escale.etc_date
    ).format().long}
  </div>

  <ul class="marchandises">
    {#each escale.marchandises as marchandise}
      <li>
        <div class="marchandise-client pure-u-1">
          <span class="marchandise_nom">{marchandise.marchandise}</span>
          <span class="client">{marchandise.client}</span>

          {#if marchandise.tonnage_outturn}
            <span class="quantite tonnage">
              {marchandise.tonnage_outturn.toLocaleString("fr-FR", {
                minimumFractionDigits: 3,
              }) + " MT"}
            </span>
          {/if}

          {#if marchandise.cubage_outturn}
            <span class="quantite cubage">
              {marchandise.cubage_outturn.toLocaleString("fr-FR", {
                minimumFractionDigits: 3,
              }) + " m3"}
            </span>
          {/if}

          {#if marchandise.nombre_outturn}
            <span class="quantite nombre">
              {marchandise.nombre_outturn.toLocaleString("fr-FR") + " colis"}
            </span>
          {/if}
        </div>
      </li>
    {:else}
      <li>Aucune marchandise</li>
    {/each}
  </ul>
</li>

<style>
  .card {
    border: 2px solid lightgray;
    border-radius: 4px;
    padding: 1rem;
    margin-block: 1rem;
  }

  .navire {
    font-weight: bold;
    margin-bottom: 0.3rem;
  }

  .navire a,
  .navire a:visited {
    color: black;
    text-decoration: none;
  }

  .navire a:hover {
    text-decoration: underline;
  }

  .marchandises {
    margin-top: 0.5rem;
  }

  .marchandises > li {
    list-style-type: none;
    margin-left: 1rem;
  }

  .client {
    margin-left: 0.5rem;
  }

  .quantite {
    margin-left: 1rem;
  }
</style>
