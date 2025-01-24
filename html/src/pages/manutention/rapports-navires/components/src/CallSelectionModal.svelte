<script lang="ts">
  import { Modal, Button } from "flowbite-svelte";
  import Notiflix from "notiflix";

  import { fetcher } from "@app/utils";

  import { consignationEscales } from "@app/stores";

  import type { StevedoringShipReport } from "@app/types";

  export let open: boolean = false;
  export let type: "create" | "link";
  export let report: StevedoringShipReport;

  type CallSummary = {
    id: number;
    shipName: string;
  };

  let callListPromise: Promise<CallSummary[]> = null;

  $: if (open) {
    callListPromise = fetcher(
      "manutention/rapports-navires/calls-without-report"
    );
  }

  function handleCallSelection(callId: number) {
    try {
      if (type === "link") {
        Notiflix.Confirm.show(
          "Lier à l'escale",
          "Vous-vous importer les informations de l'escale consignation ?<br/>" +
            "Les informations suivantes seront importées :<br/>" +
            "Navire, Port, Quai, Marchandises",
          "Oui",
          "Non",
          _linkToCall,
          null
        );
      } else {
        _linkToCall();
      }

      async function _linkToCall() {
        const call = await consignationEscales.get(callId);

        report.linkedShippingCallId = call.id;
        report.ship = call.navire;
        report.port = call.call_port;
        report.berth = call.quai;
        report.cargoEntries = call.marchandises;
        report.startDate = call.ops_date;

        open = false;
      }
    } catch (error) {
      console.error(error);
      Notiflix.Notify.failure("Erreur lors de la récupération de l'escale.");
    }
  }
</script>

<Modal
  title={type === "create"
    ? "Création à partir d'une escale consignation"
    : "Lier à une escale consignation"}
  bind:open
  size="xs"
>
  {#await callListPromise}
    <div>Chargement...</div>
  {:then callList}
    {#each callList as call}
      <div>
        <Button on:click={() => handleCallSelection(call.id)}>
          {call.shipName}
        </Button>
      </div>
    {:else}
      <div>Aucune escale trouvée.</div>
    {/each}
  {:catch error}
    <div>Erreur: {error.message}</div>
  {/await}
</Modal>
