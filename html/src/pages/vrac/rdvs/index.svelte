<!-- routify:options title="Planning AMSB - Vrac" -->
<script lang="ts">
  import { onMount, onDestroy, setContext, getContext } from "svelte";
  import { params } from "@roxi/routify";

  import { LigneDate, LigneRdv, Placeholder } from "./components";
  import { BandeauInfo, SseConnection } from "@app/components";

  import { fetcher } from "@app/utils";

  const { vracRdvs, vracProduits, marees } = getContext<Stores>("stores");

  import type { RdvVrac, Stores } from "@app/types";

  type DateString = string;
  type GroupesRdv = Map<DateString, RdvVrac[]>;

  let dates: Set<DateString>;
  let filteredAppointments: RdvVrac[] = [];
  let groupedAppointments: GroupesRdv;
  let datesMareesSup4m = new Set<string>();

  const archives = "archives" in $params;

  setContext("archives", archives);

  if (archives) {
    vracRdvs.setSearchParams({ archives: "true" });
  } else {
    vracRdvs.setSearchParams({});
  }

  $: if ($vracRdvs && $vracProduits) {
    filteredAppointments = filterArchivedAppointments($vracRdvs);
    dates = makeDatesSet(filteredAppointments);
    groupedAppointments = groupAppointments(filteredAppointments, dates);

    updateNaviresParDate();
    updateTides();
  }

  $: datesMareesSup4m = new Set(
    ($marees || [])
      .filter((maree) => maree.te_cesson > 4)
      .map((maree) => maree.date)
  );

  function filterArchivedAppointments(appointments: typeof $vracRdvs) {
    return [...appointments.values()].filter(
      (appointment) => appointment.archive === archives
    );
  }

  function makeDatesSet(appointments: RdvVrac[]) {
    return new Set(appointments.map(({ date_rdv }) => date_rdv).sort());
  }

  /**
   * Grouper et trier les RDVs.
   */
  function groupAppointments(appointments: RdvVrac[], dates: Set<DateString>) {
    const appointmentsByDate: GroupesRdv = new Map<DateString, RdvVrac[]>();

    dates.forEach((date) => {
      appointmentsByDate.set(
        date,
        appointments
          .filter(({ date_rdv }) => date_rdv === date)
          .sort(triPlanning)
      );
    });

    return appointmentsByDate;
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
   * Navires à quai par date.
   */
  let naviresParDate = new Map<string, string[]>();

  /**
   * Récupérer les navires à quai pour les dates de RDV.
   * @param debut
   * @param fin
   */
  async function getNaviresParDate(debut: string, fin: string) {
    const listeNavires: NaviresEnActivite = await fetcher(
      `consignation/navires-en-activite`,
      {
        searchParams: {
          date_debut: debut,
          date_fin: fin,
        },
      }
    );

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

    type NaviresEnActivite = [
      {
        navire: string;
        debut: string;
        fin: string;
      },
    ];
  }

  async function updateNaviresParDate() {
    naviresParDate = await getNaviresParDate(
      [...dates][0],
      [...dates][dates.size - 1]
    );
  }

  function updateTides() {
    const params = {
      debut: [...dates][0],
      fin: [...dates][dates.size - 1],
    };

    marees.setSearchParams(params);
  }

  onMount(() => {
    document.addEventListener(
      "planning:consignation/escales",
      updateNaviresParDate
    );
  });

  onDestroy(() => {
    document.removeEventListener(
      "planning:consignation/escales",
      updateNaviresParDate
    );
  });
</script>

<!-- routify:options query-params-is-page -->
<!-- routify:options guard="vrac" -->

<SseConnection
  subscriptions={[
    "vrac/rdvs",
    "vrac/produits",
    "consignation/escales",
    "tiers",
    "config/bandeau-info",
    "marees",
  ]}
/>

<div class="sticky top-0 z-[1] ml-16 lg:ml-24">
  <BandeauInfo module="vrac" pc />
</div>

<main class="w-11/12 mx-auto mb-8">
  {#if $vracRdvs && $vracProduits}
    {#each archives ? [...groupedAppointments].reverse() : [...groupedAppointments] as [date, appointments] (date)}
      <LigneDate
        {date}
        maree={datesMareesSup4m.has(date)}
        navires={naviresParDate.get(date) || []}
      />
      <div class="divide-y">
        {#each appointments as appointment (appointment.id)}
          <LigneRdv {appointment} />
        {/each}
      </div>
    {:else}
      <p class="mt-5 text-2xl text-center">Aucun rendez-vous.</p>
    {/each}
  {:else}
    <!-- Chargement des données -->
    <Placeholder />
  {/if}
</main>
