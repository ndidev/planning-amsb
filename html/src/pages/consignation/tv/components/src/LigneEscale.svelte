<script lang="ts">
  import { getContext } from "svelte";

  import { locale } from "@app/utils";

  import type { EscaleConsignation, Stores } from "@app/types";

  const { ports } = getContext<Stores>("stores");

  export let escale: EscaleConsignation;

  $: tonnageTotal = escale.marchandises.reduce(
    (sum, { tonnage_bl }) => sum + tonnage_bl,
    0
  );
  $: cubageTotal = escale.marchandises.reduce(
    (sum, { cubage_bl }) => sum + cubage_bl,
    0
  );
  $: nombreTotal = escale.marchandises.reduce(
    (sum, { nombre_bl }) => sum + nombre_bl,
    0
  );

  // @ts-expect-error
  $: statutEscale = (escale, obtenirStatutEscale());
  $: statutEscaleTexte = (() => {
    switch (statutEscale) {
      case "atsea":
        return "";

      case "arrived":
        return "Sur rade";

      case "berthed":
        return "À quai";

      case "inops":
        return "En opérations";

      case "completed":
        return "Terminé";

      case "departed":
        return "Parti";

      default:
        return "";
    }
  })();

  /**
   * Assignation classe à chaque escale en fonction de la date
   * afin de mettre en forme le planning
   */
  function obtenirStatutEscale() {
    let statut:
      | "atsea"
      | "arrived"
      | "berthed"
      | "inops"
      | "completed"
      | "departed" = "atsea";

    let eta = creerDateDepuisETX("eta");
    let etb = creerDateDepuisETX("etb");
    let ops = creerDateDepuisETX("ops");
    let etc = creerDateDepuisETX("etc");
    let etd = creerDateDepuisETX("etd");
    let maintenant = new Date();

    if (eta && eta < maintenant) {
      statut = "arrived";
    }

    if (etb && etb < maintenant) {
      statut = "berthed";
    }

    if (ops && ops < maintenant) {
      statut = "inops";
    }

    if (etc && etc < maintenant) {
      statut = "completed";
    }

    if (etd && etd < maintenant) {
      statut = "departed";
    }

    return statut;
  }

  /**
   * Crée un objet Date.
   *
   * @param etx Type de l'ET ('eta', 'etb', 'ops', 'etc', 'etd')
   *
   * @return Date au format 'YYYY-MM-DDTHH:MM'
   */
  function creerDateDepuisETX(etx: "eta" | "etb" | "ops" | "etc" | "etd") {
    if (!escale[`${etx}_date`]) return null;

    let date = escale[`${etx}_date`]; // Date mise au format 'YYYY-MM-DD'
    let heure = escale[`${etx}_heure`];
    let regexp_heure = /^((([01][0-9]|[2][0-3]):[0-5][0-9])|24:00)/;

    if (!regexp_heure.test(heure)) {
      heure = "00:00"; // Si heure non renseignée
    } else {
      heure = heure.substring(0, 5);
    }

    return new Date(date + "T" + heure);
  }
</script>

<div
  class={`escale pure-g ${statutEscale}`}
  class:treguier={escale.call_port === "Tréguier"}
  style:background-color={`var(--bg-${statutEscale}, white)`}
