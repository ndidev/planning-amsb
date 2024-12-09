<!-- routify:options title="Planning AMSB - Dispatch vrac" -->
<script lang="ts">
  import { onDestroy, setContext } from "svelte";
  import { writable } from "svelte/store";

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

  import { FilterBanner } from "./components";
  import { PageHeading } from "@app/components";

  import { fetcher, Filtre } from "@app/utils";

  import type { BulkDispatchFilter } from "@app/types";
  import Notiflix from "notiflix";

  const emptyFilter: BulkDispatchFilter = {
    startDate: "",
    endDate: "",
    staff: [],
  };

  const filterName = "bulk-dispatch-stats-filter";

  let filter = new Filtre<BulkDispatchFilter>(
    JSON.parse(sessionStorage.getItem(filterName)) ||
      structuredClone(emptyFilter)
  );

  let stats: DispatchStats;

  const filterStore = writable(filter);

  const unsubscribeFilter = filterStore.subscribe((value) => {
    filter = value;
    fetchStats();
  });

  setContext("filter", { emptyFilter, filterStore, filterName });

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
        searchParams: filter.toSearchParams(),
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
      <Accordion multiple>
        {#if stats.staffLabels.length > 0}
          {#each Object.keys(stats.byType) as type}
            <AccordionItem open>
              <span slot="header">{type.toLocaleUpperCase()}</span>
              <Table>
                <TableHead>
                  <TableHeadCell scope="col" />

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
            </AccordionItem>
          {/each}
        {:else}
          <p class="text-center text-gray-500">Aucune donnée à afficher.</p>
        {/if}
      </Accordion>
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
