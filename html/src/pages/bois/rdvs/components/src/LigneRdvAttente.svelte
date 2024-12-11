<!-- 
  @component
  
  Ligne de RDV en attente du planning bois.

  Usage :
  ```tsx
  <LigneRdvAttente appointment: RdvBois={appointment} />
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
    ArrowRightFromLineIcon,
    ArrowRightToLineIcon,
    MessageSquareOffIcon,
    MessageSquareTextIcon,
    TruckIcon,
    UserIcon,
  } from "lucide-svelte";

  import { ThirdPartyAddress, DispatchModal } from "../";
  import {
    LucideButton,
    BoutonAction,
    IconText,
    DispatchButton,
    OrderReadyButton,
  } from "@app/components";

  import {
    notiflixOptions,
    device,
    DateUtils,
    removeDiacritics,
  } from "@app/utils";
  import type { Stores, RdvBois, Tiers } from "@app/types";

  // Stores
  const { boisRdvs, currentUser, tiers, pays } = getContext<Stores>("stores");

  export let appointment: RdvBois;
  let ligne: HTMLDivElement;

  let mc: HammerManager;
  let showModal = false;

  let showDispatchModal = writable(false);

  let awaitingDispatchBeforeOrderReady = false;

  $: client = $tiers?.get(appointment.client);
  $: livraison = $tiers?.get(appointment.livraison);
  $: chargement = $tiers?.get(appointment.chargement);
  $: transporteur = $tiers?.get(appointment.transporteur);
  $: fournisseur = $tiers?.get(appointment.fournisseur);
  $: affreteur = $tiers?.get(appointment.affreteur);

  $: loadingPlaceIsDisplayed =
    appointment.chargement && appointment.chargement !== 1;
  $: deliveryPlaceIsDisplayed =
    appointment.livraison && appointment.client !== appointment.livraison;

  const formattedDate = appointment.date_rdv
    ? new DateUtils(appointment.date_rdv).format().long
    : "Pas de date";

  function makeThirdPartyTooltip(
    thirdParty: Tiers,
    role: "chargement" | "client" | "livraison"
  ) {
    return thirdParty
      ? [
          role.charAt(0).toUpperCase() + role.slice(1) + " :",
          thirdParty.nom_complet,
          thirdParty.adresse_ligne_1,
          thirdParty.adresse_ligne_2,
          [thirdParty.cp || "", thirdParty.ville || ""]
            .filter((champ) => champ)
            .join(" "),
          thirdParty.pays.toLowerCase() === "zz"
            ? ""
            : $pays?.find(({ iso }) => thirdParty.pays === iso)?.nom ||
              thirdParty.pays,
          thirdParty.telephone,
          thirdParty.commentaire ? " " : "",
          thirdParty.commentaire,
        ]
          .filter((champ) => champ)
          .join("\n")
      : "";
  }

  function showDispatchIfNecessary(type: "beforeOrderReady") {
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

      await boisRdvs.patch(appointment.id, {
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
      showModal = false;
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

          boisRdvs.delete(appointment.id);

          Notiflix.Notify.success("Le RDV a été supprimé");
        } catch (erreur) {
          Notiflix.Notify.failure(erreur.message);
          Notiflix.Block.remove([ligne]);
        }
      },
      null,
      notiflixOptions.themes.red
    );

    showModal = false;
  }

  onMount(() => {
    mc = new Hammer(ligne);
    mc.on("press", () => {
      if ($device.is("mobile")) {
        showModal = true;
      }
    });
  });

  onDestroy(() => {
    mc.destroy();
  });
</script>

<Modal bind:open={showModal} outsideclose dismissable={false}>
  <BoutonAction on:click={toggleOrderReady}
    >{appointment.commande_prete ? "Annuler" : "Renseigner"} commande prête</BoutonAction
  >
  <BoutonAction preset="modifier" on:click={$goto(`./${appointment.id}`)} />
  <BoutonAction
    preset="copier"
    on:click={$goto(`./new?copie=${appointment.id}`)}
  />
  <BoutonAction preset="supprimer" on:click={supprimerRdv} />
  <BoutonAction preset="annuler" on:click={() => (showModal = false)} />
</Modal>

<div
  class="group grid py-2 text-gray-500 lg:min-h-11 lg:grid-cols-[17%_20%_8%_8%_8%_3%_3%_21%_auto]"
  bind:this={ligne}
>
  <!-- Date -->
  <div>
    {formattedDate}
  </div>

  <!-- Adresses -->
  <div>
    <!-- Chargement -->
    {#if loadingPlaceIsDisplayed}
      <div class="chargement">
        <IconText>
          <span slot="icon" title="Chargement"><ArrowRightFromLineIcon /></span>
          <span slot="text"><ThirdPartyAddress thirdParty={chargement} /></span>
          <span slot="tooltip">
            {makeThirdPartyTooltip(chargement, "chargement")}
          </span>
        </IconText>
      </div>
    {/if}

    <!-- Client -->
    <div class="client">
      <IconText>
        <span slot="icon" title="Client">
          {#if loadingPlaceIsDisplayed || deliveryPlaceIsDisplayed || $device.is("mobile")}
            <UserIcon />
          {/if}
        </span>
        <span slot="text"><ThirdPartyAddress thirdParty={client} /></span>
        <span slot="tooltip">
          {makeThirdPartyTooltip(client, "client")}
        </span>
      </IconText>
    </div>

    <!-- Livraison -->
    {#if deliveryPlaceIsDisplayed}
      <div class="livraison">
        <IconText>
          <span slot="icon" title="Livraison"><ArrowRightToLineIcon /></span>
          <span slot="text"><ThirdPartyAddress thirdParty={livraison} /></span>
          <span slot="tooltip">
            {makeThirdPartyTooltip(livraison, "livraison")}
          </span>
        </IconText>
      </div>
    {/if}
  </div>

  <div>
    {#if appointment.transporteur}
      <IconText hideIcon={["desktop"]}>
        <span slot="icon" title="Transporteur"><TruckIcon /></span>
        <span slot="text" style:font-weight="bold"
          >{transporteur?.nom_court || ""}</span
        >
        <span slot="tooltip">
          {#if appointment.transporteur >= 11}
            <!-- Transporteur non "spécial" -->
            {transporteur?.telephone || "Téléphone non renseigné"}
          {/if}
        </span>
      </IconText>
    {/if}
  </div>

  <!-- Affréteur -->
  <div class="affreteur" class:lie-agence={affreteur?.lie_agence || false}>
    <IconText hideIcon={["desktop"]}>
      <span slot="icon" title="Affréteur">A</span>
      <span slot="text">{affreteur?.nom_court || ""}</span>
    </IconText>
  </div>

  <!-- Fournisseur -->
  <div class="fournisseur">
    <IconText hideIcon={["desktop"]}>
      <span slot="icon" title="Fournisseur">F</span>
      <span slot="text">{fournisseur?.nom_court || ""}</span>
    </IconText>
  </div>

  <!-- Commande prête -->
  <div class="col-start-1 row-start-2 lg:col-auto lg:row-auto">
    <OrderReadyButton
      bind:orderReady={appointment.commande_prete}
      module="bois"
      {toggleOrderReady}
    />
  </div>

  <!-- Dispatch -->
  <div class="col-start-1 row-start-4 lg:col-auto lg:row-auto">
    <DispatchButton
      bind:dispatch={appointment.dispatch}
      bind:showDispatchModal
      module="bois"
    />

    <DispatchModal
      bind:appointment
      bind:showDispatchModal
      bind:awaitingDispatchBeforeOrderReady
      {toggleOrderReady}
    />
  </div>

  <!-- Commentaires -->
  <div>
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

    {#if appointment.commentaire_public && appointment.commentaire_cache}
      <div class="h-3" />
    {/if}

    {#if appointment.commentaire_cache}
      <div
        class="text-gray-400 lg:border-l-[1px] lg:border-dotted lg:border-l-gray-400 lg:pl-1"
      >
        <IconText hideIcon={["desktop"]}>
          <span slot="icon" title="Commentaire caché"
            ><MessageSquareOffIcon /></span
          >
          <span slot="text"
            >{@html appointment.commentaire_cache.replace(
              /\r\n|\r|\n/g,
              "<br/>"
            )}</span
          >
        </IconText>
      </div>
    {/if}
  </div>

  <!-- Boutons -->
  {#if $currentUser.canEdit("bois")}
    <div class="no-mobile invisible ms-auto me-2 group-hover:visible">
      <LucideButton
        preset="copy"
        on:click={() => {
          $goto(`./new?copie=${appointment.id}`);
        }}
      />
      <LucideButton
        preset="edit"
        on:click={() => {
          $goto(`./${appointment.id}`);
        }}
      />
      <LucideButton preset="delete" on:click={supprimerRdv} />
    </div>
  {/if}
</div>
