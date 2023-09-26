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
    MaterialButton,
    Modal,
    BoutonAction,
    IconText,
  } from "@app/components";

  import { notiflixOptions, device } from "@app/utils";
  import type { Stores, RdvBois, Tiers } from "@app/types";

  // Stores
  const { boisRdvs, currentUser, tiers, pays } = getContext<Stores>("stores");

  export let rdv: RdvBois;
  let ligne: HTMLDivElement;

  let mc: HammerManager;
  let afficherModal = false;

  const tiersVierge: Partial<Tiers> = {
    nom_complet: "",
    nom_court: "",
    adresse_ligne_1: "",
    adresse_ligne_2: "",
    cp: "",
    ville: "",
    pays: "",
    telephone: "",
    commentaire: "",
    lie_agence: false,
  };

  $: client = $tiers?.get(rdv.client) || tiersVierge;
  $: livraison = $tiers?.get(rdv.livraison) || tiersVierge;
  $: chargement = $tiers?.get(rdv.chargement) || tiersVierge;
  $: transporteur = $tiers?.get(rdv.transporteur) || tiersVierge;
  $: fournisseur = $tiers?.get(rdv.fournisseur) || tiersVierge;
  $: affreteur = $tiers?.get(rdv.affreteur) || tiersVierge;

  $: adresses = {
    client: [
      client.nom_court,
      client.pays.toLowerCase() === "fr" ? client.cp.substring(0, 2) : "",
      client.ville,
      ["fr", "zz"].includes(client.pays.toLowerCase())
        ? ""
        : `(${
            $pays?.find(({ iso }) => client.pays === iso)?.nom || client.pays
          })`,
    ]
      .filter((champ) => champ)
      .join(" "),

    tootipClient: !client.id
      ? "Pas de client renseigné"
      : [
          "Client :",
          client.nom_complet,
          client.adresse_ligne_1,
          client.adresse_ligne_2,
          [client.cp || "", client.ville || ""]
            .filter((champ) => champ)
            .join(" "),
          $pays?.find(({ iso }) => client.pays === iso)?.nom || client.pays,
          client.telephone,
          client.commentaire ? " " : "",
          client.commentaire,
        ]
          .filter((champ) => champ)
          .join("\n"),

    chargement: [
      chargement.nom_court,
      chargement.pays?.toLowerCase() === "fr"
        ? chargement.cp?.substring(0, 2)
        : "",
      chargement.ville,
      ["fr", "zz"].includes(chargement.pays?.toLowerCase())
        ? ""
        : `(${
            $pays?.find(({ iso }) => chargement.pays === iso)?.nom ||
            chargement.pays
          })`,
    ]
      .filter((champ) => champ)
      .join(" "),

    tooltipChargement: !chargement.id
      ? "Pas de lieu de chargement renseigné"
      : [
          "Chargement :",
          chargement.nom_complet,
          chargement.adresse_ligne_1,
          chargement.adresse_ligne_2,
          [chargement.cp || "", chargement.ville || ""]
            .filter((champ) => champ)
            .join(" "),
          chargement.pays.toLowerCase() === "zz"
            ? ""
            : $pays?.find(({ iso }) => chargement.pays === iso)?.nom ||
              chargement.pays,
          chargement.telephone,
          chargement.commentaire ? " " : "",
          chargement.commentaire,
        ]
          .filter((champ) => champ)
          .join("\n"),

    livraison: [
      livraison.nom_court,
      livraison.pays.toLowerCase() === "fr" ? livraison.cp.substring(0, 2) : "",
      livraison.ville,
      ["fr", "zz"].includes(livraison.pays.toLowerCase())
        ? ""
        : `(${
            $pays?.find(({ iso }) => livraison.pays === iso)?.nom ||
            livraison.pays
          })`,
    ]
      .filter((champ) => champ)
      .join(" "),

    tooltipLivraison: !livraison.id
      ? "Pas de lieu de livraison renseigné"
      : [
          "Livraison :",
          livraison.nom_complet,
          livraison.adresse_ligne_1,
          livraison.adresse_ligne_2,
          [livraison.cp || "", livraison.ville || ""]
            .filter((champ) => champ)
            .join(" "),
          livraison.pays.toLowerCase() === "zz"
            ? ""
            : $pays?.find(({ iso }) => livraison.pays === iso)?.nom ||
              livraison.pays,
          livraison.telephone,
          livraison.commentaire ? " " : "",
          livraison.commentaire,
        ]
          .filter((champ) => champ)
          .join("\n"),
  };

  $: chargementAffiche = rdv.chargement && rdv.chargement !== 1;
  $: livraisonAffiche = rdv.livraison && rdv.client !== rdv.livraison;

  const formattedDate = rdv.date_rdv
    ? new Date(rdv.date_rdv).toLocaleDateString("fr-FR", {
        weekday: "long",
        day: "numeric",
        month: "long",
        year: "numeric",
      })
    : "Pas de date";

  /**
   * Renseigner commande prête en cliquant sur l'icône paquet.
   */
  async function renseignerCommandePrete() {
    try {
      await boisRdvs.patch(rdv.id, {
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

<div class="rdv pure-g" bind:this={ligne}>
  <div class="pure-u-1 pure-u-lg-22-24">
    <div class="date-rdv pure-u-1 pure-u-lg-4-24">{formattedDate}</div>

    <div class="adresses-tiers pure-u-1 pure-u-lg-5-24">
      {#if chargementAffiche}
        <div class="chargement">
          <IconText>
            <span slot="icon" title="Chargement">line_start_diamond</span>
            <span slot="text">{adresses.chargement}</span>
            <span slot="tooltip">{adresses.tooltipChargement}</span>
          </IconText>
        </div>
      {/if}

      <div class="client">
        <IconText>
          <span slot="icon" title="Client">
            {#if chargementAffiche || livraisonAffiche}
              person
            {/if}
          </span>
          <span slot="text">{adresses.client}</span>
          <span slot="tooltip">{adresses.tootipClient}</span>
        </IconText>
      </div>

      {#if livraisonAffiche}
        <div class="livraison">
          <IconText>
            <span slot="icon" title="Livraison">line_end_circle</span>
            <span slot="text">{adresses.livraison}</span>
            <span slot="tooltip">{adresses.tooltipLivraison}</span>
          </IconText>
        </div>
      {/if}
    </div>

    <div class="transporteur pure-u-1 pure-u-lg-2-24">
      {#if rdv.transporteur}
        <IconText hideIcon={["desktop"]}>
          <span slot="icon" title="Transporteur">local_shipping</span>
          <span slot="text" style:font-weight="bold"
            >{transporteur.nom_court}</span
          >
          <span slot="tooltip">
            {#if rdv.transporteur >= 11}
              <!-- Transporteur non "spécial" -->
              {transporteur.telephone || "Téléphone non renseigné"}
            {/if}
          </span>
        </IconText>
      {/if}
    </div>

    <div
      class="affreteur pure-u-1 pure-u-lg-2-24"
      class:lie-agence={affreteur.lie_agence}
    >
      <IconText iconType="text" hideIcon={["desktop"]}>
        <span slot="icon" title="Affréteur">A</span>
        <span slot="text">{affreteur.nom_court}</span>
      </IconText>
    </div>

    <div class="fournisseur pure-u-1 pure-u-lg-2-24">
      <IconText iconType="text" hideIcon={["desktop"]}>
        <span slot="icon" title="Fournisseur">F</span>
        <span slot="text">{fournisseur.nom_court}</span>
      </IconText>
    </div>

    <div class="commande_prete pure-u-1 pure-u-lg-1-24">
      {#if rdv.commande_prete && !$currentUser.canEdit("bois")}
        <IconText hideText={["desktop"]}>
          <span slot="icon" title="Commande prête">package_2</span>
          <span slot="text">Commande prête</span>
        </IconText>
      {/if}

      {#if rdv.commande_prete && $currentUser.canEdit("bois")}
        <div class="commande_prete-bouton-annuler">
          <MaterialButton
            icon="package_2"
            title="Annuler la préparation de commande"
            invert
            on:click={renseignerCommandePrete}
          />
        </div>
      {/if}

      {#if !rdv.commande_prete && $currentUser.canEdit("bois")}
        <div class="commande_prete-bouton-confirmer">
          <MaterialButton
            icon="package_2"
            title="Renseigner commande prête"
            on:click={renseignerCommandePrete}
          />
        </div>
      {/if}
    </div>

    <div class="commentaires pure-u-1 pure-u-lg-6-24">
      {#if rdv.commentaire_public}
        <div class="commentaire-public">
          <IconText hideIcon={["desktop"]}>
            <span slot="icon" title="Commentaire public">comment</span>
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
        <div class="separateur" />
      {/if}

      {#if rdv.commentaire_cache}
        <div class="commentaire_cache">
          <IconText hideIcon={["desktop"]}>
            <span slot="icon" title="Commentaire caché">comments_disabled</span>
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
  </div>

  {#if $currentUser.canEdit("bois")}
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
</div>

<style>
  .rdv {
    color: #555;
    padding: 8px 0 8px 5px;
    border-bottom: 1px solid #999;
    align-items: baseline;
  }

  .rdv:last-child {
    border-bottom: none;
  }

  .rdv .date-rdv {
    margin-left: 5px;
  }

  .adresses-tiers,
  .transporteur,
  .affreteur,
  .fournisseur,
  .commentaires,
  .commande_prete {
    margin-left: 5px;
  }

  .commentaires .separateur {
    height: 10px;
  }

  .commentaire_cache {
    --commentaire-cache-color: hsl(0, 0%, 70%);
    color: var(--commentaire-cache-color);
  }

  .commande_prete-bouton-confirmer {
    display: none;
  }

  /* Mobile */
  @media screen and (max-width: 767px) {
    .transporteur,
    .commentaires {
      margin-top: 10px;
    }
  }

  /* Desktop */
  @media screen and (min-width: 768px) {
    .rdv {
      min-height: 2.75rem; /* Pour éviter saut de contenu lors de hover avec icônes Material */
    }

    .rdv:hover .copie-modif-suppr {
      visibility: visible;
      margin-right: 10px;
    }

    .commentaire_cache {
      border-left: 1px dotted var(--commentaire-cache-color);
    }

    .rdv:hover .commande_prete-bouton-confirmer {
      display: inline-block;
    }
  }
</style>
