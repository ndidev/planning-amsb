<script lang="ts">
  import { onDestroy, getContext } from "svelte";
  import type { Writable } from "svelte/store";

  import { Modal, Label, Input } from "flowbite-svelte";
  import Notiflix from "notiflix";

  import { LucideButton, BoutonAction, Svelecte } from "@app/components";

  import { validerFormulaire } from "@app/utils";

  import type { RdvVrac, Stores } from "@app/types";

  const { vracRdvs } = getContext<Stores>("stores");

  let form: HTMLFormElement;
  let updateButton: BoutonAction;

  export let showDispatchModal: Writable<boolean>;
  export let appointment: RdvVrac;

  export let awaitingDispatchBeforeOrderReady: boolean = false;
  export let toggleOrderReady: () => void = () => {};

  export let awaitingDispatchBeforeArchive: boolean;
  export let toggleArchive: () => void;

  type Dispatch = Array<
    RdvVrac["dispatch"][number] & {
      new?: boolean;
      deleted?: boolean;
    }
  >;

  let dispatch: Dispatch;

  const unsubscribeShowModal = showDispatchModal.subscribe((modalIsShown) => {
    if (modalIsShown) {
      dispatch = structuredClone(appointment.dispatch);
    }
  });

  function addDispatchLine() {
    dispatch = [
      ...dispatch,
      {
        staffId: null,
        date: new Date().toISOString().split("T")[0],
        remarks: "",
        new: true,
      },
    ];
  }

  function deleteDispatchLine(index: number) {
    if (dispatch[index].new) {
      dispatch.splice(index, 1);
    } else {
      dispatch[index].deleted = true;
    }

    dispatch = dispatch;
  }

  async function updateDispatch() {
    if (!validerFormulaire(form)) return;

    try {
      updateButton.$set({ disabled: true });

      const savedDispatch = dispatch.filter((item) => !item.deleted);

      await vracRdvs.patch(appointment.id, {
        dispatch: savedDispatch,
      });

      Notiflix.Notify.success("Le dispatch a été mis à jour.");

      savedDispatch.forEach((item) => {
        delete item.new;
      });

      appointment.dispatch = savedDispatch;

      $showDispatchModal = false;

      if (awaitingDispatchBeforeOrderReady === true) {
        awaitingDispatchBeforeOrderReady = false;
        toggleOrderReady();
      }

      if (awaitingDispatchBeforeArchive === true) {
        awaitingDispatchBeforeArchive = false;
        toggleArchive();
      }
    } catch (erreur) {
      Notiflix.Notify.failure(erreur.message);
    } finally {
      updateButton.$set({ disabled: false });
    }
  }

  function cancelUpdate() {
    appointment.dispatch = dispatch
      .filter((item) => !item.new)
      .map((item) => {
        delete item.deleted;
        return item;
      });

    awaitingDispatchBeforeOrderReady = false;
    awaitingDispatchBeforeArchive = false;

    $showDispatchModal = false;
  }

  onDestroy(() => {
    unsubscribeShowModal();
  });
</script>

<Modal
  title="Dispatch"
  bind:open={$showDispatchModal}
  dismissable={false}
  size="lg"
>
  <div class="text-lg">
    Ajouter une ligne
    <LucideButton
      preset="add"
      title="Ajouter une ligne"
      on:click={addDispatchLine}
    />
  </div>

  <form class="divide-y" bind:this={form}>
    {#each dispatch as dispatchItem, index}
      {#if !dispatchItem.deleted}
        <div
          class="flex flex-col items-center gap-2 py-1 lg:flex-row lg:gap-4 lg:py-2"
        >
          <div class="w-full">
            <Label for="">Personnel</Label>
            <Svelecte
              type="staff"
              inputId="staff-{index}"
              name="Personnel"
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
              name="Date"
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
      {/if}
    {/each}
  </form>

  <div class="text-center">
    <!-- Bouton "Modifier" -->
    <BoutonAction
      preset="modifier"
      on:click={updateDispatch}
      bind:this={updateButton}
    />

    <!-- Bouton "Annuler" -->
    <BoutonAction preset="annuler" on:click={cancelUpdate} />
  </div>
</Modal>
