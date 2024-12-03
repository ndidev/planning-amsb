<!-- routify:options title="Planning AMSB - RDV vrac" -->
<script lang="ts">
  import { tick, getContext } from "svelte";
  import { params, goto, redirect } from "@roxi/routify";

  import { Label, Input, Textarea, Toggle, Select } from "flowbite-svelte";
  import { CircleHelpIcon } from "lucide-svelte";
  import Notiflix from "notiflix";

  import {
    PageHeading,
    Svelecte,
    Chargement,
    BoutonAction,
    LucideButton,
  } from "@app/components";
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
    commentaire_public: "",
    commentaire_prive: "",
    archive: false,
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
  const archives = "archives" in $params;

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
      // Preset archive to avoid fetching race condition
      vracRdvs.setSearchParams(rdv.archive ? { archives: "true" } : {}, false);
      $goto(`./${rdv.archive ? "?archives" : ""}`);
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
      // Preset archive to avoid fetching race condition
      vracRdvs.setSearchParams(rdv.archive ? { archives: "true" } : {}, false);
      $goto(`./${rdv.archive ? "?archives" : ""}`);
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
          $goto(`./${archives ? "?archives" : ""}`);
        } catch (erreur) {
          Notiflix.Notify.failure(erreur.message);
          boutonSupprimer.$set({ disabled: false });
        }
      },
      () => boutonSupprimer.$set({ disabled: false }),
      notiflixOptions.themes.red
    );
  }

  /**
   * Afficher les explications pour le commentaire privé.
   */
  function showPrivateCommentsHelp() {
    Notiflix.Report.info(
      "Commentaire privé",
      "Ce commentaire ne sera pas visible sur l'écran TV.",
      "Fermer"
    );
  }
</script>

<!-- routify:options param-is-page -->
<!-- routify:options guard="vrac/edit" -->

<main class="mx-auto w-10/12 lg:w-1/3">
  <PageHeading>Rendez-vous</PageHeading>

  {#if !rdv}
    <Chargement />
  {:else}
    <form
      class="flex flex-col gap-3 mb-4"
      bind:this={formulaire}
      use:preventFormSubmitOnEnterKeydown
    >
      <!-- Produit -->
      <div>
        <Label for="produit">Produit</Label>
        <Select
          id="produit"
          name="produit"
          data-nom="Produit"
          bind:value={rdv.produit}
          on:change={selectionnerPremiereQualite}
          required
          placeholder=""
        >
          <option value="">Sélectionnez</option>
          {#if $vracProduits}
            {#each [...$vracProduits.values()] as _produit}
              <option value={_produit.id}>{_produit.nom}</option>
            {/each}
          {/if}
        </Select>
      </div>

      <!-- Qualité -->
      <div>
        <Label for="qualite">Qualité</Label>
        <Select
          id="qualite"
          name="qualite"
          data-nom="Qualité"
          bind:value={rdv.qualite}
          placeholder=""
          disabled={produit.qualites.length === 0}
        >
          {#each produit.qualites as qualite}
            <option value={qualite.id}>{qualite.nom}</option>
          {/each}
        </Select>
      </div>

      <!-- Date -->
      <div>
        <Label for="date_rdv">Date (jj/mm/aaaa)</Label>
        <Input
          type="date"
          name="date_rdv"
          id="date_rdv"
          data-nom="Date"
          bind:value={rdv.date_rdv}
          required
        />
      </div>

      <!-- Heure -->
      <div>
        <Label for="heure">Heure RDV (hh:mm)</Label>
        <Input
          type="time"
          name="heure"
          id="heure"
          step="60"
          data-nom="Heure"
          bind:value={rdv.heure}
        />
      </div>

      <!-- Quantité -->
      <div>
        <Label for="quantite">
          Quantité
          {#if produit.unite}
            (<span id="unite">{produit.unite}</span>)
          {/if}
        </Label>
        <Input
          type="number"
          min="0"
          name="quantite"
          id="quantite"
          data-nom="Quantité"
          bind:value={rdv.quantite}
          required
        />
      </div>

      <!-- Max -->
      <div>
        <Toggle name="max" bind:checked={rdv.max}
          >Max (la quantité ne doit pas être dépassée)</Toggle
        >
      </div>

      <!-- Commande prête -->
      <div>
        <Toggle name="commande_prete" bind:checked={rdv.commande_prete}
          >Commande prête</Toggle
        >
      </div>

      <!-- Fournisseur -->
      <div>
        <Label for="fournisseur">Fournisseur</Label>
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
      <div>
        <Label for="client">Client</Label>
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
      <div>
        <Label for="transporteur">Transporteur</Label>
        <Svelecte
          inputId="transporteur"
          type="tiers"
          role="vrac_transporteur"
          name="Transporteur"
          bind:value={rdv.transporteur}
        />
      </div>

      <!-- Numéro commande -->
      <div>
        <Label for="num_commande">Numéro commande</Label>
        <Input
          type="text"
          id="num_commande"
          bind:value={rdv.num_commande}
          data-nom="Numéro commande"
        />
      </div>

      <!-- Commentaire public -->
      <div>
        <Label for="public-comments">Commentaire public</Label>
        <Textarea
          id="public-comments"
          bind:value={rdv.commentaire_public}
          rows={3}
          cols={30}
        />
      </div>

      <!-- Commentaire privé -->
      <div>
        <Label for="private-comments"
          >Commentaire privé <LucideButton
            icon={CircleHelpIcon}
            on:click={showPrivateCommentsHelp}
          /></Label
        >
        <Textarea
          id="private-comments"
          bind:value={rdv.commentaire_prive}
          rows={3}
          cols={30}
        />
      </div>

      <!-- Archive -->
      <div>
        <Toggle name="archive" bind:checked={rdv.archive}>Archivé</Toggle>
      </div>
    </form>

    <!-- Validation/Annulation/Suppression -->
    <div class="text-center">
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
          $goto(`./${archives ? "?archives" : ""}`);
        }}
      />
    </div>
  {/if}
</main>
