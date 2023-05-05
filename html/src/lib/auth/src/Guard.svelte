<!-- 
  @component

  Restreindre l'accès à une page.
  
  Usage:
  ```tsx
  <Guard>
    {page}
  </Guard>
  ```
 -->
<script lang="ts">
  import { getContext } from "svelte";
  import { goto, page } from "@roxi/routify";

  import { UserRoles } from "@app/auth";
  import type { ModuleId, Stores } from "@app/types";

  const { currentUser } = getContext<Stores>("stores");

  let canDisplay = $currentUser.canUseApp;

  $: guard = $page.meta.guard as string;

  $: if (guard) {
    let [rubrique, roleMini] = guard.split("/") as [ModuleId, string];

    canDisplay =
      $currentUser.canUseApp &&
      UserRoles[roleMini?.toUpperCase() || "ACCESS"] <=
        $currentUser.getRole(rubrique);

    if (!canDisplay) {
      $goto("/login");
    }
  }
</script>

{#if canDisplay}
  <slot />
{/if}
