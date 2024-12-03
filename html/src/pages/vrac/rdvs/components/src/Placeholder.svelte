<script lang="ts"></script>

{#each Array(5) as date}
  <!-- Placeholder date -->
  <div class="mb-4 mt-8 h-6 w-1/4 bg-gray-200 lg:w-3/24" />

  <div class="divide-y">
    {#each Array(3) as rdv}
      <!-- Placeholder RDV -->
      <div
        class="grid grid-cols-[50px_1fr] gap-2 py-2 lg:grid-cols-[4%_16%_4%_8%_29%_8%_16%_auto]"
      >
        {#each Array(7) as i}
          <div class="h-6 bg-gray-200" />
        {/each}
      </div>
    {/each}
  </div>
{/each}
