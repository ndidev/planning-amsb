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

  let form: HTMLFormElement;
  let createButton: BoutonAction;
  let updateButton: BoutonAction;
  let deleteButton: BoutonAction;

  const newAppointment: RdvVrac = {
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
    dispatch: [],
  };

  const newProduct: Partial<ProduitVrac> = {
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

  let appointment: RdvVrac = isNew && !copie ? { ...newAppointment } : null;

  (async () => {
    try {
      if (id || copie) {
        appointment = structuredClone(await vracRdvs.get(id || copie));
        if (!appointment) throw new Error();
      }
    } catch (error) {
      $redirect("./new");
    }
  })();

  $: product = $vracProduits?.get(appointment?.produit) || newProduct;

  /**
   * Aide à la saisie.
   *
   * Sélectionner la première qualité d'un produit
   * lors du changement de la liste déroulante.
   */
  async function selectionnerPremiereQualite() {
    await tick(); // Attendre la mise à jour du composant après le changement de la liste déroulante
    appointment.qualite = product.qualites[0]?.id || null;
  }

  /**
   * Créer le RDV.
   */
  async function createAppointment() {
    if (!validerFormulaire(form)) return;

    createButton.$set({ disabled: true });

    try {
      await vracRdvs.create(appointment);

      Notiflix.Notify.success("Le RDV a été ajouté");
      // Preset archive to avoid fetching race condition
      vracRdvs.setSearchParams(
        appointment.archive ? { archives: "true" } : {},
        false
      );
      $goto(`./${appointment.archive ? "?archives" : ""}`);
    } catch (erreur) {
      Notiflix.Notify.failure(erreur.message);
      createButton.$set({ disabled: false });
    }
  }

  /**
   * Modifier le RDV.
   */
  async function updateAppointment() {
    if (!validerFormulaire(form)) return;

    updateButton.$set({ disabled: true });

    try {
      await vracRdvs.update(appointment);

      Notiflix.Notify.success("Le RDV a été modifié");
      // Preset archive to avoid fetching race condition
      vracRdvs.setSearchParams(
        appointment.archive ? { archives: "true" } : {},
        false
      );
      $goto(`./${appointment.archive ? "?archives" : ""}`);
    } catch (erreur) {
      Notiflix.Notify.failure(erreur.message);
      updateButton.$set({ disabled: false });
    }
  }

  /**
   * Supprimer le RDV.
   */
  function deleteAppointment() {
    if (!id) return;

    deleteButton.$set({ disabled: true });

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
          deleteButton.$set({ disabled: false });
        }
      },
      () => deleteButton.$set({ disabled: false }),
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

  function addDispatchLine() {
    appointment.dispatch = [
      ...appointment.dispatch,
      {
        staffId: null,
        date: "",
        remarks: "",
      },
    ];
  }

  function deleteDispatchLine(index: number) {
    appointment.dispatch.splice(index, 1);

    appointment.dispatch = appointment.dispatch;
  }
</script>

<!-- routify:options param-is-page -->
<!-- routify:options guard="vrac/edit" -->

<main class="mx-auto w-10/12 lg:w-1/3">
  <PageHeading>Rendez-vous</PageHeading>

  {#if !appointment}
    <Chargement />
  {:else}
    <form
      class="flex flex-col gap-3 mb-4"
      bind:this={form}
      use:preventFormSubmitOnEnterKeydown
    >
      <!-- Produit -->
      <div>
        <Label for="produit">Produit</Label>
        <Select
          id="produit"
          name="produit"
          data-nom="Produit"
          bind:value={appointment.produit}
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
          bind:value={appointment.qualite}
          placeholder=""
          disabled={product.qualites.length === 0}
        >
          {#each product.qualites as qualite}
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
          bind:value={appointment.date_rdv}
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
          bind:value={appointment.heure}
        />
      </div>

      <!-- Quantité -->
      <div>
        <Label for="quantite">
          Quantité
          {#if product.unite}
            (<span id="unite">{product.unite}</span>)
          {/if}
        </Label>
        <Input
          type="number"
          min="0"
          name="quantite"
          id="quantite"
          data-nom="Quantité"
          bind:value={appointment.quantite}
          required
        />
      </div>

      <!-- Max -->
      <div>
        <Toggle name="max" bind:checked={appointment.max}
          >Max (la quantité ne doit pas être dépassée)</Toggle
        >
      </div>

      <!-- Commande prête -->
      <div>
        <Toggle name="commande_prete" bind:checked={appointment.commande_prete}
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
          bind:value={appointment.fournisseur}
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
          bind:value={appointment.client}
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
          bind:value={appointment.transporteur}
        />
      </div>

      <!-- Numéro commande -->
      <div>
        <Label for="num_commande">Numéro commande</Label>
        <Input
          type="text"
          id="num_commande"
          bind:value={appointment.num_commande}
          data-nom="Numéro commande"
        />
      </div>

      <!-- Commentaire public -->
      <div>
        <Label for="public-comments">Commentaire public</Label>
        <Textarea
          id="public-comments"
          bind:value={appointment.commentaire_public}
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
          bind:value={appointment.commentaire_prive}
          rows={3}
          cols={30}
        />
      </div>

      <!-- Archive -->
      <div>
        <Toggle name="archive" bind:checked={appointment.archive}
          >Archivé</Toggle
        >
      </div>

      <!-- Dispatch -->
      <div>
        <div class="text-xl font-bold">
          Dispatch
          <LucideButton
            preset="add"
            title="Ajouter une ligne"
            on:click={addDispatchLine}
          />
        </div>
        <div class="divide-y">
          {#each appointment.dispatch as dispatchItem, index}
            <div
              class="flex flex-col items-center gap-2 py-1 lg:flex-row lg:gap-4 lg:py-2"
            >
              <div class="w-full">
                <Label for="">Personnel</Label>
                <Svelecte
                  type="staff"
                  inputId="staff-{index}"
                  bind:value={dispatchItem.staffId}
                  placeholder="Sélectionner le personnel"
                  required
                />
              </div>

              <div class="w-full lg:w-min">
                <Label for="date-{index}">Date</Label>
                <Input
                  type="date"
                  id="date-{index}"
                  name="Date"
                  bind:value={dispatchItem.date}
                  required
                />
              </div>

              <div class="w-full">
                <Label for="remarks-{index}">Remarques</Label>
                <Input
                  type="text"
                  id="remarks-{index}"
                  bind:value={dispatchItem.remarks}
                  list="remarks"
                />
                <datalist id="remarks">
                  <option value="JCB"></option>
                  <option value="Trémie"></option>
                  <option value="Chargeuse"></option>
                </datalist>
              </div>
              <div>
                <LucideButton
                  preset="delete"
                  title="Supprimer la ligne"
                  on:click={() => deleteDispatchLine(index)}
                />
              </div>
            </div>
          {/each}
        </div>
      </div>
    </form>

    <!-- Validation/Annulation/Suppression -->
    <div class="text-center">
      {#if isNew}
        <!-- Bouton "Ajouter" -->
        <BoutonAction
          preset="ajouter"
          on:click={createAppointment}
          bind:this={createButton}
        />
      {:else}
        <!-- Bouton "Modifier" -->
        <BoutonAction
          preset="modifier"
          on:click={updateAppointment}
          bind:this={updateButton}
        />
        <!-- Bouton "Supprimer" -->
        <BoutonAction
          preset="supprimer"
          on:click={deleteAppointment}
          bind:this={deleteButton}
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
