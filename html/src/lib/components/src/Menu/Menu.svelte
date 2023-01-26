<!-- 
  @component
  
  Menu de navigation.

  Usage :
  ```tsx
  <Menu />
  ```
 -->
<script lang="ts">
  import { onMount } from "svelte";

  import { MaterialButton } from "@app/components";
  import UserFooter from "./UserFooter.svelte";

  import { currentUser } from "@app/stores";

  import { sitemap } from "@app/utils";

  type Nouveau =
    | {
        rubrique: string;
        title: string;
        href: string;
      }
    | undefined;

  /**
   * Si défini, affiche une icône pour un nouveau RDV.
   */
  export let nouveau: Nouveau = undefined;

  let nav: HTMLElement;

  function toggleDisplay() {
    const affichageMenu = JSON.parse(sessionStorage.getItem("menu") || "true");
    nav.style.display = !affichageMenu ? "block" : "none";
    sessionStorage.setItem("menu", JSON.stringify(!affichageMenu));
  }

  onMount(() => {
    /**
     * Utilisation de sessionStorage pour enregistrer l'affichage du menu.
     * Si sessionStorage absent, affichage du menu.
     */
    {
      const affichageMenu = JSON.parse(
        sessionStorage.getItem("menu") || "true"
      );
      nav.style.display = affichageMenu ? "block" : "none";
    }
  });
</script>

<div id="menu-toggle-add">
  <MaterialButton
    icon="menu"
    title="Menu"
    fontSize="36px"
    on:click={toggleDisplay}
  />
  {#if nouveau && $currentUser.canEdit(nouveau.rubrique)}
    <MaterialButton
      icon="add"
      title={nouveau.title}
      fontSize="36px"
      on:click={() => (location.href = nouveau.href)}
    />
  {/if}
</div>

<nav class="menu-nav" bind:this={nav}>
  <ul>
    {#each [...sitemap] as [module, { affichage, tree: { href, children } }]}
      {#if $currentUser.canAccess(module)}
        <li>
          {#if href}
            <a {href} title={affichage} class="menu-rubrique menu-link"
              >{affichage}</a
            >
          {:else}
            <span class="menu-rubrique">{affichage}</span>
          {/if}
          {#if children}
            <ul>
              {#each children as { affichage, roleMini, href }}
                {#if $currentUser.getRole(module) >= roleMini}
                  <li>
                    <a {href} title={affichage} class="menu-link">{affichage}</a
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

  <UserFooter />
</nav>

<style>
  :root {
    --couleur-texte-menu: rgb(100, 100, 100);
    --menu-font-family: Arial, Helvetica, sans-serif;
  }

  #menu-toggle-add {
    position: fixed;
    top: 10px;
    left: 10px;
    z-index: 8;
  }

  /******************/

  .menu-nav {
    position: fixed;
    top: 0;
    left: 0;
    width: 180px;
    height: 100vh;
    margin: 0;
    padding: 0;
    z-index: 7;
    color: var(--couleur-texte-menu);
    font-family: var(--menu-font-family);
    text-transform: uppercase;
    font-size: 12px;
    background: rgb(240, 240, 240);
    display: none;
  }

  .menu-nav > ul:first-child {
    margin-top: 60px;
  }

  .menu-nav ul {
    margin-left: 0px;
    padding-left: 10px;
  }

  .menu-nav li {
    list-style: none;
    margin-left: 0;
    padding: 0;
  }

  .menu-rubrique {
    display: inline-block;
    font-weight: bold;
    height: 25px;
  }

  .menu-link {
    display: inline-block;
    height: 25px;
    padding: 0;
    color: var(--couleur-texte-menu);
    text-decoration: none;
  }

  .menu-link:hover {
    text-decoration: underline;
  }
</style>
