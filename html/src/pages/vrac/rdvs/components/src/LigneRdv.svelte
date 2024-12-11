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
  import { writable } from "svelte/store";

  import { goto } from "@roxi/routify";

  import Notiflix from "notiflix";
  import Hammer from "hammerjs";
  import { Modal } from "flowbite-svelte";
  import {
    ArchiveIcon,
    ArchiveRestoreIcon,
    MessageSquareOffIcon,
    MessageSquareTextIcon,
  } from "lucide-svelte";

  import { DispatchModal } from "../";
  import {
    LucideButton,
    BoutonAction,
    IconText,
    DispatchButton,
    OrderReadyButton,
  } from "@app/components";

  import { notiflixOptions, device, removeDiacritics } from "@app/utils";
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

  export let appointment: RdvVrac;
  let ligne: HTMLDivElement;

  let mc: HammerManager;
  let showMenuModal = false;

  let showDispatchModal = writable(false);

  let awaitingDispatchBeforeOrderReady = false;
  let awaitingDispatchBeforeArchive = false;

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

  $: client = $tiers?.get(appointment.client) || { ...tiersVierge };

  $: transporteur = $tiers?.get(appointment.transporteur) || { ...tiersVierge };

  $: produit = $vracProduits?.get(appointment.produit) || { ...produitVierge };

  $: qualite = produit.qualites.find(
    (qualite) => qualite.id === appointment.qualite
  ) || { ...qualiteVierge };

  function showDispatchIfNecessary(type: "beforeOrderReady" | "beforeArchive") {
    const normalizedRemarks = removeDiacritics(
      appointment.dispatch.map(({ remarks }) => remarks).join()
    );

    switch (type) {
      case "beforeOrderReady":
        if (
          !appointment.commande_prete &&
          !normalizedRemarks.includes("prepa")
        ) {
          appointment.dispatch = [
            ...appointment.dispatch,
            {
              staffId: null,
              date: new Date().toISOString().split("T")[0],
              remarks: "Préparation",
              new: true,
            },
          ];

          awaitingDispatchBeforeOrderReady = true;
          $showDispatchModal = true;
        }
        break;

      case "beforeArchive":
        if (!appointment.archive && produit.unite === "BB") {
          if (!normalizedRemarks.includes("jcb")) {
            appointment.dispatch = [
              ...appointment.dispatch,
              {
                staffId: null,
                date: new Date().toISOString().split("T")[0],
                remarks: "JCB",
                new: true,
              },
            ];

            if (
              !normalizedRemarks.includes("tremi") &&
              !appointment.commentaire_public
                .toLocaleLowerCase()
                .includes("taut") &&
              !appointment.commentaire_prive
                .toLocaleLowerCase()
                .includes("taut")
            ) {
              appointment.dispatch = [
                ...appointment.dispatch,
                {
                  staffId: null,
                  date: new Date().toISOString().split("T")[0],
                  remarks: "Trémie",
                  new: true,
                },
              ];
            }

            awaitingDispatchBeforeArchive = true;
            $showDispatchModal = true;
          }
        }

        if (
          !appointment.archive &&
          produit.unite === "T" &&
          !normalizedRemarks.includes("charge")
        ) {
          appointment.dispatch = [
            ...appointment.dispatch,
            {
              staffId: null,
              date: new Date().toISOString().split("T")[0],
              remarks: "Chargeuse",
              new: true,
            },
          ];

          awaitingDispatchBeforeArchive = true;
          $showDispatchModal = true;
        }

        if (!appointment.archive && appointment.dispatch.length === 0) {
          appointment.dispatch = [
            ...appointment.dispatch,
            {
              staffId: null,
              date: new Date().toISOString().split("T")[0],
              remarks: "",
              new: true,
            },
          ];

          awaitingDispatchBeforeArchive = true;
          $showDispatchModal = true;
        }

        break;

      default:
        break;
    }
  }

  /**
   * Renseigner commande prête en cliquant sur l'icône paquet.
   */
  async function toggleOrderReady() {
    showDispatchIfNecessary("beforeOrderReady");

    if (awaitingDispatchBeforeOrderReady) return;

    try {
      const newState = !appointment.commande_prete;

      await vracRdvs.patch(appointment.id, {
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
      showMenuModal = false;
    }
  }

  function toggleArchive() {
    showDispatchIfNecessary("beforeArchive");

    if (awaitingDispatchBeforeArchive) return;

    Notiflix.Confirm.show(
      appointment.archive ? "Restauration RDV" : "Archivage RDV",
      appointment.archive
        ? "Voulez-vous vraiment restaurer le RDV ?"
        : "Voulez-vous vraiment archiver le RDV ?",
      appointment.archive ? "Restaurer" : "Archiver",
      "Annuler",
      async function () {
        try {
          Notiflix.Block.dots(
            [ligne],
            `${appointment.archive ? "Restauration" : "Archivage"} en cours...`
          );
          ligne.style.minHeight = "initial";

          await vracRdvs.patch(appointment.id, {
            archive: !appointment.archive,
          });

          Notiflix.Notify.success(
            `Le RDV a été ${appointment.archive ? "restauré" : "archivé"}`
          );
        } catch (erreur) {
          Notiflix.Notify.failure(erreur.message);
          Notiflix.Block.remove([ligne]);
        }
      },
      null,
      notiflixOptions.themes.orange
    );

    showMenuModal = false;
  }

  onMount(() => {
    mc = new Hammer(ligne);
    mc.on("press", () => {
      if ($device.is("mobile")) {
        showMenuModal = true;
      }
    });
  });

  onDestroy(() => {
    mc.destroy();
  });
</script>

<Modal bind:open={showMenuModal} outsideclose dismissable={false}>
  <BoutonAction on:click={toggleOrderReady}
    >{appointment.commande_prete ? "Annuler" : "Renseigner"} commande prête</BoutonAction
  >
  <BoutonAction preset="modifier" on:click={$goto(`./${appointment.id}`)} />
  <BoutonAction
    preset="copier"
    on:click={$goto(`./new?copie=${appointment.id}`)}
  />
  <BoutonAction
    text={appointment.archive ? "Restaurer" : "Archiver"}
    color="hsl(32, 100%, 50%)"
    on:click={toggleArchive}
  />
  <BoutonAction preset="annuler" on:click={() => (showMenuModal = false)} />
</Modal>

<div
  class="group grid grid-cols-[50px_1fr] gap-2 py-2 lg:grid-cols-[4%_16%_3%_3%_8%_27%_8%_16%_auto] lg:text-lg"
  bind:this={ligne}
>
  <!-- Heure -->
  <div
    class="font-bold text-[#d91ffa] [grid-area:1/1/6/2] lg:col-auto lg:row-auto"
  >
    {appointment.heure ?? ""}
  </div>

  <!-- Produit + qualité -->
  <div class="font-bold">
    <span style:color={produit.couleur}>{produit.nom}</span>

    {#if appointment.qualite}
      <span style:color={qualite.couleur}>{qualite.nom}</span>
    {/if}
  </div>

  <!-- Commande prête -->
  <div class="col-start-1 row-start-2 lg:col-auto lg:row-auto">
    <OrderReadyButton
      bind:orderReady={appointment.commande_prete}
      module="vrac"
      {toggleOrderReady}
    />
  </div>

  <!-- Dispatch -->
  <div class="col-start-1 row-start-3 lg:col-auto lg:row-auto">
    <DispatchButton
      bind:dispatch={appointment.dispatch}
      bind:showDispatchModal
      module="vrac"
    />

    <DispatchModal
      bind:appointment
      bind:showDispatchModal
      bind:awaitingDispatchBeforeOrderReady
      {toggleOrderReady}
      bind:awaitingDispatchBeforeArchive
      {toggleArchive}
    />
  </div>

  <!-- Quantité + unité + max -->
  <div style:color={appointment.max ? "red" : "initial"}>
    <span class="font-bold">{appointment.quantite}</span>
    <span>{produit.unite}</span>
    <span>{appointment.max ? "max" : ""}</span>
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
  <div>{appointment.num_commande}</div>

  {#if $currentUser.canEdit("vrac")}
    <div class="no-mobile invisible ms-auto group-hover:visible">
      <LucideButton
        preset="copy"
        on:click={() => {
          $goto(`./new?copie=${appointment.id}${archives ? "&archives" : ""}`);
        }}
      />
      <LucideButton
        preset="edit"
        on:click={() => {
          $goto(`./${appointment.id}${archives ? "?archives" : ""}`);
        }}
      />
      <LucideButton
        icon={appointment.archive ? ArchiveRestoreIcon : ArchiveIcon}
        on:click={toggleArchive}
        title={appointment.archive ? "Restaurer" : "Archiver"}
        hoverColor="hsl(32, 100%, 50%)"
      />
    </div>
  {/if}

  <!-- Espacement -->
  <div class="lg:col-span-3"></div>

  <!-- Commentaires -->
  <div class="lg:col-span-4">
    <!-- Commentaire public -->
    {#if appointment.commentaire_public}
      <div class="lg:pl-1">
        <IconText hideIcon={["desktop"]}>
          <span slot="icon" title="Commentaire public"
            ><MessageSquareTextIcon /></span
          >
          <span slot="text"
            >{@html appointment.commentaire_public.replace(
              /\r\n|\r|\n/g,
              "<br/>"
            )}</span
          >
        </IconText>
      </div>
    {/if}

    {#if appointment.commentaire_public && appointment.commentaire_prive}
      <div class="h-3" />
    {/if}

    <!-- Commentaire privé -->
    {#if appointment.commentaire_prive}
      <div
        class="text-gray-400 lg:border-l-[1px] lg:border-dotted lg:border-l-gray-400 lg:pl-1"
      >
        <IconText hideIcon={["desktop"]}>
          <span slot="icon" title="Commentaire caché"
            ><MessageSquareOffIcon /></span
          >
          <span slot="text"
            >{@html appointment.commentaire_prive.replace(
              /\r\n|\r|\n/g,
              "<br/>"
            )}</span
          >
        </IconText>
      </div>
    {/if}
  </div>
</div>
