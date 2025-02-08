<script lang="ts"></script>

{#each Array(5) as date}
  <div class="mb-4 mt-8 flex gap-2">
    <div class="h-6 w-1/4 bg-gray-200 lg:w-3/24" />
    <div class="h-6 w-1/4 bg-gray-200 lg:w-2/24" />
  </div>

  <div class="divide-y">
    {#each Array(3) as rdv}
      <div
        class="grid grid-cols-[50px_1fr] gap-2 py-2 lg:grid-cols-[4%_4%_20%_8%_3%_8%_8%_3%_8%_20%_auto]"
      >
        <!-- Heure d'arrivée -->
        <div
          class="h-6 bg-gray-200 [grid-area:1/1/6/2] lg:col-auto lg:row-auto"
        ></div>

        <!-- Heure de départ -->
        <div
          class="col-start-1 row-start-2 h-6 bg-gray-200 lg:col-auto lg:row-auto"
        ></div>

        <!-- Adresses -->
        <div class="h-6 bg-gray-200"></div>

        <!-- Transporteur -->
        <div class="h-6 bg-gray-200"></div>

        <!-- Confirmation d'affrètement -->
        <div
          class="col-start-1 row-start-4 h-6 bg-gray-200 lg:col-auto lg:row-auto"
        ></div>

        <!-- Affréteur -->
        <div class="h-6 bg-gray-200"></div>

        <!-- Fournisseur -->
        <div class="h-6 bg-gray-200"></div>

        <!-- Commande prête -->
        <div
          class="col-start-1 row-start-3 h-6 bg-gray-200 lg:col-auto lg:row-auto"
        ></div>

        <!-- Numéro B/L -->
        <div class="no-mobile h-6 bg-gray-200"></div>

        <!-- Commentaires -->
        <div class="h-6 bg-gray-200"></div>
      </div>
    {/each}
  </div>
{/each}
