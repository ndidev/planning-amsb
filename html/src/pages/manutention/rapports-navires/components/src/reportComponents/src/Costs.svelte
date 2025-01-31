<script lang="ts">
  import { SectionTitle } from "../";

  import { NumberUtils } from "@app/utils";

  import type { StevedoringShipReport } from "@app/types";

  export let report: StevedoringShipReport;

  let totalCraneHours = Object.values(report.entriesByDate)
    .flatMap(({ cranes }) => cranes)
    .reduce((acc, curr) => acc + curr.hoursWorked, 0);

  let costs = {
    // Standard costs
    permanentStaffHours: Object.values(report.entriesByDate)
      .flatMap(({ permanentStaff }) => permanentStaff)
      .reduce((acc, curr) => acc + curr.hoursWorked, 0),
    craneHours: totalCraneHours,
    equipmentHours: Object.values(report.entriesByDate)
      .flatMap(({ equipments }) => equipments)
      .reduce((acc, curr) => acc + curr.hoursWorked, 0),

    // Subcontracts
    tempStaffHours: Object.values(report.entriesByDate)
      .flatMap(({ tempStaff }) => tempStaff)
      .reduce((acc, curr) => acc + curr.hoursWorked, 0),
    truckingHours: Object.values(report.entriesByDate)
      .flatMap(({ trucking }) => trucking)
      .reduce((acc, curr) => acc + curr.hoursWorked, 0),
    truckingCost: Object.values(report.entriesByDate)
      .flatMap(({ trucking }) => trucking)
      .reduce((acc, curr) => acc + curr.cost, 0),
    truckingDetails: Object.values(report.entriesByDate)
      .flatMap(({ trucking }) => trucking)
      .reduce(
        (acc: { [name: string]: { hours: number; cost: number } }, curr) => {
          if (!acc[curr.subcontractorName]) {
            acc[curr.subcontractorName] = {
              hours: 0,
              cost: 0,
            };
          }

          acc[curr.subcontractorName].hours += curr.hoursWorked;
          acc[curr.subcontractorName].cost += curr.cost;

          return acc;
        },
        {}
      ),
    otherSubcontractsHours: Object.values(report.entriesByDate)
      .flatMap(({ otherSubcontracts }) => otherSubcontracts)
      .reduce((acc, curr) => acc + curr.hoursWorked, 0),
    otherSubcontractsCost: Object.values(report.entriesByDate)
      .flatMap(({ otherSubcontracts }) => otherSubcontracts)
      .reduce((acc, curr) => acc + curr.cost, 0),
    otherSubcontractsDetails: Object.values(report.entriesByDate)
      .flatMap(({ otherSubcontracts }) => otherSubcontracts)
      .reduce(
        (acc: { [name: string]: { hours: number; cost: number } }, curr) => {
          if (!acc[curr.subcontractorName]) {
            acc[curr.subcontractorName] = {
              hours: 0,
              cost: 0,
            };
          }

          acc[curr.subcontractorName].hours += curr.hoursWorked;
          acc[curr.subcontractorName].cost += curr.cost;

          return acc;
        },
        {}
      ),
  };
</script>

<div>
  <SectionTitle>Coûts</SectionTitle>

  <div
    class="ms-2 flex flex-col gap-2 lg:flex-row lg:gap-6 print:flex-row print:gap-12 print:text-sm"
  >
    <div>
      <div class="font-bold">Coûts standards</div>
      <div>Personnel : <span>{costs.permanentStaffHours}h</span></div>
      <div>Grues : <span>{costs.craneHours}</span>h</div>
      <div>Engins : <span>{costs.equipmentHours}h</span></div>
    </div>
    <div>
      <div class="font-bold">Sous-traitance</div>
      <div>Intérim : <span>{costs.permanentStaffHours}h</span></div>
      <div>
        <span
          class:underline={costs.truckingHours || costs.truckingCost}
          class:cursor-help={costs.truckingHours || costs.truckingCost}
          id="trucking-costs"
          title="Afficher le détail">Brouettage</span
        >
        : <span>{costs.truckingHours}h</span> /
        <span>{NumberUtils.formatCost(costs.truckingCost)}</span>
      </div>
      <div>
        <span
          class:underline={costs.otherSubcontractsHours ||
            costs.otherSubcontractsCost}
          class:cursor-help={costs.otherSubcontractsHours ||
            costs.otherSubcontractsCost}
          id="otherSubcontracts-costs"
          title="Afficher le détail">Autres</span
        >
        : <span>{costs.otherSubcontractsHours}h</span> /
        <span>{NumberUtils.formatCost(costs.otherSubcontractsCost)}</span>
      </div>
    </div>
  </div>
</div>
