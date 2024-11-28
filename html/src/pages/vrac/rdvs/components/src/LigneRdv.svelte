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
  import {
    PackageIcon,
    PackageCheckIcon,
    PackageXIcon,
    ArchiveIcon,
    ArchiveRestoreIcon,
  } from "lucide-svelte";

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

<div
  class="group grid grid-cols-[50px_1fr] gap-2 border-b-[1px] border-gray-300 py-2 last:border-none lg:grid-cols-[4%_16%_4%_8%_29%_8%_16%_auto] lg:text-lg"
  bind:this={ligne}
>
  <!-- Heure -->
  <div
    class="font-bold text-[#d91ffa] [grid-area:1/1/6/2] lg:col-auto lg:row-auto"
  >
    {rdv.heure ?? ""}
  </div>

  <!-- Produit + qualité -->
  <div class="font-bold">
    <span style:color={produit.couleur}>{produit.nom}</span>

    {#if rdv.qualite}
      <span style:color={qualite.couleur}>{qualite.nom}</span>
    {/if}
  </div>

  <!-- Commande prête -->
  <div class="col-start-1 row-start-2 lg:col-auto lg:row-auto">
    {#if rdv.commande_prete}
      <div
        class="text-center lg:group-hover:[display:var(--display-on-over)] align-middle"
        style:--display-on-over={$currentUser.canEdit("vrac")
          ? "none"
          : "block"}
      >
        <PackageIcon />
      </div>
    {/if}

    {#if $currentUser.canEdit("vrac")}
      <div class="hidden text-center lg:group-hover:block align-middle">
        <LucideButton
          icon={rdv.commande_prete ? PackageXIcon : PackageCheckIcon}
          title={rdv.commande_prete
            ? "Annuler la préparation de commande"
            : "Renseigner commande prête"}
          on:click={toggleOrderReady}
        />
      </div>
    {/if}
  </div>

  <!-- Quantité + unité + max -->
  <div style:color={rdv.max ? "red" : "initial"}>
    <span class="font-bold">{rdv.quantite}</span>
    <span>{produit.unite}</span>
    <span>{rdv.max ? "max" : ""}</span>
  </div>

  <!-- Client -->
  <div>
    {client.nom_court}
    {client.ville}
  </div>

  <!-- Transporteur -->
  <div class="font-bold">
    {transporteur.nom_court}
  </div>

  <!-- Numéro de commande -->
  <div>{rdv.num_commande}</div>

  {#if $currentUser.canEdit("vrac")}
    <div class="no-mobile invisible ms-auto group-hover:visible">
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
        hoverColor="hsl(32, 100%, 50%)"
      />
    </div>
  {/if}

  <!-- Espacement -->
  <div class="lg:col-span-3"></div>

  <!-- Commentaire -->
  <div class="lg:col-span-4">
    {@html rdv.commentaire.replace(/(?:\r\n|\r|\n)/g, "<br>")}
  </div>
</div>
