<!-- routify:options title="Planning AMSB - Rapports navires" -->
<script lang="ts">
  import { onDestroy } from "svelte";

  import { PageHeading, Chargement, SseConnection } from "@app/components";

  import { ShipReportCard, FilterModal, filter } from "./components";

  import { stevedoringShipReports } from "@app/stores";

  import type { StevedoringShipReport } from "@app/types";

  let reports: StevedoringShipReport[];

  let reportsReady = stevedoringShipReports.getReadyState();

  const unsubscribeFilter = filter.subscribe((value) => {
    const params = value.toSearchParams();
    stevedoringShipReports.setSearchParams(params);

    reportsReady = stevedoringShipReports.getReadyState();
  });

  const unsubscribeReports = stevedoringShipReports.subscribe((value) => {
    if (!value) return;

    reports = [...value.values()];
  });

  onDestroy(() => {
    unsubscribeFilter();
    unsubscribeReports();
  });
</script>

<!-- routify:options guard="manutention" -->

<SseConnection subscriptions={["manutention/rapports-navires"]} />

<FilterModal />

<main class="mx-auto w-10/12 lg:w-2/3">
  <PageHeading>Rapports navires</PageHeading>

  {#await reportsReady}
    <Chargement />
  {:then}
    {#each reports as report (report.id)}
      <ShipReportCard {report} />
    {:else}
      <div class="p-4 text-center">Aucun rapport trouvé.</div>
    {/each}
  {:catch}
    <div class="p-4 text-center">Erreur de chargement.</div>
  {/await}
</main>
