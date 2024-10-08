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
   * Renseigner commande prête en cliquant sur l'icône paquet.
   */
  async function renseignerCommandePrete() {
    try {
      await vracRdvs.patch(rdv.id, {
        commande_prete: !rdv.commande_prete,
      });
    } catch (err) {
      Notiflix.Notify.failure(err.message);
    } finally {
      afficherModal = false;
    }
  }

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
    {#if rdv.commande_prete}
      <span class="material-symbols-outlined no-desktop" title="Commande prête"
        >package_2</span
      >
    {/if}
  </div>

  <div class="commande_prete pure-u-1 pure-u-lg-1-24 no-mobile">
    {#if rdv.commande_prete && !$currentUser.canEdit("vrac")}
      <span class="material-symbols-outlined" title="Commande prête"
        >package_2</span
      >
    {/if}

    {#if rdv.commande_prete && $currentUser.canEdit("vrac")}
      <div class="commande_prete-bouton-annuler">
        <MaterialButton
          icon="package_2"
          title="Annuler la préparation de commande"
          invert
          on:click={renseignerCommandePrete}
        />
      </div>
    {/if}

    {#if !rdv.commande_prete && $currentUser.canEdit("vrac")}
      <div class="commande_prete-bouton-confirmer">
        <MaterialButton
          icon="package_2"
          title="Renseigner commande prête"
          on:click={renseignerCommandePrete}
        />
      </div>
    {/if}
  </div>

  <div
    class="quantite-unite pure-u-lg-2-24 pure-u-6-24"
    style:color={rdv.max ? "red" : "initial"}
  >
    <span class="quantite">{rdv.quantite}</span>
    <span class="unite">{produit.unite}</span>
    <span class="max">{rdv.max ? "max" : ""}</span>
  </div>

  <div class="client pure-u-lg-7-24 pure-u-1">
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

  <div class="pure-u-lg-6-24">
    <!-- Espacement -->
  </div>
  <div class="commentaire pure-u-lg-17-24 pure-u-1">
    {@html rdv.commentaire.replace(/(?:\r\n|\r|\n)/g, "<br>")}
  </div>
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

  .commande_prete-bouton-confirmer {
    display: none;
  }

  /* Mobile */
  @media screen and (max-width: 767px) {
    .no-mobile {
      display: none;
    }

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

    .commande_prete {
      text-align: left;
    }
  }

  /* Desktop */
  @media screen and (min-width: 768px) {
    .no-desktop {
      display: none;
    }

    .rdv {
      font-size: 1.2rem;
      padding: 8px 0 8px 5px;
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

    .commande_prete {
      text-align: center;
    }

    .rdv:hover .commande_prete-bouton-confirmer {
      display: inline-block;
    }
  }
</style>
