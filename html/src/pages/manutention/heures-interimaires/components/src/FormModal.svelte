<script lang="ts">
  import { getContext } from "svelte";

  import { Modal, Label, Input } from "flowbite-svelte";
  import Notiflix from "notiflix";

  import { BoutonAction, Svelecte, NumericInput } from "@app/components";

  import {
    notiflixOptions,
    validerFormulaire,
    preventFormSubmitOnEnterKeydown,
  } from "@app/utils";

  import type { TempWorkHours, Stores } from "@app/types";

  const { stevedoringTempWorkHours } = getContext<Stores>("stores");

  let form: HTMLFormElement;
  let createButton: BoutonAction;
  let updateButton: BoutonAction;
  let deleteButton: BoutonAction;

  const newTempWorkHoursEntry = {
    ...stevedoringTempWorkHours.getTemplate(),
    date: new Date().toISOString().split("T")[0],
    hoursWorked: 8,
  };

  export let tempWorkHours: TempWorkHours = { ...newTempWorkHoursEntry };

  let initialTempWorkHours = structuredClone(tempWorkHours);

  export let open = false;

  let isNew = tempWorkHours.id === null;

  async function createHours() {
    if (!validerFormulaire(form)) return;

    createButton.$set({ disabled: true });

    try {
      await stevedoringTempWorkHours.create(tempWorkHours);

      Notiflix.Notify.success("Les heures ont été ajoutées");

      initialTempWorkHours = structuredClone(tempWorkHours);
      open = false;
    } catch (erreur) {
      Notiflix.Notify.failure(erreur.message);
      console.error(erreur);
      createButton.$set({ disabled: false });
    }
  }

  async function updateHours() {
    if (!validerFormulaire(form)) return;

    updateButton.$set({ disabled: true });

    try {
      await stevedoringTempWorkHours.update(tempWorkHours);

      Notiflix.Notify.success("Les heures ont été modifiées");
      open = false;
    } catch (erreur) {
      Notiflix.Notify.failure(erreur.message);
      console.error(erreur);
      updateButton.$set({ disabled: false });
    }
  }

  function deleteHours() {
    if (!tempWorkHours.id) return;

    deleteButton.$set({ disabled: true });

    // Demande de confirmation
    Notiflix.Confirm.show(
      "Suppression des heures",
      `Voulez-vous vraiment supprimer les heures ?`,
      "Supprimer",
      "Annuler",
      async function () {
        try {
          await stevedoringTempWorkHours.delete(tempWorkHours.id);

          Notiflix.Notify.success("L'équipement a été supprimé");
          open = false;
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

  function cancel() {
    tempWorkHours = structuredClone(initialTempWorkHours);
    open = false;
  }
</script>

<Modal
  title="Heures intérimaire"
  bind:open
  autoclose={false}
  outsideclose={false}
>
  <form
    class="flex flex-col gap-3 mb-4"
    bind:this={form}
    use:preventFormSubmitOnEnterKeydown
  >
    <!-- Intérimaire -->
    <div>
      <Label for="staff">Intérimaire</Label>
      <Svelecte
        type="interimaires"
        inputId="staff"
        name="staff"
        bind:value={tempWorkHours.staffId}
        placeholder="Intérimaire"
        required
      />
    </div>

    <!-- Date -->
    <div>
      <Label for="date">Date</Label>
      <Input
        type="date"
        id="date"
        name="date"
        bind:value={tempWorkHours.date}
        placeholder="Date"
      />
    </div>

    <!-- Heures -->
    <div>
      <Label for="hoursWorked">Heures</Label>
      <NumericInput
        id="hoursWorked"
        format="+2"
        bind:value={tempWorkHours.hoursWorked}
        placeholder="Heures"
        required
      />
    </div>

    <!-- Commentaires -->
    <div>
      <Label for="comments">Commentaires</Label>
      <Input
        id="comments"
        bind:value={tempWorkHours.comments}
        placeholder="Commentaires"
      />
    </div>
  </form>

  <!-- Validation/Annulation/Suppression -->
  <div class="text-center">
    {#if isNew}
      <!-- Bouton "Ajouter" -->
      <BoutonAction
        preset="ajouter"
        on:click={createHours}
        bind:this={createButton}
      />
    {:else}
      <!-- Bouton "Modifier" -->
      <BoutonAction
        preset="modifier"
        on:click={updateHours}
        bind:this={updateButton}
      />
      <!-- Bouton "Supprimer" -->
      <BoutonAction
        preset="supprimer"
        on:click={deleteHours}
        bind:this={deleteButton}
      />
    {/if}

    <!-- Bouton "Annuler" -->
    <BoutonAction preset="annuler" on:click={cancel} />
  </div>
</Modal>
