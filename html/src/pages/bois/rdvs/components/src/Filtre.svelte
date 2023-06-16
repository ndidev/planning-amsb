<!-- 
  @component
  
  Bandeau filtre pour le planning bois.

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

  import type { FiltreBois } from "@app/types";

  const filtre = getContext<Writable<Filtre<FiltreBois>>>("filtre");

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
    sessionStorage.setItem("filtre-planning-bois", JSON.stringify(_filtre));

    filtre.set(new Filtre(_filtre));
  }

  /**
   * Supprimer le filtre.
   */
  function supprimerFiltre() {
    sessionStorage.removeItem("filtre-planning-bois");

    filtre.set(new Filtre({}));
    _filtre = {};
  }
</script>

<div
  id="bandeau-filtre"
  style:background={filtreActif ? "rgb(255, 210, 210)" : "white"}
>
  <button
    id="toggle-filtre"
    on:click={() => (filtreAffiche = !filtreAffiche)}
    title={`${filtreAffiche ? "Masquer" : "Afficher"} le filtre`}
  >
    {filtreAffiche ? "Masquer" : "Afficher"} le filtre
  </button>

  <form style:display={filtreAffiche ? "grid" : "none"}>
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
      <!-- Filtre fournisseur -->
      <div>
        <label for="filtre_fournisseur">Fournisseur</label>
        <Svelecte
          inputId="filtre_fournisseur"
          type="tiers"
          role="bois_fournisseur"
          bind:value={_filtre.fournisseur}
          placeholder="Fournisseur"
          multiple
          style="width: 100%;"
        />
      </div>

      <!-- Filtre client -->
      <div>
        <label for="filtre_client">Client</label>
        <Svelecte
          inputId="filtre_client"
          type="tiers"
          role="bois_client"
          bind:value={_filtre.client}
          placeholder="Client"
          multiple
          style="width: 100%;"
        />
      </div>
    </div>

    <div class="bloc">
      <!-- Filtre chargement -->
      <div class="">
        <label for="filtre_chargement">Chargement</label>
        <Svelecte
          inputId="filtre_chargement"
          type="tiers"
          role="bois_client"
          bind:value={_filtre.chargement}
          placeholder="Chargement"
          multiple
          style="width: 100%;"
        />
      </div>

      <!-- Filtre livraison -->
      <div>
        <label for="filtre_livraison">Livraison</label>
        <Svelecte
          inputId="filtre_livraison"
          type="tiers"
          role="bois_client"
          bind:value={_filtre.livraison}
          placeholder="Livraison"
          multiple
          style="width: 100%;"
        />
      </div>
    </div>

    <div class="bloc">
      <!-- Filtre transporteur -->
      <div>
        <label for="filtre_transporteur">Transporteur</label>
        <Svelecte
          inputId="filtre_transporteur"
          type="tiers"
          role="bois_transporteur"
          bind:value={_filtre.transporteur}
          placeholder="Transporteur"
          multiple
          style="width: 100%;"
        />
      </div>

      <!-- Filtre affréteur -->
      <div>
        <label for="filtre_affreteur">Affréteur</label>
        <Svelecte
          inputId="filtre_affreteur"
          type="tiers"
          role="bois_affreteur"
          bind:value={_filtre.affreteur}
          placeholder="Affréteur"
          multiple
          style="width: 100%;"
        />
      </div>
    </div>

    <div class="bloc">
      <!-- Boutons filtre -->
      <div>
        <button
          type="submit"
          name="filtrer"
          class="pure-button"
          on:click|preventDefault={appliquerFiltre}
        >
          Filtrer
        </button>
        <!-- <BoutonAction on:click={appliquerFiltre}>Filtrer</BoutonAction> -->
      </div>
      <div>
        <button
          type="reset"
          name="supprimer_filtre"
          class="pure-button"
          on:click={supprimerFiltre}
        >
          Supprimer le filtre
        </button>
        <!-- <BoutonAction on:click={supprimerFiltre}>
          Supprimer le filtre
        </BoutonAction> -->
      </div>
    </div>
  </form>
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

  form {
    display: flex;
    flex-direction: column;
    row-gap: 10px;
  }

  form > .bloc {
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
    margin: 3px auto;
    width: 80%;
    border-radius: 4px;
  }

  /* Desktop */
  @media screen and (min-width: 768px) {
    form {
      display: grid;
      grid-template-columns: 12% repeat(3, 23%) 15%;
      column-gap: 1%;
    }

    button {
      margin: 25px 0 5px 0;
    }
  }
</style>
