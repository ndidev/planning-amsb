<!-- 
  @component
  
  Ligne de configuration d'une côte.

  Usage :
  ```tsx
  <LigneCote cote: Cote={cote}>
  ```
 -->
<script lang="ts">
  import { ConfigLine } from "../../";
  import { LucideButton, NumericInput } from "@app/components";

  import Notiflix from "notiflix";

  import { fetcher } from "@app/utils";

  import type { Cote } from "@app/types";

  export let cote: Cote;
  let coteInitial = structuredClone(cote);

  let modificationEnCours = false;

  let ligne: HTMLDivElement;

  /**
   * Validation des modifications
   */
  async function validerModification() {
    try {
      Notiflix.Block.dots([ligne]);
      ligne.style.minHeight = "initial";

      cote.valeur = parseFloat(String(cote.valeur || 0));

      await fetcher(`config/cotes/${cote.cote}`, {
        requestInit: {
          method: "PUT",
          body: JSON.stringify(cote),
        },
      });

      Notiflix.Notify.success("Les informations ont été modifiées");
      coteInitial = cote;
      modificationEnCours = false;
    } catch (erreur) {
      Notiflix.Notify.failure(erreur.message);
    } finally {
      Notiflix.Block.remove([ligne]);
    }
  }

  /**
   * Annulation des modifications
   */
  function annulerModification() {
    cote = structuredClone(coteInitial);
    modificationEnCours = false;
  }
</script>

<ConfigLine bind:modificationEnCours bind:ligne>
  <div>
    <NumericInput
      id={"config_cotes_" + cote.cote}
      format="+2"
      bind:value={cote.valeur}
      on:input={() => (modificationEnCours = true)}
    />
  </div>

  <!-- Boutons -->
  <div slot="actions">
    {#if modificationEnCours}
      <LucideButton preset="confirm" on:click={validerModification} />
      <LucideButton preset="cancel" on:click={annulerModification} />
    {/if}
  </div>
</ConfigLine>
