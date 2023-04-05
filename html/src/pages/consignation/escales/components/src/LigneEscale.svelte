<!-- 
  @component
  
  Ligne du planning de consignation de navires.

  Usage :
  ```tsx
  <LigneEscale escale: EscaleConsignation />
  ```
 -->
<script lang="ts">
  import { onMount, onDestroy, getContext } from "svelte";
  import { goto } from "@roxi/routify";

  import Hammer from "hammerjs";

  import { MaterialButton, Modal, BoutonAction } from "@app/components";

  import { Device } from "@app/utils";

  import type { EscaleConsignation, Stores } from "@app/types";

  const { currentUser, tiers, ports } = getContext<Stores>("stores");

  export let escale: EscaleConsignation;
  let ligne: HTMLDivElement;

  let mc: HammerManager;
  let afficherModal = false;

  const archives: boolean = getContext("archives");

  // @ts-expect-error
  $: statutEscale = (escale, archives ? null : obtenirStatutEscale());

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

  /**
   * Assignation classe à chaque escale en fonction de la date
   * afin de mettre en forme le planning.
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

  onMount(() => {
    mc = new Hammer(ligne);
    mc.on("press", () => {
      if (Device.is("mobile")) {
        afficherModal = true;
      }
    });
  });

  onDestroy(() => {
    mc.destroy();
  });
</script>

{#if afficherModal}
  <Modal on:outclick={() => (afficherModal = false)}>
    <div
      style:background="white"
      style:padding="20px"
      style:border-radius="20px"
    >
      <BoutonAction preset="modifier" on:click={$goto(`./${escale.id}`)} />
      <BoutonAction preset="annuler" on:click={() => (afficherModal = false)} />
    </div>
  </Modal>
{/if}

<div
  bind:this={ligne}
  class={`escale pure-g ${statutEscale}`}
  class:treguier={escale.call_port === "Tréguier"}
  style:--bg-color={`var(--bg-${statutEscale})`}
>
  <!-- Navire / Armateur -->
  <div class="navire-armateur bloc pure-u-lg-4-24 pure-u-1">
    <div class="navire">{escale.navire}</div>

    {#if escale.voyage}
      <div class="voyage">{`(escale n°${escale.voyage})`}</div>
    {/if}

    <div class="armateur">{$tiers?.get(escale.armateur)?.nom_court || ""}</div>
  </div>

  <!-- Dates et heures -->
  <div class="etx bloc pure-u-lg-4-24 pure-u-1">
    <!-- ETA -->
    <div class="eta etx" class:actif={statutEscale === "atsea"}>
      <span class="nom">ETA :</span>
      <span class="date">
        {escale.eta_date ? new Date(escale.eta_date).toLocaleDateString() : ""}
      </span>
      <span class="heure">{escale.eta_heure}</span>
    </div>

    <!-- ETB -->
    <div class="etb etx" class:actif={statutEscale === "arrived"}>
      <span class="nom">ETB :</span>
      <span class="date">
        {escale.etb_date ? new Date(escale.etb_date).toLocaleDateString() : ""}
      </span>
      <span class="heure">{escale.etb_heure}</span>
    </div>

    <!-- OPS -->
    <div class="ops etx" class:actif={statutEscale === "berthed"}>
      <span class="nom">Ops :</span>
      <span class="date">
        {escale.ops_date ? new Date(escale.ops_date).toLocaleDateString() : ""}
      </span>
      <span class="heure">{escale.ops_heure}</span>
    </div>

    <!-- ETC -->
    <div class="etc etx" class:actif={statutEscale === "inops"}>
      <span class="nom">ETC :</span>
      <span class="date">
        {escale.etc_date ? new Date(escale.etc_date).toLocaleDateString() : ""}
      </span>
      <span class="heure">{escale.etc_heure}</span>
    </div>

    <!-- ETD -->
    <div class="etd etx" class:actif={statutEscale === "completed"}>
      <span class="nom">ETD :</span>
      <span class="date">
        {escale.etd_date ? new Date(escale.etd_date).toLocaleDateString() : ""}
      </span>
      <span class="heure">{escale.etd_heure}</span>
    </div>
  </div>

  <!-- TE -->
  <div class="te bloc pure-u-lg-2-24 pure-u-1">
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
  <div class="ports bloc pure-u-lg-3-24 pure-u-1">
    <div class="last_port">
      {$ports?.find((port) => port.locode === escale.last_port)
        ?.nom_affichage || ""}
    </div>
    <div class="port-quai-escale">
      <div class="call-port">{escale.call_port}</div>
      <div class="quai">{escale.quai}</div>
    </div>
    <div class="next_port">
      {$ports?.find((port) => port.locode === escale.next_port)
        ?.nom_affichage || "À ordres"}
    </div>
  </div>

  <!-- Marchandises -->
  <div class="marchandises bloc pure-u-lg-9-24 pure-u-1">
    {#each escale.marchandises as marchandise}
      <div class="marchandise">
        <div class="marchandise-client pure-u-1">
          <span class="marchandise_nom">{marchandise.marchandise}</span>
          <span class="client">{marchandise.client}</span>
        </div>

        <div class="pure-u-1">
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
        </div>
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

  <!-- Copie / Modification / Suppression -->
  {#if $currentUser.canEdit("consignation")}
    <div class="copie-modif-suppr">
      <MaterialButton
        preset="modifier"
        on:click={$goto(`./${escale.id}${archives ? "?archives" : ""}`)}
      />
    </div>
  {/if}

  <!-- Commentaire -->
  {#if escale.commentaire}
    <div class="commentaire pure-u-1">
      {@html escale.commentaire.replace(/\r\n|\r|\n/g, "<br/>")}
    </div>
  {/if}
</div>

<style>
  * {
    --etx-actif-size: 1.1em;
    --etx-actif-weight: bold;
    --bg-arrived: rgba(255, 255, 0, 0.6);
    --bg-berthed: rgba(160, 0, 200, 0.8);
    --bg-inops: rgba(60, 250, 255, 0.6);
    --bg-completed: rgba(0, 255, 0, 0.6);
    --bg-departed: rgba(0, 80, 255, 0.8);
  }

  /* PLANNING */

  .escale {
    padding: 10px 0px 10px 20px;
    border-bottom: 1px solid #ddd;
    background-color: var(--bg-color, "white");
  }

  .escale:last-child {
    margin-bottom: 50px;
    border-bottom: none;
  }

  .escale:global(.treguier) {
    color: #0d74ba;
  }

  .bloc {
    display: flex;
    flex-direction: column;
    justify-content: center;
  }

  .etx :is(.date, .heure),
  .client {
    margin-left: 0.4em;
  }

  .navire {
    font-size: 1.5em;
    margin-bottom: 0.7em;
  }

  .voyage {
    color: rgb(100, 100, 100);
  }

  .ports.bloc {
    display: flex;
    flex-direction: column;
    justify-content: space-around;
  }

  .port-quai-escale {
    margin: 5px 0;
    font-weight: bold;
  }

  .marchandise_nom {
    font-weight: bold;
  }

  .marchandise .quantite,
  .total .quantite {
    margin-left: 10px;
  }

  :is(.marchandise, .total) .cubage {
    font-style: italic;
  }

  .total {
    margin-top: 5px;
  }

  /* Couleurs suivant statut escale */

  .etx.actif {
    font-size: var(--etx-actif-size);
    font-weight: var(--etx-actif-weight);
  }

  .escale.berthed,
  .escale.berthed .voyage {
    color: #fff;
  }

  .escale.departed,
  .escale.departed .voyage {
    color: #fff;
  }

  /* Mobile */
  @media screen and (max-width: 767px) {
    .escale {
      align-items: flex-start;
    }

    .navire-armateur {
      flex-direction: row;
      justify-content: flex-start;
      align-items: baseline;
      column-gap: 10px;
    }

    .bloc {
      margin: 3px 5px;
    }

    .escale:is(.departed) .etx {
      display: none;
    }

    .ports.bloc {
      text-align: left;
    }

    .call-port,
    .quai {
      display: inline;
    }

    .commentaire {
      margin-top: 10px;
      margin-left: 0;
    }
  }

  /* Desktop */
  @media screen and (min-width: 768px) {
    .escale {
      align-items: center;
    }

    .escale:hover {
      background-color: rgba(0, 0, 0, 0.1);
    }

    .escale:hover .copie-modif-suppr {
      visibility: visible;
    }

    .bloc {
      margin-left: 0.3em;
    }

    .escale.departed {
      display: none;
    }

    .ports.bloc {
      text-align: center;
    }

    .call-port,
    .quai {
      display: block;
    }

    .commentaire {
      margin-top: 10px;
      margin-left: 30px;
    }
  }
</style>
