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

  import { MaterialButton, BoutonAction, Modal } from "@app/components";

  import { notiflixOptions, device } from "@app/utils";
  import { HTTP } from "@app/errors";
  import type { Stores, RdvBois, Tiers } from "@app/types";

  const { currentUser, boisRdvs, tiers, pays } = getContext<Stores>("stores");

  export let rdv: RdvBois;
  let ligne: HTMLDivElement;

  let inputNumeroBL: HTMLDivElement;

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

  $: statut = (() => {
    if (rdv.heure_arrivee && !rdv.heure_depart) return "arrive";
    if (rdv.heure_arrivee && rdv.heure_depart) return "parti";
    return "";
  })();

  let numero_bl = rdv.numero_bl;

  const unsubscribe = boisRdvs.subscribe((rdvs) => {
    numero_bl = rdvs.get(rdv.id)?.numero_bl;
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
      afficherModal = false;
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
      afficherModal = false;
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
    unsubscribe();
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
      <BoutonAction preset="annuler" on:click={() => (afficherModal = false)} />
    </div>
  </Modal>
{/if}

<div
  class="rdv pure-g"
  style:--bg-color={`var(--bg-${statut})`}
  bind:this={ligne}
>
  <div class="heures pure-u-3-24 pure-u-lg-2-24">
    {#if rdv.heure_arrivee}
      <div class="heure heure-arrivee pure-u-1 pure-u-lg-11-24">
        {rdv.heure_arrivee}
      </div>
    {/if}

    {#if !rdv.heure_arrivee && $currentUser.canEdit("bois")}
      <div class="horloge horloge-arrivee pure-u-1 pure-u-lg-11-24">
        <MaterialButton
          icon="schedule"
          title="Renseigner l'heure d'arrvée"
          on:click={renseignerHeureArrivee}
        />
      </div>
    {/if}

    {#if rdv.heure_depart}
      <div class="heure heure-depart pure-u-1 pure-u-lg-11-24">
        {rdv.heure_depart}
      </div>
    {/if}

    {#if rdv.heure_arrivee && !rdv.heure_depart && $currentUser.canEdit("bois")}
      <div class="horloge horloge-depart pure-u-1 pure-u-lg-11-24">
        <MaterialButton
          icon="schedule"
          title="Renseigner l'heure de départ"
          on:click={renseignerHeureDepart}
        />
      </div>
    {/if}
  </div>

  <div class="pure-u-21-24 pure-u-lg-20-24">
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
                : `(${
                    $pays?.find(({ iso }) => chargement.pays === iso)?.nom ||
                    chargement.pays
                  })`,
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

    {#if $currentUser.canEdit("bois")}
      <div
        class="confirmation_affretement pure-u-lg-1-24"
        style:visibility={$tiers?.get(rdv.affreteur)?.lie_agence
          ? "visible"
          : "hidden"}
        data-confirme={rdv.confirmation_affretement ? "1" : "0"}
      >
        <MaterialButton
          icon="receipt"
          title="Confirmation d'affrètement"
          color="#000000"
          on:click={toggleConfirmationAffretement}
        />
      </div>
    {/if}

    <div
      class="affreteur pure-u-1 pure-u-lg-2-24"
      class:lie-agence={affreteur.lie_agence}
    >
      {affreteur.nom_court}
    </div>

    <div class="fournisseur pure-u-1 pure-u-lg-2-24">
      {fournisseur.nom_court}
    </div>

    {#if $currentUser.canEdit("bois")}
      <div
        class="numero_bl pure-u-lg-3-24"
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
      />
    {:else}
      <div class="numero_bl pure-u-lg-3-24">
        {rdv.numero_bl}
      </div>
    {/if}

    <div class="commentaire commentaire_public pure-u-1 pure-u-lg-4-24">
      {@html rdv.commentaire_public.replace(/(?:\r\n|\r|\n)/g, "<br>")}
    </div>

    <div class="commentaire commentaire_cache pure-u-1 pure-u-lg-4-24">
      {@html rdv.commentaire_cache.replace(/(?:\r\n|\r|\n)/g, "<br>")}
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
    --bg-arrive: rgb(255, 225, 140);
    --bg-parti: rgb(215, 255, 200);
    background-color: var(--bg-color, none);
    padding: 8px 0 8px 5px;
    border-bottom: 1px solid #999;
    align-items: baseline;
  }

  .rdv:last-child {
    border-bottom: none;
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

  .chargement-client-livraison,
  .transporteur,
  .affreteur,
  .fournisseur,
  .numero_bl,
  .commentaire {
    margin-left: 5px;
  }

  .heure {
    font-weight: bold;
    color: #d91ffa;
    text-align: center;
  }

  .horloge {
    display: none;
    text-align: center;
  }

  .chargement-client-livraison:hover .tooltip-livraison {
    visibility: visible;
    font-size: 0.8em;
  }

  .role {
    color: rgb(100, 100, 100);
    margin-right: 0.4em;
  }

  .affreteur:global(.lie_agence) {
    color: blue;
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

  .numero_bl[contenteditable]:hover {
    border: 1px solid #555;
    background-color: rgba(0, 0, 0, 0.1);
  }

  .commentaire_cache {
    color: rgb(100, 100, 100);
  }

  /* Confirmation d'affrètement */
  .confirmation_affretement :global(button) {
    position: relative;
  }

  .confirmation_affretement :global(button):hover {
    font-variation-settings: "FILL" 1;
  }

  .confirmation_affretement :global(button::after) {
    position: absolute;
    font-family: "Material Symbols Outlined";
    content: "close";
    color: red;
    font-size: 24px;
    left: 15px;
  }

  .confirmation_affretement[data-confirme="1"] :global(button::after) {
    position: absolute;
    font-family: "Material Symbols Outlined";
    content: "check";
    color: green;
    font-size: 24px;
    left: 15px;
  }

  /* Mobile */
  @media screen and (max-width: 767px) {
    .numero_bl,
    .confirmation_affretement {
      display: none;
    }
  }

  /* Desktop */
  @media screen and (min-width: 768px) {
    /* Affichage des horloges */
    .rdv:hover {
      background-color: rgba(0, 0, 0, 0.1);
    }

    .rdv:hover .copie-modif-suppr {
      visibility: visible;
      margin-right: 10px;
    }

    .rdv:hover .horloge {
      display: inline-block;
    }

    .numero_bl,
    .confirmation_affretement {
      display: inline-block;
    }
  }
</style>
