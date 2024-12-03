<!-- 
  @component
  
  Bouton d'extraction du registre d'affrètement.

  Usage :
  ```tsx
  <ExtractionRegistre />
  ```
 -->
<script lang="ts">
  import { getContext } from "svelte";
  import type { Writable } from "svelte/store";

  import Notiflix from "notiflix";
  import { ScrollTextIcon } from "lucide-svelte";

  import { LucideButton } from "@app/components";
  import { type Filtre, fetcher, notiflixOptions, DateUtils } from "@app/utils";
  import type { FiltreBois } from "@app/types";

  const filtre = getContext<Writable<Filtre<FiltreBois>>>("filtre");

  /**
   * Afficher la fenêtre de choix des dates.
   */
  function afficherChoixDates() {
    let date_debut: string =
      $filtre.data.date_debut ??
      new DateUtils(new Date()).jourOuvrePrecedent().toLocaleISODateString();
    let date_fin: string =
      $filtre.data.date_fin ??
      new DateUtils(new Date()).toLocaleISODateString();

    Notiflix.Confirm.show(
      "Extraire le registre d'affrètement",
      `<div class="text-right">
          <div class="mb-1 mr-8">
            <label>Date début : <input type="date" value="${date_debut}"></label>
          </div>
          <div class="mb-1 mr-8">
            <label>Date fin : <input type="date" value="${date_fin}"></label>
          </div>
        </div>`,
      "Extraire",
      "Annuler",
      () =>
        extraireRegistreAffretement(
          document.querySelector<HTMLInputElement>(".date_debut").value,
          document.querySelector<HTMLInputElement>(".date_fin").value
        ),
      null,
      notiflixOptions.themes.green
    );
  }

  /**
   * Bouton registre
   *
   * Extraction du registre d'affrètement
   */
  async function extraireRegistreAffretement(
    date_debut: string,
    date_fin: string
  ) {
    try {
      const params = { date_debut, date_fin };

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
    title="Extraire registre d'affrètement"
    on:click={afficherChoixDates}
  />
</div>

<style>
  .bouton-registre {
    --size: 50px;

    place-items: center;
    position: fixed;
    right: 30px;
    top: 15px;
    width: var(--size);
    height: var(--size);
    z-index: 3;

    background: radial-gradient(
      circle at center,
      white 0,
      white 50%,
      transparent 100%
    );
    border-radius: 50%;
  }
</style>
