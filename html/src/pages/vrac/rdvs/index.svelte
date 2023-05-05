<!-- routify:options title="Planning AMSB - Vrac" -->
<script lang="ts">
  import { onMount, onDestroy } from "svelte";
  import { derived } from "svelte/store";

  import { LigneDate, LigneRdv, Placeholder } from "./components";
  import { BandeauInfo } from "@app/components";

  import { vracRdvs, vracProduits, marees } from "@app/stores";

  import { fetcher, demarrerConnexionSSE } from "@app/utils";

  import type { RdvVrac } from "@app/types";

  let source: EventSource;

  type DateString = string;
  type GroupesRdv = Map<DateString, RdvVrac[]>;

  let dates: Set<DateString>;
  let rdvsGroupes: GroupesRdv;

  $: if ($vracRdvs && $vracProduits) {
    dates = new Set(
      [...$vracRdvs.values()].map(({ date_rdv }) => date_rdv).sort()
    );

    rdvsGroupes = grouperRdvs([...$vracRdvs.values()]);

    updateNaviresParDate();
  }

  /**
   * Grouper et trier les RDVs.
   */
  function grouperRdvs(rdvs: RdvVrac[]) {
    const rdvsParDate: GroupesRdv = new Map<DateString, RdvVrac[]>();

    dates.forEach((date) => {
      rdvsParDate.set(
        date,
        rdvs.filter(({ date_rdv }) => date_rdv === date).sort(triPlanning)
      );
    });

    return rdvsParDate;
  }

  /**
   * Fonction de tri du planning.
   *
   * Tri par :
   * - heure, croissant (null en dernier)
   * - nom de produit, croissant
   * - nom de qualite, croissant
   */
  function triPlanning(a: RdvVrac, b: RdvVrac): number {
    return (
      comparerHeure(a, b) || comparerProduit(a, b) || comparerQualite(a, b)
    );

    function comparerHeure(a: RdvVrac, b: RdvVrac): number {
      if (a.heure < b.heure || (a.heure && !b.heure)) return -1;
      if (a.heure > b.heure || (!a.heure && b.heure)) return 1;
      return 0;
    }

    function comparerProduit(a: RdvVrac, b: RdvVrac): number {
      return ($vracProduits.get(a.produit)?.nom || "").localeCompare(
        $vracProduits.get(b.produit)?.nom || ""
      );
    }

    function comparerQualite(a: RdvVrac, b: RdvVrac): number {
      return (
        $vracProduits
          .get(a.produit)
          ?.qualites.find((qualite) => qualite.id === a.qualite)?.nom || ""
      ).localeCompare(
        $vracProduits
          .get(b.produit)
          ?.qualites.find((qualite) => qualite.id === b.qualite)?.nom || ""
      );
    }
  }

  /**
   * Dates pour lesquelles il y a une marée supérieure à 4m (pour le point d'exclamation).
   */
  const datesMareesSup4m = derived(
    marees,
    ($marees) =>
      new Set(
        ($marees || [])
          .filter((maree) => maree.te_cesson > 4)
          .map((maree) => maree.date)
      )
  );

  /**
   * Navires à quai par date.
   */
  let naviresParDate = new Map<string, string[]>();

  /**
   * Récupérer les navires à quai pour les dates de RDV.
   * @param debut
   * @param fin
   */
  async function getNaviresParDate(debut: string, fin: string) {
    const listeNavires: ListeNavires = await fetcher(`consignation/navires`, {
      params: {
        date_debut: debut,
        date_fin: fin,
      },
    });

    const map = new Map<string, string[]>();

    dates.forEach((date) =>
      map.set(
        date,
        listeNavires
          .map((navire) =>
            date >= navire.debut && date <= navire.fin ? navire.navire : null
          )
          .filter((navire) => navire !== null)
      )
    );

    return map;

    type ListeNavires = [
      {
        navire: string;
        debut: string;
        fin: string;
      }
    ];
  }

  const updateNaviresParDate = async () => {
    naviresParDate = await getNaviresParDate(
      [...dates][0],
      [...dates][dates.size - 1]
    );
  };

  onMount(async () => {
    source = await demarrerConnexionSSE([
      "vrac/rdvs",
      "vrac/produits",
      "consignation/escales",
      "tiers",
      "config/bandeau-info",
      "marees",
    ]);

    document.addEventListener(
      "planning:consignation/escales",
      updateNaviresParDate
    );
  });

  onDestroy(() => {
    source.close();
    document.removeEventListener(
      "planning:consignation/escales",
      updateNaviresParDate
    );
  });
</script>

<!-- routify:options guard="vrac" -->

<BandeauInfo module="vrac" pc />

<main>
  {#if $vracRdvs && $vracProduits}
    {#each [...rdvsGroupes] as [date, rdvs] (date)}
      <LigneDate
        {date}
        maree={$datesMareesSup4m.has(date)}
        navires={naviresParDate.get(date) || []}
      />
      <div>
        {#each rdvs as rdv (rdv.id)}
          <LigneRdv {rdv} />
        {/each}
      </div>
    {/each}
  {:else}
    <!-- Chargement des données -->
    <Placeholder />
  {/if}
</main>

<style>
  main {
    width: 90vw;
    margin: auto;
    margin-bottom: 2rem;
  }
</style>
