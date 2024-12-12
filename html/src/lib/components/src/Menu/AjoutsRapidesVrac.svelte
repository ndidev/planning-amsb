<script lang="ts">
  import { getContext } from "svelte";
  import Notiflix from "notiflix";

  import type { Stores } from "@app/types";

  const { vracRdvs } = getContext<Stores>("stores");

  async function createAnimalFeedAppointment() {
    const miscelaneousThirdPartyId = 6;
    const animalFeedLoadingsProductId = 1;

    try {
      await vracRdvs.create({
        id: null,
        date_rdv: new Date().toISOString().split("T")[0],
        heure: null,
        produit: animalFeedLoadingsProductId,
        qualite: null,
        quantite: 0,
        max: false,
        fournisseur: miscelaneousThirdPartyId,
        client: miscelaneousThirdPartyId,
        transporteur: null,
        commande_prete: false,
        commentaire_public: "",
        commentaire_prive: "",
        num_commande: "",
        archive: false,
        showOnTv: false,
        dispatch: [],
      });

      document.dispatchEvent(new CustomEvent("planning:bois/rdvs"));
    } catch (error) {
      Notiflix.Notify.failure(error.message);
    }
  }
</script>

<ul>
  <li>
    <button on:click={createAnimalFeedAppointment}> Vracs agro </button>
  </li>
</ul>

<style>
  ul {
    display: none;
    position: absolute;
    z-index: 9;
    background-color: hsl(0, 0%, 90%);
    list-style-type: none;
    padding: 0;
    margin: 0;
  }

  li {
    position: relative;
  }

  li:hover {
    background-color: hsl(0, 0%, 78%);
  }

  button {
    color: black;
    background-color: transparent;
    border: none;
    padding: 12px;
    font:
      bold 12px Arial,
      Helvetica;
    text-transform: uppercase;
    text-decoration: none;
    white-space: nowrap;
    display: block;
    width: 100%;
    text-align: start;
    cursor: pointer;
  }

  button:hover {
    background-color: hsl(0, 0%, 59%);
    color: white;
  }
</style>
