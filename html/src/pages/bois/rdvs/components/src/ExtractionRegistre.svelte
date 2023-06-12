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

  import { MaterialButton } from "@app/components";
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
      `<div class="pure-form pdf-form-notiflix">
          <div class="pdf-champ-notiflix">
            <label>Date début : <input type="date" class="date_debut" value="${date_debut}"></label>
          </div>
          <div class="pdf-champ-notiflix">
            <label>Date fin : <input type="date" class="date_fin" value="${date_fin}"></label>
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
        params,
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

<MaterialButton
  icon="assignment"
  title="Extraire registre d'affrètement"
  on:click={afficherChoixDates}
/>
