<!-- routify:options title="Planning AMSB - Dispatch vrac" -->
<script lang="ts">
  import { onDestroy } from "svelte";

  import {
    Accordion,
    AccordionItem,
    Table,
    TableHead,
    TableHeadCell,
    TableBody,
    TableBodyCell,
    TableBodyRow,
  } from "flowbite-svelte";

  import { FilterBanner, filter } from "./components";
  import { PageHeading } from "@app/components";

  import { fetcher } from "@app/utils";

  import Notiflix from "notiflix";

  let stats: DispatchStats;

  const unsubscribeFilter = filter.subscribe((value) => {
    fetchStats();
  });

  type DispatchStats = {
    formattedDates: string[];
    staffLabels: string[];
    byType: {
      [type in "jcb" | "trémie" | "chargeuse"]: {
        [staffLabel: string]: {
          [formattedDate: string]: number;
          total: number;
        };
      };
    };
  };

  /**
   * Récupère les statistiques par année.
   *
   * @returns Statistiques au format JSON
   */
  async function fetchStats() {
    try {
      stats = await fetcher("vrac/dispatch", {
        searchParams: $filter.toSearchParams(),
      });
    } catch (error) {
      Notiflix.Notify.failure(error.message);
      console.error(error);
    }
  }

  onDestroy(() => {
    unsubscribeFilter();
  });
</script>

<!-- routify:options guard="vrac" -->

<main>
  <PageHeading>Dispatch vrac</PageHeading>

  <FilterBanner />

  <div class="my-12 overflow-x-auto">
    {#if stats}
      {#if stats.staffLabels.length > 0}
        {#each Object.keys(stats.byType) as type}
          <div class="p-4 bg-white shadow-lg rounded-lg mb-4">
            <Table>
              <TableHead>
                <TableHeadCell scope="col" class="font-bold text-lg"
                  >{type.toLocaleUpperCase()}</TableHeadCell
                >

                <!-- Total -->
                <TableHeadCell scope="col" class="text-center"
                  >Total</TableHeadCell
                >

                <!-- En-têtes colonnes : date -->
                {#each stats.formattedDates as formattedDate}
                  <TableHeadCell scope="col" class="text-center"
                    >{formattedDate}</TableHeadCell
                  >
                {/each}
              </TableHead>

              <TableBody tableBodyClass="divide-y">
                {#each stats.staffLabels as staffLabel}
                  <TableBodyRow>
                    <TableBodyCell scope="row">{staffLabel}</TableBodyCell>

                    <!-- Total -->
                    <TableBodyCell class="text-center">
                      {stats.byType[type][staffLabel]?.total || 0}
                    </TableBodyCell>

                    <!-- Par date -->
                    {#each stats.formattedDates as formattedDate}
                      <TableBodyCell class="text-center">
                        {stats.byType[type][staffLabel]?.[formattedDate] || 0}
                      </TableBodyCell>
                    {/each}
                  </TableBodyRow>
                {/each}
              </TableBody>
            </Table>
          </div>
        {/each}
      {:else}
        <p class="text-center text-gray-500">Aucune donnée à afficher.</p>
      {/if}
    {/if}
  </div>
</main>

<style>
  main {
    width: 95%;
    margin: auto;
  }

  @media screen and (min-width: 1024px) {
    main {
      /* Largeur du menu = 256px */
      --margin-left: 280px;
      width: calc(95% - var(--margin-left));
      margin-left: var(--margin-left);
    }
  }
</style>
