<!-- routify:options title="Planning AMSB - Personnel de manutention" -->

<script lang="ts">
  import { params, goto, redirect } from "@roxi/routify";

  import { Label, Input, Toggle, Select, Textarea } from "flowbite-svelte";
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

  import { stevedoringStaff } from "@app/stores";

  import type { StevedoringStaff } from "@app/types";

  let form: HTMLFormElement;
  let createButton: BoutonAction;
  let updateButton: BoutonAction;
  let deleteButton: BoutonAction;

  /**
   * Identifiant du personnel.
   */
  let id: StevedoringStaff["id"] = parseInt($params.id);

  let isNew = $params.id === "new";

  let staff = stevedoringStaff.getTemplate();

  (async () => {
    try {
      if (id) {
        staff = structuredClone(await stevedoringStaff.get(id));
        if (!staff) throw new Error();
      }
    } catch (error) {
      $redirect("./new");
    }
  })();

  $: tempWorkAgencies = $stevedoringStaff
    ? new Set(
        [...$stevedoringStaff.values()]
          .filter((staff) => staff.type === "interim")
          .map((staff) => staff.tempWorkAgency)
          .filter((agency) => agency)
      )
    : [];

  /**
   * Créer le RDV.
   */
  async function createStaff() {
    if (!validerFormulaire(form)) return;

    createButton.$set({ disabled: true });

    try {
      await stevedoringStaff.create(staff);

      Notiflix.Notify.success("Le personnel a été ajouté");
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
  async function updateStaff() {
    if (!validerFormulaire(form)) return;

    updateButton.$set({ disabled: true });

    try {
      await stevedoringStaff.update(staff);

      Notiflix.Notify.success("Le personnel a été modifié");
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
  function deleteStaff() {
    if (!id) return;

    deleteButton.$set({ disabled: true });

    // Demande de confirmation
    Notiflix.Confirm.show(
      "Suppression de personnel",
      `Voulez-vous vraiment supprimer le personnel ?`,
      "Supprimer",
      "Annuler",
      async function () {
        try {
          await stevedoringStaff.delete(id);

          Notiflix.Notify.success("Le personnel a été supprimé");
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

<!-- routify:options guard="personnel" -->

<main class="mx-auto w-10/12 lg:w-1/3">
  <PageHeading>Personnel de manutention</PageHeading>

  {#if !staff}
    <Chargement />
  {:else}
    <form
      class="flex flex-col gap-3 mb-4"
      bind:this={form}
      use:preventFormSubmitOnEnterKeydown
    >
      <!-- Prénom -->
      <div>
        <Label for="firstname">Prénom</Label>
        <Input
          type="text"
          id="firstname"
          bind:value={staff.firstname}
          placeholder="Prénom"
        />
      </div>

      <!-- Nom de famille -->
      <div>
        <Label for="lastname">Nom de famille</Label>
        <Input
          type="text"
          id="lastname"
          bind:value={staff.lastname}
          placeholder="Nom de famille"
        />
      </div>

      <!-- Téléphone -->
      <div>
        <Label for="phone">Téléphone</Label>
        <Input
          type="tel"
          id="phone"
          bind:value={staff.phone}
          placeholder="Téléphone"
        />
      </div>

      <!-- Type -->
      <div>
        <Label for="type">Type</Label>
        <Select id="type" bind:value={staff.type} placeholder="" required>
          <option value="mensuel">Mensuel</option>
          <option value="interim">Intérimaire</option>
        </Select>
      </div>

      <!-- Agence d'intérim -->
      {#if staff.type === "interim"}
        <div>
          <Label for="tempWorkAgency">Agence d'intérim</Label>
          <Svelecte
            inputId="tempWorkAgency"
            bind:value={staff.tempWorkAgency}
            options={[...tempWorkAgencies.values()]}
            placeholder="Agence d'intérim"
            creatable
            creatablePrefix=""
            allowEditing
            required
          />
        </div>
      {/if}

      <!-- Commentaires -->
      <div>
        <Label for="comments">Commentaires</Label>
        <Textarea
          id="comments"
          bind:value={staff.comments}
          placeholder="Commentaires"
        />

        <!-- Actif -->
        <div>
          <Label for="isActive">Actif</Label>
          <Toggle id="isActive" bind:checked={staff.isActive} />
        </div>
      </div>
    </form>

    <!-- Validation/Annulation/Suppression -->
    <div class="text-center">
      {#if isNew}
        <!-- Bouton "Ajouter" -->
        <BoutonAction
          preset="ajouter"
          on:click={createStaff}
          bind:this={createButton}
        />
      {:else}
        <!-- Bouton "Modifier" -->
        <BoutonAction
          preset="modifier"
          on:click={updateStaff}
          bind:this={updateButton}
        />
        <!-- Bouton "Supprimer" -->
        <BoutonAction
          preset="supprimer"
          on:click={deleteStaff}
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
