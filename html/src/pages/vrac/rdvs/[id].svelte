<!-- routify:options title="Planning AMSB - RDV vrac" -->
<script lang="ts">
  import { tick, getContext } from "svelte";
  import { params, goto, redirect } from "@roxi/routify";

  import Notiflix from "notiflix";

  import { Svelecte, Chargement, BoutonAction } from "@app/components";
  import {
    notiflixOptions,
    validerFormulaire,
    preventFormSubmitOnEnterKeydown,
    locale,
  } from "@app/utils";

  import type { Stores, RdvVrac, ProduitVrac } from "@app/types";

  const { vracRdvs, vracProduits } = getContext<Stores>("stores");

  let formulaire: HTMLFormElement;
  let boutonAjouter: BoutonAction;
  let boutonModifier: BoutonAction;
  let boutonSupprimer: BoutonAction;

  const nouveauRdv: RdvVrac = {
    id: null,
    date_rdv: new Date()
      .toLocaleDateString(locale)
      .split("/")
      .reverse()
      .join("-"),
    heure: "",
    produit: null,
    qualite: null,
    quantite: 0,
    max: false,
    commande_prete: false,
    fournisseur: null,
    client: null,
    transporteur: null,
    num_commande: "",
    commentaire: "",
  };

  const produitVierge: Partial<ProduitVrac> = {
    id: null,
    nom: "",
    unite: "",
    qualites: [],
  };

  /**
   * Identifiant du RDV.
   */
  let id: RdvVrac["id"] = parseInt($params.id);

  const isNew = $params.id === "new";
  const copie = parseInt($params.copie);

  let rdv: RdvVrac = isNew && !copie ? { ...nouveauRdv } : null;

  (async () => {
    try {
      if (id || copie) {
        rdv = structuredClone(await vracRdvs.get(id || copie));
        if (!rdv) throw new Error();
      }
    } catch (error) {
      $redirect("./new");
    }
  })();

  $: produit = $vracProduits?.get(rdv?.produit) || produitVierge;

  /**
   * Aide à la saisie.
   *
   * Sélectionner la première qualité d'un produit
   * lors du changement de la liste déroulante.
   */
  async function selectionnerPremiereQualite() {
    await tick(); // Attendre la mise à jour du composant après le changement de la liste déroulante
    rdv.qualite = produit.qualites[0]?.id || null;
  }

  /**
   * Créer le RDV.
   */
  async function ajouterRdv() {
    if (!validerFormulaire(formulaire)) return;

    boutonAjouter.$set({ disabled: true });

    try {
      await vracRdvs.create(rdv);

      Notiflix.Notify.success("Le RDV a été ajouté");
      $goto("./");
    } catch (erreur) {
      Notiflix.Notify.failure(erreur.message);
      boutonAjouter.$set({ disabled: false });
    }
  }

  /**
   * Modifier le RDV.
   */
  async function modifierRdv() {
    if (!validerFormulaire(formulaire)) return;

    boutonModifier.$set({ disabled: true });

    try {
      await vracRdvs.update(rdv);

      Notiflix.Notify.success("Le RDV a été modifié");
      $goto("./");
    } catch (erreur) {
      Notiflix.Notify.failure(erreur.message);
      boutonModifier.$set({ disabled: false });
    }
  }

  /**
   * Supprimer le RDV.
   */
  function supprimerRdv() {
    if (!id) return;

    boutonSupprimer.$set({ disabled: true });

    // Demande de confirmation
    Notiflix.Confirm.show(
      "Suppression RDV",
      `Voulez-vous vraiment supprimer le RDV ?`,
      "Supprimer",
      "Annuler",
      async function () {
        try {
          await vracRdvs.delete(id);

          Notiflix.Notify.success("Le RDV a été supprimé");
          $goto("./");
        } catch (erreur) {
          Notiflix.Notify.failure(erreur.message);
          boutonSupprimer.$set({ disabled: false });
        }
      },
      () => boutonSupprimer.$set({ disabled: false }),
      notiflixOptions.themes.red
    );
  }
</script>

<!-- routify:options param-is-page -->
<!-- routify:options guard="vrac/edit" -->

