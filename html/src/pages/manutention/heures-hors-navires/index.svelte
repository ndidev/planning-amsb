<!-- routify:options title="Planning AMSB - Heures hors navires" -->
<script lang="ts">
  import { onDestroy, setContext } from "svelte";
  import { writable } from "svelte/store";

  import { Accordion, AccordionItem } from "flowbite-svelte";

  import { FilterBanner } from "./components";
  import { PageHeading, Chargement } from "@app/components";

  import { fetcher, Filtre, DateUtils } from "@app/utils";

  import type { StevedoringDispatchFilter } from "@app/types";
  import Notiflix from "notiflix";

  const emptyFilter: StevedoringDispatchFilter = {
    startDate: "",
    endDate: "",
    staff: [],
  };

  const filterName = "stevedoring-dispatch-filter";

  let filter = new Filtre<StevedoringDispatchFilter>(
    JSON.parse(sessionStorage.getItem(filterName)) ||
      structuredClone(emptyFilter)
  );

  const filterStore = writable(filter);

  const unsubscribeFilter = filterStore.subscribe((value) => {
    filter = value;
    fetchDispatch();
  });

  // Contexte pour le compoant BandeauFiltre
  setContext("filter", { emptyFilter, filterStore, filterName });

  type Dispatch = {
    [date: string]: {
      [contractType in "mensuel" | "interim"]: {
        [staffName: string]: {
          tempWorkAgency: string;
          bulk: { product: string; quality: string; role: string }[];
        };
      };
    };
  };

  let dispatch: Dispatch;

  /**
   * Récupère les statistiques par année.
   *
   * @returns Statistiques au format JSON
   */
  async function fetchDispatch() {
    try {
      dispatch = await fetcher("manutention/dispatch", {
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

<!-- routify:options guard="manutention" -->

<main class="mx-auto w-10/12 lg:w-2/3">
  <PageHeading>Heures hors navires</PageHeading>

  <FilterBanner />

  <div class="mt-12">
    {#if dispatch}
      <Accordion>
        {#each Object.keys(dispatch) as date}
          <AccordionItem>
            <span slot="header">{new DateUtils(date).format().long}</span>

            {#each Object.keys(dispatch[date]) as contractType}
              <div class="text-xl font-bold">
                {contractType === "mensuel" ? "Mensuels" : "Intérimaires"}
              </div>

              {#each Object.keys(dispatch[date][contractType]) as staffName}
                <div class="mb-3">
                  <div class="text-lg">
                    {staffName}
                    {#if contractType === "interim"}
                      <span>
                        ({dispatch[date][contractType][staffName]
                          .tempWorkAgency})
                      </span>
                    {/if}
                  </div>

                  <div class="ml-2">Vrac</div>
                  <ul>
                    {#each dispatch[date][contractType][staffName].bulk as { product, quality, role }}
                      <li class="ml-4 flex flex-row gap-1">
                        <span>{product}</span>
                        <span>{quality}</span>
                        <span class="ml-2">{role}</span>
                      </li>
                    {/each}
                  </ul>
                </div>
              {/each}
            {/each}
          </AccordionItem>
        {:else}
          <p class="text-center">Aucune donnée à afficher</p>
        {/each}
      </Accordion>
    {:else}
      <Chargement />
    {/if}
  </div>
</main>
