<!-- 
  @component
  
  Ligne de RDV du planning bois.

  Usage :
  ```tsx
  <LigneRdv rdv: RdvBois={rdv} />
  ```
 -->
<script lang="ts">
  import { onMount, onDestroy, getContext } from "svelte";
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
    PackageCheckIcon,
    PackageIcon,
    PackageXIcon,
    ReceiptTextIcon,
    TruckIcon,
    UserIcon,
  } from "lucide-svelte";

  import { ThirdPartyAddress, ThirdPartyTooltip } from "../";
  import { LucideButton, BoutonAction, IconText } from "@app/components";

  import { notiflixOptions, device } from "@app/utils";
  import { HTTP } from "@app/errors";
  import type { Stores, RdvBois } from "@app/types";

  const { currentUser, boisRdvs, tiers } = getContext<Stores>("stores");

  export let rdv: RdvBois;
  let ligne: HTMLDivElement;

  let inputNumeroBL: HTMLDivElement;

  let mc: HammerManager;
  let showModal = false;

  $: client = $tiers?.get(rdv.client);
  $: livraison = $tiers?.get(rdv.livraison);
  $: chargement = $tiers?.get(rdv.chargement);
  $: transporteur = $tiers?.get(rdv.transporteur);
  $: fournisseur = $tiers?.get(rdv.fournisseur);
  $: affreteur = $tiers?.get(rdv.affreteur);

  $: loadingPlaceIsDisplayed = rdv.chargement && rdv.chargement !== 1;
  $: deliveryPlaceIsDisplayed = rdv.livraison && rdv.client !== rdv.livraison;

  $: statut = (() => {
    if (rdv.heure_arrivee && !rdv.heure_depart) return "arrive";
    if (rdv.heure_arrivee && rdv.heure_depart) return "parti";
    return "";
  })();

  let numero_bl = rdv.numero_bl;

  const unsubscribe = boisRdvs.subscribe((rdvs) => {
    if (rdvs) {
      numero_bl = rdvs.get(rdv.id)?.numero_bl;
    }
  });

  /**
   * Renseigner l'heure d'arrivée en cliquant sur l'horloge.
   *
   * \+ Numéro BL automatique (Stora Enso).
   */
  async function renseignerHeureArrivee() {
    try {
      await boisRdvs.patch(rdv.id, {
        heure_arrivee: new Date().toLocaleTimeString(),
      });
    } catch (err) {
      Notiflix.Notify.failure(err.message);
    } finally {
      showModal = false;
    }
  }

  /**
   * Renseigner l'heure de départ en cliquant sur l'horloge.
   */
  async function renseignerHeureDepart() {
    try {
      await boisRdvs.patch(rdv.id, {
        heure_depart: new Date().toLocaleTimeString(),
      });
    } catch (err) {
      Notiflix.Notify.failure(err.message);
    } finally {
      showModal = false;
    }
  }

  /**
   * Cocher la case en cliquant sur zone "confirmation_affretement".
   */
  async function toggleConfirmationAffretement() {
    try {
      await boisRdvs.patch(rdv.id, {
        confirmation_affretement: !rdv.confirmation_affretement,
      });
    } catch (err) {
      Notiflix.Notify.failure(err.message);
    }
  }

  /**
   * Renseigner commande prête en cliquant sur l'icône paquet.
   */
  async function toggleOrderReady() {
    try {
      const newState = !rdv.commande_prete;

      await boisRdvs.patch(rdv.id, {
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
   * Modifier le numéro de BL.
   */
  async function changerNumeroBL() {
    numero_bl = numero_bl.trim();

    if (numero_bl === rdv.numero_bl) return;

    try {
      await boisRdvs.patch(rdv.id, { numero_bl });
    } catch (err: unknown) {
      const error = err as HTTP.Error | Error;
      if (error instanceof HTTP.BadRequest) {
        Notiflix.Report.failure("Erreur", error.message, "OK");
      } else {
        Notiflix.Notify.failure(error.message);
      }
      numero_bl = rdv.numero_bl;
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

          boisRdvs.delete(rdv.id);

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
    mc.on("press", (event) => {
      if ($device.is("mobile")) {
        showModal = true;
      }
    });
  });

  onDestroy(() => {
    unsubscribe();
    mc.destroy();
  });
</script>

<Modal bind:open={showModal} outsideclose dismissable={false}>
  {#if !rdv.heure_arrivee}
    <BoutonAction on:click={renseignerHeureArrivee}
      >Renseigner heure d'arrivée</BoutonAction
    >
  {/if}
  {#if rdv.heure_arrivee && !rdv.heure_depart}
    <BoutonAction on:click={renseignerHeureDepart}
      >Renseigner heure de départ</BoutonAction
    >
  {/if}
  <BoutonAction preset="modifier" on:click={$goto(`./${rdv.id}`)} />
  <BoutonAction preset="copier" on:click={$goto(`./new?copie=${rdv.id}`)} />
  <BoutonAction preset="supprimer" on:click={supprimerRdv} />
  <BoutonAction preset="annuler" on:click={() => (showModal = false)} />
</Modal>

<div
  bind:this={ligne}
  class="group grid grid-cols-[50px_1fr] gap-2 lg:grid-cols-[4%_4%_20%_8%_3%_8%_8%_3%_8%_20%_auto] py-2 lg:min-h-11"
  style:--bg-arrive="hsl(44, 100%, 79%)"
  style:--bg-parti="hsl(104, 100%, 89%)"
  style:background-color={`var(--bg-${statut}, none)`}
>
  <!-- Heure d'arrivée -->
  <div class="[grid-area:1/1/6/2] lg:col-auto lg:row-auto">
    {#if rdv.heure_arrivee}
      <div class="font-bold text-[#d91ffa] text-center">
        {rdv.heure_arrivee.substring(0, 5)}
      </div>
    {/if}

    {#if !rdv.heure_arrivee && $currentUser.canEdit("bois")}
      <div class="invisible group-hover:visible text-center">
        <LucideButton
          icon={ClockIcon}
          title="Renseigner l'heure d'arrvée"
          on:click={renseignerHeureArrivee}
        />
      </div>
    {/if}
  </div>

  <!-- Heure de départ -->
  <div class="col-start-1 row-start-2 lg:col-auto lg:row-auto">
    {#if rdv.heure_depart}
      <div class="font-bold text-[#d91ffa] text-center">
        {rdv.heure_depart.substring(0, 5)}
      </div>
    {/if}

    {#if rdv.heure_arrivee && !rdv.heure_depart && $currentUser.canEdit("bois")}
      <div class="invisible group-hover:visible text-center">
        <LucideButton
          icon={ClockIcon}
          title="Renseigner l'heure de départ"
          on:click={renseignerHeureDepart}
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
          <span slot="tooltip"
            ><ThirdPartyTooltip
              thirdParty={chargement}
              role="chargement"
            /></span
          >
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
        <span slot="tooltip"
          ><ThirdPartyTooltip thirdParty={client} role="client" /></span
        >
      </IconText>
    </div>

    <!-- Livraison -->
    {#if deliveryPlaceIsDisplayed}
      <div>
        <IconText>
          <span slot="icon" title="Livraison"><ArrowRightToLineIcon /></span>
          <span slot="text"><ThirdPartyAddress thirdParty={livraison} /></span>
          <span slot="tooltip"
            ><ThirdPartyTooltip thirdParty={livraison} role="livraison" /></span
          >
        </IconText>
      </div>
    {/if}
  </div>

  <!-- Transporteur -->
  <div>
    {#if rdv.transporteur}
      <IconText hideIcon={["desktop"]} showTooltip={rdv.transporteur >= 11}>
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
    class="confirmation_affretement col-start-1 row-start-4 lg:col-auto lg:row-auto text-center"
    style:visibility={$tiers?.get(rdv.affreteur)?.lie_agence
      ? "visible"
      : "hidden"}
    data-confirme={rdv.confirmation_affretement ? "1" : "0"}
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
    {#if rdv.commande_prete}
      <div
        class="text-center lg:group-hover:[display:var(--display-on-over)]"
        style:--display-on-over={$currentUser.canEdit("bois")
          ? "none"
          : "block"}
      >
        <PackageIcon />
      </div>
    {/if}

    {#if $currentUser.canEdit("bois")}
      <div class="hidden text-center lg:group-hover:block">
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
          numero_bl = rdv.numero_bl;
          inputNumeroBL.blur();
        }
      }}
      role="textbox"
      aria-label="Numéro de BL"
      tabindex="0"
    />
  {:else}
    <div class="no-mobile">
      {rdv.numero_bl}
    </div>
  {/if}

  <!-- Commentaires -->
  <div>
    <!-- Commentaire public -->
    {#if rdv.commentaire_public}
      <div class="lg:pl-1">
        <IconText hideIcon={["desktop"]}>
          <span slot="icon" title="Commentaire public"
            ><MessageSquareTextIcon /></span
          >
          <span slot="text"
            >{@html rdv.commentaire_public.replace(
              /(?:\r\n|\r|\n)/g,
              "<br>"
            )}</span
          >
        </IconText>
      </div>
    {/if}

    {#if rdv.commentaire_public && rdv.commentaire_cache}
      <div class="h-3" />
    {/if}

    <!-- Commentaire caché -->
    {#if rdv.commentaire_cache}
      <div
        class="text-gray-400 lg:border-l-[1px] lg:border-dotted lg:border-l-gray-400 lg:pl-1"
      >
        <IconText hideIcon={["desktop"]}>
          <span slot="icon" title="Commentaire caché"
            ><MessageSquareOffIcon /></span
          >
          <span slot="text"
            >{@html rdv.commentaire_cache.replace(
              /(?:\r\n|\r|\n)/g,
              "<br>"
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
          $goto(`./new?copie=${rdv.id}`);
        }}
      />
      <LucideButton
        preset="edit"
        on:click={() => {
          $goto(`./${rdv.id}`);
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
