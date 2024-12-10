<script lang="ts">
  import { onDestroy, getContext } from "svelte";
  import type { Writable } from "svelte/store";

  import { Modal, Label, Input } from "flowbite-svelte";
  import Notiflix from "notiflix";

  import { LucideButton, BoutonAction, Svelecte } from "@app/components";

  import { validerFormulaire } from "@app/utils";

  import type { RdvBois, Stores } from "@app/types";

  const { boisRdvs } = getContext<Stores>("stores");

  let form: HTMLFormElement;
  let updateButton: BoutonAction;

  export let showDispatchModal: Writable<boolean>;
  export let appointment: RdvBois;

  export let awaitingDispatchBeforeOrderReady: boolean = false;
  export let toggleOrderReady: () => void = () => {};

  export let awaitingDispatchBeforeSettingDepartureTime: boolean = false;
  export let setDepartureTime: () => void = () => {};

  let dispatch: typeof appointment.dispatch;

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
    dispatch.splice(index, 1);

    dispatch = dispatch;
  }

  async function updateDispatch() {
    if (!validerFormulaire(form)) return;

    try {
      updateButton.$set({ disabled: true });

      await boisRdvs.patch(appointment.id, {
        dispatch,
      });

      Notiflix.Notify.success("Le dispatch a été mis à jour.");

      dispatch.forEach((item) => {
        delete item.new;
      });

      appointment.dispatch = dispatch;

      $showDispatchModal = false;

      if (awaitingDispatchBeforeOrderReady === true) {
        toggleOrderReady();
        awaitingDispatchBeforeOrderReady = false;
      }

      if (awaitingDispatchBeforeSettingDepartureTime === true) {
        setDepartureTime();
        awaitingDispatchBeforeSettingDepartureTime = false;
      }
    } catch (erreur) {
      Notiflix.Notify.failure(erreur.message);
    } finally {
      updateButton.$set({ disabled: false });
    }
  }

  function cancelUpdate() {
    appointment.dispatch = dispatch.filter((item) => !item.new);
    awaitingDispatchBeforeOrderReady = false;
    awaitingDispatchBeforeSettingDepartureTime = false;

    $showDispatchModal = false;
  }

  onDestroy(() => {
    unsubscribeShowModal();
  });
</script>

<Modal title="Dispatch" bind:open={$showDispatchModal} dismissable={false}>
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

        <div class="w-min">
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
            <option value="Chargement"></option>
            <option value="Déchargement"></option>
            <option value="Préparation"></option>
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
