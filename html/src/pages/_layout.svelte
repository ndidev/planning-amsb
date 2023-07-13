<script lang="ts">
  import { page, metatags } from "@roxi/routify";

  import {
    Menu,
    OfflineBanner,
    ConnexionSSE,
    EnvFooter,
  } from "@app/components";

  import { Guard, SessionChecker } from "@app/auth";

  import type { ModuleId } from "@app/types";

  $: metatags.title = $page.title;

  $: rubrique = $page.path.split("/")[1] as ModuleId;
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

    <EnvFooter />
  </div>
</Guard>

<style>
  @import "/src/css/commun.css";
  @import "/src/css/formulaire.css";

  .page {
    min-height: calc(100svh - var(--footer-height));
  }
</style>
