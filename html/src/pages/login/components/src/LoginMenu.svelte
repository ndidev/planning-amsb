<!--
  @component
    
  Menu des rubriques.

  Usage :
  ```tsx
  <LoginMenu />
  ```
 -->
<script lang="ts">
  import { onMount, onDestroy, getContext } from "svelte";
  import type { Writable } from "svelte/store";

  import { currentUser } from "@app/stores";

  import { demarrerConnexionSSE, sitemap } from "@app/utils";

  let source: EventSource;

  const screen: Writable<string> = getContext("screen");

  onMount(async () => {
    source = await demarrerConnexionSSE([]);
  });

  onDestroy(() => {
    source.close();
  });
</script>

{#if $currentUser.canUseApp}
  <div class="choix-conteneur pure-g">
    {#each [...sitemap] as [module, { affichage, tree: { href, children } }]}
      {#if $currentUser.canAccess(module)}
        <div class="choix pure-u-1">
          <a href={href || children[0].href}>{affichage}</a>
        </div>
      {/if}
    {/each}
  </div>
{:else}
  {screen.set("loginForm")}
{/if}

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
