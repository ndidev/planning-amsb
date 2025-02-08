<script lang="ts">
  import { Modal, Button, Accordion, AccordionItem } from "flowbite-svelte";
  import { EyeIcon, EyeOffIcon } from "lucide-svelte";
  import Notiflix from "notiflix";

  import { LucideButton } from "@app/components";

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

  let callList: CallSummary[] = null;
  let callListPromise: Promise<CallSummary[]> = null;
  let callListError: Error = null;

  let ignoredCalls: CallSummary[] = null;
  let ignoredCallsPromise: Promise<CallSummary[]> = null;
  let ignoredCallsError: Error = null;

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

  async function ignoreCall(call: CallSummary) {
    try {
      await fetcher(`manutention/rapports-navires/ignored-shipping-calls`, {
        requestInit: {
          method: "POST",
          body: JSON.stringify({ callId: call.id }),
        },
      });
      Notiflix.Notify.success("L'escale est ignorée.");

      if (callList) {
        callList = callList.filter((c) => c.id !== call.id);
      }

      if (ignoredCalls) {
        ignoredCalls = [...ignoredCalls, call];
      }
    } catch (error) {
      console.error(error);
      Notiflix.Notify.failure("Erreur lors de l'ignorance de l'escale.");
    }
  }

  async function unignoreCall(call: CallSummary) {
    try {
      await fetcher(
        `manutention/rapports-navires/ignored-shipping-calls/${call.id}`,
        {
          requestInit: {
            method: "DELETE",
          },
        }
      );
      Notiflix.Notify.success("L'escale est rétablie.");

      if (ignoredCalls) {
        ignoredCalls = ignoredCalls.filter((c) => c.id !== call.id);
      }

      if (callList) {
        callList = [...callList, call];
      }
    } catch (error) {
      console.error(error);
      Notiflix.Notify.failure(
        "Erreur lors du rétablissement l'escale comme non ignorée."
      );
    }
  }
</script>

<Modal
  title={type === "create"
    ? "Création à partir d'une escale consignation"
    : "Lier à une escale consignation"}
  bind:open
  on:open={() => {
    callListPromise = fetcher(
      "manutention/rapports-navires/calls-without-report"
    );
    ignoredCallsPromise = fetcher(
      "manutention/rapports-navires/ignored-shipping-calls"
    );

    callList = null;
    callListError = null;
    callListPromise
      .then((calls) => (callList = calls))
      .catch((error) => (callListError = error));

    ignoredCalls = null;
    ignoredCallsError = null;
    ignoredCallsPromise
      .then((calls) => (ignoredCalls = calls))
      .catch((error) => (ignoredCallsError = error));
  }}
  size="xs"
>
  <!-- Escales disponibles -->
  {#if callList && !callListError}
    {#each callList as call}
      <div>
        <Button on:click={() => handleCallSelection(call.id)}>
          {call.shipName}
        </Button>
        <LucideButton
          icon={EyeOffIcon}
          on:click={() => ignoreCall(call)}
          title="Ignorer l'escale"
          size="20px"
        />
      </div>
    {:else}
      <div>Aucune escale trouvée.</div>
    {/each}
  {:else if !callListError}
    <div>Chargement...</div>
  {:else}
    <div>
      <span class="text-red-500">Erreur :</span>
      {callListError.message}
    </div>
  {/if}

  <!-- Escales ignorées -->
  {#if ignoredCalls && !ignoredCallsError}
    <Accordion flush>
      <AccordionItem>
        <span slot="header">Escales ignorées</span>
        {#each ignoredCalls as call}
          <div>
            {call.shipName}
            <LucideButton
              icon={EyeIcon}
              on:click={() => unignoreCall(call)}
              title="Rétablir l'escale"
              size="20px"
            />
          </div>
        {:else}
          <div>Aucune escale ignorée.</div>
        {/each}
      </AccordionItem>
    </Accordion>
  {:else if !ignoredCallsError}
    <div>Chargement...</div>
  {:else}
    <div>
      <span class="text-red-500">Erreur :</span>
      {ignoredCallsError.message}
    </div>
  {/if}
</Modal>
