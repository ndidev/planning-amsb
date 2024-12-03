<!-- 
  @component
  
  Ligne de RDV en attente du planning bois.

  Usage :
  ```tsx
  <LigneRdvAttente rdv: RdvBois={rdv} />
  ```
 -->
<script lang="ts">
  import { onMount, onDestroy, getContext } from "svelte";
  import { goto } from "@roxi/routify";

  import Notiflix from "notiflix";
  import Hammer from "hammerjs";
  import {
    ArrowRightFromLineIcon,
    ArrowRightToLineIcon,
    MessageSquareOffIcon,
    MessageSquareTextIcon,
    PackageCheckIcon,
    PackageIcon,
    PackageXIcon,
    TruckIcon,
    UserIcon,
  } from "lucide-svelte";

  import { ThirdPartyAddress, ThirdPartyTooltip } from "../";
  import { LucideButton, Modal, BoutonAction, IconText } from "@app/components";

  import { notiflixOptions, device, DateUtils } from "@app/utils";
  import type { Stores, RdvBois } from "@app/types";

  // Stores
  const { boisRdvs, currentUser, tiers } = getContext<Stores>("stores");

  export let rdv: RdvBois;
  let ligne: HTMLDivElement;

  let mc: HammerManager;
  let afficherModal = false;

  $: client = $tiers?.get(rdv.client);
  $: livraison = $tiers?.get(rdv.livraison);
  $: chargement = $tiers?.get(rdv.chargement);
  $: transporteur = $tiers?.get(rdv.transporteur);
  $: fournisseur = $tiers?.get(rdv.fournisseur);
  $: affreteur = $tiers?.get(rdv.affreteur);

  $: loadingPlaceIsDisplayed = rdv.chargement && rdv.chargement !== 1;
  $: deliveryPlaceIsDisplayed = rdv.livraison && rdv.client !== rdv.livraison;

  const formattedDate = rdv.date_rdv
    ? new DateUtils(rdv.date_rdv).format().long
    : "Pas de date";

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

<div
  class="group grid py-2 text-gray-500 lg:min-h-11 lg:grid-cols-[17%_20%_8%_8%_8%_3%_24%_auto]"
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
    <div class="client">
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
      <div class="livraison">
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

  <div>
    {#if rdv.transporteur}
      <IconText hideIcon={["desktop"]}>
        <span slot="icon" title="Transporteur"><TruckIcon /></span>
        <span slot="text" style:font-weight="bold"
          >{transporteur?.nom_court || ""}</span
        >
        <span slot="tooltip">
          {#if rdv.transporteur >= 11}
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
    {#if rdv.commande_prete}
      <div
        class="lg:text-center lg:group-hover:[display:var(--display-on-over)]"
        style:--display-on-over={$currentUser.canEdit("bois")
          ? "none"
          : "block"}
      >
        <PackageIcon />
        <span class="lg:hidden">Commande prête</span>
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

  <!-- Commentaires -->
  <div>
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