<main class="formulaire">
  <h1>Rendez-vous</h1>

  {#if !rdv}
    <Chargement />
  {:else}
    <form
      class="pure-form pure-form-aligned"
      bind:this={formulaire}
      use:preventFormSubmitOnEnterKeydown
    >
      <!-- Produit -->
      <div class="pure-control-group form-control">
        <label for="produit">Produit</label>
        <select
          id="produit"
          name="produit"
          data-nom="Produit"
          bind:value={rdv.produit}
          on:change={selectionnerPremiereQualite}
          required
        >
          <option value="">Sélectionnez</option>
          {#if $vracProduits}
            {#each [...$vracProduits.values()] as _produit}
              <option value={_produit.id}>{_produit.nom}</option>
            {/each}
          {/if}
        </select>
      </div>

      <!-- Qualité -->
      <div class="pure-control-group form-control">
        <label for="qualite">Qualité</label>
        <select
          id="qualite"
          name="qualite"
          data-nom="Qualité"
          bind:value={rdv.qualite}
          disabled={produit.qualites.length === 0}
        >
          {#each produit.qualites as qualite}
            <option value={qualite.id}>{qualite.nom}</option>
          {/each}
        </select>
      </div>

      <!-- Date -->
      <div class="pure-control-group form-control">
        <label for="date_rdv">Date (jj/mm/aaaa)</label>
        <input
          type="date"
          name="date_rdv"
          id="date_rdv"
          data-nom="Date"
          bind:value={rdv.date_rdv}
          required
        />
      </div>

      <!-- Heure -->
      <div class="pure-control-group form-control">
        <label for="heure">Heure RDV (hh:mm)</label>
        <input
          type="time"
          name="heure"
          id="heure"
          step="60"
          data-nom="Heure"
          bind:value={rdv.heure}
        />
      </div>

      <!-- Quantité + unité -->
      <div class="pure-control-group form-control">
        <label for="quantite">
          Quantité
          {#if produit.unite}
            (<span id="unite">{produit.unite}</span>)
          {/if}
        </label>
        <input
          type="number"
          min="0"
          name="quantite"
          id="quantite"
          data-nom="Quantité"
          bind:value={rdv.quantite}
          required
        />
        <span class="pure-form-message-inline">
          <label class="pure-checkbox"
            ><input
              type="checkbox"
              name="max"
              id="max"
              bind:checked={rdv.max}
            /> Max</label
          >
        </span>
      </div>

      <!-- Commande prête -->
      <div class="pure-control-group">
        <label for="commande_prete">Commande prête</label>
        <input
          type="checkbox"
          name="commande_prete"
          id="commande_prete"
          bind:checked={rdv.commande_prete}
        />
      </div>

      <!-- Fournisseur -->
      <div class="pure-control-group">
        <label for="fournisseur">Fournisseur</label>
        <Svelecte
          inputId="fournisseur"
          type="tiers"
          role="vrac_fournisseur"
          name="Fournisseur"
          bind:value={rdv.fournisseur}
          required
        />
      </div>

      <!-- Client -->
      <div class="pure-control-group">
        <label for="client">Client</label>
        <Svelecte
          inputId="client"
          type="tiers"
          role="vrac_client"
          name="Client"
          bind:value={rdv.client}
          required
        />
      </div>

      <!-- Transporteur -->
      <div class="pure-control-group">
        <label for="transporteur">Transporteur</label>
        <Svelecte
          inputId="transporteur"
          type="tiers"
          role="vrac_transporteur"
          name="Transporteur"
          bind:value={rdv.transporteur}
        />
      </div>

      <!-- Numéro commande -->
      <div class="pure-control-group form-control">
        <label for="num_commande">Numéro commande</label>
        <input
          type="text"
          name="num_commande"
          id="num_commande"
          bind:value={rdv.num_commande}
          data-nom="Numéro commande"
        />
      </div>

      <!-- Commentaire -->
      <div class="pure-control-group form-control">
        <label for="commentaire">Commentaire</label>
        <textarea
          class="rdv_commentaire"
          name="commentaire"
          id="commentaire"
          bind:value={rdv.commentaire}
          rows="3"
          cols="30"
        />
      </div>
    </form>

    <!-- Validation/Annulation/Suppression -->
    <div class="boutons">
      {#if isNew}
        <!-- Bouton "Ajouter" -->
        <BoutonAction
          preset="ajouter"
          on:click={ajouterRdv}
          bind:this={boutonAjouter}
        />
      {:else}
        <!-- Bouton "Modifier" -->
        <BoutonAction
          preset="modifier"
          on:click={modifierRdv}
          bind:this={boutonModifier}
        />
        <!-- Bouton "Supprimer" -->
        <BoutonAction
          preset="supprimer"
          on:click={supprimerRdv}
          bind:this={boutonSupprimer}
        />
      {/if}

      <!-- Bouton "Annuler" -->
      <BoutonAction
        preset="annuler"
        on:click={() => {
          $goto("./");
        }}
      />
    </div>
  {/if}
</main>

<style>
</style>
