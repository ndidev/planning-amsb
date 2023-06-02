<!-- 
  @component
  
  Ligne de RDV du planning vrac.

  Usage :
  ```tsx
  <LigneRdv rdv: RdvVrac={rdv} />
  ```
 -->
<script lang="ts">
  import { onMount, onDestroy, getContext } from "svelte";

  import { goto } from "@roxi/routify";

  import Notiflix from "notiflix";
  import Hammer from "hammerjs";

  import { MaterialButton, BoutonAction, Modal } from "@app/components";

  import { notiflixOptions, device } from "@app/utils";
  import type {
    Stores,
    RdvVrac,
    ProduitVrac,
    QualiteVrac,
    Tiers,
  } from "@app/types";

  // Stores
  const { currentUser, vracProduits, vracRdvs, tiers } =
    getContext<Stores>("stores");

  export let rdv: RdvVrac;
  let ligne: HTMLDivElement;

  let mc: HammerManager;
  let afficherModal = false;

  const tiersVierge: Partial<Tiers> = {
    nom_court: "",
  };

  const produitVierge: Partial<ProduitVrac> = {
    nom: "",
    couleur: "#000000",
    qualites: [],
  };

  const qualiteVierge: Partial<QualiteVrac> = {
    nom: "",
    couleur: "#000000",
  };

  $: client = $tiers?.get(rdv.client) || { ...tiersVierge };

  $: transporteur = $tiers?.get(rdv.transporteur) || { ...tiersVierge };

  $: produit = $vracProduits?.get(rdv.produit) || { ...produitVierge };

  $: qualite = produit.qualites.find(
    (qualite) => qualite.id === rdv.qualite
  ) || { ...qualiteVierge };

  /**
   * Supprimer le RDV.
   */
  function supprimerRdv() {
    Notiflix.Confirm.show(
      "Suppression RDV",
      "Voulez-vous vraiment supprimer le RDV ?",
      "Supprimer",
      "Annuler",
      async function () {
        try {
          Notiflix.Block.dots([ligne], notiflixOptions.texts.suppression);
          ligne.style.minHeight = "initial";

          vracRdvs.delete(rdv.id);

          Notiflix.Notify.success("Le RDV a été supprimé");
        } catch (erreur) {
          Notiflix.Notify.failure(erreur.message);
          Notiflix.Block.remove([ligne]);
        }
      },
      null,
      notiflixOptions.themes.red
    );

    afficherModal = false;
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
      <BoutonAction preset="modifier" on:click={$goto(`./${rdv.id}`)} />
      <BoutonAction preset="copier" on:click={$goto(`./new?copie=${rdv.id}`)} />
      <BoutonAction preset="supprimer" on:click={supprimerRdv} />
      <BoutonAction preset="annuler" on:click={() => (afficherModal = false)} />
    </div>
  </Modal>
{/if}

<div class="rdv pure-g" bind:this={ligne}>
  <div class="heure pure-u-lg-1-24 pure-u-4-24">{rdv.heure ?? ""}</div>

  <div class="produit-qualite pure-u-lg-4-24 pure-u-12-24">
    <span class="produit" style:color={produit.couleur}>{produit.nom}</span>
    {#if rdv.qualite}
      <span class="qualite" style:color={qualite.couleur}>{qualite.nom}</span>
    {/if}
  </div>

  <div class="quantite-unite pure-u-lg-2-24 pure-u-6-24">
    <span class="quantite">{rdv.quantite}</span>
    <span class="unite">{produit.unite}</span>
    <span class="max">{rdv.max ? "max" : ""}</span>
  </div>

  <div class="client pure-u-lg-8-24 pure-u-1">
    {client.nom_court}
    {client.ville}
  </div>
  <div class="transporteur pure-u-lg-3-24 pure-u-1">
    {transporteur.nom_court}
  </div>

  <div class="num_commande pure-u-lg-3-24 pure-u-12-24">{rdv.num_commande}</div>

  {#if $currentUser.canEdit("vrac")}
    <div class="copie-modif-suppr">
      <MaterialButton
        preset="copier"
        on:click={() => {
          $goto(`./new?copie=${rdv.id}`);
        }}
      />
      <MaterialButton
        preset="modifier"
        on:click={() => {
          $goto(`./${rdv.id}`);
        }}
      />
      <MaterialButton preset="supprimer" on:click={supprimerRdv} />
    </div>
  {/if}

  <div class="pure-u-lg-5-24">
    <!-- Espacement -->
  </div>
  <div class="commentaire pure-u-lg-18-24 pure-u-1">{rdv.commentaire}</div>
  <!-- <hr /> -->
</div>

<style>
  .rdv {
    border-bottom: 1px solid #ddd;
  }

  .rdv:last-child {
    border-bottom: none;
  }

  .heure,
  .produit-qualite,
  .quantite-unite,
  .client,
  .transporteur,
  .num_commande {
    display: inline-block;
  }

  .qualite,
  .unite,
  .max {
    margin-left: 0.3em;
  }

  .heure {
    font-weight: bold;
    color: #d91ffa;
  }

  .produit-qualite,
  .quantite,
  .transporteur {
    font-weight: bold;
  }

  /* Mobile */
  @media screen and (max-width: 767px) {
    .rdv {
      padding: 8px 0;
    }

    .produit-qualite {
      margin-left: 0;
    }

    .client,
    .transporteur,
    .num_commande,
    .commentaire {
      margin-left: 16.667%;
    }

    .quantite-unite {
      margin-left: auto;
      text-align: right;
    }
  }

  /* Desktop */
  @media screen and (min-width: 768px) {
    .rdv {
      font-size: 1.2rem;
      padding: 8px 0 8px 5px;
    }

    .rdv:hover {
      background-color: rgba(0, 0, 0, 0.1);
    }

    .rdv:hover > .copie-modif-suppr {
      visibility: visible;
      margin-right: 10px;
    }

    .produit-qualite,
    .client,
    .transporteur,
    .num_commande,
    .commentaire {
      margin-left: 10px;
    }
  }
</style>
