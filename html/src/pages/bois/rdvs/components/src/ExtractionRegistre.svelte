<!-- 
  @component
  
  Bouton d'extraction du registre d'affrètement.

  Usage :
  ```tsx
  <ExtractionRegistre />
  ```
 -->
<script lang="ts">
  import Notiflix from "notiflix";
  import { Modal, Label, Input, Button } from "flowbite-svelte";
  import { ScrollTextIcon } from "lucide-svelte";

  import { filter } from "../";
  import { LucideButton } from "@app/components";
  import { fetcher, DateUtils } from "@app/utils";

  // Affichage de la fenêtre modale
  let showModal = false;
  let startDate: string;
  let endDate: string;

  /**
   * Bouton registre
   *
   * Extraction du registre d'affrètement
   */
  async function extractRegistry(startDate: string, endDate: string) {
    try {
      const params = { date_debut: startDate, date_fin: endDate };

      const blob = await fetcher<Blob>("bois/registre", {
        searchParams: params,
        accept: "blob",
      });

      const file = URL.createObjectURL(blob);
      const filename = "registre_bois.csv";
      const link = document.createElement("a");
      link.href = file;
      link.download = filename;
      link.click();
    } catch (error) {
      Notiflix.Notify.failure(error.message);
    }
  }
</script>

<div class="bouton-registre hidden lg:grid">
  <LucideButton
    icon={ScrollTextIcon}
    title="Extraire le registre d'affrètement"
    on:click={() => (showModal = true)}
  />
</div>

<Modal
  title="Extraire le registre d'affrètement"
  bind:open={showModal}
  outsideclose
  autoclose
  dismissable={false}
  size="xs"
  on:open={() => {
    startDate =
      $filter.data.date_debut ||
      new DateUtils(new Date()).getPreviousWorkingDay().toLocaleISODateString();
    endDate =
      $filter.data.date_fin ||
      new DateUtils(new Date()).toLocaleISODateString();
  }}
>
  <div>
    <Label for="registry-start-date">Date début :</Label>
    <Input type="date" id="registry-start-date" bind:value={startDate} />
  </div>
  <div>
    <Label for="registry-end-date">Date fin :</Label>
    <Input type="date" id="registry-end-date" bind:value={endDate} />
  </div>
  <div class="text-center">
    <Button on:click={() => extractRegistry(startDate, endDate)}
      >Extraire</Button
    >
    <Button on:click={() => (showModal = false)} color="dark">Annuler</Button>
  </div>
</Modal>

<style>
  .bouton-registre {
    --size: 50px;

    place-items: center;
    position: fixed;
    right: 20px;
    bottom: calc(var(--footer-height) + 70px);
    width: var(--size);
    height: var(--size);
    z-index: 3;
    border-radius: 50%;
    background: radial-gradient(
      circle at center,
      white 0,
      white 50%,
      transparent 100%
    );
  }
</style>
