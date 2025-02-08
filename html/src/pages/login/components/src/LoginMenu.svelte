<!--
  @component
    
  Menu des rubriques.

  Usage :
  ```tsx
  <LoginMenu />
  ```
 -->
<script lang="ts">
  import { onDestroy, getContext } from "svelte";
  import type { Writable } from "svelte/store";

  import { currentUser } from "@app/stores";

  import { sitemap } from "@app/utils";
  import { SseConnection } from "@app/components";

  const screen: Writable<string> = getContext("screen");

  const unsubscribeUser = currentUser.subscribe((user) => {
    if (!user.canUseApp) {
      screen.set("loginForm");
    }
  });

  onDestroy(() => {
    unsubscribeUser();
  });
</script>

<SseConnection />

<div class="text-center mt-[5%]">
  {#each [...sitemap] as [rubrique, { affichage, tree: { href, children } }]}
    {#if $currentUser.canAccess(rubrique)}
      <div class="mt-3 w-full">
        <a
          href={href || children[0].href}
          class="p-2 text-2xl text-gray-500 hover:text-gray-700 uppercase hover:underline outline-2 outline-sky-200"
          >{affichage}</a
        >
      </div>
    {/if}
  {/each}
</div>
