<script lang="ts">
  import { page, metatags } from "@roxi/routify";

  import { Menu, OfflineBanner, ConnexionSSE } from "@app/components";

  import { Guard, SessionChecker } from "@app/auth";

  import type { ModuleId } from "@app/types";

  $: metatags.title = $page.title;

  $: rubrique = $page.path.split("/")[1] as ModuleId;

  const afficherFooterMode = import.meta.env.MODE !== "production";
</script>

<!-- Connexion SSE pour les infos utilisateur -->
<ConnexionSSE />

<SessionChecker />

<Guard>
  <div class="container">
    <OfflineBanner />

    <Menu module={rubrique} />

    <div class="page">
      <slot />
    </div>

    {#if afficherFooterMode}
      <footer>
        Mode <strong>{import.meta.env.MODE}</strong>
      </footer>
    {/if}
  </div>
</Guard>

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
    background-color: lightpink;
    color: black;
    text-align: center;
  }
</style>
