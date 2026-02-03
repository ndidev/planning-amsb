<script lang="ts">
  // import { UserRoundIcon, UserRoundCheckIcon } from "lucide-svelte";
  import UserRoundIcon from "lucide-svelte/icons/user-round";
  import UserRoundCheckIcon from "lucide-svelte/icons/user-round-check";
  import { Tooltip } from "flowbite-svelte";

  import { LucideButton } from "@app/components";

  import { currentUser, stevedoringStaff } from "@app/stores";

  import type { ModuleId } from "@app/types";

  type DispatchItem = {
    staffId: number;
    remarks: string;
    new?: boolean;
  };

  export let dispatch: DispatchItem[];
  export let showDispatchModal: boolean;
  export let module: ModuleId;
</script>

<div class="text-center align-middle">
  {#if dispatch.filter((item) => !item.new).length > 0}
    {#if $currentUser.canEdit(module)}
      <LucideButton
        icon={UserRoundCheckIcon}
        color="green"
        staticallyColored
        title="Renseigner le dispatch"
        on:click={() => (showDispatchModal = true)}
      />
      <Tooltip type="auto">
        {#each dispatch as { staffId, remarks }, index}
          <div>
            {$stevedoringStaff?.get(staffId)?.fullname ||
              "(Personnel supprimé)"}
            {#if remarks}
              : {remarks}
            {/if}
          </div>
        {/each}
      </Tooltip>
    {:else}
      <UserRoundCheckIcon color="green" />
      <Tooltip type="auto">
        {#each dispatch as { staffId, remarks }, index}
          <div>
            {$stevedoringStaff?.get(staffId)?.fullname ||
              "(Personnel supprimé)"}
            {#if remarks}
              : {remarks}
            {/if}
          </div>
        {/each}
      </Tooltip>
    {/if}
  {:else if $currentUser.canEdit("vrac")}
    <LucideButton
      icon={UserRoundIcon}
      title="Renseigner le dispatch"
      on:click={() => (showDispatchModal = true)}
    />
  {:else}
    <UserRoundIcon color="lightgray" />
  {/if}
</div>
