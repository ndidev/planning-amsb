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
  import { goto, page } from "@roxi/routify";

  import { UserRoles } from "./UserRoles";
  import type { ModuleId } from "@app/types";

  import { currentUser } from "@app/stores/src/currentUser";

  let canDisplay = $currentUser.canUseApp;

  $: guard = $page.meta.guard as string;

  $: {
    if (guard) {
      let [rubrique, roleMini] = guard.split("/") as [ModuleId, string];

      canDisplay =
        $currentUser.canUseApp &&
        UserRoles[roleMini?.toUpperCase() || "ACCESS"] <=
          $currentUser.getRole(rubrique);
    }

    if (!canDisplay) {
      $goto("/");
      window.location.reload();
    }
  }
</script>

{#if canDisplay}
  <slot />
{/if}
