<!-- routify:options title="Planning AMSB - Personnel de manutention" -->

<script lang="ts">
  import { getContext } from "svelte";

  import { Accordion, AccordionItem, Button, Search } from "flowbite-svelte";

  import { StaffCard } from "./components";
  import { PageHeading } from "@app/components";

  import type { StevedoringStaff, Stores } from "@app/types";
  import { Chargement } from "@app/components";

  const { stevedoringStaff } = getContext<Stores>("stores");

  let search: string = "";

  $: permanentStaff =
    // @ts-expect-error
    (search,
    [...($stevedoringStaff?.values() || [])]
      .filter((staff) => staff.type === "cdi")
      .filter((staff) => staff.deletedAt === null)
      .filter(filterStaffBySearch));

  $: temporaryStaff =
    // @ts-expect-error
    (search,
    [...($stevedoringStaff?.values() || [])]
      .filter((staff) => staff.type === "interim")
      .filter((staff) => staff.deletedAt === null)
      .filter(filterStaffBySearch));

  function groupStaffByLastnameInitial(staffList: StevedoringStaff[]) {
    return staffList.reduce((acc, staff) => {
      const initial = staff.lastname[0].toUpperCase();
      if (!acc.has(initial)) acc.set(initial, []);
      acc.get(initial).push(staff);
      return acc;
    }, new Map<string, StevedoringStaff[]>());
  }

  function filterStaffBySearch(staff: StevedoringStaff) {
    return (
      staff.lastname.toLocaleLowerCase().includes(search) ||
      staff.firstname.toLocaleLowerCase().includes(search)
    );
  }
</script>

<!-- routify:options guard="manutention" -->

<main class="mx-auto w-10/12 lg:w-2/3">
  <PageHeading>Personnel de manutention</PageHeading>

  {#if $stevedoringStaff}
    <div class="text-center">
      <Button href="/manutention/personnel/new" class="mb-4">
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
        <span slot="header">CDI ({permanentStaff.length})</span>
        <div class="flex flex-col gap-4">
          {#each [...groupStaffByLastnameInitial(permanentStaff).entries()] as [initial, staffList]}
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
          {#each [...groupStaffByLastnameInitial(temporaryStaff).entries()] as [initial, staffList]}
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
