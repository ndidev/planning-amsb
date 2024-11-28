<!-- 
  @component
  
  Menu de navigation.

  Usage :
  ```tsx
  <Menu module?: string />
  ```
 -->
<script lang="ts">
  import { url, goto, page, params } from "@roxi/routify";

  import {
    Drawer,
    Sidebar,
    SidebarDropdownItem,
    SidebarDropdownWrapper,
    SidebarGroup,
    SidebarItem,
    SidebarWrapper,
  } from "flowbite-svelte";
  import { sineIn } from "svelte/easing";
  import { MenuIcon } from "lucide-svelte";
  import Notiflix from "notiflix";

  import { LucideButton } from "@app/components";
  import AddButton from "./AddButton.svelte";

  import { currentUser } from "@app/stores";
  import { User } from "@app/auth";
  import { sitemap, device } from "@app/utils";
  import type { ModuleId } from "@app/types";

  /**
   * Module de l'application.
   */
  export let module: ModuleId;

  let displayMenu = false;

  $: menuButtonFontSize = $device.isSmallerThan("desktop") ? "24px" : "36px";

  $: activeUrl =
    $page.path.replace("/index", "") +
    ("archives" in $params ? "?archives" : "");

  /**
   * Déconnecter l'utilisateur courant.
   */
  async function logout() {
    try {
      await $currentUser.logout();
      currentUser.set(new User());
      $goto("/");
    } catch (error: any) {
      Notiflix.Notify.failure(error.message);
      console.error(error);
    }
  }
</script>

<div class="fixed left-0 top-0 m-3 z-[100]">
  <!-- Affichage/masquage du menu -->
  <LucideButton
    icon={MenuIcon}
    title="Menu"
    size={menuButtonFontSize}
    on:click={() => {
      displayMenu = !displayMenu;
    }}
  />

  <!-- Nouveau RDV -->
  {#if $currentUser.canEdit(module)}
    <AddButton rubrique={module} />
  {/if}
</div>

<Drawer
  bind:hidden={displayMenu}
  backdrop={false}
  activateClickOutside={false}
  transitionType="fly"
  transitionParams={{ x: -320, duration: 200, easing: sineIn }}
  width="w-64"
  id="menu-drawer"
>
  <Sidebar class={`fixed inset-y-0 left-0 w-64`} {activeUrl}>
    <SidebarWrapper class="pt-16 h-full flex flex-col">
      {#each [...sitemap] as [module, { affichage, tree: { href, children, devices } }]}
        {@const deviceMatches = devices?.includes($device.type) ?? true}
        {#if $currentUser.canAccess(module) && deviceMatches}
          <SidebarGroup>
            {#if href}
              <SidebarItem href={$url(href)} label={affichage} />
            {:else}
              <SidebarDropdownWrapper
                label={affichage}
                isOpen={activeUrl.includes(module)}
              >
                {#if children}
                  {#each children as { affichage, roleMini, href, devices }}
                    {#if $currentUser.getRole(module) >= roleMini && deviceMatches}
                      <SidebarDropdownItem
                        href={$url(href)}
                        label={affichage}
                        active={activeUrl === href}
                      />
                    {/if}
                  {/each}
                {/if}
              </SidebarDropdownWrapper>
            {/if}
          </SidebarGroup>
        {/if}
      {/each}

      <SidebarGroup border class="mt-auto">
        <SidebarItem
          href={$url("/user")}
          label={$currentUser.nom}
          class="font-bold"
          title="Configuration utilisateur"
        />
        <SidebarItem label="Se déconnecter" on:click={logout} />
      </SidebarGroup>
    </SidebarWrapper>
  </Sidebar>
</Drawer>
