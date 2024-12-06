<script lang="ts">
  import { onMount, getContext } from "svelte";

  import { Modal, Label, Input } from "flowbite-svelte";
  import Notiflix from "notiflix";

  import { LucideButton, BoutonAction, Svelecte } from "@app/components";

  import type { RdvVrac, Stores } from "@app/types";

  const { vracRdvs } = getContext<Stores>("stores");

  let updateButton: BoutonAction;

  export let showDispatchModal = false;
  export let appointment: RdvVrac;
  export let awaitingDispatchBeforeArchive: boolean;
  export let toggleArchive: () => void;

  let dispatch: typeof appointment.dispatch;

  function addDispatchLine() {
    dispatch = [
      ...dispatch,
      {
        staffId: null,
        remarks: "",
      },
    ];
  }

  function deleteDispatchLine(index: number) {
    dispatch.splice(index, 1);

    dispatch = dispatch;
  }

  async function updateDispatch() {
    try {
      updateButton.$set({ disabled: true });

      await vracRdvs.patch(appointment.id, {
        dispatch,
      });

      Notiflix.Notify.success("Le dispatch a été mis à jour.");

      appointment.dispatch = dispatch;

      showDispatchModal = false;

      if (awaitingDispatchBeforeArchive === true) {
        toggleArchive();
      }

      awaitingDispatchBeforeArchive = false;
    } catch (erreur) {
      Notiflix.Notify.failure(erreur.message);
    } finally {
      updateButton.$set({ disabled: false });
    }
  }

  function restoreDispatch() {
    dispatch = structuredClone(appointment.dispatch);
  }

  onMount(() => {
    restoreDispatch();
  });
</script>

<Modal title="Dispatch" bind:open={showDispatchModal} dismissable={false}>
  <div class="text-lg">
    Ajouter une ligne
    <LucideButton
      preset="add"
      title="Ajouter une ligne"
      on:click={addDispatchLine}
    />
  </div>

  <div class="divide-y">
    {#each dispatch as dispatchItem, index}
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
        <div class="w-full">
          <Label for="remarks-{index}">Rôle</Label>
          <Input type="text" bind:value={dispatchItem.remarks} />
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

  <div class="text-center">
    <!-- Bouton "Modifier" -->
    <BoutonAction
      preset="modifier"
      on:click={updateDispatch}
      bind:this={updateButton}
    />

    <!-- Bouton "Annuler" -->
    <BoutonAction
      preset="annuler"
      on:click={() => {
        restoreDispatch();
        showDispatchModal = false;
      }}
    />
  </div>
</Modal>
