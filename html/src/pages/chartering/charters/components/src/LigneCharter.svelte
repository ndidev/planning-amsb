<!-- 
  @component
  
  Ligne du planning d'affrètement maritime.

  Usage :
  ```tsx
  <LigneCharter charter: Charter />
  ```
 -->
<script lang="ts">
  import { onMount, onDestroy, getContext } from "svelte";
  import { goto } from "@roxi/routify";

  import Hammer from "hammerjs";

  import { MaterialButton, Modal, BoutonAction } from "@app/components";

  import { device } from "@app/utils";

  import type { Charter, Stores } from "@app/types";

  const { currentUser, tiers, ports } = getContext<Stores>("stores");

  export let charter: Charter;
  let ligne: HTMLDivElement;

  let mc: HammerManager;
  let afficherModal = false;

  const archives: boolean = getContext("archives");

  // @ts-expect-error
  $: statutCharter = (charter, archives ? null : obtenirStatutCharter());

  /**
   * Assignation d'une classe à chaque affrètement en fonction de son statut
   * afin de mettre en forme le planning.
   */
  function obtenirStatutCharter() {
    switch (charter.statut) {
      case 0:
        return "plannifie";

      case 1:
        return "confirme";

      case 2:
        return "affrete";

      case 3:
        return "charge";

      case 4:
        return "termine";

      default:
        return "";
    }
  }

  onMount(() => {
    mc = new Hammer(ligne);
    mc.on("press", () => {
      if ($device.is("mobile")) {
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
      <BoutonAction preset="modifier" on:click={$goto(`./${charter.id}`)} />
      <BoutonAction preset="annuler" on:click={() => (afficherModal = false)} />
    </div>
  </Modal>
{/if}

<div bind:this={ligne} class={`charter pure-g ${statutCharter}`}>
  <!-- Navire / Armateur -->
  <div class="navire-armateur bloc pure-u-lg-4-24 pure-u-1">
    <div class="navire">{charter.navire}</div>
    <div class="armateur">{$tiers?.get(charter.armateur)?.nom_court || ""}</div>
    <div class="affreteur">
      {$tiers?.get(charter.affreteur)?.nom_court || ""}
    </div>
    <div class="courtier">
      {$tiers?.get(charter.courtier)?.nom_court || ""}
    </div>
  </div>

  <!-- Dates -->
  <div class="etx bloc pure-u-lg-4-24 pure-u-1">
    <!-- Laycan début -->
    <div class="etx">
      <span>LC début :</span>
      <span class="lc_debut date">
        {charter.lc_debut
          ? new Date(charter.lc_debut).toLocaleDateString()
          : ""}
      </span>
    </div>
    <!-- Laycan fin -->
    <div class="etx">
      <span>LC fin :</span>
      <span class="lc_fin date">
        {charter.lc_fin ? new Date(charter.lc_fin).toLocaleDateString() : ""}
      </span>
    </div>
    <!-- C/P -->
    <div class="etx">
      <span>C/P :</span>
      <span class="cp date">
        {charter.cp_date ? new Date(charter.cp_date).toLocaleDateString() : ""}
      </span>
    </div>
  </div>

  <!-- Legs -->
  <div class="legs bloc pure-u-lg-12-24 pure-u-1">
    {#each charter.legs as leg}
      <div class="leg pure-u-1">
        <div>
          <span class="marchandise">{leg.marchandise}</span>
          <span class="quantite">{leg.quantite}</span>
          <span class="ports"
            >{$ports?.find(({ locode }) => leg.pol === locode)?.nom_affichage ||
              ""} > {$ports?.find(({ locode }) => leg.pod === locode)
              ?.nom_affichage || ""}</span
          >
        </div>
        {#if leg.commentaire}
          <div class="commentaire">
            {@html leg.commentaire.replace(/\r\n|\r|\n/g, "<br/>")}
          </div>
        {/if}
      </div>
    {/each}
  </div>

  <!-- Copie / Modification / Suppression -->
  {#if $currentUser.canEdit("chartering")}
    <div class="copie-modif-suppr">
      <MaterialButton
        preset="modifier"
        on:click={$goto(`./${charter.id}${archives ? "?archives" : ""}`)}
      />
    </div>
  {/if}

  <!-- Commentaire -->
  {#if charter.commentaire}
    <div class="commentaire pure-u-1">
      {@html charter.commentaire.replace(/\r\n|\r|\n/g, "<br/>")}
    </div>
  {/if}
</div>

<style>
  * {
    --status-bar-width: 10px;
    --status-color: transparent;
    --plannifie: hsla(0, 0%, 80%, 1);
    --confirme: hsl(210, 100%, 50%);
    --affrete: hsla(270, 100%, 50%, 1);
    --charge: hsla(30, 100%, 50%, 1);
    --termine: hsla(120, 100%, 50%, 1);
  }

  .charter {
    padding: 10px 0px 10px 20px;
    border-bottom: 1px solid #ddd;
    border-left: var(--status-bar-width) solid var(--status-color);
    align-items: flex-start;
  }

  .charter:first-child {
    margin-top: 40px;
  }

  .charter:last-child {
    margin-bottom: 40px;
    border-bottom: none;
  }

  .charter:hover {
    background-color: rgba(0, 0, 0, 0.1);
  }

  .charter:hover .copie-modif-suppr {
    visibility: visible;
  }

  .bloc {
    margin: 3px 5px;
  }

  .navire {
    font-size: 1.5em;
    margin-bottom: 10px;
    width: auto;
  }

  .etx .date {
    margin-left: 5px;
  }

  .commentaire {
    margin-top: 10px;
    margin-left: 0;
  }

  /** LEGS */
  .leg {
    margin-bottom: 10px;
  }

  .leg .marchandise {
    font-weight: bold;
  }

  .leg .quantite {
    margin-left: 10px;
  }

  .leg .ports {
    margin-left: 10px;
  }

  .leg .commentaire {
    margin: 5px 0 0 10px;
  }

  /* STATUT AFFRÈTEMENT */
  .charter.plannifie {
    --status-color: var(--plannifie);
  }

  .charter.confirme {
    --status-color: var(--confirme);
  }

  .charter.affrete {
    --status-color: var(--affrete);
  }

  .charter.charge {
    --status-color: var(--charge);
  }

  .charter.termine {
    --status-color: var(--termine);
  }

  /* Desktop */
  @media screen and (min-width: 768px) {
    .charter {
      align-items: center;
    }

    .bloc {
      margin-left: 10px;
    }

    .commentaire {
      margin-left: 30px;
    }
  }
</style>
