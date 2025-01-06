<script lang="ts">
  import { Modal, Label, Input } from "flowbite-svelte";
  import Notiflix from "notiflix";

  import { BoutonAction } from "@app/components";

  import {
    validerFormulaire,
    preventFormSubmitOnEnterKeydown,
    fetcher,
    DateUtils,
  } from "@app/utils";

  let form: HTMLFormElement;
  let downloadButton: BoutonAction;

  export let open = false;

  let date = new DateUtils(
    DateUtils.getMondayOfPreviousWeek()
  ).toLocaleISODateString();

  async function downloadReport() {
    if (!validerFormulaire(form)) return;

    downloadButton.$set({ disabled: true });

    try {
      const blob: Blob = await fetcher(
        "manutention/heures-interimaires/rapport",
        {
          accept: "blob",
          searchParams: {
            date,
          },
        }
      );

      const file = URL.createObjectURL(blob);
      const filename = "heures-interimaires.zip";
      const link = document.createElement("a");
      link.href = file;
      link.download = filename;
      link.click();
      open = false;
    } catch (erreur) {
      Notiflix.Notify.failure(erreur.message);
      console.error(erreur);
      downloadButton.$set({ disabled: false });
    }
  }
</script>

<Modal
  title="Rapport d'heures intérimaires"
  bind:open
  autoclose={false}
  outsideclose={false}
  size="xs"
>
  <form
    class="flex flex-col gap-3 mb-4"
    bind:this={form}
    use:preventFormSubmitOnEnterKeydown
  >
    <!-- Date -->
    <div class="w-full">
      <Label for="date">Semaine du</Label>
      <Input type="date" id="date" name="Date" bind:value={date} required />
    </div>
  </form>

  <!-- Validation/Annulation/Suppression -->
  <div class="text-center">
    <!-- Bouton "Ajouter" -->
    <BoutonAction
      preset="ajouter"
      text="Télécharger"
      on:click={downloadReport}
      bind:this={downloadButton}
    />

    <!-- Bouton "Annuler" -->
    <BoutonAction preset="annuler" on:click={() => (open = false)} />
  </div>
</Modal>
