<!-- 
  @component
  
  Ligne de RDV du planning bois.

  Usage :
  ```tsx
  <LigneRdv rdv: RdvBois={rdv} />
  ```
 -->
<script lang="ts">
  import { onMount, onDestroy } from "svelte";
  import { goto } from "@roxi/routify";

  import Notiflix from "notiflix";
  import Hammer from "hammerjs";
  import { Modal } from "flowbite-svelte";
  import {
    ArrowRightFromLineIcon,
    ArrowRightToLineIcon,
    ClockIcon,
    MessageSquareOffIcon,
    MessageSquareTextIcon,
    ReceiptTextIcon,
    TruckIcon,
    UserIcon,
  } from "lucide-svelte";

  import {
    ThirdPartyAddress,
    DispatchModal,
    getDedicatedStaffForSupplier,
  } from "../";
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
    removeDiacritics,
    DateUtils,
  } from "@app/utils";
  import { HTTP } from "@app/errors";

  import { currentUser, boisRdvs, tiers, pays } from "@app/stores";

  import type { RdvBois, Tiers } from "@app/types";

  export let appointment: RdvBois;
  let ligne: HTMLDivElement;

  let inputNumeroBL: HTMLDivElement;

  let mc: HammerManager;
  let showMenuModal = false;

  let showDispatchModal = false;

  let awaitingDispatchBeforeOrderReady = false;
  let awaitingDispatchBeforeSettingDepartureTime = false;

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

  $: statut = (() => {
    if (appointment.heure_arrivee && !appointment.heure_depart) return "arrive";
    if (appointment.heure_arrivee && appointment.heure_depart) return "parti";
    return "";
  })();

  let numero_bl = appointment.numero_bl;

  const unsubscribe = boisRdvs.subscribe((rdvs) => {
    if (rdvs) {
      numero_bl = rdvs.get(appointment.id)?.numero_bl;
    }
  });

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

  function showDispatchIfNecessary(
    type: "beforeOrderReady" | "beforeSettingDepartureTime"
  ) {
    const normalizedRemarks = removeDiacritics(
      appointment.dispatch.map(({ remarks }) => remarks).join()
    );

    switch (type) {
      case "beforeOrderReady":
        if (!appointment.commande_prete && !/prepa/i.test(normalizedRemarks)) {
          awaitingDispatchBeforeOrderReady = true;
          showDispatchModal = true;
        }
        break;

      case "beforeSettingDepartureTime":
        if (!appointment.heure_depart && !/charge/i.test(normalizedRemarks)) {
          awaitingDispatchBeforeSettingDepartureTime = true;
          showDispatchModal = true;
        }
        break;

      default:
        break;
    }
  }

  /**
   * Renseigner l'heure d'arrivée en cliquant sur l'horloge.
   *
   * \+ Numéro BL automatique (Stora Enso).
   */
  async function setArrivalTime() {
    try {
      await boisRdvs.patch(appointment.id, {
        heure_arrivee: new Date().toLocaleTimeString(),
      });
    } catch (err) {
      Notiflix.Notify.failure(err.message);
    } finally {
      showMenuModal = false;
    }
  }

  /**
   * Renseigner l'heure de départ en cliquant sur l'horloge.
   */
  async function setDepartureTime() {
    showDispatchIfNecessary("beforeSettingDepartureTime");

    if (awaitingDispatchBeforeSettingDepartureTime) return;

    try {
      await boisRdvs.patch(appointment.id, {
        heure_depart: new Date().toLocaleTimeString(),
      });
    } catch (err) {
      Notiflix.Notify.failure(err.message);
    } finally {
      showMenuModal = false;
    }
  }

  /**
   * Cocher la case en cliquant sur zone "confirmation_affretement".
   */
  async function toggleConfirmationAffretement() {
    try {
      await boisRdvs.patch(appointment.id, {
        confirmation_affretement: !appointment.confirmation_affretement,
      });
    } catch (err) {
      Notiflix.Notify.failure(err.message);
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
      showMenuModal = false;
    }
  }

  /**
   * Modifier le numéro de BL.
   */
  async function changerNumeroBL() {
    numero_bl = numero_bl.trim();

    if (numero_bl === appointment.numero_bl) return;

    try {
      await boisRdvs.patch(appointment.id, { numero_bl });
    } catch (err: unknown) {
      const error = err as HTTP.Error | Error;
      if (error instanceof HTTP.BadRequest) {
        Notiflix.Report.failure("Erreur", error.message, "OK");
      } else {
        Notiflix.Notify.failure(error.message);
      }
      numero_bl = appointment.numero_bl;
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

    showMenuModal = false;
  }

  onMount(() => {
    mc = new Hammer(ligne);
    mc.on("press", (event) => {
      if ($device.is("mobile")) {
        showMenuModal = true;
      }
    });
  });

  onDestroy(() => {
    unsubscribe();
    mc.destroy();
  });
</script>

<Modal bind:open={showMenuModal} outsideclose dismissable={false}>
  {#if !appointment.heure_arrivee}
    <BoutonAction on:click={setArrivalTime}
      >Renseigner heure d'arrivée</BoutonAction
    >
  {/if}
  {#if appointment.heure_arrivee && !appointment.heure_depart}
    <BoutonAction on:click={setDepartureTime}
      >Renseigner heure de départ</BoutonAction
    >
  {/if}
  <BoutonAction on:click={toggleOrderReady}
    >{appointment.commande_prete ? "Annuler" : "Renseigner"} commande prête</BoutonAction
  >
  <BoutonAction preset="modifier" on:click={$goto(`./${appointment.id}`)} />
  <BoutonAction
    preset="copier"
    on:click={$goto(`./new?copie=${appointment.id}`)}
  />
  <BoutonAction preset="supprimer" on:click={supprimerRdv} />
  <BoutonAction preset="annuler" on:click={() => (showMenuModal = false)} />
</Modal>

<div
  bind:this={ligne}
  class="group grid grid-cols-[50px_1fr] gap-2 lg:grid-cols-[4%_4%_19%_8%_3%_8%_8%_2%_2%_8%_20%_auto] py-2 lg:min-h-11"
  style:--bg-arrive="hsl(44, 100%, 79%)"
  style:--bg-parti="hsl(104, 100%, 89%)"
  style:background-color={`var(--bg-${statut}, none)`}
>
  <!-- Heure d'arrivée -->
  <div class="[grid-area:1/1/6/2] lg:col-auto lg:row-auto">
    {#if appointment.heure_arrivee}
      <div class="font-bold text-[#d91ffa] text-center">
        {appointment.heure_arrivee.substring(0, 5)}
      </div>
    {/if}

    {#if !appointment.heure_arrivee && appointment.date_rdv === new DateUtils().toLocaleISODateString() && (appointment.chargement === 1 || appointment.livraison === 1) && $currentUser.canEdit("bois")}
      <div class="invisible group-hover:visible text-center">
        <LucideButton
          icon={ClockIcon}
          title="Renseigner l'heure d'arrvée"
          on:click={setArrivalTime}
        />
      </div>
    {/if}
  </div>

  <!-- Heure de départ -->
  <div class="col-start-1 row-start-2 lg:col-auto lg:row-auto">
    {#if appointment.heure_depart}
      <div class="font-bold text-[#d91ffa] text-center">
        {appointment.heure_depart.substring(0, 5)}
      </div>
    {/if}

    {#if appointment.heure_arrivee && !appointment.heure_depart && $currentUser.canEdit("bois")}
      <div class="invisible group-hover:visible text-center">
        <LucideButton
          icon={ClockIcon}
          title="Renseigner l'heure de départ"
          on:click={setDepartureTime}
        />
      </div>
    {/if}
  </div>

  <!-- Adresses -->
  <div>
    <!-- Chargement -->
    {#if loadingPlaceIsDisplayed}
      <div>
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
    <div>
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
      <div>
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

  <!-- Transporteur -->
  <div>
    {#if appointment.transporteur}
      <IconText
        hideIcon={["desktop"]}
        showTooltip={appointment.transporteur >= 11}
      >
        <span slot="icon" title="Transporteur"><TruckIcon /></span>
        <span slot="text" style:font-weight="bold"
          >{transporteur?.nom_court || ""}</span
        >
        <span slot="tooltip">
          <!-- Transporteur non "spécial" -->
          {transporteur?.telephone || "Téléphone non renseigné"}
        </span>
      </IconText>
    {/if}
  </div>

  <!-- Confirmation d'affrètement -->
  <div
    class="confirmation_affretement col-start-1 row-start-5 lg:col-auto lg:row-auto text-center"
    style:visibility={$tiers?.get(appointment.affreteur)?.lie_agence
      ? "visible"
      : "hidden"}
    data-confirme={appointment.confirmation_affretement ? "1" : "0"}
  >
    {#if $currentUser.canEdit("bois")}
      <LucideButton
        icon={ReceiptTextIcon}
        title="Confirmation d'affrètement"
        color="#000000"
        staticallyColored
        on:click={toggleConfirmationAffretement}
      />
    {/if}
  </div>

  <!-- Affréteur -->
  <div>
    <IconText hideIcon={["desktop"]}>
      <span slot="icon" title="Affréteur">A</span>
      <span slot="text" style:color={affreteur?.lie_agence ? "blue" : "inherit"}
        >{affreteur?.nom_court || ""}</span
      >
    </IconText>
  </div>

  <!-- Fournisseur -->
  <div>
    <IconText hideIcon={["desktop"]}>
      <span slot="icon" title="Fournisseur">F</span>
      <span slot="text">{fournisseur?.nom_court || ""}</span>
    </IconText>
  </div>

  <!-- Commande prête -->
  <div class="col-start-1 row-start-3 lg:col-auto lg:row-auto">
    {#if appointment.chargement === 1}
      <OrderReadyButton
        bind:orderReady={appointment.commande_prete}
        module="bois"
        {toggleOrderReady}
      />
    {/if}
  </div>

  <!-- Dispatch -->
  <div class="col-start-1 row-start-4 lg:col-auto lg:row-auto">
    {#if appointment.chargement === 1 || appointment.livraison === 1}
      <DispatchButton
        bind:dispatch={appointment.dispatch}
        bind:showDispatchModal
        module="bois"
      />

      <DispatchModal
        bind:appointment
        bind:open={showDispatchModal}
        bind:awaitingDispatchBeforeOrderReady
        {toggleOrderReady}
        bind:awaitingDispatchBeforeSettingDepartureTime
        {setDepartureTime}
      />
    {/if}
  </div>

  <!-- Numéro B/L -->
  {#if $currentUser.canEdit("bois")}
    <div
      class="no-mobile numero_bl"
      contenteditable
      bind:this={inputNumeroBL}
      bind:textContent={numero_bl}
      on:blur={changerNumeroBL}
      on:keydown={(e) => {
        if (e.key === "Enter") {
          inputNumeroBL.blur();
        }
        if (e.key === "Escape") {
          numero_bl = appointment.numero_bl;
          inputNumeroBL.blur();
        }
      }}
      role="textbox"
      aria-label="Numéro de BL"
      tabindex="0"
    />
  {:else}
    <div class="no-mobile">
      {appointment.numero_bl}
    </div>
  {/if}

  <!-- Commentaires -->
  <div>
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

    {#if appointment.commentaire_public && appointment.commentaire_cache}
      <div class="h-3" />
    {/if}

    <!-- Commentaire caché -->
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

<style>
  .numero_bl[contenteditable]:hover {
    border: 1px solid hsl(0, 0%, 33%);
    background-color: hsla(0, 0%, 0%, 0.1);
  }

  /* Confirmation d'affrètement */
  .confirmation_affretement :global(button) {
    position: relative;
  }

  .confirmation_affretement :global(button::after) {
    position: absolute;
    font-size: 1.5rem;
    left: 0.6em;
  }

  .confirmation_affretement[data-confirme="0"] :global(button::after) {
    content: "✘";
    color: red;
  }

  .confirmation_affretement[data-confirme="1"] :global(button::after) {
    content: "✔︎";
    color: green;
  }
</style>
