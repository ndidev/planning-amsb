<!-- routify:options title="Planning AMSB - Bois" -->
<script lang="ts">
  import { onDestroy, setContext, getContext } from "svelte";
  import { writable } from "svelte/store";

  import { BandeauInfo, ConnexionSSE } from "@app/components";
  import {
    ExtractionRegistre,
    Filtre as BandeauFiltre,
    Placeholder,
    LigneDate,
    LigneRdv,
    LigneDateAttente,
    LigneRdvAttente,
  } from "./components";

  import { Filtre } from "@app/utils";
  import type { Stores, RdvBois, FiltreBois, CamionsParDate } from "@app/types";

  const { boisRdvs, tiers } = getContext<Stores>("stores");

  type DateString = string;
  type GroupesRdv = Map<DateString, RdvBois[]>;

  // Stores Filtre et RDVs
  let filtre = new Filtre<FiltreBois>(
    JSON.parse(sessionStorage.getItem("filtre-planning-bois")) || {}
  );

  const storeFiltre = writable(filtre);

  let rdvsBois: typeof $boisRdvs = null;

  const unsubscribeFiltre = storeFiltre.subscribe((value) => {
    boisRdvs.setParams(value.toParams());
  });

  const unsubscribeRdvs = boisRdvs.subscribe((rdvs) => {
    rdvsBois = rdvs;
  });

  setContext("filtre", storeFiltre);

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

<ConnexionSSE
  subscriptions={[
    "bois/rdvs",
    "tiers",
    "config/bandeau-info",
    "config/ajouts-rapides",
  ]}
/>

<BandeauInfo module="bois" pc />

<div class="filtre">
  <BandeauFiltre />
</div>

<!-- Filtre SQL pour registre affrètement -->
<div id="bouton-registre">
  <ExtractionRegistre />
</div>

<main>
  {#if rdvsBois}
    <!-- RDVs en attente -->
    <div>
      <LigneDateAttente camions={camions.get("attente")} />
      {#each [...rdvsGroupes.get("attente")] as rdv (rdv.id)}
        <LigneRdvAttente {rdv} />
      {/each}
    </div>

    <!-- RDVs plannifiés -->
    {#each [...rdvsGroupes] as [date, rdvs] (date)}
      {#if date !== "attente" && date !== null}
        <div>
          <LigneDate {date} camions={camions.get(date)} />
          {#each rdvs as rdv (rdv.id)}
            <LigneRdv {rdv} />
          {/each}
        </div>
      {/if}
    {/each}
  {:else}
    <!-- Chargement des données -->
    <Placeholder />
  {/if}
</main>

<style>
  * {
    --couleur-total: hsl(0, 0%, 0%);
    --couleur-attendus: hsl(0, 0%, 31%);
    --couleur-parc: hsl(29, 100%, 74%);
    --couleur-charges: hsl(110, 52%, 55%);
  }

  .filtre {
    position: sticky;
    top: 0;
    left: 0;
    margin-left: 100px;
    z-index: 2;
  }

  /* LISTE RDV */

  main {
    width: 95%;
    margin: auto;
    margin-bottom: 6rem;
  }

  /* Mobile */
  @media screen and (max-width: 767px) {
    #bouton-registre {
      display: none;
    }
  }

  /* Desktop */
  @media screen and (min-width: 768px) {
    #bouton-registre {
      --size: 50px;

      display: grid;
      position: fixed;
      right: 30px;
      top: 15px;
      width: var(--size);
      height: var(--size);
      z-index: 3;

      background: radial-gradient(
        circle at center,
        white 0,
        white 50%,
        transparent 100%
      );
      border-radius: 50%;
    }
  }
</style>
