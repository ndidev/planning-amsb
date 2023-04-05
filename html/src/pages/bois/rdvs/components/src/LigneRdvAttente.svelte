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

  import { MaterialButton, Modal, BoutonAction } from "@app/components";

  import { notiflixOptions, Device } from "@app/utils";
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

  const formattedDate = rdv.date_rdv
    ? new Date(rdv.date_rdv).toLocaleDateString("fr-FR", {
        weekday: "long",
        day: "numeric",
        month: "long",
        year: "numeric",
      })
    : "Pas de date";

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
      if (Device.is("mobile")) {
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

<div class="rdv-attente pure-g" bind:this={ligne}>
  <div class="date-rdv pure-u-1 pure-u-lg-4-24">{formattedDate}</div>

  <div class="chargement-client-livraison pure-u-1 pure-u-lg-4-24">
    {#if rdv.chargement && rdv.chargement !== 1}
      <div class="chargement">
        <span class="role">chargement</span>
        <span class="adresse">
          {[
            chargement.nom_court,
            chargement.pays?.toLowerCase() === "fr"
              ? chargement.cp?.substring(0, 2)
              : "",
            chargement.ville,
            chargement.pays?.toLowerCase() === "fr"
              ? ""
              : `(${chargement.pays})`,
          ]
            .filter((champ) => champ)
            .join(" ")}
        </span>
      </div>
    {/if}

    <div class="client">
      {#if rdv.chargement !== 1 && rdv.client !== rdv.livraison}
        <span class="role">client</span>
      {/if}
      <span class="adresse">
        {[
          client.nom_court,
          client.pays.toLowerCase() === "fr" ? client.cp.substring(0, 2) : "",
          client.ville,
          client.pays.toLowerCase() === "fr"
            ? ""
            : `(${
                $pays?.find(({ iso }) => client.pays === iso)?.nom ||
                client.pays
              })`,
        ]
          .filter((champ) => champ)
          .join(" ")}
      </span>
    </div>

    {#if rdv.livraison && rdv.client !== rdv.livraison}
      <div class="livraison">
        <span class="role">livraison</span>
        <span class="adresse">
          {[
            livraison.nom_court,
            livraison.pays.toLowerCase() === "fr"
              ? livraison.cp.substring(0, 2)
              : "",
            livraison.ville,
            livraison.pays.toLowerCase() === "fr"
              ? ""
              : `(${
                  $pays?.find(({ iso }) => livraison.pays === iso)?.nom ||
                  livraison.pays
                })`,
          ]
            .filter((champ) => champ)
            .join(" ")}
        </span>
      </div>
    {/if}

    <div class="tooltip tooltip-livraison">
      {!livraison.id
        ? "Pas de lieu de livraison renseigné"
        : [
            livraison.nom_complet,
            livraison.adresse_ligne_1,
            livraison.adresse_ligne_2,
            [livraison.cp || "", livraison.ville || ""]
              .filter((champ) => champ)
              .join(" "),
            $pays?.find(({ iso }) => livraison.pays === iso)?.nom ||
              livraison.pays,
            livraison.telephone,
            livraison.commentaire,
          ]
            .filter((champ) => champ)
            .join("\n")}
    </div>
  </div>

  <div class="transporteur pure-u-1 pure-u-lg-2-24">
    <span class="transporteur-nom">{transporteur.nom_court}</span>
    {#if rdv.transporteur >= 11}
      <!-- Transporteur non "spécial" -->
      <div class="tooltip tooltip-transporteur">
        {transporteur.telephone || "Téléphone non renseigné"}
      </div>
    {/if}
  </div>

  <div
    class="affreteur pure-u-1 pure-u-lg-2-24"
    class:lie-agence={affreteur.lie_agence}
  >
    {affreteur.nom_court}
  </div>

  <div class="fournisseur pure-u-1 pure-u-lg-2-24">{fournisseur.nom_court}</div>

  <div class="commentaire commentaire_public pure-u-1 pure-u-lg-4-24">
    {rdv.commentaire_public.replace(/(?:\r\n|\r|\n)/g, "<br>")}
  </div>

  <div class="commentaire commentaire_cache pure-u-1 pure-u-lg-4-24">
    {rdv.commentaire_cache.replace(/(?:\r\n|\r|\n)/g, "<br>")}
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
  .rdv-attente {
    color: #555;
    padding: 8px 0 8px 5px;
    border-bottom: 1px solid #999;
    align-items: baseline;
  }

  .rdv-attente:last-child {
    border-bottom: none;
  }

  .rdv-attente:hover {
    background-color: rgba(0, 0, 0, 0.1);
  }

  .rdv-attente:hover .copie-modif-suppr {
    visibility: visible;
  }

  .rdv-attente .date-rdv {
    margin-left: 5px;
  }

  .tooltip {
    visibility: hidden;
    font-weight: normal;
    white-space: pre;
    background-color: black;
    color: white;
    padding: 5px;
    border-radius: 6px;
    position: absolute;
    z-index: 1;
  }

  /* .heure, */
  /* .horloge, */
  .chargement-client-livraison,
  .transporteur,
  .affreteur,
  .fournisseur,
  .commentaire {
    margin-left: 5px;
  }

  .chargement-client-livraison:hover .tooltip-livraison {
    visibility: visible;
    font-size: 0.8em;
  }

  .role {
    color: rgb(100, 100, 100);
    margin-right: 0.4em;
  }

  .transporteur {
    font-weight: bold;
  }

  .tooltip-transporteur {
    font-size: 0.8em;
  }

  .transporteur:hover .tooltip-transporteur {
    visibility: visible;
  }

  :global(.affreteur.lie-agence) {
    color: blue;
  }

  .commentaire_cache {
    color: rgb(100, 100, 100);
  }
</style>
