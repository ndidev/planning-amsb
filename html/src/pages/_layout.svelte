<!-- routify:options preload="proximity" -->
<script lang="ts">
  import { page, metatags, goto } from "@roxi/routify";

  import { Menu, OfflineBanner } from "@app/components";

  import { currentUser } from "@app/stores";

  import type { ModuleId } from "@app/types";

  $: metatags.title = $page.title;

  $: module = $page.path.split("/")[1] as ModuleId;

  const afficherFooterMode = import.meta.env.MODE !== "production";
</script>

{#if $currentUser.canUseApp}
  <div class="container">
    <OfflineBanner />

    <div class="page">
      <Menu {module} />

      <slot />
    </div>

    {#if afficherFooterMode}
      <footer>
        Mode <strong>{import.meta.env.MODE}</strong>
      </footer>
    {/if}
  </div>
{:else}
  {$goto("/login")}
{/if}

<style>
  @import "/src/css/commun.css";
  @import "/src/css/formulaire.css";

  * {
    --footer-height: 50px;
  }

  .page {
    min-height: calc(100svh - var(--footer-height));
  }

  footer {
    position: sticky;
    bottom: 0;
    height: var(--footer-height);
    line-height: var(--footer-height);
    z-index: 10;
    background-color: lightpink;
    color: black;
    text-align: center;
  }
</style>
