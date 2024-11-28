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

  import { Label, Input } from "flowbite-svelte";
  import Notiflix from "notiflix";

  import { ConfigLine } from "../../";
  import { LucideButton } from "@app/components";

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
  });
</script>

<ConfigLine bind:modificationEnCours bind:ligne>
  <div class="w-full lg:w-11/24">
    <!-- Nom -->
    <div class="mb-4">
      <Label for={"nom_" + service.service}>Nom</Label>
      <Input
        type="text"
        id={"nom_" + service.service}
        bind:value={service.nom}
      />
    </div>

    <!-- Adresse (ligne 1) -->
    <div class="mb-4">
      <Label for={"adresse_ligne_1_" + service.service}>Adresse (ligne 1)</Label
      >
      <Input
        type="text"
        id={"adresse_ligne_1_" + service.service}
        bind:value={service.adresse_ligne_1}
      />
    </div>

    <!-- Adresse (ligne 2) -->
    <div class="mb-4">
      <Label for={"adresse_ligne_2_" + service.service}>Adresse (ligne 2)</Label
      >
      <Input
        type="text"
        id={"adresse_ligne_2_" + service.service}
        bind:value={service.adresse_ligne_2}
      />
    </div>

    <!-- Code Postal -->
    <div class="mb-4">
      <Label for={"cp_" + service.service}>Code Postal</Label>
      <Input type="text" id={"cp_" + service.service} bind:value={service.cp} />
    </div>

    <!-- Ville -->
    <div class="mb-4">
      <Label for={"ville_" + service.service}>Ville</Label>
      <Input
        type="text"
        id={"ville_" + service.service}
        bind:value={service.ville}
      />
    </div>

    <!-- Pays -->
    <div class="mb-4">
      <Label for={"pays_" + service.service}>Pays</Label>
      <Input
        type="text"
        id={"pays_" + service.service}
        bind:value={service.pays}
      />
    </div>
  </div>

  <div class="w-full lg:w-11/24 mb-auto">
    <!-- Téléphone -->
    <div class="mb-4">
      <Label for={"telephone_" + service.service}>Téléphone</Label>
      <Input
        type="text"
        id={"telephone_" + service.service}
        bind:value={service.telephone}
      />
    </div>

    <!-- Mobile -->
    <div class="mb-4">
      <Label for={"mobile_" + service.service}>Mobile</Label>
      <Input
        type="text"
        id={"mobile_" + service.service}
        bind:value={service.mobile}
      />
    </div>

    <!-- E-mail -->
    <div class="mb-4">
      <Label for={"email_" + service.service}>E-mail</Label>
      <Input
        type="text"
        id={"email_" + service.service}
        bind:value={service.email}
      />
    </div>
  </div>

  <!-- Boutons -->
  <div slot="actions">
    {#if modificationEnCours}
      <LucideButton preset="confirm" on:click={validerModification} />
      <LucideButton preset="cancel" on:click={annulerModification} />
    {/if}
  </div>
</ConfigLine>
