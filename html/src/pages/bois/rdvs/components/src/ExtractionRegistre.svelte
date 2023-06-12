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
  import { type Filtre, fetcher } from "@app/utils";
  import type { FiltreBois } from "@app/types";

  const filtre = getContext<Writable<Filtre<FiltreBois>>>("filtre");

  /**
   * Bouton registre
   *
   * Extraction du registre d'affrètement
   */
  async function extraireRegistreAffretement() {
    try {
      const params: { date_debut?: string; date_fin?: string } = {};
      if ($filtre.data.date_debut) params.date_debut = $filtre.data.date_debut;
      if ($filtre.data.date_fin) params.date_fin = $filtre.data.date_fin;
      console.log({ params });

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
  on:click={extraireRegistreAffretement}
/>
