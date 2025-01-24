<!-- routify:options title="Planning AMSB - Personnel de manutention" -->

<script lang="ts">
  import { params, goto, redirect } from "@roxi/routify";

  import { Label, Input, Toggle, Textarea } from "flowbite-svelte";
  import Notiflix from "notiflix";

  import {
    PageHeading,
    Chargement,
    BoutonAction,
    Svelecte,
  } from "@app/components";

  import {
    notiflixOptions,
    validerFormulaire,
    preventFormSubmitOnEnterKeydown,
  } from "@app/utils";

  import { stevedoringEquipments } from "@app/stores";

  import type { StevedoringEquipment } from "@app/types";
  // import { StevedoringEquipment } from "@app/entities";

  let form: HTMLFormElement;
  let createButton: BoutonAction;
  let updateButton: BoutonAction;
  let deleteButton: BoutonAction;

  /**
   * Identifiant de l'équipement.
   */
  let id: StevedoringEquipment["id"] = parseInt($params.id);

  let isNew = $params.id === "new";

  let equipment = stevedoringEquipments.getTemplate();

  (async () => {
    try {
      if (id) {
        equipment = structuredClone(await stevedoringEquipments.get(id));
        if (!equipment) throw new Error();
      }
    } catch (error) {
      $redirect("./new");
    }
  })();

  $: types = $stevedoringEquipments
    ? new Set(
        [...$stevedoringEquipments.values()]
          .map((equipment) => equipment.type)
          .filter((agency) => agency)
      )
    : [];

  /**
   * Créer le RDV.
   */
  async function createEquipment() {
    if (!validerFormulaire(form)) return;

    createButton.$set({ disabled: true });

    try {
      await stevedoringEquipments.create(equipment);

      Notiflix.Notify.success("L'équipement a été ajouté");
      $goto("./");
    } catch (erreur) {
      Notiflix.Notify.failure(erreur.message);
      console.error(erreur);
      createButton.$set({ disabled: false });
    }
  }

  /**
   * Modifier le RDV.
   */
  async function updateEquipment() {
    if (!validerFormulaire(form)) return;

    updateButton.$set({ disabled: true });

    try {
      await stevedoringEquipments.update(equipment);

      Notiflix.Notify.success("L'équipement a été modifié");
      $goto("./");
    } catch (erreur) {
      Notiflix.Notify.failure(erreur.message);
      console.error(erreur);
      updateButton.$set({ disabled: false });
    }
  }

  /**
   * Supprimer le RDV.
   */
  function deleteEquipment() {
    if (!id) return;

    deleteButton.$set({ disabled: true });

    // Demande de confirmation
    Notiflix.Confirm.show(
      "Suppression d'équipement",
      `Voulez-vous vraiment supprimer l'équipement ?`,
      "Supprimer",
      "Annuler",
      async function () {
        try {
          await stevedoringEquipments.delete(id);

          Notiflix.Notify.success("L'équipement a été supprimé");
          $goto("./");
        } catch (erreur) {
          Notiflix.Notify.failure(erreur.message);
          console.error(erreur);
          deleteButton.$set({ disabled: false });
        }
      },
      () => deleteButton.$set({ disabled: false }),
      notiflixOptions.themes.red
    );
  }
</script>

<!-- routify:options guard="manutention/edit" -->

<main class="mx-auto w-10/12 lg:w-1/3">
  <PageHeading>Équipement de manutention</PageHeading>

  {#if !equipment}
    <Chargement />
  {:else}
    <form
      class="flex flex-col gap-3 mb-4"
      bind:this={form}
      use:preventFormSubmitOnEnterKeydown
    >
      <!-- Marque -->
      <div>
        <Label for="brand">Marque</Label>
        <Input
          type="text"
          id="brand"
          bind:value={equipment.brand}
          placeholder="Marque"
        />
      </div>

      <!-- Modèle -->
      <div>
        <Label for="model">Modèle</Label>
        <Input
          type="text"
          id="model"
          bind:value={equipment.model}
          placeholder="Modèle"
        />
      </div>

      <!-- Type -->
      <div>
        <Label for="type">Type</Label>
        <Svelecte
          inputId="type"
          bind:value={equipment.type}
          options={[...types.values()]}
          placeholder="Type"
          creatable
          creatablePrefix=""
          allowEditing
          required
        />
      </div>

      <!-- Numéro interne -->
      <div>
        <Label for="internalNumber">Numéro interne</Label>
        <Input
          type="text"
          id="internalNumber"
          bind:value={equipment.internalNumber}
          placeholder="Numéro interne"
        />
      </div>

      <!-- Numéro de série -->
      <div>
        <Label for="serialNumber">Numéro de série</Label>
        <Input
          type="text"
          id="serialNumber"
          bind:value={equipment.serialNumber}
          placeholder="Numéro de série"
        />
      </div>

      <!-- Commentaires -->
      <div>
        <Label for="comments">Commentaires</Label>
        <Textarea
          id="comments"
          bind:value={equipment.comments}
          placeholder="Commentaires"
        />

        <!-- Actif -->
        <div>
          <Label for="isActive">Actif</Label>
          <Toggle id="isActive" bind:checked={equipment.isActive} />
        </div>
      </div>
    </form>

    <!-- Validation/Annulation/Suppression -->
    <div class="text-center">
      {#if isNew}
        <!-- Bouton "Ajouter" -->
        <BoutonAction
          preset="ajouter"
          on:click={createEquipment}
          bind:this={createButton}
        />
      {:else}
        <!-- Bouton "Modifier" -->
        <BoutonAction
          preset="modifier"
          on:click={updateEquipment}
          bind:this={updateButton}
        />
        <!-- Bouton "Supprimer" -->
        <BoutonAction
          preset="supprimer"
          on:click={deleteEquipment}
          bind:this={deleteButton}
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
