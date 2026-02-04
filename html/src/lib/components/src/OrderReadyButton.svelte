<script lang="ts">
  import PackageIcon from "lucide-svelte/icons/package";
  import PackageCheckIcon from "lucide-svelte/icons/package-check";
  import PackageXIcon from "lucide-svelte/icons/package-x";

  import { LucideButton } from "@app/components";

  import { currentUser } from "@app/stores";

  import type { ModuleId } from "@app/types";

  export let orderReady: boolean;
  export let toggleOrderReady: () => void;
  export let module: ModuleId;
</script>

{#if orderReady}
  <div
    class="text-center lg:group-hover:[display:var(--display-on-over)]"
    style:--display-on-over={$currentUser.canEdit(module) ? "none" : "block"}
  >
    <PackageIcon />
  </div>
{/if}

{#if $currentUser.canEdit(module)}
  <div class="hidden text-center lg:group-hover:block">
    <LucideButton
      icon={orderReady ? PackageXIcon : PackageCheckIcon}
      title={orderReady
        ? "Annuler la préparation de commande"
        : "Renseigner commande prête"}
      on:click={toggleOrderReady}
    />
  </div>
{/if}
