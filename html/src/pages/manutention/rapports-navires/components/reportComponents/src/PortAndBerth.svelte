<script lang="ts">
  import type { StevedoringShipReport } from "@app/types";

  export let report: StevedoringShipReport;
</script>

<div>
  <div>
    <span class="font-bold">Port :</span>
    {report.port}
  </div>
  <div>
    <span class="font-bold">Quai :</span>
    {report.berth}
  </div>
</div>
