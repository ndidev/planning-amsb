<!-- routify:options title="Planning AMSB - Bois" -->
<script lang="ts">
  import { onDestroy } from "svelte";

  import { BandeauInfo, SseConnection } from "@app/components";
  import {
    ExtractionRegistre,
    FilterModal,
    filter,
    Placeholder,
    LigneDate,
    LigneRdv,
    LigneDateAttente,
    LigneRdvAttente,
  } from "./components";

  import { boisRdvs, tiers } from "@app/stores";

  import type { RdvBois, CamionsParDate } from "@app/types";

  type DateString = string;
  type GroupesRdv = Map<DateString, RdvBois[]>;

  let appointments: typeof $boisRdvs = null;

  const unsubscribeFilter = filter.subscribe((value) => {
    const params = value.toSearchParams();
    boisRdvs.setSearchParams(params);
  });

  const unsubscribeAppointments = boisRdvs.subscribe((value) => {
    appointments = value;
  });

  let dates: Set<DateString>;
  let groupedAppointments: GroupesRdv;
  let trucks: Map<DateString, CamionsParDate>;

  $: if (appointments) {
    dates = new Set(
      [...appointments.values()]
        .map(({ date_rdv, attente }) => (attente ? null : date_rdv))
        .sort()
    );

    groupedAppointments =
      ($tiers, groupAppointments([...appointments.values()]));

    trucks = new Map();

    groupedAppointments.forEach((rdvs, date) => {
      const trucksStats: CamionsParDate = {
        total: rdvs.length,
        attendus: rdvs.filter((rdv) => !rdv.heure_arrivee && !rdv.heure_depart)
          .length,
        sur_parc: rdvs.filter((rdv) => rdv.heure_arrivee && !rdv.heure_depart)
          .length,
        charges: rdvs.filter((rdv) => rdv.heure_arrivee && rdv.heure_depart)
          .length,
      };

      trucks.set(date, trucksStats);
    });
  }

  /**
   * Grouper et trier les RDVs.
   */
  function groupAppointments(appointments: RdvBois[]) {
    const appointmentsByDate: GroupesRdv = new Map<DateString, RdvBois[]>();

    dates.forEach((date) => {
      appointmentsByDate.set(
        date,
        appointments
          .filter(({ date_rdv, attente }) => date_rdv === date && !attente)
          .sort(sortPlanning)
      );
    });

    appointmentsByDate.set(
      "attente",
      appointments.filter(({ attente }) => attente).sort()
    );

    return appointmentsByDate;
  }

  /**
   * Fonction de tri du planning.
   *
   * Tri par :
   * - heure d'arrivée, croissant (null en dernier)
   * - heure de départ, croissant (null en dernier)
   * - nom de client, croissant
   * - numéro de BL, croissant
   */
  function sortPlanning(a: RdvBois, b: RdvBois): number {
    return (
      compareArrivalTime(a, b) ||
      compareDepartureTime(a, b) ||
      compareSupplierName(a, b) ||
      compareCustomerName(a, b) ||
      compareDeliveryNoteNumber(a, b)
    );

    function compareArrivalTime(a: RdvBois, b: RdvBois): number {
      if (
        a.heure_arrivee < b.heure_arrivee ||
        (a.heure_arrivee && !b.heure_arrivee)
      )
        return -1;
      if (
        a.heure_arrivee > b.heure_arrivee ||
        (!a.heure_arrivee && b.heure_arrivee)
      )
        return 1;
      return 0;
    }

    function compareDepartureTime(a: RdvBois, b: RdvBois): number {
      if (
        a.heure_depart < b.heure_depart ||
        (a.heure_depart && !b.heure_depart)
      )
        return -1;
      if (
        a.heure_depart > b.heure_depart ||
        (!a.heure_depart && b.heure_depart)
      )
        return 1;
      return 0;
    }

    function compareCustomerName(a: RdvBois, b: RdvBois): number {
      if (!$tiers) return 0;

      return ($tiers.get(a.client)?.nom_court || "").localeCompare(
        $tiers.get(b.client)?.nom_court || ""
      );
    }

    function compareSupplierName(a: RdvBois, b: RdvBois): number {
      if (!$tiers) return 0;

      return ($tiers.get(a.fournisseur)?.nom_court || "").localeCompare(
        $tiers.get(b.fournisseur)?.nom_court || ""
      );
    }

    function compareDeliveryNoteNumber(a: RdvBois, b: RdvBois): number {
      if (a.numero_bl < b.numero_bl) return -1;
      if (a.numero_bl > b.numero_bl) return 1;
      return 0;
    }
  }

  onDestroy(() => {
    unsubscribeAppointments();
    unsubscribeFilter();
  });
</script>

<!-- routify:options guard="bois" -->

<SseConnection
  subscriptions={[
    "bois/rdvs",
    "tiers",
    "config/bandeau-info",
    "config/ajouts-rapides",
  ]}
/>

<div class="sticky top-0 z-[1] ml-16 lg:ml-24">
  <BandeauInfo module="bois" pc />
</div>

<ExtractionRegistre />

<FilterModal />

<main class="w-11/12 mx-auto mb-24">
  {#if appointments}
    <!-- RDVs en attente -->
    <div>
      <LigneDateAttente camions={trucks.get("attente")} />
      <div class="divide-y">
        {#each [...groupedAppointments.get("attente")] as appointment (appointment.id)}
          <LigneRdvAttente {appointment} />
        {/each}
      </div>
    </div>

    <!-- RDVs plannifiés -->
    {#each [...groupedAppointments] as [date, scheduledAppointments] (date)}
      {#if date !== "attente" && date !== null}
        <div>
          <LigneDate {date} camions={trucks.get(date)} />
          <div class="divide-y">
            {#each scheduledAppointments as appointment (appointment.id)}
              <LigneRdv {appointment} />
            {/each}
          </div>
        </div>
      {/if}
    {/each}
  {:else}
    <!-- Chargement des données -->
    <Placeholder />
  {/if}
</main>
