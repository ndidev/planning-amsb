<!-- 
  @component
  
  Bandeau filtre pour le planning consignation.

  Usage :
  ```tsx
  <Filtre />
  ```
 -->
<script lang="ts">
  import { onMount, getContext } from "svelte";
  import type { Writable } from "svelte/store";

  import { Svelecte } from "@app/components";
  import { Filtre, fetcher } from "@app/utils";

  import type { FiltreConsignation } from "@app/types";

  const filtre = getContext<Writable<Filtre<FiltreConsignation>>>("filtre");

  let _filtre = { ...$filtre.data };

  let listeNavires: string[] = [];
  let listeMarchandises: string[] = [];
  let listeClients: string[] = [];

  /**
   * Enregistrer le filtre.
   */
  async function appliquerFiltre() {
    sessionStorage.setItem(
      "filtre-stats-consignation",
      JSON.stringify(_filtre)
    );

    filtre.set(new Filtre(_filtre));
  }

  /**
   * Supprimer le filtre.
   */
  function supprimerFiltre() {
    sessionStorage.removeItem("filtre-stats-consignation");

    filtre.set(new Filtre({}));
    _filtre = {};
  }

  onMount(async () => {
    listeNavires = await fetcher<string[]>("consignation/navires");
    listeMarchandises = await fetcher<string[]>("consignation/marchandises");
    listeClients = await fetcher<string[]>("consignation/clients");
  });
</script>

<div id="bandeau-filtre">
  <div class="champs">
    <!-- Dates -->
    <div class="filtre_bloc">
      <div>
        <label for="date_debut">Du</label>
        <input
          type="date"
          id="date_debut"
          class="datepicker pure-input-1"
          name="date_debut"
          bind:value={_filtre.date_debut}
        />
      </div>

      <div>
        <label for="date_fin">Au</label>
        <input
          type="date"
          id="date_fin"
          class="datepicker pure-input-1"
          name="date_fin"
          bind:value={_filtre.date_fin}
        />
      </div>
    </div>

    <div class="filtre_bloc">
      <!-- Filtre navire -->
      <div>
        <label for="filtre_navire">Navires</label>
        <Svelecte
          inputId="filtre_navire"
          options={listeNavires}
          bind:value={_filtre.navire}
          labelAsValue
          placeholder="Navires"
          multiple
          virtualList
          style="width: 100%;"
        />
      </div>

      <!-- Filtre armateur -->
      <div>
        <label for="filtre_armateur">Armateurs</label>
        <Svelecte
          inputId="filtre_armateur"
          type="tiers"
          role="maritime_armateur"
          bind:value={_filtre.armateur}
          placeholder="Armateurs"
          multiple
          style="width: 100%;"
        />
      </div>
    </div>

    <div class="filtre_bloc">
      <!-- Filtre marchandises -->
      <div>
        <label for="filtre_marchandise">Marchandises</label>
        <Svelecte
          inputId="filtre_marchandise"
          options={listeMarchandises}
          bind:value={_filtre.marchandise}
          labelAsValue
          placeholder="Marchandises"
          multiple
          virtualList
          style="width: 100%;"
        />
      </div>

      <!-- Filtre clients -->
      <div>
        <label for="filtre_client">Clients</label>
        <Svelecte
          inputId="filtre_client"
          options={listeClients}
          bind:value={_filtre.client}
          labelAsValue
          placeholder="Clients"
          multiple
          virtualList
          style="width: 100%;"
        />
      </div>
    </div>

    <div class="filtre_bloc">
      <!-- Filtre port précédent -->
      <div>
        <label for="filtre_last_port">Ports précédents</label>
        <Svelecte
          inputId="filtre_last_port"
          type="port"
          bind:value={_filtre.last_port}
          placeholder="Ports précédents"
          multiple
          virtualList
          style="width: 100%;"
        />
      </div>

      <!-- Filtre port suivant -->
      <div>
        <label for="filtre_next_port">Ports suivants</label>
        <Svelecte
          inputId="filtre_next_port"
          type="port"
          bind:value={_filtre.next_port}
          placeholder="Ports suivants"
          multiple
          virtualList
          style="width: 100%;"
        />
      </div>
    </div>

    <div class="filtre_bloc">
      <!-- Boutons filtre -->
      <div>
        <button name="filtrer" class="pure-button" on:click={appliquerFiltre}>
          Filtrer
        </button>
      </div>
      <div>
        <button
          name="supprimer_filtre"
          class="pure-button"
          on:click={supprimerFiltre}
        >
          Supprimer le filtre
        </button>
      </div>
    </div>
  </div>
</div>

<style>
  .filtre_bloc {
    display: grid;
    row-gap: 10px;
  }

  label {
    display: inline-block;
    margin-bottom: 5px;
    margin-left: 5px;
  }

  input[type="date"] {
    /* Pour correspondre au style de Svelecte */
    width: 100%;
    border: 1px solid #ccc;
    border-radius: 4px;
    min-height: 38px;
  }

  button {
    margin: 25px 0 5px 0;
    width: 80%;
    border-radius: 4px;
  }

  .champs {
    display: grid;
    grid-template-columns: 12% repeat(3, 23%) 15%;
    column-gap: 1%;
  }

  @media screen and (max-width: 480px) {
    #bandeau-filtre {
      width: 60%;
      padding: 0;
      margin: auto;
    }

    button {
      margin: 3px auto;
    }
  }
</style>
