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

  // import Hammer from "hammerjs";

  import { MaterialButton } from "@app/components";
  import AjoutsRapidesBois from "./AjoutsRapidesBois.svelte";

  import { device } from "@app/utils";

  import type { Stores, ModuleId } from "@app/types";

  const { configAjoutsRapides } = getContext<Stores>("stores");

  // let mc: HammerManager;
  let addButton: HTMLSpanElement;

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

  const ajoutsRapides: { [P in keyof typeof $configAjoutsRapides]: any } = {
    bois: AjoutsRapidesBois,
  };

  /**
   * Module de l'application.
   */
  export let rubrique: ModuleId;

  $: nouveau = nouveaux.get(rubrique);

  // let afficherAjoutsRapides = false;

  // onMount(() => {
  //   mc = new Hammer(addButton);
  //   mc.on("press", () => {
  //     if ($device.is("mobile")) {
  //       afficherAjoutsRapides = true;
  //     }
  //   });
  // });

  // onDestroy(() => {
  //   mc.destroy();
  // });
</script>

{#if nouveau}
  <span class="add-button" bind:this={addButton}>
    <MaterialButton
      icon="add"
      title={nouveau.title}
      fontSize={$device.isSmallerThan("desktop") ? "24px" : "36px"}
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
      position: relative;
      /* isolation: isolate; */
    }

    /* .add-button::before {
      content: "";
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      border-radius: 50%;
      background-color: aqua;
      z-index: -1;
    } */
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
