<script lang="ts" context="module">
  import type { StevedoringStaff } from "@app/types";

  export function getDedicatedStaffForSupplier(
    supplierId: RdvBois["fournisseur"]
  ) {
    const map = new Map<RdvBois["fournisseur"], StevedoringStaff["id"]>([
      [219, 13], // W2W => YV
    ]);

    return map.get(supplierId) || null;
  }
</script>

<script lang="ts">
  import { Modal, Label, Input } from "flowbite-svelte";
  import Notiflix from "notiflix";

  import { LucideButton, BoutonAction, Svelecte } from "@app/components";

  import { validerFormulaire, DateUtils } from "@app/utils";

  import { boisRdvs } from "@app/stores";

  import type { RdvBois } from "@app/types";
  import { get } from "svelte/store";

  let form: HTMLFormElement;
  let updateButton: BoutonAction;

  export let open: boolean;
  export let appointment: RdvBois;

  export let awaitingDispatchBeforeOrderReady: boolean = false;
  export let toggleOrderReady: () => void = () => {};

  export let awaitingDispatchBeforeSettingDepartureTime: boolean = false;
  export let setDepartureTime: () => void = () => {};

  type Dispatch = Array<
    RdvBois["dispatch"][number] & {
      new?: boolean;
      deleted?: boolean;
    }
  >;

  let dispatch: Dispatch;

  function addDispatchLine() {
    dispatch = [
      ...dispatch,
      {
        staffId: null,
        date: new DateUtils().toLocaleISODateString(),
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

  function addLoadingDispatchLineIfLoadingStarted() {
    if (
      appointment.heure_arrivee &&
      !dispatch.some((item) => item.remarks === "Chargement")
    ) {
      dispatch = [
        ...dispatch,
        {
          staffId: getDedicatedStaffForSupplier(appointment.fournisseur),
          date: new DateUtils(appointment.date_rdv).toLocaleISODateString(),
          remarks: appointment.chargement === 1 ? "Chargement" : "Déchargement",
          new: true,
        },
      ];
    }
  }

  async function updateDispatch() {
    if (!validerFormulaire(form)) return;

    try {
      updateButton.$set({ disabled: true });

      appointment = await boisRdvs.patch(appointment.id, {
        dispatch: dispatch.filter((item) => !item.deleted),
      });

      Notiflix.Notify.success("Le dispatch a été mis à jour.");

      open = false;

      if (awaitingDispatchBeforeOrderReady === true) {
        awaitingDispatchBeforeOrderReady = false;
        toggleOrderReady();
      }

      if (awaitingDispatchBeforeSettingDepartureTime === true) {
        awaitingDispatchBeforeSettingDepartureTime = false;
        setDepartureTime();
      }
    } catch (erreur) {
      Notiflix.Notify.failure(erreur.message);
    } finally {
      updateButton.$set({ disabled: false });
    }
  }

  function cancelUpdate() {
    awaitingDispatchBeforeOrderReady = false;
    awaitingDispatchBeforeSettingDepartureTime = false;
    open = false;
  }
</script>

<Modal
  title="Dispatch"
  bind:open
  dismissable={false}
  size="lg"
  on:open={() => {
    dispatch = structuredClone(appointment.dispatch);
    addLoadingDispatchLineIfLoadingStarted();
  }}
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
