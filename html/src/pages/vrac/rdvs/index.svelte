<!-- routify:options title="Planning AMSB - Vrac" -->
<script lang="ts">
  import { onMount, onDestroy } from "svelte";

  import {
    LigneDate,
    LigneRdv,
    Placeholder,
    FilterModal,
    filter,
  } from "./components";
  import { BandeauInfo, SseConnection } from "@app/components";

  import { fetcher } from "@app/utils";

  import {
    vracRdvs,
    vracProduits,
    consignationEscales,
    tiers,
    configBandeauInfo,
    marees,
  } from "@app/stores";

  import type { RdvVrac } from "@app/types";

  type DateString = string;
  type GroupesRdv = Map<DateString, RdvVrac[]>;

  let appointments: RdvVrac[];
  let groupedAppointments: GroupesRdv;
  let dates: Set<DateString>;
  let datesMareesSup4m = new Set<DateString>();

  let appointmentsReady = vracRdvs.getReadyState();

  const unsubscribeFilter = filter.subscribe((value) => {
    const params = value.toSearchParams();
    vracRdvs.setSearchParams(params);

    appointmentsReady = vracRdvs.getReadyState();
  });

  const unsubscribeAppointments = vracRdvs.subscribe((value) => {
    if (!value) return;

    appointments = [...value.values()];
    dates = makeDatesSet(appointments);
    groupedAppointments = groupAppointments(appointments, dates);

    updateNaviresParDate();
    updateTides();
  });

  $: datesMareesSup4m = new Set(
    ($marees || [])
      .filter((maree) => maree.te_cesson > 4)
      .map((maree) => maree.date),
  );

  function makeDatesSet(appointments: RdvVrac[]) {
    return new Set(
      [...appointments.values()].map(({ date_rdv }) => date_rdv).sort(),
    );
  }

  /**
   * Grouper et trier les RDVs.
   */
  function groupAppointments(appointments: RdvVrac[], dates: Set<DateString>) {
    const appointmentsByDate: GroupesRdv = new Map<DateString, RdvVrac[]>();

    dates.forEach((date) => {
      appointmentsByDate.set(
        date,
        [...appointments.values()]
          .filter(({ date_rdv }) => date_rdv === date)
          .sort(sortAppointments),
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
  function sortAppointments(a: RdvVrac, b: RdvVrac): number {
    // Vracs agro et Divers en premier
    if ([1, 2].includes(a.produit) || [1, 2].includes(b.produit)) {
      return a.produit - b.produit;
    }

    return compareTime(a, b) || compareProduct(a, b) || compareQuality(a, b);

    function compareTime(a: RdvVrac, b: RdvVrac): number {
      if (a.heure < b.heure || (a.heure && !b.heure)) return -1;
      if (a.heure > b.heure || (!a.heure && b.heure)) return 1;
      return 0;
    }

    function compareProduct(a: RdvVrac, b: RdvVrac): number {
      return ($vracProduits?.get(a.produit)?.nom || "").localeCompare(
        $vracProduits?.get(b.produit)?.nom || "",
      );
    }

    function compareQuality(a: RdvVrac, b: RdvVrac): number {
      return (
        $vracProduits
          ?.get(a.produit)
          ?.qualites.find((qualite) => qualite.id === a.qualite)?.nom || ""
      ).localeCompare(
        $vracProduits
          ?.get(b.produit)
          ?.qualites.find((qualite) => qualite.id === b.qualite)?.nom || "",
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
      },
    );

    const map = new Map<string, string[]>();

    dates.forEach((date) =>
      map.set(
        date,
        listeNavires
          .map((navire) =>
            date >= navire.debut && date <= navire.fin ? navire.navire : null,
          )
          .filter((navire) => navire !== null),
      ),
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
      [...dates][dates.size - 1],
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
      `planning:${consignationEscales.endpoint}`,
      updateNaviresParDate,
    );
  });

  onDestroy(() => {
    document.removeEventListener(
      `planning:${consignationEscales.endpoint}`,
      updateNaviresParDate,
    );

    unsubscribeAppointments();
    unsubscribeFilter();
  });
</script>

<!-- routify:options query-params-is-page -->
<!-- routify:options guard="vrac" -->

<SseConnection
  subscriptions={[
    vracRdvs.endpoint,
    vracProduits.endpoint,
    consignationEscales.endpoint,
    tiers.endpoint,
    configBandeauInfo.endpoint,
    marees.endpoint,
  ]}
/>

<div class="sticky top-0 z-[1] ml-16 lg:ml-24">
  <BandeauInfo module="vrac" pc />

  <FilterModal />
</div>

<main class="w-11/12 mx-auto mb-8">
  {#await Promise.all([appointmentsReady, vracProduits.getReadyState()])}
    <Placeholder />
  {:then}
    {#each groupedAppointments as [date, appointments] (date)}
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
  {/await}
</main>
