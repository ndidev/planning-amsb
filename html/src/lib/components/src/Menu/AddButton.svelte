<!-- 
  @component
  
  Bouton d'ajout de RDV.

  Usage:
  ```tsx
  <AddButton rubrique: string />
  ```
 -->
<script lang="ts">
  import { getContext } from "svelte";
  import { goto } from "@roxi/routify";

  import { PlusIcon } from "lucide-svelte";

  import { LucideButton } from "@app/components";
  import AjoutsRapidesBois from "./AjoutsRapidesBois.svelte";
  import AjoutsRapidesVrac from "./AjoutsRapidesVrac.svelte";

  import { device } from "@app/utils";

  import type { Stores, ModuleId } from "@app/types";

  // const { configAjoutsRapides } = getContext<Stores>("stores");

  type Nouveau =
    | {
        title: string;
        href: string;
      }
    | undefined;

  const nouveaux = new Map<ModuleId, Nouveau>([
    ["vrac", { title: "Nouveau RDV", href: "/vrac/rdvs/new" }],
    ["bois", { title: "Nouveau RDV", href: "/bois/rdvs/new" }],
    [
      "consignation",
      { title: "Nouvelle escale", href: "/consignation/escales/new" },
    ],
    [
      "chartering",
      { title: "Nouvel affrètement", href: "/chartering/charters/new" },
    ],
  ]);

  // const ajoutsRapides: { [P in keyof typeof $configAjoutsRapides]: any } = {
  const ajoutsRapides = {
    bois: AjoutsRapidesBois,
    vrac: AjoutsRapidesVrac,
  };

  /**
   * Module de l'application.
   */
  export let rubrique: ModuleId;

  $: nouveau = nouveaux.get(rubrique);
</script>

{#if nouveau}
  <span class="add-button">
    <LucideButton
      icon={PlusIcon}
      title={nouveau.title}
      size={$device.isSmallerThan("desktop") ? "24px" : "36px"}
      on:click={() => $goto(nouveau.href)}
    />

    {#if rubrique in ajoutsRapides}
      <svelte:component this={ajoutsRapides[rubrique]} />
    {/if}
  </span>
{/if}

<style>
  /* Mobile */
  @media screen and (max-width: 767px) {
    .add-button {
      display: inline-block;
    }
  }

  /* Desktop */
  @media screen and (min-width: 768px) {
    .add-button {
      display: inline-block;
      position: relative;
    }

    .add-button:hover :global(> ul) {
      display: block;
    }
  }
</style>
