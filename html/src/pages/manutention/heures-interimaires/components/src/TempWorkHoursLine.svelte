<script lang="ts">
  import { Label, Input } from "flowbite-svelte";
  import Notiflix from "notiflix";

  import { LucideButton, Svelecte, NumericInput } from "@app/components";
  import { notiflixOptions, DateUtils } from "@app/utils";

  import type { TempWorkHours, StevedoringStaff } from "@app/types";

  import {
    stevedoringStaff,
    stevedoringTempWorkHours,
    currentUser,
  } from "@app/stores";

  export let tempWorkHours: TempWorkHours;

  let initialTempWorkHours = structuredClone(tempWorkHours);

  let line: HTMLDivElement;

  let isNew = tempWorkHours.id < 1;

  let editing = isNew;

  let staff: StevedoringStaff;

  $: if ($stevedoringStaff) {
    staff = $stevedoringStaff.get(tempWorkHours.staffId);
  }

  function stringifyTime(time: number): string {
    const hours = Math.floor(time);
    const minutes = Math.round((time - hours) * 60);
    return `${hours}h${minutes.toString().padStart(2, "0")}`;
  }

  async function createTempWorkHours() {
    try {
      Notiflix.Block.dots([line], notiflixOptions.texts.ajout);
      line.style.minHeight = "initial";

      await stevedoringTempWorkHours.create(tempWorkHours);

      Notiflix.Notify.success("Les heures ont été ajoutées");
    } catch (erreur) {
      Notiflix.Notify.failure(erreur.message);
    } finally {
      Notiflix.Block.remove([line]);
    }
  }

  async function updateTempWorkHours() {
    try {
      Notiflix.Block.dots([line], notiflixOptions.texts.modification);
      line.style.minHeight = "initial";

      await stevedoringTempWorkHours.update(tempWorkHours);

      Notiflix.Notify.success("Les heures ont été modifiées");
      initialTempWorkHours = structuredClone(tempWorkHours);
      editing = false;
    } catch (erreur) {
      Notiflix.Notify.failure(erreur.message);
    } finally {
      Notiflix.Block.remove([line]);
    }
  }

  async function deleteTempWorkHours() {
    const staffName = staff?.fullname;
    const formattedDate = new DateUtils(tempWorkHours.date).format().long;

    Notiflix.Confirm.show(
      "Suppression des heures",
      `Voulez-vous vraiment supprimer les heures de <strong>${staffName}</strong> pour le <strong>${formattedDate}</strong> ?`,
      "Supprimer",
      "Annuler",
      async function () {
        try {
          Notiflix.Block.dots([line], notiflixOptions.texts.suppression);
          line.style.minHeight = "initial";

          await stevedoringTempWorkHours.delete(tempWorkHours.id);

          Notiflix.Notify.success("Les heures ont été supprimées");
        } catch (erreur) {
          console.error(erreur);
          Notiflix.Notify.failure(erreur.message);
          Notiflix.Block.remove([line]);
        }
      },
      null,
      notiflixOptions.themes.red
    );
  }

  function cancelCreate() {
    stevedoringTempWorkHours.cancel(tempWorkHours.id);
  }

  function cancelUpdate() {
    tempWorkHours = structuredClone(initialTempWorkHours);
    editing = false;
  }
</script>

<div
  class="group my-2 flex flex-row flex-wrap items-center gap-1 lg:flex-row lg:flex-nowrap lg:gap-4"
  bind:this={line}
>
  {#if !editing}
    <div class="whitespace-nowrap">
      {`${staff?.fullname} (${staff?.tempWorkAgency})`}
    </div>

    <div class="ms-auto lg:ms-0">
      {stringifyTime(tempWorkHours.hoursWorked)}
    </div>

    <div class="w-full lg:w-auto">{tempWorkHours.comments}</div>

    {#if !isNew && $currentUser.canEdit("manutention")}
      <div
        class="invisible hidden group-hover:visible lg:inline whitespace-nowrap"
      >
        <LucideButton preset="edit" on:click={() => (editing = true)} />
        <LucideButton preset="delete" on:click={deleteTempWorkHours} />
      </div>
    {/if}
  {:else}
    <div class="w-full lg:w-2/5">
      <Label for="staff">Personnel</Label>
      <Svelecte
        type="interimaires"
        inputId="staff"
        name="staff"
        bind:value={tempWorkHours.staffId}
        placeholder="Personnel"
        required
      />
    </div>

    <div class="w-full lg:w-auto">
      <Label for="hoursWorked">Heures</Label>
      <NumericInput
        id="hoursWorked"
        format="+2"
        bind:value={tempWorkHours.hoursWorked}
        placeholder="Heures"
        required
      />
    </div>

    <div class="w-full lg:w-2/5">
      <Label for="comments">Commentaires</Label>
      <Input type="text" id="comments" bind:value={tempWorkHours.comments} />
    </div>

    <!-- Boutons -->
    <div class="flex flex-auto flex-row text-center">
      <LucideButton
        preset="confirm"
        on:click={isNew ? createTempWorkHours : updateTempWorkHours}
      />
      <LucideButton
        preset="cancel"
        on:click={isNew ? cancelCreate : cancelUpdate}
      />
    </div>
  {/if}
</div>
