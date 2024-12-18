<!-- 
  @component
  
  Ligne du planning d'affrètement maritime.

  Usage :
  ```tsx
  <LigneCharter charter: Charter />
  ```
 -->
<script lang="ts">
  import { onMount, onDestroy, getContext } from "svelte";
  import { goto } from "@roxi/routify";

  import Hammer from "hammerjs";
  import { Modal } from "flowbite-svelte";

  import { Badge, LucideButton, BoutonAction } from "@app/components";

  import { device, locale } from "@app/utils";

  import { currentUser, tiers, ports } from "@app/stores";

  import type { Charter } from "@app/types";

  export let charter: Charter;
  let ligne: HTMLDivElement;

  let mc: HammerManager;
  let showModal = false;

  const archives: boolean = getContext("archives");

  type Status = {
    name: "planned" | "confirmed" | "chartered" | "loaded" | "completed";
    color: string;
    text: string;
  };

  const statusMap = new Map<Status["name"], Status>([
    [
      "planned",
      { name: "planned", color: "hsla(0, 0%, 80%, 1)", text: "Planifié" },
    ],
    [
      "confirmed",
      { name: "confirmed", color: "hsl(210, 100%, 50%)", text: "Confirmé" },
    ],
    [
      "chartered",
      { name: "chartered", color: "hsla(270, 100%, 50%, 1)", text: "Affrété" },
    ],
    [
      "loaded",
      { name: "loaded", color: "hsla(30, 100%, 50%, 1)", text: "Chargé" },
    ],
    [
      "completed",
      { name: "completed", color: "hsla(120, 100%, 50%, 1)", text: "Terminé" },
    ],
  ]);

  let charterStatus: Status | null = null;

  // @ts-expect-error
  $: charterStatus = (charter, getCharterStatus());

  /**
   * Assignation d'une classe à chaque affrètement en fonction de son statut
   * afin de mettre en forme le planning.
   */
  function getCharterStatus() {
    switch (charter.statut) {
      case 0:
        return statusMap.get("planned");

      case 1:
        return statusMap.get("confirmed");

      case 2:
        return statusMap.get("chartered");

      case 3:
        return statusMap.get("loaded");

      case 4:
        return statusMap.get("completed");

      default:
        return null;
    }
  }

  onMount(() => {
    mc = new Hammer(ligne);
    mc.on("press", () => {
      if ($device.is("mobile")) {
        showModal = true;
      }
    });
  });

  onDestroy(() => {
    mc.destroy();
  });
</script>

<Modal bind:open={showModal} outsideclose dismissable={false}>
  <BoutonAction preset="modifier" on:click={$goto(`./${charter.id}`)} />
  <BoutonAction preset="annuler" on:click={() => (showModal = false)} />
</Modal>

<div
  class="group grid gap-1 border-l-[10px] border-l-[color:var(--status-color)] px-6 py-2 text-sm first:mt-10 last:mb-12 lg:grid-cols-[20%_20%_50%_auto] lg:gap-2 lg:px-10 lg:text-base"
  style:--status-color={charterStatus?.color || "transparent"}
  bind:this={ligne}
>
  <!-- Navire / Statut / Armateur -->
  <div>
    <!-- Navire -->
    <div class="mb-2 text-2xl">{charter.navire}</div>

    <!-- Statut -->
    {#if charterStatus}
      <div class="mb-2">
        <Badge color={charterStatus.color} size="sm">{charterStatus.text}</Badge
        >
      </div>
    {/if}

    <!-- Armateur -->
    <div>{$tiers?.get(charter.armateur)?.nom_court || ""}</div>

    <!-- Affréteur -->
    <div>
      {$tiers?.get(charter.affreteur)?.nom_court || ""}
    </div>

    <!-- Courtier -->
    <div>
      {$tiers?.get(charter.courtier)?.nom_court || ""}
    </div>
  </div>

  <!-- Dates -->
  <div class="etx">
    <!-- Laycan début -->
    <div>
      <span>LC début :</span>
      <span class="ml-1">
        {charter.lc_debut
          ? new Date(charter.lc_debut).toLocaleDateString(locale)
          : ""}
      </span>
    </div>

    <!-- Laycan fin -->
    <div>
      <span>LC fin :</span>
      <span class="ml-1">
        {charter.lc_fin
          ? new Date(charter.lc_fin).toLocaleDateString(locale)
          : ""}
      </span>
    </div>

    <!-- C/P -->
    <div>
      <span>C/P :</span>
      <span class="ml-1">
        {charter.cp_date
          ? new Date(charter.cp_date).toLocaleDateString(locale)
          : ""}
      </span>
    </div>
  </div>

  <!-- Legs -->
  <div class="legs">
    {#each charter.legs as leg}
      <div class="leg">
        <div>
          <span class="marchandise">{leg.marchandise}</span>
          <span class="quantite">{leg.quantite}</span>
          <span class="ports"
            >{$ports?.find(({ locode }) => leg.pol === locode)?.nom_affichage ||
              ""} > {$ports?.find(({ locode }) => leg.pod === locode)
              ?.nom_affichage || ""}</span
          >
        </div>
        {#if leg.commentaire}
          <div class="commentaire">
            {@html leg.commentaire.replace(/\r\n|\r|\n/g, "<br/>")}
          </div>
        {/if}
      </div>
    {/each}
  </div>

  <!-- Copie / Modification / Suppression -->
  {#if $currentUser.canEdit("chartering")}
    <div class="no-mobile invisible ms-auto self-center group-hover:visible">
      <LucideButton
        preset="edit"
        on:click={$goto(`./${charter.id}${archives ? "?archives" : ""}`)}
      />
    </div>
  {/if}

  <!-- Commentaire -->
  {#if charter.commentaire}
    <div class="mt-3 lg:col-span-5 lg:ml-6">
      {@html charter.commentaire.replace(/\r\n|\r|\n/g, "<br/>")}
    </div>
  {/if}
</div>

<style>
  /** LEGS */
  .leg {
    margin-bottom: 10px;
  }

  .leg .marchandise {
    font-weight: bold;
  }

  .leg .quantite {
    margin-left: 10px;
  }

  .leg .ports {
    margin-left: 10px;
  }

  .leg .commentaire {
    margin: 5px 0 0 10px;
  }
</style>
