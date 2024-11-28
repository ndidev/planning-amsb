<!-- 
  @component
  
  Ligne de date du planning vrac

  Usage :
  ```tsx
  <LigneDate
    date: string={"yyyy-mm-dd"}
    maree: boolean/>
  ```
 -->
<script lang="ts">
  import { WavesIcon, ShipIcon } from "lucide-svelte";

  export let date: string;

  /**
   * `true` si une marée supérieure à 4m à cette date.
   */
  export let maree: boolean = false;

  /**
   * Navires à quai durant cette date.
   */
  export let navires: string[];

  import { DateUtils } from "@app/utils";

  const formattedDate = new DateUtils(date).format().long;
</script>

<div class="mb-2 mt-5 w-full first:mt-12 lg:mb-4 lg:mt-8 lg:text-lg">
  <span class="text-green-400">{formattedDate}</span>

  <!-- Point d'exclamation si vive-eau -->
  {#if maree}
    <span
      class="relative ms-3 inline cursor-help align-top text-red-500"
      title="Navires potentiellement à quai"><WavesIcon /></span
    >
  {/if}

  <!-- Pictogramme + nom des navires à quai si applicable -->
  {#if navires.length > 0}
    <div class="group relative ms-3 inline cursor-help text-red-500">
      <i class="align-top"><ShipIcon /></i>
      <div
        class="absolute left-8 top-0 hidden h-auto w-auto whitespace-nowrap border-2 bg-amber-50 p-1 text-xs group-hover:block"
      >
        {@html navires.join("<br />")}
      </div>
    </div>
  {/if}
</div>
