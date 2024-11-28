<script lang="ts">
  import { page, metatags } from "@roxi/routify";

  import {
    Menu,
    OfflineBanner,
    SseConnection,
    EnvFooter,
  } from "@app/components";

  import { Guard, SessionChecker } from "@app/auth";

  import type { ModuleId } from "@app/types";

  $: metatags.title = $page.title;

  $: rubrique = $page.path.split("/")[1] as ModuleId;
</script>

<!-- Connexion SSE pour les infos utilisateur -->
<SseConnection />

<SessionChecker />

<Guard>
  <OfflineBanner />

  <Menu module={rubrique} />

  <div class="page mx-auto w-full">
    <slot />
  </div>

  <EnvFooter />
</Guard>

<style>
  .page {
    margin-bottom: calc(var(--footer-height) + 50px);
  }
</style>
