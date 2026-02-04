<script lang="ts">
  import { sineIn } from "svelte/easing";
  import { goto } from "@roxi/routify";

  import { Drawer, CloseButton, Tabs, TabItem } from "flowbite-svelte";
  import PencilIcon from "lucide-svelte/icons/pencil";
  import PrinterIcon from "lucide-svelte/icons/printer";
  import Notiflix from "notiflix";

  import {
    Cargoes,
    Comments,
    Costs,
    Customers,
    DailyEntries,
    InvoiceInstructions,
    PortAndBerth,
    Rate,
    Storage,
  } from "../reportComponents";
  import { LucideButton } from "@app/components";

  import { fetcher } from "@app/utils";

  import { currentUser } from "@app/stores";

  import type { StevedoringShipReport } from "@app/types";

  let printButton: HTMLButtonElement;

  export let report: StevedoringShipReport;
  export let hidden = true;

  let transitionParams = {
    x: 320,
    duration: 200,
    easing: sineIn,
  };

  async function printReport() {
    try {
      Notiflix.Block.merge({ svgSize: "24px" });
      Notiflix.Block.circle([printButton]);
      printButton.style.minHeight = "initial";

      const blob: Blob = await fetcher(
        `manutention/rapports-navires/${report.id}/pdf`,
        { accept: "blob" },
      );

      const file = URL.createObjectURL(blob);
      const filename = "Rapport navire " + report.ship + ".pdf";
      const link = document.createElement("a");
      link.href = file;
      link.download = filename;
      link.click();
    } catch (error) {
      Notiflix.Notify.failure(error.message);
    } finally {
      Notiflix.Block.remove([printButton]);
    }
  }
</script>

<Drawer
  placement="right"
  bgOpacity="bg-opacity-10"
  width="w-3/4 lg:w-2/3"
  transitionType="fly"
  {transitionParams}
  bind:hidden
>
  <CloseButton
    on:click={() => (hidden = true)}
    class="absolute top-0 right-0 m-4"
  />
  <div class="flex flex-col gap-2 lg:gap-6 mt-8">
    <!-- Nom -->
    <div class="text-3xl *:align-baseline">
      <span class="font-bold">{report.ship}</span>

      {#if $currentUser.canEdit("manutention")}
        <LucideButton
          preset="edit"
          icon={PencilIcon}
          on:click={$goto(`/manutention/rapports-navires/${report.id}`)}
          align="baseline"
        />
      {/if}

      <LucideButton
        preset="copy"
        icon={PrinterIcon}
        on:click={printReport}
        title="Imprimer le rapport"
        align="baseline"
        bind:button={printButton}
      />
    </div>

    <PortAndBerth {report} />

    <Comments {report} />

    <InvoiceInstructions {report} />

    <Customers {report} />

    <div>
      <Tabs contentClass="dark:bg-gray-800">
        {#each report.subreports as subreport, index}
          <TabItem
            title="Sous-rapport {index + 1}"
            open={index === 0}
            divClass="mt-3 flex flex-col gap-2 lg:gap-6"
          >
            <Cargoes {report} {subreport} />

            <Storage {report} {subreport} />

            <Costs {subreport} />

            <Rate {subreport} />

            <DailyEntries {subreport} />
          </TabItem>
        {/each}
      </Tabs>
    </div>
  </div>
</Drawer>
