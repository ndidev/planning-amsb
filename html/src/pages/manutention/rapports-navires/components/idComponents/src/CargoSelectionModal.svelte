<script lang="ts">
  import { Modal, Checkbox } from "flowbite-svelte";

  import { BoutonAction } from "@app/components";

  import type { StevedoringShipReport } from "@app/types";

  export let open = false;
  export let report: StevedoringShipReport;
  export let subreport: StevedoringShipReport["subreports"][number];
  export let index: number;
  export let creationMode: boolean = false;
  export let deleteSubreport: (
    subreport: StevedoringShipReport["subreports"][number]
  ) => void;

  let otherSubreports: StevedoringShipReport["subreports"];

  let form: HTMLFormElement;

  function cargoIsInSubreport(cargoId: number) {
    return subreport.cargoIds.includes(cargoId);
  }

  function cargoIsInOtherSubreports(cargoId: number) {
    return otherSubreports.some((_subreport) =>
      _subreport.cargoIds.includes(cargoId)
    );
  }

  function getSubreportIndexForCargo(cargoId: number) {
    return report.subreports.findIndex((_subreport) =>
      _subreport.cargoIds.includes(cargoId)
    );
  }

  function updateCargoes() {
    const selectedCargoes = Array.from(form.elements)
      .filter((element) => element instanceof HTMLInputElement)
      .filter((element) => element.checked)
      .map((element) =>
        report.cargoEntries.find((cargo) => String(cargo.id) === element.value)
      );

    subreport.cargoIds = selectedCargoes.map((cargo) => cargo.id);

    report.subreports = report.subreports;

    open = false;
  }

  function cancelUpdate() {
    console.debug("cancel cargo selection", { creationMode });

    if (creationMode) {
      deleteSubreport(subreport);
    }

    open = false;
  }
</script>

<Modal
  title={`Marchandises sous-rapport ${index + 1}`}
  bind:open
  size="xs"
  on:open={() => {
    otherSubreports = report.subreports.filter(
      (_subreport) => _subreport !== subreport
    );
  }}
>
  <form bind:this={form}>
    {#each report.cargoEntries as cargo}
      <Checkbox
        value={cargo.id}
        checked={cargoIsInSubreport(cargo.id)}
        disabled={cargoIsInOtherSubreports(cargo.id)}
        >{cargo.cargoName} ({cargo.customer})
        {#if cargoIsInOtherSubreports(cargo.id)}
          <span class="italic font-normal ms-2">
            (sous-rapport {getSubreportIndexForCargo(cargo.id) + 1})
          </span>
        {/if}
      </Checkbox>
    {:else}
      <div class="italic">Aucune marchandise</div>
    {/each}
  </form>

  <div class="text-center">
    <!-- Bouton "Modifier" -->
    <BoutonAction preset="ajouter" text="Valider" on:click={updateCargoes} />

    <!-- Bouton "Annuler" -->
    <BoutonAction preset="annuler" on:click={cancelUpdate} />
  </div>
</Modal>