>
  <!-- Navire / Armateur -->
  <div class="navire-armateur bloc pure-u-5-24">
    <div class="navire">{escale.navire}</div>

    {#if escale.voyage}
      <div class="voyage">{`(escale n°${escale.voyage})`}</div>
    {/if}
  </div>

  <!-- Dates et heures -->
  <div class="etx bloc pure-u-3-24">
    <!-- ETA -->
    {#if statutEscale === "atsea"}
      <div class="eta">
        <span class="date">
          {escale.eta_date
            ? new Date(escale.eta_date).toLocaleDateString(locale)
            : ""}
        </span>
        <span class="heure">{escale.eta_heure}</span>
      </div>
    {:else}
      <div class="etape">
        {statutEscaleTexte}
      </div>
    {/if}
  </div>

  <!-- TE -->
  <div class="te bloc pure-u-2-24">
    <div>
      A : <span class="te_arrivee">
        {escale.te_arrivee
          ? escale.te_arrivee.toLocaleString(undefined, {
              minimumFractionDigits: 2,
            }) + " m"
          : ""}
      </span>
    </div>

    <div>
      D : <span class="te_depart">
        {escale.te_depart
          ? escale.te_depart.toLocaleString(undefined, {
              minimumFractionDigits: 2,
            }) + " m"
          : ""}
      </span>
    </div>
  </div>

  <!-- Ports -->
  <div class="ports bloc pure-u-3-24">
    <div class="last_port">
      {$ports?.find((port) => port.locode === escale.last_port)
        ?.nom_affichage || ""}
    </div>
    <div class="quai">
      {escale.quai}
    </div>
    <div class="next_port">
      {$ports?.find((port) => port.locode === escale.next_port)
        ?.nom_affichage || "À ordres"}
    </div>
  </div>

  <!-- Marchandises -->
  <div class="marchandises bloc pure-u-9-24">
    {#each escale.marchandises as marchandise}
      <div class="marchandise">
        <span class="environ">{marchandise.environ ? "~" : ""}</span>

        {#if marchandise.tonnage_bl}
          <span class="quantite tonnage">
            {marchandise.tonnage_bl.toLocaleString("fr-FR", {
              minimumFractionDigits: 3,
            }) + " MT"}
          </span>
        {/if}

        {#if marchandise.cubage_bl}
          <span class="quantite cubage">
            {marchandise.cubage_bl.toLocaleString("fr-FR", {
              minimumFractionDigits: 3,
            }) + " m3"}
          </span>
        {/if}

        {#if marchandise.nombre_bl}
          <span class="quantite nombre">
            {marchandise.nombre_bl.toLocaleString("fr-FR") + " colis"}
          </span>
        {/if}

        <span class="marchandise_nom">{marchandise.marchandise}</span>
      </div>
    {/each}

    {#if escale.marchandises.length > 1 && (tonnageTotal || cubageTotal || nombreTotal)}
      <div class="total pure-u-1">
        Total

        {#if tonnageTotal}
          <span class="quantite tonnage">
            {tonnageTotal.toLocaleString("fr-FR", {
              minimumFractionDigits: 3,
            }) + " MT"}
          </span>
        {/if}

        {#if cubageTotal}
          <span class="quantite cubage">
            {cubageTotal.toLocaleString("fr-FR", {
              minimumFractionDigits: 3,
            }) + " m3"}
          </span>
        {/if}

        {#if nombreTotal}
          <span class="quantite nombre">
            {nombreTotal.toLocaleString("fr-FR") + " colis"}
          </span>
        {/if}
      </div>
    {/if}
  </div>
</div>

<style>
  * {
    --etx-actif-size: 1.3rem;
    --etx-actif-weight: normal;
    --etx-actif-transform: uppercase;
    --bg-arrived: hsla(60, 100%, 50%, 0.6);
    --bg-berthed: hsla(288, 100%, 39%, 0.8);
    --bg-inops: hsla(182, 100%, 62%, 0.6);
    --bg-completed: hsla(120, 100%, 50%, 0.6);
    --bg-departed: hsla(221, 100%, 50%, 0.8);
  }

  /* PLANNING */

  .escale {
    font-size: 1.2em;
    padding: 20px 0px 20px 20px;
    border-bottom: 1px solid #999;
  }

  .escale:last-child {
    margin-bottom: 50px;
    border-bottom: none;
  }

  .escale.treguier {
    color: #0d74ba;
  }

  .bloc {
    display: flex;
    flex-direction: column;
    justify-content: center;
    margin-left: 0.3em;
  }

  .etx :is(.date, .heure) {
    margin-left: 0.4em;
  }

  .navire {
    font-size: 1.6em;
  }

  .voyage {
    color: hsl(0, 0%, 39%);
    margin-top: 0.7em;
  }

  .ports.bloc {
    display: flex;
    flex-direction: column;
    justify-content: space-around;
    text-align: center;
  }

  .quai {
    margin: 5px 0;
    font-weight: bold;
  }

  .marchandise_nom {
    font-weight: bold;
  }

  .marchandise .cubage {
    font-style: italic;
  }

  .marchandise .quantite,
  .total .quantite {
    margin-left: 10px;
  }

  .marchandise span {
    margin-left: 0.7em;
  }

  :is(.marchandise, .total) .cubage {
    font-style: italic;
  }

  .total {
    margin-top: 5px;
  }

  /* Couleurs suivant statut escale */

  .eta,
  .etape {
    display: inline-block;
    font-size: var(--etx-actif-size);
    font-weight: var(--etx-actif-weight);
    text-transform: var(--etx-actif-transform);
  }

  .escale.berthed,
  .escale.berthed .voyage {
    color: #fff;
  }

  .escale.departed,
  .escale.departed .voyage {
    color: #fff;
  }
</style>
