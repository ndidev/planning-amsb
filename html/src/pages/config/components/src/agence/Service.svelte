<!-- 
  @component
  
  Bloc de configuration des informations d'un service de l'agence.

  Usage :
  ```tsx
  <Service service: ServiceAgence={service}>
  ```
 -->
<script lang="ts">
  import { onMount } from "svelte";

  import Notiflix from "notiflix";

  import { MaterialButton } from "@app/components";

  import { fetcher, notiflixOptions } from "@app/utils";

  import type { ServiceAgence } from "@app/types";

  export let service: ServiceAgence;
  let serviceInitial: ServiceAgence = structuredClone(service);

  let modificationEnCours: boolean = false;

  let ligne: HTMLDivElement;

  /**
   * Validation des modifications
   */
  async function validerModification() {
    try {
      Notiflix.Block.dots([ligne], notiflixOptions.texts.modification);
      ligne.style.minHeight = "initial";

      serviceInitial = (await fetcher(`config/agence/${service.service}`, {
        requestInit: {
          method: "PUT",
          body: JSON.stringify(service),
        },
      })) as ServiceAgence;

      Notiflix.Notify.success("Les informations ont été modifiées");
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
    service = structuredClone(serviceInitial);
    modificationEnCours = false;
  }

  onMount(() => {
    ligne.id = "config_agence_" + service.service;

    // Si changement d'une ligne, activation de la classe "modificationEnCours"
    for (const input of ligne.querySelectorAll("input")) {
      input.oninput = () => (modificationEnCours = true);
    }
  });
</script>

<div class="ligne pure-form" class:modificationEnCours bind:this={ligne}>
  <div class="bloc pure-u-1 pure-u-lg-11-24">
    <!-- Nom -->
    <div class="pure-control-group">
      <label for={"nom_" + service.service}>Nom</label>
      <input
        type="text"
        id={"nom_" + service.service}
        bind:value={service.nom}
      />
    </div>

    <!-- Adresse (ligne 1) -->
    <div class="pure-control-group">
      <label for={"adresse_ligne_1_" + service.service}>Adresse (ligne 1)</label
      >
      <input
        type="text"
        id={"adresse_ligne_1_" + service.service}
        bind:value={service.adresse_ligne_1}
      />
    </div>

    <!-- Adresse (ligne 2) -->
    <div class="pure-control-group">
      <label for={"adresse_ligne_2_" + service.service}>Adresse (ligne 2)</label
      >
      <input
        type="text"
        id={"adresse_ligne_2_" + service.service}
        bind:value={service.adresse_ligne_2}
      />
    </div>

    <!-- Code Postal -->
    <div class="pure-control-group">
      <label for={"cp_" + service.service}>Code Postal</label>
      <input type="text" id={"cp_" + service.service} bind:value={service.cp} />
    </div>

    <!-- Ville -->
    <div class="pure-control-group">
      <label for={"ville_" + service.service}>Ville</label>
      <input
        type="text"
        id={"ville_" + service.service}
        bind:value={service.ville}
      />
    </div>

    <!-- Pays -->
    <div class="pure-control-group">
      <label for={"pays_" + service.service}>Pays</label>
      <input
        type="text"
        id={"pays_" + service.service}
        bind:value={service.pays}
      />
    </div>
  </div>

  <div class="bloc pure-u-1 pure-u-lg-11-24">
    <!-- Téléphone -->
    <div class="pure-control-group">
      <label for={"telephone_" + service.service}>Téléphone</label>
      <input
        type="text"
        id={"telephone_" + service.service}
        bind:value={service.telephone}
      />
    </div>

    <!-- Mobile -->
    <div class="pure-control-group">
      <label for={"mobile_" + service.service}>Mobile</label>
      <input
        type="text"
        id={"mobile_" + service.service}
        bind:value={service.mobile}
      />
    </div>

    <!-- E-mail -->
    <div class="pure-control-group">
      <label for={"email_" + service.service}>E-mail</label>
      <input
        type="text"
        id={"email_" + service.service}
        bind:value={service.email}
      />
    </div>
  </div>

  <!-- Boutons -->
  <span class="valider-annuler">
    <MaterialButton
      icon="done"
      title="Valider"
      on:click={validerModification}
    />
    <MaterialButton
      icon="close"
      title="Annuler"
      on:click={annulerModification}
    />
  </span>
</div>

<style>
  .ligne {
    text-align: right;
  }

  input {
    width: 70%;
  }

  @media screen and (max-width: 480px) {
    .ligne {
      text-align: left;
      flex-direction: column;
      width: 100%;
    }

    input {
      width: 100%;
    }
  }
</style>
