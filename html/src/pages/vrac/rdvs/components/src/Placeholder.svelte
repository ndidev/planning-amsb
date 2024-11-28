<script lang="ts"></script>

{#each Array(5) as date}
  <!-- Placeholder date -->
  <div class="mt-8 mb-4">
    <div class="inline-block h-6 bg-gray-200 me-3 w-1/4 lg:w-3/24" />
  </div>

  {#each Array(3) as rdv}
    <!-- Placeholder RDV -->
    <div class="p-1">
      <div class="inline-block h-6 bg-gray-200 me-3 w-4/24 lg:w-1/24" />
      <div class="inline-block h-6 bg-gray-200 me-3 w-6/24 lg:w-2/24" />
      <div class="inline-block h-6 bg-gray-200 me-3 w-6/24 lg:w-2/24" />
      <div class="inline-block h-6 bg-gray-200 me-3 w-6/24 lg:w-2/24" />
      <div class="inline-block h-6 bg-gray-200 me-3 w-full lg:w-8/24" />
      <div class="inline-block h-6 bg-gray-200 me-3 w-full lg:w-3/24" />
      <div class="inline-block h-6 bg-gray-200 me-3 w-12/24 lg:w-3/24" />
      <hr />
    </div>
  {/each}
{/each}
