<!-- routify:options title="Planning AMSB - Statistiques bois" -->
<script lang="ts">
  import { onDestroy } from "svelte";

  import Notiflix from "notiflix";

  import { FilterModal, filter } from "./components";
  import { PageHeading } from "@app/components";

  import { fetcher } from "@app/utils";

  const unsubscribeFiltre = filter.subscribe((value) => {
    fetchStats();
  });

  type Stats = {
    Total: number;
    ByYear: {
      [year: string]: {
        [month in
          | "1"
          | "2"
          | "3"
          | "4"
          | "5"
          | "6"
          | "7"
          | "8"
          | "9"
          | "10"
          | "11"
          | "12"]: number;
      };
    };
  };

  let stats: Stats;

  /**
   * Récupère les statistiques par année.
   *
   * @returns Statistiques au format JSON
   */
  async function fetchStats() {
    try {
      stats = await fetcher("bois/stats", {
        searchParams: $filter.toSearchParams(),
      });
    } catch (error) {
      Notiflix.Notify.failure(error.message);
      console.error(error);
    }
  }

  onDestroy(() => {
    unsubscribeFiltre();
  });
</script>

<!-- routify:options guard="bois" -->

<main>
  <PageHeading>Statistiques</PageHeading>

  <FilterModal />

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
            <tr>
              <th scope="row">{year}</th>
              {#each Object.entries(yearStats) as [monthNumber, numberOfTrucks]}
                <td>{numberOfTrucks.toLocaleString("fr-FR")}</td>
              {/each}

              <!-- Total par année -->
              <td class="total">
                {Object.values(yearStats)
                  .reduce((sum, current) => sum + current, 0)
                  .toLocaleString("fr-FR")}
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
                  // Total des camions par mois
                  Object.values(stats.ByYear)
                    .map((yearStats) => yearStats[monthIndex + 1])
                    .reduce(
                      (total, numberOfTrucks) => total + numberOfTrucks,
                      0
                    ) /
                    // Nombre d'années (ignorer les années à zéro camion)
                    (Object.values(stats.ByYear)
                      .map((yearStats) => yearStats[monthIndex + 1])
                      .filter((value) => value).length || 1)
                ).toLocaleString("fr-FR")}
              </td>
            {/each}
            <td class="total">{stats.Total.toLocaleString("fr-FR")}</td>
          </tr>
        </tfoot>
      </table>
    {/if}
  </div>
</main>

<style>
  main {
    width: calc(95%);
    margin: auto;
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

  @media screen and (min-width: 768px) {
    main {
      /* Largeur du menu = 256px */
      --margin-left: 280px;
      width: calc(95% - var(--margin-left));
      margin-left: var(--margin-left);
    }
  }
</style>
