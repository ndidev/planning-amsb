<!-- 
  @component
  
  Bandeau filtre pour le planning affrètement maritime.

  Usage :
  ```tsx
  <Filtre />
  ```
 -->
<script lang="ts">
  import { getContext } from "svelte";
  import type { Writable } from "svelte/store";

  import { Svelecte } from "@app/components";

  import { Filtre, device } from "@app/utils";

  import type { FiltreCharter } from "@app/types";

  const filtre = getContext<Writable<Filtre<FiltreCharter>>>("filtre");

  let _filtre = { ...$filtre.data };

  $: filtreActif =
    Object.values({ ...$filtre.data }).filter((value) =>
      Array.isArray(value) ? (value.length > 0 ? value : undefined) : value
    ).length > 0;
  $: filtreAffiche = filtreActif && $device.is("desktop");

  /**
   * Enregistrer le filtre.
   */
  async function appliquerFiltre() {
    sessionStorage.setItem(
      "filtre-planning-chartering",
      JSON.stringify(_filtre)
    );

    filtre.set(new Filtre(_filtre));
  }

  /**
   * Supprimer le filtre.
   */
  function supprimerFiltre() {
    sessionStorage.removeItem("filtre-planning-chartering");

    filtre.set(new Filtre({}));
    _filtre = {};
  }
</script>

<!-- Filtre par date/client -->
<div
  id="bandeau-filtre"
  style:background-color={filtreActif ? "hsl(0, 100%, 92%)" : "white"}
>
  <button
    id="toggle-filtre"
    on:click={() => (filtreAffiche = !filtreAffiche)}
    title={`${filtreAffiche ? "Masquer" : "Afficher"} le filtre`}
  >
    {filtreAffiche ? "Masquer" : "Afficher"} le filtre
  </button>

  <div class="champs" style:display={filtreAffiche ? "grid" : "none"}>
    <!-- Dates -->
    <div class="bloc">
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

    <div class="bloc">
      <!-- Filtre affréteur -->
      <div>
        <label for="filtre_affreteur">Affréteur</label>
        <Svelecte
          inputId="filtre_affreteur"
          type="tiers"
          role="maritime_affreteur"
          bind:value={_filtre.affreteur}
          placeholder="Affréteur"
          multiple
        />
      </div>

      <!-- Filtre armateur -->
      <div>
        <label for="filtre_armateur">Armateur</label>
        <Svelecte
          inputId="filtre_armateur"
          type="tiers"
          role="maritime_armateur"
          bind:value={_filtre.armateur}
          placeholder="Armateur"
          multiple
        />
      </div>
    </div>

    <div class="bloc">
      <!-- Filtre courtier -->
      <div>
        <label for="filtre_courtier">Courtier</label>
        <Svelecte
          inputId="filtre_courtier"
          type="tiers"
          role="maritime_courtier"
          bind:value={_filtre.courtier}
          placeholder="Courtier"
          multiple
        />
      </div>

      <!-- Filtre statut -->
      <div>
        <label for="filtre_statut">Statut</label>
        <select id="filtre_statut" bind:value={_filtre.statut}>
          <option value="">Tous</option>
          <option value="0">Plannifié (pas confirmé)</option>
          <option value="1">Confirmé par l'affréteur</option>
          <option value="2">Affrété</option>
          <option value="3">Chargement effectué</option>
          <option value="4">Voyage terminé</option>
        </select>
      </div>
    </div>

    <div class="bloc">
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
  #toggle-filtre {
    width: 90%;
    border: none;
    border-radius: 0;
    border-bottom: 1px solid #ccc;
    background-color: transparent;
    margin: 15px 0 15px 0;
    cursor: pointer;
  }

  .champs {
    display: flex;
    flex-direction: column;
    row-gap: 10px;
  }

  .champs > .bloc {
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

  select#filtre_statut {
    /* Pour correspondre au style de Svelecte */
    width: 100%;
    border: 1px solid #ccc;
    border-radius: 4px;
    min-height: 38px;
  }

  button {
    margin: 3px auto;
    width: 80%;
    border-radius: 4px;
  }

  /* Desktop */
  @media screen and (min-width: 768px) {
    .champs {
      display: grid;
      grid-template-columns: 12% repeat(3, 23%) 15%;
      column-gap: 1%;
    }

    button {
      margin: 25px 0 5px 0;
    }
  }
</style>
