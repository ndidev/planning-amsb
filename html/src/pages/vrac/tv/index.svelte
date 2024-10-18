<!-- routify:options title="Planning AMSB - Vrac" -->
<script lang="ts">
  import { LigneDate, LigneRdv } from "./components";
  import { BandeauInfo, SseConnection } from "@app/components";

  import { vracRdvs, vracProduits, currentUser } from "@app/stores";

  import type { RdvVrac } from "@app/types";

  type GroupesRdv = Map<string, RdvVrac[]>;

  let dates: Set<string>;
  let rdvsGroupes: GroupesRdv;

  $: if ($vracRdvs && $vracProduits) {
    dates = new Set(
      [...$vracRdvs.values()].map(({ date_rdv }) => date_rdv).sort()
    );
    rdvsGroupes = grouperRdvs([...$vracRdvs.values()]);
  }

  /**
   * Grouper et trier les RDVs.
   */
  function grouperRdvs(rdvs: RdvVrac[]) {
    const groupes: GroupesRdv = new Map<string, RdvVrac[]>();

    dates.forEach((date) => {
      groupes.set(
        date,
        rdvs.filter(({ date_rdv }) => date_rdv === date).sort(triPlanning)
      );
    });

    return groupes;
  }

  /**
   * Fonction de tri du planning.
   *
   * Tri par :
   * - nom de produit, croissant
   * - heure, croissant (null en dernier)
   * - nom de qualite, croissant
   */
  function triPlanning(a: RdvVrac, b: RdvVrac): number {
    if (!$vracProduits) return 0;

    return (
      comparerProduit(a, b) || comparerHeure(a, b) || comparerQualite(a, b)
    );

    function comparerHeure(a: RdvVrac, b: RdvVrac): number {
      if (a.heure < b.heure || (a.heure && !b.heure)) return -1;
      if (a.heure > b.heure || (!a.heure && b.heure)) return 1;
      return 0;
    }

    function comparerProduit(a: RdvVrac, b: RdvVrac): number {
      return ($vracProduits?.get(a.produit)?.nom || "").localeCompare(
        $vracProduits?.get(b.produit)?.nom || ""
      );
    }

    function comparerQualite(a: RdvVrac, b: RdvVrac): number {
      return (
        $vracProduits
          ?.get(a.produit)
          ?.qualites.find((qualite) => qualite.id === a.qualite)?.nom || ""
      ).localeCompare(
        $vracProduits
          ?.get(b.produit)
          ?.qualites.find((qualite) => qualite.id === b.qualite)?.nom || ""
      );
    }
  }
</script>

{#if $currentUser.canUseApp && $currentUser.canAccess("vrac")}
  <SseConnection
    subscriptions={[
      "vrac/rdvs",
      "vrac/produits",
      "tiers",
      "config/bandeau-info",
    ]}
  />

  <BandeauInfo module="vrac" tv />

  <main>
    {#if $vracRdvs && $vracProduits}
      {#each [...rdvsGroupes] as [date, rdvs] (date)}
        <LigneDate {date} />
        <div>
          {#each rdvs as rdv (rdv.id)}
            <LigneRdv {rdv} />
          {/each}
        </div>
      {/each}
    {/if}
  </main>
{/if}

<style>
  /* ARRIERE-PLAN */

  main::before {
    content: " ";
    position: fixed;
    z-index: -1;
    width: 100vw;
    height: 100vh;
    margin: 0;
    padding: 0;
    opacity: 0.05;
    background-image: url("/src/images/logo_agence_combi.min.svg");
    background-repeat: no-repeat;
    background-position: center center;
    background-attachment: fixed;
    background-size: 60%;
  }

  /* LISTE RDV */

  main {
    width: 95vw;
    margin: auto;
  }
</style>
