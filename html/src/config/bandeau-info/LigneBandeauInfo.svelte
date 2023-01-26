<!-- 
  @component
  
  Ligne de configuration d'une ligne du bandeau d'information.

  Usage :
  ```tsx
  <LigneBandeauInfo ligneInfo: LigneBandeauInfo={ligneInfo}>
  ```
 -->
<script lang="ts">
  import { onMount } from "svelte";

  import { MaterialButton } from "@app/components";

  import { lignesBandeauInfo } from "@app/stores";

  import Notiflix from "notiflix";
  import autosize from "autosize";

  import { env } from "@app/utils";

  export let ligneInfo: LigneBandeauInfo;
  let ligneInfoInitial: LigneBandeauInfo = structuredClone(ligneInfo);

  let isNew: boolean =
    typeof ligneInfo.id === "string" && ligneInfo.id.startsWith("new_");

  let modificationEnCours = isNew; // Modification en cours par défaut si nouveau compte uniquement;

  let ligne: HTMLLIElement;

  /**
   * Valider les modifications.
   */
  async function validerModification() {
    const url = new URL(env.api);
    url.pathname += `config/bandeau-info/${ligneInfo.id}`;

    try {
      Notiflix.Block.dots(`#${ligne.id}`);

      const reponse = await fetch(url, {
        method: "PUT",
        body: JSON.stringify(ligneInfo),
      });

      if (!reponse.ok) {
        throw new Error(`${reponse.status}, ${reponse.statusText}`);
      }

      Notiflix.Notify.success("La ligne d'information a été modifiée");
      modificationEnCours = false;
      ligneInfoInitial = structuredClone(await reponse.json());
    } catch (erreur) {
      Notiflix.Notify.failure(erreur.message);
    } finally {
      Notiflix.Block.remove(`#${ligne.id}`);
    }
  }

  /**
   * Annuler les modifications.
   */
  function annulerModification() {
    ligneInfo = structuredClone(ligneInfoInitial);
    modificationEnCours = false;
  }

  /**
   * Valider l'ajout.
   */
  async function validerAjout() {
    const url = new URL(env.api);
    url.pathname += `config/bandeau-info`;

    try {
      Notiflix.Block.dots(`#${ligne.id}`);

      const tempUid = ligneInfo.id;

      const reponse = await fetch(url, {
        method: "POST",
        body: JSON.stringify(ligneInfo),
      });

      if (!reponse.ok) {
        throw new Error(`${reponse.status}, ${reponse.statusText}`);
      }

      ligneInfo = await reponse.json();

      // Mise à jour du store
      lignesBandeauInfo.update((lignes) => {
        lignes[ligneInfo.module].push(ligneInfo);
        lignes[ligneInfo.module] = lignes[ligneInfo.module].filter(
          (ligne: LigneBandeauInfo) => ligne.id !== tempUid
        );
        return lignes;
      });

      Notiflix.Notify.success("La ligne d'information a été ajoutée");
    } catch (erreur) {
      Notiflix.Notify.failure(erreur.message);
    } finally {
      Notiflix.Block.remove(`#${ligne.id}`);
    }
  }

  /**
   * Annuler l'ajout.
   */
  function annulerAjout() {
    lignesBandeauInfo.update((lignes) => {
      lignes[ligneInfo.module] = lignes[ligneInfo.module].filter(
        (_ligne: LigneBandeauInfo) => _ligne.id !== ligneInfo.id
      );
      return lignes;
    });
  }

  /**
   * Supprimer une ligne.
   */
  function supprimerLigne() {
    // Demande de confirmation
    Notiflix.Confirm.show(
      "Suppression ligne d'information",
      `Voulez-vous vraiment supprimer la ligne d'information ?`,
      "Supprimer",
      "Annuler",
      async function () {
        const url = new URL(env.api);
        url.pathname += `config/bandeau-info/${ligneInfo.id}`;

        try {
          Notiflix.Block.dots(`#${ligne.id}`);

          const reponse = await fetch(url, {
            method: "DELETE",
          });

          if (!reponse.ok) {
            throw new Error(`${reponse.status}, ${reponse.statusText}`);
          }

          Notiflix.Notify.success("La ligne d'information a été supprimée");

          // Mise à jour du store
          lignesBandeauInfo.update((lignes) => {
            lignes[ligneInfo.module] = lignes[ligneInfo.module].filter(
              (ligne) => ligne.id !== ligneInfo.id
            );
            return lignes;
          });
        } catch (erreur) {
          Notiflix.Notify.failure(erreur.message);
        } finally {
          Notiflix.Block.remove(`#${ligne.id}`);
        }
      },
      null,
      {
        titleColor: "#ff5549",
        okButtonColor: "#f8f8f8",
        okButtonBackground: "#ff5549",
        cancelButtonColor: "#f8f8f8",
        cancelButtonBackground: "#a9a9a9",
      }
    );
  }

  onMount(() => {
    ligne.id = "config_bandeau-info_" + ligneInfo.id;

    // Si changement d'une ligne, activation de la classe "modificationEnCours"
    ligne
      .querySelectorAll<HTMLInputElement | HTMLTextAreaElement>(
        ".pc, .tv, .couleur, .message"
      )
      .forEach((input) => {
        input.onchange = () => (modificationEnCours = true);
        input.oninput = () => (modificationEnCours = true);
      });

    autosize(ligne.querySelector("textarea"));
  });
</script>

<li class="ligne pure-form" class:modificationEnCours bind:this={ligne}>
  <div class="bloc pure-u-1 pure-u-lg-6-24">
    <!-- Active PC -->
    <span class="champ pure-u-7-24">
      <label class="pure-checkbox"
        >PC
        <input type="checkbox" bind:checked={ligneInfo.pc} />
      </label>
    </span>
    <!-- Active TV -->
    <span class="champ pure-u-7-24">
      <label class="pure-checkbox"
        >TV
        <input type="checkbox" bind:checked={ligneInfo.tv} />
      </label>
    </span>
    <!-- Couleur -->
    <span class="champ pure-u-7-24">
      <input type="color" class="couleur" bind:value={ligneInfo.couleur} />
    </span>
  </div>
  <!-- Message -->
  <span class="champ pure-u-1 pure-u-lg-16-24">
    <textarea class="message" bind:value={ligneInfo.message} rows="1" />
  </span>
  <!-- Boutons -->
  <span class="actions">
    {#if !isNew && !modificationEnCours}
      <MaterialButton
        icon="delete"
        title="Supprimer"
        on:click={supprimerLigne}
      />
    {/if}
  </span>
  <span class="valider-annuler">
    <MaterialButton
      icon="done"
      title="Valider"
      on:click={isNew ? validerAjout : validerModification}
    />
    <MaterialButton
      icon="close"
      title="Annuler"
      on:click={isNew ? annulerAjout : annulerModification}
    />
  </span>
</li>

<style>
  .ligne .champ {
    margin-left: 1%;
  }

  input[type="color"] {
    margin: 0.5em 0;
  }

  textarea {
    width: 100%;
    padding: 5px;
  }

  @media screen and (max-width: 480px) {
    .message {
      margin-top: 5px;
    }
  }
</style>
