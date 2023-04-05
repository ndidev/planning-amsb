<script lang="ts">
  import { onDestroy, setContext } from "svelte";
  import { writable } from "svelte/store";

  import { Filtre as BandeauFiltre } from "./components";

  import { fetcher, Filtre } from "@app/utils";

  import type { FiltreBois } from "@app/types";
  import Notiflix from "notiflix";

  let filtre = new Filtre<FiltreBois>(
    JSON.parse(sessionStorage.getItem("filtre-stats-bois")) || {}
  );

  const storeFiltre = writable(filtre);

  const unsubscribeFiltre = storeFiltre.subscribe((value) => {
    filtre = value;
    recupererStats();
  });

  setContext("filtre", storeFiltre);

  type Stats = {
    Total: number;
    "Par année": {
      [annee: string]: {
        "1": number;
        "2": number;
        "3": number;
        "4": number;
        "5": number;
        "6": number;
        "7": number;
        "8": number;
        "9": number;
        "10": number;
        "11": number;
        "12": number;
      };
    };
  };

  let stats: Stats;

  /**
   * Récupère les statistiques par année.
   *
   * @returns Statistiques au format JSON
   */
  async function recupererStats() {
    try {
      stats = await fetcher("bois/stats", { params: filtre.toParams() });
    } catch (error) {
      Notiflix.Notify.failure(error.message);
      console.error(error);
    }
  }

  onDestroy(() => {
    unsubscribeFiltre();
  });
</script>

<main class="formulaire">
  <h1>Statistiques</h1>

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
          {#each Object.entries(stats["Par année"]) as [annee, statsAnnee]}
            <tr>
              <th scope="row">{annee}</th>
              {#each Object.entries(statsAnnee) as [mois, camions]}
                <td>{camions.toLocaleString("fr-FR")}</td>
              {/each}

              <!-- Total par année -->
              <td class="total">
                {Object.values(statsAnnee)
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
            {#each [...Array(12).keys()] as mois}
              <td>
                {Math.round(
                  // Total des camions par mois
                  Object.values(stats["Par année"])
                    .map((statsAnnee) => statsAnnee[mois + 1])
                    .reduce((total, valeur) => total + valeur, 0) /
                    // Nombre d'années (ignorer les années à zéro camion)
                    (Object.values(stats["Par année"])
                      .map((statsAnnee) => statsAnnee[mois + 1])
                      .filter((valeur) => valeur).length || 1)
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
    width: calc(95% - 200px); /* 200px = largeur du menu */
    margin-left: 200px;
  }

  h1 {
    margin: 20px 0;
    text-align: center;
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

  @media screen and (max-width: 480px) {
    main {
      width: calc(95%);
      margin: auto;
    }
  }
</style>
