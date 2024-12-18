<!-- routify:options title="Planning AMSB - Personnel de manutention" -->

<script lang="ts">
  import { Button, Search } from "flowbite-svelte";

  import { EquipmentCard } from "./components";
  import { Chargement, PageHeading, SseConnection } from "@app/components";

  import { stevedoringEquipments } from "@app/stores";

  import type { StevedoringEquipment } from "@app/types";

  let search: string = "";

  $: displayEquipments =
    // @ts-expect-error
    (search,
    [...($stevedoringEquipments?.values() || [])].filter(
      filterEquipmentsBySearch
    ));

  function groupEquipmentByBrand(equipmentList: StevedoringEquipment[]) {
    return equipmentList.reduce((map, equipment) => {
      const brand = equipment.brand;
      if (!map.has(brand)) map.set(brand, []);
      map.get(brand).push(equipment);
      return map;
    }, new Map<string, StevedoringEquipment[]>());
  }

  function filterEquipmentsBySearch(equipment: StevedoringEquipment) {
    return (
      equipment.brand.toLocaleLowerCase().includes(search) ||
      equipment.model.toLocaleLowerCase().includes(search) ||
      equipment.internalNumber.toLocaleLowerCase().includes(search) ||
      equipment.serialNumber.toLocaleLowerCase().includes(search)
    );
  }
</script>

<!-- routify:options guard="manutention" -->

<SseConnection subscriptions={["stevedoring/equipments"]} />

<main class="mx-auto w-10/12 lg:w-2/3">
  <PageHeading>Équipements de manutention</PageHeading>

  {#if $stevedoringEquipments}
    <div class="text-center">
      <Button href="/manutention/equipements/new" class="mb-4">
        Ajouter un équipement de manutention
      </Button>
    </div>

    <div>
      <Search
        placeholder="Rechercher un équipement de manutention"
        bind:value={search}
        class="mb-4"
      />
    </div>

    <div class="flex flex-col gap-4">
      {#each [...groupEquipmentByBrand(displayEquipments).entries()] as [initial, equipmentList]}
        <div class="mb-2">
          <h2 class="text-2xl font-bold">{initial}</h2>
          <div class="flex flex-row flex-wrap gap-4">
            {#each equipmentList as equipment}
              <EquipmentCard {equipment} />
            {/each}
          </div>
        </div>
      {/each}
    </div>
  {:else}
    <Chargement />
  {/if}
</main>
