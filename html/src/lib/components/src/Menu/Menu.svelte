<!-- 
  @component
  
  Menu de navigation.

  Usage :
  ```tsx
  <Menu module?: string />
  ```
 -->
<script lang="ts">
  import { url, beforeUrlChange } from "@roxi/routify";

  import { MaterialButton } from "@app/components";
  import AddButton from "./AddButton.svelte";
  import UserFooter from "./UserFooter.svelte";

  import { currentUser } from "@app/stores";

  import { sitemap, device } from "@app/utils";

  import type { ModuleId } from "@app/types";

  /**
   * Module de l'application.
   */
  export let module: ModuleId;

  let nav: HTMLElement;

  let affichageMenu = false;

  $: menuButtonFontSize = $device.isSmallerThan("desktop") ? "24px" : "36px";

  // Cacher le menu lors le la navigation sur mobile
  $beforeUrlChange((event, route) => {
    if (nav.offsetWidth >= document.body.offsetWidth) {
      affichageMenu = false;
    }

    return true;
  });
</script>

<div class="menu-toggle-add">
  <!-- Affichage/masquage du menu -->
  <MaterialButton
    icon="menu"
    title="Menu"
    fontSize={menuButtonFontSize}
    on:click={() => {
      affichageMenu = !affichageMenu;
    }}
  />

  <!-- Nouveau RDV -->
  {#if $currentUser.canEdit(module)}
    <AddButton rubrique={module} />
  {/if}
</div>

<nav bind:this={nav} style="display: {affichageMenu ? 'flex' : 'none'};">
  <ul>
    {#each [...sitemap] as [module, { affichage, tree: { href, children, devices } }]}
      {@const deviceMatches = devices?.includes($device.type) ?? true}
      {#if $currentUser.canAccess(module) && deviceMatches}
        <li>
          {#if href}
            <a href={$url(href)} title={affichage} class="rubrique link"
              >{affichage}</a
            >
          {:else}
            <span class="rubrique">{affichage}</span>
          {/if}
          {#if children}
            <ul>
              {#each children as { affichage, roleMini, href, devices }}
                {#if $currentUser.getRole(module) >= roleMini && deviceMatches}
                  <li>
                    <a href={$url(href)} title={affichage} class="link"
                      >{affichage}</a
                    >
                  </li>
                {/if}
              {/each}
            </ul>
          {/if}
        </li>
      {/if}
    {/each}
  </ul>

  <div class="user-footer" style="display: {affichageMenu ? 'block' : 'none'};">
    <UserFooter />
  </div>
</nav>

<style>
  * {
    --couleur-texte-menu: hsl(0, 0%, 39%);
    --menu-font-family: Arial, Helvetica, sans-serif;
    --bg-color: hsl(0, 0%, 94%);
  }

  .menu-toggle-add {
    position: fixed;
    top: 0;
    left: 0;
    margin: 10px;
    z-index: 25;
  }

  /******************/

  nav {
    display: flex;
    flex-direction: column;
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    z-index: 20;
    margin: 0;
    padding: 0;
    background: hsl(0, 0%, 94%);
    color: var(--couleur-texte-menu);
    font-family: var(--menu-font-family);
    text-transform: uppercase;
    text-align: center;
  }

  nav > ul {
    margin-top: 60px;
  }

  nav li {
    list-style: none;
  }

  .rubrique {
    display: inline-block;
    font-weight: bold;
    margin-top: 1rem;
    margin-bottom: 0.4rem;
  }

  .link {
    display: inline-block;
    height: 1.5em;
    padding: 0;
    color: var(--couleur-texte-menu);
    text-decoration: none;
  }

  .link:not(.rubrique) {
    text-transform: none;
    font-variant: small-caps;
  }

  .link:hover {
    text-decoration: underline;
  }

  .user-footer {
    align-self: center;
    margin-top: auto;
    margin-bottom: 10px;
    color: var(--couleur-texte-menu);
    font-family: var(--menu-font-family);
  }

  /* Desktop */
  @media screen and (min-width: 768px) {
    nav {
      width: 200px;
      text-align: initial;
      font-size: 0.8rem;
    }

    nav ul {
      margin-left: 0px;
      padding-left: 10px;
    }

    nav li {
      margin-left: 0;
      padding: 0;
    }

    .user-footer {
      align-self: flex-start;
      padding-left: 10px;
      font-size: 0.8rem;
    }
  }
</style>
