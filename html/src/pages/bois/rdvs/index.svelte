<!-- routify:options title="Planning AMSB - Bois" -->
<script lang="ts">
  import { onDestroy, getContext } from "svelte";

  import { BandeauInfo, SseConnection } from "@app/components";
  import {
    ExtractionRegistre,
    FilterBanner,
    filter,
    Placeholder,
    LigneDate,
    LigneRdv,
    LigneDateAttente,
    LigneRdvAttente,
  } from "./components";

  import type { Stores, RdvBois, CamionsParDate } from "@app/types";

  const { boisRdvs, tiers } = getContext<Stores>("stores");

  type DateString = string;
  type GroupesRdv = Map<DateString, RdvBois[]>;

  let rdvsBois: typeof $boisRdvs = null;

  const unsubscribeFiltre = filter.subscribe((value) => {
    boisRdvs.setSearchParams(value.toSearchParams());
  });

  const unsubscribeRdvs = boisRdvs.subscribe((rdvs) => {
    rdvsBois = rdvs;
  });

  let dates: Set<DateString>;
  let rdvsGroupes: GroupesRdv;
  let camions: Map<DateString, CamionsParDate>;

  $: if (rdvsBois) {
    dates = new Set(
      [...rdvsBois.values()]
        .map(({ date_rdv, attente }) => (attente ? null : date_rdv))
        .sort()
    );

    rdvsGroupes = ($tiers, grouperRdvs([...rdvsBois.values()]));

    camions = new Map();

    rdvsGroupes.forEach((rdvs, date) => {
      const statsCamions: CamionsParDate = {
        total: rdvs.length,
        attendus: rdvs.filter((rdv) => !rdv.heure_arrivee && !rdv.heure_depart)
          .length,
        sur_parc: rdvs.filter((rdv) => rdv.heure_arrivee && !rdv.heure_depart)
          .length,
        charges: rdvs.filter((rdv) => rdv.heure_arrivee && rdv.heure_depart)
          .length,
      };

      camions.set(date, statsCamions);
    });
  }

  /**
   * Grouper et trier les RDVs.
   */
  function grouperRdvs(rdvs: RdvBois[]) {
    const rdvsParDate: GroupesRdv = new Map<DateString, RdvBois[]>();

    dates.forEach((date) => {
      rdvsParDate.set(
        date,
        rdvs
          .filter(({ date_rdv, attente }) => date_rdv === date && !attente)
          .sort(triPlanning)
      );
    });

    rdvsParDate.set("attente", rdvs.filter(({ attente }) => attente).sort());

    return rdvsParDate;
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
  function triPlanning(a: RdvBois, b: RdvBois): number {
    return (
      comparerHeureArrivee(a, b) ||
      comparerHeureDepart(a, b) ||
      comparerFournisseur(a, b) ||
      comparerClient(a, b) ||
      comparerNumeroBL(a, b)
    );

    function comparerHeureArrivee(a: RdvBois, b: RdvBois): number {
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

    function comparerHeureDepart(a: RdvBois, b: RdvBois): number {
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

    function comparerClient(a: RdvBois, b: RdvBois): number {
      if (!$tiers) return 0;

      return ($tiers.get(a.client)?.nom_court || "").localeCompare(
        $tiers.get(b.client)?.nom_court || ""
      );
    }

    function comparerFournisseur(a: RdvBois, b: RdvBois): number {
      if (!$tiers) return 0;

      return ($tiers.get(a.fournisseur)?.nom_court || "").localeCompare(
        $tiers.get(b.fournisseur)?.nom_court || ""
      );
    }

    function comparerNumeroBL(a: RdvBois, b: RdvBois): number {
      if (a.numero_bl < b.numero_bl) return -1;
      if (a.numero_bl > b.numero_bl) return 1;
      return 0;
    }
  }

  onDestroy(() => {
    unsubscribeRdvs();
    unsubscribeFiltre();
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

  <FilterBanner />
</div>

<ExtractionRegistre />

<main class="w-11/12 mx-auto mb-24">
  {#if rdvsBois}
    <!-- RDVs en attente -->
    <div>
      <LigneDateAttente camions={camions.get("attente")} />
      <div class="divide-y">
        {#each [...rdvsGroupes.get("attente")] as appointment (appointment.id)}
          <LigneRdvAttente {appointment} />
        {/each}
      </div>
    </div>

    <!-- RDVs plannifiés -->
    {#each [...rdvsGroupes] as [date, scheduledAppointments] (date)}
      {#if date !== "attente" && date !== null}
        <div>
          <LigneDate {date} camions={camions.get(date)} />
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
