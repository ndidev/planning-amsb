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
  import { ConnexionSSE } from "@app/components";

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

<ConnexionSSE />

<div class="choix-conteneur pure-g">
  {#each [...sitemap] as [rubrique, { affichage, tree: { href, children } }]}
    {#if $currentUser.canAccess(rubrique)}
      <div class="choix pure-u-1">
        <a href={href || children[0].href}>{affichage}</a>
      </div>
    {/if}
  {/each}
</div>

<style>
  .choix-conteneur {
    text-align: center;
    margin-top: 5%;
  }

  .choix {
    margin: 10px auto;
  }

  .choix a {
    color: #666;
    font-size: 1.5em;
    text-decoration: none;
    text-transform: uppercase;
    padding: 10px;
  }

  .choix a:hover,
  .choix a:focus {
    color: #333;
    text-decoration: underline;
    outline: 3px solid lightblue;
  }
</style>
