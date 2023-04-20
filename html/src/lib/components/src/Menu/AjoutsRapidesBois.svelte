<script lang="ts">
  import { onDestroy, getContext } from "svelte";
  import Notiflix from "notiflix";

  import { locale } from "@app/utils";

  import type { Stores, AjoutRapideBois, Tiers } from "@app/types";

  const { configAjoutsRapides, boisRdvs, tiers } = getContext<Stores>("stores");

  let ajoutsRapides: Map<Tiers["id"], AjoutRapideBois[]>;

  const unsubscribe = configAjoutsRapides.subscribe((configsAjouts) => {
    ajoutsRapides = new Map();

    [...configsAjouts.bois.values()]
      .sort((a, b) => trierParNom("client", a, b))
      .forEach((ajout) => {
        // Peuplement de l'objet rdvsBois
        if (!ajoutsRapides.has(ajout.client)) {
          ajoutsRapides.set(ajout.client, []);
        }

        ajoutsRapides.get(ajout.client).push(ajout);
      });

    // Pour chaque client, tri suivant le nom du transporteur
    ajoutsRapides.forEach((rdvs) =>
      rdvs.sort((a, b) => trierParNom("transporteur", a, b))
    );

    ajoutsRapides = ajoutsRapides;
  });

  /**
   * Trier un tableau de RDVs rapides par nom de tiers.
   * @param role Rôle du tiers servant au tri.
   * @param a
   * @param b
   */
  function trierParNom(
    role: keyof Omit<AjoutRapideBois, "id" | "module">,
    a: AjoutRapideBois,
    b: AjoutRapideBois
  ) {
    return $tiers?.get(a[role]).nom_court < $tiers?.get(b[role]).nom_court
      ? -1
      : 1;
  }

  async function creerRdv(rdv: AjoutRapideBois) {
    try {
      await boisRdvs.create({
        id: null,
        date_rdv: new Date()
          .toLocaleDateString(locale)
          .split("/")
          .reverse()
          .join("-"),
        heure_arrivee: new Date().toLocaleTimeString(),
        heure_depart: null,
        fournisseur: rdv.fournisseur,
        client: rdv.client,
        livraison: rdv.livraison,
        chargement: rdv.chargement,
        transporteur: rdv.transporteur,
        affreteur: rdv.affreteur,
        confirmation_affretement: false,
        attente: false,
        commentaire_public: "",
        commentaire_cache: "",
        numero_bl: "",
      });

      document.dispatchEvent(new CustomEvent("planning:bois/rdvs"));
    } catch (error) {
      Notiflix.Notify.failure(error.message);
    }
  }

  onDestroy(() => {
    unsubscribe();
  });
</script>

{#if ajoutsRapides.size > 0}
  <ul>
    {#each [...ajoutsRapides.entries()] as [client, rdvs]}
      <li>
        <button on:click={() => creerRdv({ ...rdvs[0], transporteur: null })}>
          {$tiers?.get(client).nom_court}
        </button>
        <ul>
          {#each rdvs as rdv}
            <li class="menu-rapide">
              <button on:click={() => creerRdv(rdv)}>
                {$tiers?.get(rdv.transporteur).nom_court}
              </button>
            </li>
          {/each}
        </ul>
      </li>
    {/each}
  </ul>
{/if}

<style>
  ul {
    display: none;
    position: absolute;
    z-index: 9;
    background-color: rgb(230, 230, 230);
    list-style-type: none;
    padding: 0;
    margin: 0;
  }

  ul ul {
    left: 100%;
    top: 0;
    background-color: rgb(200, 200, 200);
  }

  li {
    position: relative;
  }

  li:hover {
    background-color: rgb(200, 200, 200);
  }

  li:hover > ul {
    display: block;
  }

  button {
    color: black;
    background-color: transparent;
    border: none;
    padding: 12px;
    font: bold 12px Arial, Helvetica;
    text-transform: uppercase;
    text-decoration: none;
    white-space: nowrap;
    display: block;
    width: 100%;
    text-align: start;
    cursor: pointer;
  }

  button:hover {
    background-color: rgb(150, 150, 150);
    color: white;
  }
</style>
