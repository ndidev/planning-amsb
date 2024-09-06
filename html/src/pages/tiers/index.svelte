<!-- routify:options title="Planning AMSB - Tiers" -->
<script lang="ts" context="module">
  import { addFormatter } from "svelecte/src/Svelecte.svelte";

  addFormatter({
    select: (item, isSelected: boolean) => {
      if (isSelected) {
        return `<div>${item.texte}</div>`;
      }

      return `<span ${!item.actif ? "style='color: darkgray;'" : ""}>${
        item.texte
      }</span>`;
    },
  });
</script>

<script lang="ts">
  import { onMount, onDestroy } from "svelte";

  import Notiflix from "notiflix";

  import {
    MaterialButton,
    Svelecte,
    BoutonAction,
    ConnexionSSE,
  } from "@app/components";

  import { tiers } from "@app/stores";

  import AUCUN_LOGO from "/src/images/nologo.min.svg";
  import ERREUR_LOGO from "/src/images/erreurlogo.min.svg";

  import {
    fetcher,
    validerFormulaire,
    notiflixOptions,
    preventFormSubmitOnEnterKeydown,
  } from "@app/utils";

  import type { Tiers } from "@app/types";

  /** Formulaire. */
  let form: HTMLFormElement;

  /** Élément de sélection du logo. */
  let inputLogo: HTMLInputElement;

  /** Élément d'affichage du logo. */
  let thumbnail: HTMLImageElement;

  let boutonAjouter: BoutonAction;
  let boutonModifier: BoutonAction;
  let boutonSupprimer: BoutonAction;

  const modeleTiers: Tiers = {
    id: null,
    nom_court: "",
    nom_complet: "",
    adresse_ligne_1: "",
    adresse_ligne_2: "",
    cp: "",
    ville: "",
    pays: "",
    telephone: "",
    commentaire: "",
    bois_fournisseur: false,
    bois_client: false,
    bois_transporteur: false,
    bois_affreteur: false,
    vrac_fournisseur: false,
    vrac_client: false,
    vrac_transporteur: false,
    maritime_armateur: false,
    maritime_affreteur: false,
    maritime_courtier: false,
    non_modifiable: false,
    lie_agence: false,
    logo: null,
    actif: true,
    nombre_rdv: 0,
  };

  type ListeTiers = {
    id: string;
    texte: string;
    texteRecherche: string;
    actif: boolean;
  }[];

  /**
   * Liste peuplant la liste déroulante.
   */
  let listeDeroulanteTiers: ListeTiers = [];

  /**
   * Identifiant du tiers sélectionné.
   */
  let selectedId: string = "new";

  let selectedTiers = structuredClone(modeleTiers);

  /**
   * Tiers sélectionné dans la liste déroulante.
   */
  $: changerSelectedTiers(selectedId);

  const unsubscribeTiers = tiers.subscribe((listeTiers) => {
    if (!listeTiers) return;

    listeDeroulanteTiers = [...listeTiers.values()]
      .filter((tiers) => tiers.non_modifiable === false)
      .map((tiers) => {
        return {
          id: tiers.id.toString(),
          texte: `${tiers.nom_court} - ${tiers.ville}`,
          texteRecherche: `${tiers.nom_court} ${tiers.nom_complet} ${tiers.ville}`,
          actif: tiers.actif,
        };
      })
      .sort((a, b) => (a.texte.toLowerCase() < b.texte.toLowerCase() ? -1 : 1));
  });

  let logoJustDeleted = false;
  let logoJustRestored = false;

  type NombreRdv = {
    id: number;
    nombre_rdv: number;
  };

  /**
   * Au changement de la liste déroulante,
   * lecture de la valeur sélectionnée dans la liste déroulante
   * puis changement des champs et boutons
   */
  async function changerSelectedTiers(selectedId: string) {
    if (inputLogo) inputLogo.value = null;
    logoJustDeleted = false;

    selectedTiers = structuredClone(
      $tiers?.get(parseInt(selectedId)) || modeleTiers
    );

    // Récupère le nombre de RDV pour le tiers sélectionné.
    try {
      if (!isNaN(parseInt(selectedId))) {
        selectedTiers.nombre_rdv = (
          await fetcher<NombreRdv>(`tiers/${selectedId}/nombre_rdv`)
        ).nombre_rdv;
      }
    } catch (error) {
      Notiflix.Notify.failure(error.message);
    }
  }

  /**
   * Afficher une preview du logo lorsqu'un fichier est choisi.
   */
  function afficherPreviewLogo() {
    /** Données du fichier logo. */
    const fichier = inputLogo.files[0];

    if (!fichier.type.startsWith("image/")) return;

    const reader = new FileReader();
    reader.onload = () => {
      const result = reader.result as string;

      selectedTiers.logo = {
        type: fichier.type,
        data: result,
      };
    };
    reader.readAsDataURL(fichier);
  }

  /**
   * Suppression du logo existant.
   */
  async function supprimerLogoExistant() {
    if (logoJustRestored) {
      logoJustRestored = false;
      return;
    }

    logoJustDeleted = true;
    selectedTiers.logo = null;
  }

  /**
   * Rétablir le logo existant
   * (= annuler la suppression ou le choix de fichier).
   */
  function retablirLogoExistant() {
    if (logoJustDeleted) {
      logoJustDeleted = false;
      return;
    }

    logoJustRestored = true;
    inputLogo.value = null;
    selectedTiers.logo = $tiers?.get(selectedTiers.id).logo || AUCUN_LOGO;
  }

  /**
   * Annuler les modifications.
   */
  function annulerModification() {
    changerSelectedTiers(selectedId);
  }

  /**
   * Nouveau tiers
   */
  async function ajouterTiers() {
    if (!validerFormulaire(form)) return;

    boutonAjouter.$set({ block: true });

    try {
      selectedId = (await tiers.create(selectedTiers)).id.toString();

      Notiflix.Notify.success("Le tiers a été créé");
    } catch (erreur) {
      Notiflix.Notify.failure(erreur.message);
      boutonAjouter.$set({ block: false });
    }
  }

  /**
   * Modifier tiers
   */
  async function modifierTiers() {
    if (!validerFormulaire(form)) return;

    boutonModifier.$set({ block: true });

    try {
      await tiers.update(selectedTiers);

      Notiflix.Notify.success("Le tiers a été modifié");
    } catch (erreur) {
      Notiflix.Notify.failure(erreur.message);
    } finally {
      boutonModifier.$set({ block: false });
    }
  }

  /**
   * Suppression tiers
   */
  function supprimerTiers() {
    // Demande de confirmation
    Notiflix.Confirm.show(
      "Suppression tiers",
      `Voulez-vous vraiment supprimer le tiers <b>${selectedTiers.nom_court} - ${selectedTiers.ville}</b> ?`,
      "Supprimer",
      "Annuler",
      async function () {
        boutonSupprimer.$set({ block: true });

        try {
          await tiers.delete(parseInt(selectedId));

          Notiflix.Notify.success("Le tiers a été supprimé");

          selectedId = "new";
        } catch (erreur) {
          Notiflix.Notify.failure(erreur.message);
          boutonSupprimer.$set({ block: false });
        }
      },
      null,
      notiflixOptions.themes.red
    );
  }

  onMount(() => {
    thumbnail.onerror = () => {
      thumbnail.src = ERREUR_LOGO;
    };
  });

  onDestroy(() => {
    unsubscribeTiers();
  });
