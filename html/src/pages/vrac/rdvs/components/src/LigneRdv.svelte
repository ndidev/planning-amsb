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
  import { PackageIcon, ArchiveIcon, ArchiveRestoreIcon } from "lucide-svelte";

  import { LucideButton, BoutonAction, Modal } from "@app/components";

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

  const archives: boolean = getContext("archives");

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
  async function toggleOrderReady() {
    try {
      const newState = !rdv.commande_prete;

      await vracRdvs.patch(rdv.id, {
        commande_prete: newState,
      });

      Notiflix.Notify.success(
        newState
          ? "Commande marquée comme prête"
          : "Commande marquée comme non prête"
      );
    } catch (err) {
      Notiflix.Notify.failure(err.message);
    } finally {
      afficherModal = false;
    }
  }

  function toggleArchive() {
    Notiflix.Confirm.show(
      rdv.archive ? "Restauration RDV" : "Archivage RDV",
      rdv.archive
        ? "Voulez-vous vraiment restaurer le RDV ?"
        : "Voulez-vous vraiment archiver le RDV ?",
      rdv.archive ? "Restaurer" : "Archiver",
      "Annuler",
      async function () {
        try {
          Notiflix.Block.dots(
            [ligne],
            `${rdv.archive ? "Restauration" : "Archivage"} en cours...`
          );
          ligne.style.minHeight = "initial";

          await vracRdvs.patch(rdv.id, { archive: !rdv.archive });

          Notiflix.Notify.success(
            `Le RDV a été ${rdv.archive ? "restauré" : "archivé"}`
          );
        } catch (erreur) {
          Notiflix.Notify.failure(erreur.message);
          Notiflix.Block.remove([ligne]);
        }
      },
      null,
      notiflixOptions.themes.orange
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
      <BoutonAction
        text={rdv.archive ? "Restaurer" : "Archiver"}
        color="hsl(32, 100%, 50%)"
        on:click={toggleArchive}
      />
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
      <span class="no-desktop" title="Commande prête"><PackageIcon /></span>
    {/if}
  </div>

  <div class="commande_prete pure-u-1 pure-u-lg-1-24 no-mobile">
    {#if rdv.commande_prete && !$currentUser.canEdit("vrac")}
      <span title="Commande prête"><PackageIcon /></span>
    {/if}

    {#if rdv.commande_prete && $currentUser.canEdit("vrac")}
      <div class="commande_prete-bouton-annuler">
        <LucideButton
          icon={PackageIcon}
          title="Annuler la préparation de commande"
          on:click={toggleOrderReady}
          invert
        />
      </div>
    {/if}

    {#if !rdv.commande_prete && $currentUser.canEdit("vrac")}
      <div class="commande_prete-bouton-confirmer">
        <LucideButton
          icon={PackageIcon}
          title="Renseigner commande prête"
          on:click={toggleOrderReady}
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
      <LucideButton
        preset="copy"
        on:click={() => {
          $goto(`./new?copie=${rdv.id}${archives ? "&archives" : ""}`);
        }}
      />
      <LucideButton
        preset="edit"
        on:click={() => {
          $goto(`./${rdv.id}${archives ? "?archives" : ""}`);
        }}
      />
      <LucideButton
        icon={rdv.archive ? ArchiveRestoreIcon : ArchiveIcon}
        on:click={toggleArchive}
        title={rdv.archive ? "Restaurer" : "Archiver"}
        color="hsl(32, 100%, 50%)"
      />
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
      min-height: 2rem;
    }

    .rdv:hover .commande_prete-bouton-confirmer {
      display: inline-block;
    }
  }
</style>
