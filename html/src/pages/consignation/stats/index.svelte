<!-- routify:options title="Planning AMSB - Statistiques consignation" -->
<script lang="ts">
  import { onDestroy, setContext } from "svelte";
  import { writable } from "svelte/store";

  import { Filtre as BandeauFiltre, CarteEscale } from "./components";
  import { PageHeading } from "@app/components";

  import { fetcher, Filtre } from "@app/utils";

  import type { FiltreConsignation, EscaleConsignation } from "@app/types";
  import Notiflix from "notiflix";

  let filtre = new Filtre<FiltreConsignation>(
    JSON.parse(sessionStorage.getItem("filtre-stats-consignation")) || {}
  );

  let stats: Stats;
  let details: EscaleConsignation[] = [];

  const storeFiltre = writable(filtre);

  const unsubscribeFiltre = storeFiltre.subscribe((value) => {
    filtre = value;
    recupererStats();
    details = [];
  });

  setContext("filtre", storeFiltre);

  type Stats = {
    Total: number;
    ByYear: {
      [annee: string]: {
        "1": { nombre: number; ids: number[] };
        "2": { nombre: number; ids: number[] };
        "3": { nombre: number; ids: number[] };
        "4": { nombre: number; ids: number[] };
        "5": { nombre: number; ids: number[] };
        "6": { nombre: number; ids: number[] };
        "7": { nombre: number; ids: number[] };
        "8": { nombre: number; ids: number[] };
        "9": { nombre: number; ids: number[] };
        "10": { nombre: number; ids: number[] };
        "11": { nombre: number; ids: number[] };
        "12": { nombre: number; ids: number[] };
      };
    };
  };

  /**
   * Récupère les statistiques par année.
   *
   * @returns Statistiques au format JSON
   */
  async function recupererStats() {
    try {
      stats = await fetcher("consignation/stats", {
        searchParams: filtre.toSearchParams(),
      });
    } catch (error) {
      Notiflix.Notify.failure(error.message);
      console.error(error);
    }
  }

  async function recupererDetails(ids: number[]) {
    if (ids.length === 0) {
      details = [];
      return;
    }

    const escales = await fetcher<EscaleConsignation[]>(
      `consignation/stats/${ids.join(",")}`
    );

    details = escales;
  }

  onDestroy(() => {
    unsubscribeFiltre();
  });
</script>

<!-- routify:options guard="consignation" -->

<main>
  <PageHeading>Statistiques</PageHeading>

  <!-- Filtre par date/client -->
  <BandeauFiltre />

  <div id="statistiques">
    {#if stats}
      <table>
        <!-- En-têtes colonnes : mois -->
        <thead>
          <tr>
            <th scope="col" />
            <th scope="col">Janvier</th>
            <th scope="col">Février</th>
            <th scope="col">Mars</th>
            <th scope="col">Avril</th>
            <th scope="col">Mai</th>
            <th scope="col">Juin</th>
            <th scope="col">Juillet</th>
            <th scope="col">Août</th>
            <th scope="col">Septembre</th>
            <th scope="col">Octobre</th>
            <th scope="col">Novembre</th>
            <th scope="col">Décembre</th>
            <th scope="col">Total</th>
          </tr>
        </thead>

        <tbody>
          {#each Object.entries(stats.ByYear) as [year, yearStats]}
            {@const yearTotal = Object.values(yearStats)
              .map(({ nombre }) => nombre)
              .reduce((sum, current) => sum + current, 0)}

            <tr>
              <th scope="row">{year}</th>
              {#each Object.entries(yearStats) as [monthIndex, monthStats]}
                <td>
                  <button on:click={() => recupererDetails(monthStats.ids)}
                    >{monthStats.nombre.toLocaleString("fr-FR")}</button
                  >
                </td>
              {/each}

              <!-- Total par année -->
              <td class="total">
                {#if yearTotal > 0}
                  <button
                    class="bold"
                    on:click={() =>
                      recupererDetails(
                        Object.values(yearStats)
                          .map(({ ids }) => ids)
                          .reduce((prev, current) => [...current, ...prev], [])
                      )}
                  >
                    {Object.values(yearStats)
                      .map(({ nombre }) => nombre)
                      .reduce((sum, current) => sum + current, 0)
                      .toLocaleString("fr-FR")}
                  </button>
                {:else}
                  0
                {/if}
              </td>
            </tr>
          {/each}
        </tbody>

        <!-- Ligne des moyennes et total général -->
        <tfoot>
          <tr>
            <th>Moyenne</th>
            {#each [...Array(12).keys()] as monthIndex}
              <td>
                {Math.round(
                  // Total des escales par mois
                  Object.values(stats.ByYear)
                    .map((statsAnnee) => statsAnnee[monthIndex + 1].nombre)
                    .reduce((total, valeur) => total + valeur, 0) /
                    // Nombre d'années (ignorer les années à zéro escale)
                    (Object.values(stats.ByYear)
                      .map((yearStats) => yearStats[monthIndex + 1])
                      .filter((valeur) => valeur).length || 1)
                ).toLocaleString("fr-FR")}
              </td>
            {/each}
            <td class="total"
              ><button
                class="bold"
                on:click={() =>
                  recupererDetails(
                    Object.values(stats.ByYear)
                      .map((year) =>
                        Object.values(year)
                          .map(({ ids }) => ids)
                          .reduce((prev, current) => [...prev, ...current], [])
                      )
                      .reduce((prev, current) => [...prev, ...current], [])
                  )}
              >
                {stats.Total.toLocaleString("fr-FR")}
              </button>
            </td>
          </tr>
        </tfoot>
      </table>
    {/if}
  </div>

  <ul class="details">
    {#each details as detail}
      <CarteEscale escale={detail} />
    {/each}
  </ul>
</main>

<style>
  main {
    /* Largeur du menu = 256px */
    --margin-left: 280px;
    width: calc(95% - var(--margin-left));
    margin-left: var(--margin-left);
  }

  .bold {
    font-weight: bold;
  }

  /* STATS */

  #statistiques {
    overflow-x: auto;
    margin-top: 50px;
    margin-bottom: 50px;
  }

  table {
    width: 100%;
    border-collapse: collapse;
  }

  :is(table, th, td) {
    border: 1px solid black;
  }

  :is(th, tfoot) {
    font-weight: bold;
  }

  :is(th, td) {
    padding: 3px;
    width: calc(100% / 14);
  }

  th[scope="col"] {
    background-color: lightblue;
  }

  th[scope="row"] {
    background-color: lightcyan;
  }

  td.total {
    background-color: hsl(170, 100%, 80%);
    font-weight: bold;
  }

  tfoot {
    background-color: lightyellow;
  }

  td {
    text-align: right;
    padding-right: 5px;
  }

  tr:nth-child(even) {
    background-color: #eee;
  }

  table button {
    width: 100%;
    margin: 0;
    padding: 0;
    text-align: right;
    background-color: transparent;
    border: none;
    cursor: pointer;
  }

  table td:has(button:is(:hover, :focus)) {
    background-color: bisque;
  }

  /* DETAILS */

  .details {
    list-style-type: none;
  }

  @media screen and (max-width: 480px) {
    main {
      width: calc(95%);
      margin: auto;
    }
  }
</style>
