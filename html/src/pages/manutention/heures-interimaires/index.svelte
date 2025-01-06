<!-- routify:options title="Planning AMSB - Heures intérimaires" -->
<script lang="ts">
  import { onDestroy } from "svelte";

  import { Button, Input, ButtonGroup } from "flowbite-svelte";
  import {
    PlusCircleIcon,
    RectangleEllipsisIcon,
    DownloadIcon,
  } from "lucide-svelte";
  import Notiflix from "notiflix";

  import {
    FilterBanner,
    filter,
    TempWorkHoursLine,
    AddHoursModal,
    ReportModal,
  } from "./components";
  import { PageHeading, Chargement, SseConnection } from "@app/components";

  import { DateUtils, fetcher } from "@app/utils";

  import { stevedoringTempWorkHours, currentUser } from "@app/stores";

  import type { TempWorkHours } from "@app/types";

  const unsubscribeFilter = filter.subscribe((value) => {
    stevedoringTempWorkHours.setSearchParams(value.toSearchParams());
  });

  type TempWorkHoursByDate = {
    [date: string]: TempWorkHours[];
  };

  let tempWorkHoursByDate: TempWorkHoursByDate;

  let prefillDate = new Date().toISOString().split("T")[0];

  let addHoursModalOpen = false;
  let reportModalOpen = false;

  $: if ($stevedoringTempWorkHours) {
    tempWorkHoursByDate = [...$stevedoringTempWorkHours.values()]
      .sort((a, b) => a.date.localeCompare(b.date))
      .reduce((acc, item) => {
        if (!acc[item.date]) acc[item.date] = [];
        acc[item.date].push(item);
        return acc;
      }, {});
  }

  function addEntryForDate(date: string) {
    const newEntry = stevedoringTempWorkHours.new();
    newEntry.date = date;
    newEntry.hoursWorked = 8;
  }

  async function preFillForDate(date: string) {
    try {
      const ids = await fetcher<number[]>(
        `manutention/dispatch-interimaire/${date}`
      );

      // Ensure we don't add duplicates
      const existingIds = new Set(
        Object.values(tempWorkHoursByDate[date] || {}).map(
          (entry) => entry.staffId
        )
      );

      const newIds = ids.filter((id) => !existingIds.has(id));

      if (newIds.length === 0) {
        Notiflix.Notify.info("Aucun nouvel intérimaire à ajouter");
        return;
      }

      newIds.map((id) => {
        const newEntry = stevedoringTempWorkHours.new();
        newEntry.date = date;
        newEntry.staffId = id;
        newEntry.hoursWorked = 8;
        return newEntry;
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

<SseConnection
  subscriptions={["stevedoring/temp-work-hours", "stevedoring/staff"]}
/>

{#if $currentUser.canEdit("manutention")}
  <AddHoursModal bind:open={addHoursModalOpen} />
{/if}

<ReportModal bind:open={reportModalOpen} />

<main class="mx-auto w-10/12 lg:w-2/3">
  <PageHeading>Heures intérimaires</PageHeading>

  <FilterBanner />

  {#if $currentUser.canEdit("manutention")}
    <div class="mt-6 flex flex-col lg:flex-row gap-2 lg:gap-4 justify-center">
      <Button on:click={() => (addHoursModalOpen = true)}>
        <PlusCircleIcon size={20} />
        <span class="ms-2">Ajouter des heures</span>
      </Button>

      <ButtonGroup>
        <Input type="date" bind:value={prefillDate} class="w-1/2 lg:w-min" />
        <Button
          on:click={() => preFillForDate(prefillDate)}
          color="light"
          class="w-1/2 lg:w-auto"
        >
          <RectangleEllipsisIcon size={20} />
          <span class="ms-2">Pré-remplir </span>
        </Button>
      </ButtonGroup>

      <Button on:click={() => (reportModalOpen = true)}>
        <DownloadIcon size={20} />
        <span class="ms-2">Télécharger les heures</span>
      </Button>
    </div>
  {/if}

  <div class="mt-12">
    {#if tempWorkHoursByDate}
      {#each Object.keys(tempWorkHoursByDate) as date}
        <div class="p-4 bg-white shadow-lg rounded-lg mb-4">
          <div class="font-bold text-lg">
            {new DateUtils(date).format().long}
          </div>

          {#if $currentUser.canEdit("manutention")}
            <div class="mt-2">
              <Button
                on:click={() => addEntryForDate(date)}
                color="light"
                class="mb-4"
                size="sm"
              >
                <PlusCircleIcon size={16} />
                <span class="ms-2">Ajouter des heures</span>
              </Button>

              <Button
                on:click={() => preFillForDate(date)}
                color="light"
                class="mb-4"
                size="sm"
              >
                <RectangleEllipsisIcon size={16} />
                <span class="ms-2">Pré-remplir</span>
              </Button>
            </div>
          {/if}

          {#each tempWorkHoursByDate[date] as tempWorkHoursEntry (tempWorkHoursEntry.id)}
            <TempWorkHoursLine tempWorkHours={tempWorkHoursEntry} />
          {/each}
        </div>
      {:else}
        <p class="text-center">Aucune donnée à afficher</p>
      {/each}
    {:else}
      <Chargement />
    {/if}
  </div>
</main>
