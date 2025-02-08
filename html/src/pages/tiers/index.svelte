<!-- routify:options title="Planning AMSB - Tiers" -->
<script lang="ts" context="module">
  import { addRenderer } from "svelecte";

  type ListeTiers = {
    id: string;
    texte: string;
    texteRecherche: string;
    actif: boolean;
  }[];

  addRenderer("select", (item: ListeTiers[number], isSelected, inputValue) => {
    return isSelected
      ? `<div>${item.texte}</div>`
      : `<span ${!item.actif ? "style='color: darkgray;'" : ""}>${item.texte}</span>`;
  });
</script>

<script lang="ts">
  import { onMount, onDestroy } from "svelte";

  import {
    Label,
    Input,
    Checkbox,
    Toggle,
    Textarea,
    Fileupload,
  } from "flowbite-svelte";
  import { UndoIcon } from "lucide-svelte";
  import Notiflix from "notiflix";

  import {
    LucideButton,
    Svelecte,
    BoutonAction,
    PageHeading,
    SseConnection,
  } from "@app/components";

  import { tiers } from "@app/stores";

  import NO_LOGO_IMAGE from "/src/images/nologo.min.svg";
  import LOGO_ERROR_IMAGE from "/src/images/erreurlogo.min.svg";

  import {
    fetcher,
    validerFormulaire,
    notiflixOptions,
    preventFormSubmitOnEnterKeydown,
  } from "@app/utils";

  let form: HTMLFormElement;

  /** Élément de sélection du logo. */
  let inputLogo: Fileupload;
  let logoFileList: FileList | undefined;
  let thumbnail: HTMLImageElement;
  $: if (logoFileList) {
    displayLogoPreview();
  } else {
    restoreExistingLogo();
  }

  let addButton: BoutonAction;
  let editButton: BoutonAction;
  let deleteButton: BoutonAction;

  const thirdPartyTemplate = tiers.getTemplate();

  /**
   * Liste peuplant la liste déroulante.
   */
  let listeDeroulanteTiers: ListeTiers = [];

  /**
   * Identifiant du tiers sélectionné.
   */
  let selectedId: string = "new";

  let selectedTiers = structuredClone(thirdPartyTemplate);

  /**
   * Tiers sélectionné dans la liste déroulante.
   */
  $: changeSelectedTiers(selectedId);

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

  type NombreRdv = number;

  /**
   * Au changement de la liste déroulante,
   * lecture de la valeur sélectionnée dans la liste déroulante
   * puis changement des champs et boutons
   */
  async function changeSelectedTiers(selectedId: string) {
    if (inputLogo) inputLogo.value = null;
    logoJustDeleted = false;

    selectedTiers = structuredClone(
      $tiers?.get(parseInt(selectedId)) || thirdPartyTemplate
    );

    // Récupère le nombre de RDV pour le tiers sélectionné.
    try {
      if (!isNaN(parseInt(selectedId))) {
        selectedTiers.nombre_rdv = await fetcher<NombreRdv>(
          `tiers/${selectedId}/nombre_rdv`
        );
      }
    } catch (error) {
      Notiflix.Notify.failure(error.message);
    }
  }

  /**
   * Afficher une preview du logo lorsqu'un fichier est choisi.
   */
  function displayLogoPreview() {
    if (!logoFileList) return;

    /** Données du fichier logo. */
    const file = logoFileList[0];

    if (!file.type.startsWith("image/")) return;

    const reader = new FileReader();
    reader.onload = () => {
      const result = reader.result as string;

      selectedTiers.logo = {
        type: file.type,
        data: result,
      };
    };
    reader.readAsDataURL(file);
  }

  /**
   * Suppression du logo existant.
   */
  async function deleteExistingLogo() {
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
  function restoreExistingLogo() {
    if (logoJustDeleted) {
      logoJustDeleted = false;
      return;
    }

    logoJustRestored = true;
    logoFileList = undefined;
    selectedTiers.logo = $tiers?.get(selectedTiers.id)?.logo || null;
  }

  /**
   * Annuler les modifications.
   */
  function cancelEdit() {
    changeSelectedTiers(selectedId);
  }

  /**
   * Nouveau tiers
   */
  async function addThirdParty() {
    if (!validerFormulaire(form)) return;

    addButton.$set({ block: true });

    try {
      selectedId = (await tiers.create(selectedTiers)).id.toString();

      Notiflix.Notify.success("Le tiers a été créé");
    } catch (erreur) {
      Notiflix.Notify.failure(erreur.message);
      addButton.$set({ block: false });
    }
  }

  /**
   * Modifier tiers
   */
  async function editThirdParty() {
    if (!validerFormulaire(form)) return;

    editButton.$set({ block: true });

    try {
      await tiers.update(selectedTiers);

      Notiflix.Notify.success("Le tiers a été modifié");
    } catch (erreur) {
      Notiflix.Notify.failure(erreur.message);
    } finally {
      editButton.$set({ block: false });
    }
  }

  /**
   * Suppression tiers
   */
  function deleteThirdParty() {
    // Demande de confirmation
    Notiflix.Confirm.show(
      "Suppression tiers",
      `Voulez-vous vraiment supprimer le tiers <b>${selectedTiers.nom_court} - ${selectedTiers.ville}</b> ?`,
      "Supprimer",
      "Annuler",
      async function () {
        deleteButton.$set({ block: true });

        try {
          await tiers.delete(parseInt(selectedId));

          Notiflix.Notify.success("Le tiers a été supprimé");

          selectedId = "new";
        } catch (erreur) {
          Notiflix.Notify.failure(erreur.message);
          deleteButton.$set({ block: false });
        }
      },
      null,
      notiflixOptions.themes.red
    );
  }

  onMount(() => {
    thumbnail.onerror = () => {
      thumbnail.src = LOGO_ERROR_IMAGE;
    };
  });

  onDestroy(() => {
    unsubscribeTiers();
  });
</script>

<!-- routify:options guard="tiers" -->

<SseConnection subscriptions={[tiers.endpoint]} />

<main class="mx-auto w-11/12 lg:w-7/12">
  <PageHeading>Tiers</PageHeading>

  <!-- Liste déroulante -->
  <div class="mb-3 flex">
    <Svelecte
      options={listeDeroulanteTiers}
      bind:value={selectedId}
      valueField="id"
      searchProps={{ fields: "texteRecherche" }}
      placeholder="Nouveau..."
      renderer="select"
    />
  </div>

  <form bind:this={form} use:preventFormSubmitOnEnterKeydown>
    <!-- Nom complet -->
    <div class="mb-4">
      <Label for="nom_complet">Nom complet</Label>
      <Input
        type="text"
        id="nom_complet"
        name="nom_complet"
        data-nom="Nom complet"
        placeholder="Nom complet"
        maxlength={255}
        bind:value={selectedTiers.nom_complet}
        required
      />
    </div>

    <!-- Nom abbrégé -->
    <div class="mb-4">
      <Label for="nom_court">Nom abbrégé</Label>
      <Input
        type="text"
        id="nom_court"
        name="nom_court"
        placeholder="Nom abbrégé"
        maxlength={255}
        bind:value={selectedTiers.nom_court}
      />
    </div>

    <!-- Adresse ligne 1 -->
    <div class="mb-4">
      <Label for="adresse_ligne_1">Adresse (ligne 1)</Label>
      <Input
        type="text"
        id="adresse_ligne_1"
        name="adresse_ligne_1"
        placeholder="Adresse (ligne 1)"
        maxlength={255}
        bind:value={selectedTiers.adresse_ligne_1}
      />
    </div>

    <!-- Adresse ligne 2 -->
    <div class="mb-4">
      <Label for="adresse_ligne_2">Adresse (ligne 2)</Label>
      <Input
        type="text"
        id="adresse_ligne_2"
        name="adresse_ligne_2"
        placeholder="Adresse (ligne 2)"
        maxlength={255}
        bind:value={selectedTiers.adresse_ligne_2}
      />
    </div>

    <!-- Code postal -->
    <div class="mb-4">
      <Label for="cp">Code Postal</Label>
      <Input
        type="text"
        id="cp"
        name="cp"
        placeholder="Code postal"
        maxlength={20}
        data-nom="Code postal"
        bind:value={selectedTiers.cp}
      />
    </div>

    <!-- Ville -->
    <div class="mb-4">
      <Label for="ville">Ville</Label>
      <Input
        type="text"
        id="ville"
        name="ville"
        placeholder="Ville"
        maxlength={255}
        data-nom="Ville"
        bind:value={selectedTiers.ville}
        required
      />
    </div>

    <!-- Pays -->
    <div class="mb-4">
      <Label for="pays">Pays</Label>
      <div class="flex">
        <Svelecte
          type="pays"
          bind:value={selectedTiers.pays}
          inputId="pays"
          name="Pays"
          required
        />
      </div>
    </div>

    <!-- Téléphone -->
    <div class="mb-4">
      <Label for="telephone">Téléphone</Label>
      <Input
        type="text"
        id="telephone"
        name="telephone"
        placeholder="Téléphone"
        maxlength={255}
        data-nom="Téléphone"
        bind:value={selectedTiers.telephone}
      />
    </div>

    <!-- Commentaire -->
    <div class="mb-4">
      <Label for="commentaire">Commentaire</Label>
      <Textarea
        id="commentaire"
        name="commentaire"
        bind:value={selectedTiers.commentaire}
        rows={5}
        cols={30}
        placeholder="Horaires, indications diverses..."
        maxlength={65535}
      />
    </div>

    <!-- Logo -->
    <div class="mb-4">
      <Label for="logo">Logo</Label>
      <Fileupload
        id="logo"
        accept="image/jpeg, image/png, image/webp, image/gif, image/bmp"
        bind:files={logoFileList}
        clearable
      />
    </div>
    <div class="mb-4">
      {#if typeof selectedTiers.logo === "string"}
        <LucideButton
          preset="delete"
          title="Supprimer le logo existant"
          on:click={deleteExistingLogo}
        />
      {/if}
      {#if selectedTiers.logo === null && $tiers?.get(selectedTiers.id)?.logo}
        <LucideButton
          icon={UndoIcon}
          title="Rétablir le logo existant"
          on:click={restoreExistingLogo}
        />
      {/if}
      <img
        id="thumbnail"
        class="inline-block"
        bind:this={thumbnail}
        src={(typeof selectedTiers.logo === "string"
          ? selectedTiers.logo
          : selectedTiers.logo?.data) || NO_LOGO_IMAGE}
        alt="Logo"
        width="auto"
        height={80}
      />
    </div>

    <!-- Rôles -->
    <div>
      <fieldset>
        <div class="flex flex-col lg:flex-row flex-wrap">
          <!-- Bois -->
          <div class="w-full lg:w-1/3 mb-4">
            <legend>Bois</legend>
            <Checkbox
              name="bois_fournisseur"
              bind:checked={selectedTiers.roles.bois_fournisseur}
            >
              Fournisseur
            </Checkbox>
            <Checkbox
              name="bois_client"
              bind:checked={selectedTiers.roles.bois_client}
            >
              Client
            </Checkbox>
            <Checkbox
              name="bois_transporteur"
              bind:checked={selectedTiers.roles.bois_transporteur}
            >
              Transporteur
            </Checkbox>
            <Checkbox
              name="bois_affreteur"
              bind:checked={selectedTiers.roles.bois_affreteur}
            >
              Affréteur
            </Checkbox>
          </div>

          <!-- Vrac -->
          <div class="w-full lg:w-1/3 mb-4">
            <legend>Vrac</legend>
            <Checkbox
              name="vrac_fournisseur"
              bind:checked={selectedTiers.roles.vrac_fournisseur}
            >
              Fournisseur
            </Checkbox>
            <Checkbox
              name="vrac_client"
              bind:checked={selectedTiers.roles.vrac_client}
            >
              Client
            </Checkbox>
            <Checkbox
              name="vrac_transporteur"
              bind:checked={selectedTiers.roles.vrac_transporteur}
            >
              Transporteur
            </Checkbox>
          </div>

          <!-- Maritime -->
          <div class="w-full lg:w-1/3 mb-4">
            <legend>Maritime</legend>
            <Checkbox
              name="maritime_armateur"
              bind:checked={selectedTiers.roles.maritime_armateur}
            >
              Armateur
            </Checkbox>
            <Checkbox
              name="maritime_courtier"
              bind:checked={selectedTiers.roles.maritime_courtier}
            >
              Courtier
            </Checkbox>
            <Checkbox
              name="maritime_affreteur"
              bind:checked={selectedTiers.roles.maritime_affreteur}
            >
              Affréteur
            </Checkbox>
          </div>
        </div>
      </fieldset>
    </div>

    <!-- Actif -->
    <div class="mb-4">
      <Toggle name="actif" bind:checked={selectedTiers.actif}>Actif</Toggle>
    </div>
  </form>

  <!-- Validation/Annulation/Suppression -->

  <div class="text-center">
    <!-- Bouton "Ajouter" -->
    {#if !selectedTiers.id}
      <BoutonAction
        preset="ajouter"
        bind:this={addButton}
        on:click={addThirdParty}
      />
    {/if}

    <!-- Bouton "Modifier" -->
    {#if selectedTiers.id}
      <BoutonAction
        preset="modifier"
        bind:this={editButton}
        on:click={editThirdParty}
      />
    {/if}

    <!-- Bouton "Supprimer" -->
    {#if selectedTiers.id}
      <div class="tooltip">
        <BoutonAction
          preset="supprimer"
          bind:this={deleteButton}
          on:click={deleteThirdParty}
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
    <BoutonAction preset="annuler" on:click={cancelEdit} />
  </div>
</main>

<style>
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
</style>
