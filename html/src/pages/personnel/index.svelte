<!-- routify:options title="Planning AMSB - Personnel de manutention" -->

<script lang="ts">
  import { Accordion, AccordionItem, Button, Search } from "flowbite-svelte";

  import { StaffCard } from "./components";
  import { Chargement, PageHeading, SseConnection } from "@app/components";

  import { removeDiacritics } from "@app/utils";

  import { stevedoringStaff } from "@app/stores";

  import type { StevedoringStaff } from "@app/types";

  let search: string = "";

  $: permanentStaff =
    // @ts-expect-error
    (search,
    [...($stevedoringStaff?.values() || [])]
      .filter((staff) => staff.type === "mensuel")
      .filter((staff) => staff.deletedAt === null)
      .filter(filterStaffBySearch)).sort(
      (a, b) =>
        a.lastname.localeCompare(b.lastname) ||
        a.firstname.localeCompare(b.firstname)
    );

  $: temporaryStaff =
    // @ts-expect-error
    (search,
    [...($stevedoringStaff?.values() || [])]
      .filter((staff) => staff.type === "interim")
      .filter((staff) => staff.deletedAt === null)
      .filter(filterStaffBySearch)).sort(
      (a, b) =>
        a.lastname.localeCompare(b.lastname) ||
        a.firstname.localeCompare(b.firstname)
    );

  function groupStaffByLastnameInitial(staffList: StevedoringStaff[]) {
    return staffList.reduce((map, staff) => {
      if (!staff.lastname && !staff.firstname) return map; // Passer si le nom est vide
      const initial = (staff.lastname || staff.firstname)[0].toUpperCase();
      if (!map.has(initial)) map.set(initial, []);
      map.get(initial).push(staff);
      return map;
    }, new Map<string, StevedoringStaff[]>());
  }

  function filterStaffBySearch(staff: StevedoringStaff) {
    const normalizedSearch = removeDiacritics(search);
    const normalizedFirstname = removeDiacritics(staff.firstname);
    const normalizedLastname = removeDiacritics(staff.lastname);

    return (
      normalizedLastname.includes(normalizedSearch) ||
      normalizedFirstname.includes(normalizedSearch)
    );
  }
</script>

<!-- routify:options guard="personnel" -->

<SseConnection subscriptions={[stevedoringStaff.endpoint]} />

<main class="mx-auto w-10/12 lg:w-2/3">
  <PageHeading>Personnel de manutention</PageHeading>

  {#if $stevedoringStaff}
    <div class="text-center">
      <Button href="/personnel/new" class="mb-4">
        Ajouter un membre du personnel
      </Button>
    </div>

    <div>
      <Search
        placeholder="Rechercher un membre du personnel"
        bind:value={search}
        class="mb-4"
      />
    </div>

    <Accordion multiple>
      <!-- CDI -->
      <AccordionItem open>
        <span slot="header">Mensuel ({permanentStaff.length})</span>
        <div class="flex flex-col gap-4">
          {#each [...new Map([...groupStaffByLastnameInitial(permanentStaff)].sort()).entries()] as [initial, staffList]}
            <div class="mb-2">
              <h2 class="text-2xl font-bold">{initial}</h2>
              <div class="flex flex-row flex-wrap gap-4">
                {#each staffList as staff}
                  <StaffCard {staff} />
                {/each}
              </div>
            </div>
          {/each}
        </div>
      </AccordionItem>

      <!-- Intérim -->
      <AccordionItem open={search !== ""}>
        <span slot="header">Intérimaires ({temporaryStaff.length})</span>
        <div class="flex flex-col gap-4">
          {#each [...new Map([...groupStaffByLastnameInitial(temporaryStaff)].sort()).entries()] as [initial, staffList]}
            <div class="mb-2">
              <h2 class="text-2xl font-bold">{initial}</h2>
              <div class="flex flex-row flex-wrap gap-4">
                {#each staffList as staff}
                  <StaffCard {staff} />
                {/each}
              </div>
            </div>
          {/each}
        </div>
      </AccordionItem>
    </Accordion>
  {:else}
    <Chargement />
  {/if}
</main>