</script>

<!-- routify:options guard="tiers" -->

<ConnexionSSE subscriptions={["tiers"]} />

<main class="formulaire">
  <h1>Tiers</h1>

  <!-- Liste déroulante -->
  <div class="liste-deroulante">
    <Svelecte
      options={listeDeroulanteTiers}
      bind:value={selectedId}
      valueField="id"
      searchField="texteRecherche"
      labelField="texte"
      placeholder="Nouveau..."
      renderer="select"
    />
  </div>

  <form
    class="pure-form pure-form-aligned"
    bind:this={form}
    use:preventFormSubmitOnEnterKeydown
  >
    <!-- Nom complet -->
    <div class="pure-control-group">
      <label for="nom_complet">Nom complet</label>
      <input
        type="text"
        id="nom_complet"
        name="nom_complet"
        data-nom="Nom complet"
        placeholder="Nom complet"
        maxlength="255"
        bind:value={selectedTiers.nom_complet}
        required
      />
    </div>

    <!-- Nom abbrégé -->
    <div class="pure-control-group">
      <label for="nom_court">Nom abbrégé</label>
      <input
        type="text"
        id="nom_court"
        name="nom_court"
        placeholder="Nom abbrégé"
        maxlength="255"
        bind:value={selectedTiers.nom_court}
      />
    </div>

    <!-- Adresse ligne 1 -->
    <div class="pure-control-group">
      <label for="adresse_ligne_1">Adresse (ligne 1)</label>
      <input
        type="text"
        id="adresse_ligne_1"
        name="adresse_ligne_1"
        placeholder="Adresse (ligne 1)"
        maxlength="255"
        bind:value={selectedTiers.adresse_ligne_1}
      />
    </div>

    <!-- Adresse ligne 2 -->
    <div class="pure-control-group">
      <label for="adresse_ligne_2">Adresse (ligne 2)</label>
      <input
        type="text"
        id="adresse_ligne_2"
        name="adresse_ligne_2"
        placeholder="Adresse (ligne 2)"
        maxlength="255"
        bind:value={selectedTiers.adresse_ligne_2}
      />
    </div>

    <!-- Code postal -->
    <div class="pure-control-group">
      <label for="cp">Code Postal</label>
      <input
        type="text"
        id="cp"
        name="cp"
        placeholder="Code postal"
        maxlength="20"
        data-nom="Code postal"
        bind:value={selectedTiers.cp}
      />
    </div>

    <!-- Ville -->
    <div class="pure-control-group">
      <label for="ville">Ville</label>
      <input
        type="text"
        id="ville"
        name="ville"
        placeholder="Ville"
        maxlength="255"
        data-nom="Ville"
        bind:value={selectedTiers.ville}
        required
      />
    </div>

    <!-- Pays -->
    <div class="pure-control-group">
      <label for="pays">Pays</label>
      <Svelecte
        type="pays"
        bind:value={selectedTiers.pays}
        inputId="pays"
        name="Pays"
        required
      />
    </div>

    <!-- Téléphone -->
    <div class="pure-control-group">
      <label for="telephone">Téléphone</label>
      <input
        type="text"
        id="telephone"
        name="telephone"
        placeholder="Téléphone"
        maxlength="255"
        data-nom="Téléphone"
        bind:value={selectedTiers.telephone}
      />
    </div>

    <!-- Commentaire -->
    <div class="pure-control-group">
      <label for="commentaire">Commentaire</label>
      <textarea
        id="commentaire"
        name="commentaire"
        bind:value={selectedTiers.commentaire}
        rows="5"
        cols="30"
        placeholder="Horaires, indications diverses..."
        maxlength="65535"
      />
    </div>

    <!-- Logo -->
    <div class="pure-control-group">
      <label for="logo">Logo</label>
      <input
        type="file"
        accept="image/jpeg, image/png, image/webp, image/gif, image/bmp"
        id="logo"
        bind:this={inputLogo}
        on:change={afficherPreviewLogo}
      />
      {#if selectedTiers.logo && typeof selectedTiers.logo === "object"}
        <MaterialButton
          icon="cancel"
          title="Annuler le choix de fichier"
          on:click={retablirLogoExistant}
          color="hsla(0, 100%, 50%, 0.5)"
          hoverColor="hsla(0, 100%, 50%, 1)"
        />
      {/if}
    </div>
    <div class="pure-control-group">
      <label>
        {#if typeof selectedTiers.logo === "string"}
          <MaterialButton
            preset="supprimer"
            title="Supprimer le logo existant"
            on:click={supprimerLogoExistant}
          />
        {/if}
        {#if selectedTiers.logo === null && $tiers?.get(selectedTiers.id)?.logo}
          <MaterialButton
            icon="undo"
            title="Rétablir le logo existant"
            on:click={retablirLogoExistant}
          />
        {/if}
      </label>
      <img
        id="thumbnail"
        bind:this={thumbnail}
        src={(typeof selectedTiers.logo === "string"
          ? selectedTiers.logo
          : selectedTiers.logo?.data) || AUCUN_LOGO}
        alt="Logo"
        width="auto"
        height="100"
      />
    </div>

    <!-- Rôles -->
    <div class="pure-controls">
      <fieldset>
        <div class="roles">
          <div class="grid__container">
            <legend>Bois</legend>
            <!-- Bois -->
            <label class="pure-checkbox">
              <input
                type="checkbox"
                name="bois_fournisseur"
                bind:checked={selectedTiers.bois_fournisseur}
              />
              Fournisseur
            </label>
            <label class="pure-checkbox">
              <input
                type="checkbox"
                name="bois_client"
                bind:checked={selectedTiers.bois_client}
              />
              Client
            </label>
            <label class="pure-checkbox">
              <input
                type="checkbox"
                name="bois_transporteur"
                bind:checked={selectedTiers.bois_transporteur}
              />
              Transporteur
            </label>
            <label class="pure-checkbox">
              <input
                type="checkbox"
                name="bois_affreteur"
                bind:checked={selectedTiers.bois_affreteur}
              />
              Affréteur
            </label>
          </div>
          <div class="grid__container">
            <legend>Vrac</legend>
            <!-- Vrac -->
            <label class="pure-checkbox">
              <input
                type="checkbox"
                name="vrac_fournisseur"
                bind:checked={selectedTiers.vrac_fournisseur}
              />
              Fournisseur
            </label>
            <label class="pure-checkbox">
              <input
                type="checkbox"
                name="vrac_client"
                bind:checked={selectedTiers.vrac_client}
              />
              Client
            </label>
            <label class="pure-checkbox">
              <input
                type="checkbox"
                name="vrac_transporteur"
                bind:checked={selectedTiers.vrac_transporteur}
              />
              Transporteur
            </label>
          </div>
          <div class="grid__container">
            <legend>Maritime</legend>
            <!-- Maritime -->
            <label class="pure-checkbox">
              <input
                type="checkbox"
                name="maritime_armateur"
                bind:checked={selectedTiers.maritime_armateur}
              />
              Armateur
            </label>
            <label class="pure-checkbox">
              <input
                type="checkbox"
                name="maritime_courtier"
                bind:checked={selectedTiers.maritime_courtier}
              />
              Courtier
            </label>
            <label class="pure-checkbox">
              <input
                type="checkbox"
                name="maritime_affreteur"
                bind:checked={selectedTiers.maritime_affreteur}
              />
              Affréteur
            </label>
          </div>
        </div>
      </fieldset>
    </div>

    <!-- Actif -->
    <div class="pure-control-group">
      <label for="actif">Actif</label>
      <input
        type="checkbox"
        name="actif"
        id="actif"
        bind:checked={selectedTiers.actif}
      />
    </div>
  </form>

  <!-- Validation/Annulation/Suppression -->

  <div class="boutons">
    <!-- Bouton "Ajouter" -->
    {#if !selectedTiers.id}
      <BoutonAction
        preset="ajouter"
        bind:this={boutonAjouter}
        on:click={ajouterTiers}
      />
    {/if}

    <!-- Bouton "Modifier" -->
    {#if selectedTiers.id}
      <BoutonAction
        preset="modifier"
        bind:this={boutonModifier}
        on:click={modifierTiers}
      />
    {/if}

    <!-- Bouton "Supprimer" -->
    {#if selectedTiers.id}
      <div class="tooltip">
        <BoutonAction
          preset="supprimer"
          bind:this={boutonSupprimer}
          on:click={supprimerTiers}
          disabled={selectedTiers.nombre_rdv > 0 ||
            selectedTiers.nombre_rdv === undefined}
        />
        <!-- Affichage info-bulle si impossibilité de supprimer -->
        {#if selectedTiers.nombre_rdv > 0}
          <div class="tooltip-supprimer">
            Le tiers est concerné par {selectedTiers.nombre_rdv} rdv.<br
            />Impossible de le supprimer.
          </div>
        {/if}
        {#if selectedTiers.nombre_rdv === undefined}
          <div class="tooltip-supprimer">
            Récupération du nombre de RDV en cours...
          </div>
        {/if}
      </div>
    {/if}

    <!-- Bouton "Annuler" -->
    <BoutonAction preset="annuler" on:click={annulerModification} />
  </div>
</main>

<style>
  .liste-deroulante {
    margin-bottom: 10px;
    margin-left: calc(10em + 20px);
  }

  .roles {
    display: flex;
    flex-wrap: wrap;
  }

  .grid__container {
    width: 250px;
  }

  /* Info-bulle en cas de suppression impossible */
  .tooltip {
    display: inline;
    position: relative;
  }

  .tooltip-supprimer {
    position: absolute;
    display: inline-block;
    left: 50%;
    transform: translate(-50%);
    top: 200%;
    padding: 5px;
    border-radius: 6px;
    z-index: 1;
    white-space: pre;
    color: white;
    background-color: black;
  }

  .tooltip-supprimer::after /* Flêche du tooltip */ {
    content: " ";
    white-space: nowrap;
    position: absolute;
    bottom: 100%; /* At the top of the tooltip */
    left: 50%;
    margin-left: -5px;
    border-width: 5px;
    border-style: solid;
    border-color: transparent transparent black transparent;
  }
  /* --------------- */

  @media (max-width: 800px) {
    .liste-deroulante {
      margin-left: 0;
    }

    .roles {
      flex-direction: column;
    }

    .grid__container {
      width: 100%;
    }

    .grid__container:not(:first-child) {
      margin-top: 20px;
    }
  }
</style>
