<!-- 
  @component
  
  Ligne du planning de consignation de navires.

  Usage :
  ```tsx
  <LigneEscale escale: EscaleConsignation />
  ```
 -->
<script lang="ts">
  import { onMount, onDestroy, getContext } from "svelte";
  import { goto } from "@roxi/routify";

  import Hammer from "hammerjs";

  import { LucideButton, Modal, BoutonAction } from "@app/components";

  import { device, locale, luminance } from "@app/utils";

  import type { EscaleConsignation, Stores } from "@app/types";

  const { currentUser, tiers, ports } = getContext<Stores>("stores");

  export let escale: EscaleConsignation;
  let line: HTMLDivElement;

  let mc: HammerManager;
  let showModal = false;

  const archives: boolean = getContext("archives");

  $: tonnageTotal = escale.marchandises.reduce(
    (sum, { tonnage_bl }) => sum + tonnage_bl,
    0
  );
  $: cubageTotal = escale.marchandises.reduce(
    (sum, { cubage_bl }) => sum + cubage_bl,
    0
  );
  $: nombreTotal = escale.marchandises.reduce(
    (sum, { nombre_bl }) => sum + nombre_bl,
    0
  );

  type ETX = {
    status: typeof callStatus;
    acronym: string;
    date: string;
    time: string;
    background: string;
  };

  $: etxList = [
    {
      status: "atsea",
      acronym: "ETA",
      date: escale.eta_date
        ? new Date(escale.eta_date).toLocaleDateString(locale)
        : "",
      time: escale.eta_heure,
      background: "hsla(0, 100%, 100%, 1)",
    },
    {
      status: "arrived",
      acronym: "ETB",
      date: escale.etb_date
        ? new Date(escale.etb_date).toLocaleDateString(locale)
        : "",
      time: escale.etb_heure,
      background: "hsla(60, 100%, 50%, 0.6)",
    },
    {
      status: "berthed",
      acronym: "Ops",
      date: escale.ops_date
        ? new Date(escale.ops_date).toLocaleDateString(locale)
        : "",
      time: escale.ops_heure,
      background: "hsla(288, 100%, 39%, 0.8)",
    },
    {
      status: "inops",
      acronym: "ETC",
      date: escale.etc_date
        ? new Date(escale.etc_date).toLocaleDateString(locale)
        : "",
      time: escale.etc_heure,
      background: "hsla(182, 100%, 62%, 0.6)",
    },
    {
      status: "completed",
      acronym: "ETD",
      date: escale.etd_date
        ? new Date(escale.etd_date).toLocaleDateString(locale)
        : "",
      time: escale.etd_heure,
      background: "hsla(120, 100%, 50%, 0.6)",
    },
    {
      status: "departed",
      acronym: "",
      date: "",
      time: "",
      background: "hsla(221, 100%, 50%, 0.8)",
    },
  ] satisfies ETX[];

  let callStatus: ReturnType<typeof getCallStatus> | null = null;
  let backgroundColor: string;
  let textColor: string;

  $: {
    callStatus = (escale, archives ? null : getCallStatus());

    backgroundColor =
      etxList.find((etx) => etx.status === callStatus)?.background || "white";

    textColor = luminance.getTextColor(backgroundColor);

    if (escale.call_port === "Tréguier" && textColor === "black") {
      textColor = "hsla(210, 100%, 40%, 0.9)";
    }
  }

  /**
   * Assignation classe à chaque escale en fonction de la date
   * afin de mettre en forme le planning.
   */
  function getCallStatus() {
    let status:
      | "atsea"
      | "arrived"
      | "berthed"
      | "inops"
      | "completed"
      | "departed" = "atsea";

    let eta = makeDateFromETX("eta");
    let etb = makeDateFromETX("etb");
    let ops = makeDateFromETX("ops");
    let etc = makeDateFromETX("etc");
    let etd = makeDateFromETX("etd");
    let now = new Date();

    if (eta && eta < now) {
      status = "arrived";
    }

    if (etb && etb < now) {
      status = "berthed";
    }

    if (ops && ops < now) {
      status = "inops";
    }

    if (etc && etc < now) {
      status = "completed";
    }

    if (etd && etd < now) {
      status = "departed";
    }

    return status;
  }

  /**
   * Crée un objet Date.
   *
   * @param etx Type de l'ET ('eta', 'etb', 'ops', 'etc', 'etd')
   *
   * @return Date au format 'YYYY-MM-DDTHH:MM'
   */
  function makeDateFromETX(etx: "eta" | "etb" | "ops" | "etc" | "etd") {
    if (!escale[`${etx}_date`]) return null;

    let date = escale[`${etx}_date`]; // Date mise au format 'YYYY-MM-DD'
    let time = escale[`${etx}_heure`];
    let timeRegex = /^((([01][0-9]|[2][0-3]):[0-5][0-9])|24:00)/;

    if (!timeRegex.test(time)) {
      time = "00:00"; // Si heure non renseignée
    } else {
      time = time.substring(0, 5);
    }

    return new Date(date + "T" + time);
  }

  onMount(() => {
    mc = new Hammer(line);
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

{#if showModal}
  <Modal on:outclick={() => (showModal = false)}>
    <div
      style:background="white"
      style:padding="20px"
      style:border-radius="20px"
    >
      <BoutonAction
        preset="modifier"
        on:click={$goto(`./${escale.id}${archives ? "?archives" : ""}`)}
      />
      <BoutonAction preset="annuler" on:click={() => (showModal = false)} />
    </div>
  </Modal>
{/if}

<div
  class="group grid gap-1 border-b-[1px] border-gray-300 px-6 py-2 text-sm first:mt-3 last:mb-12 last:border-none lg:grid-cols-[20%_15%_10%_10%_40%_auto] lg:gap-2 lg:px-10 lg:text-base"
  class:departed={callStatus === "departed"}
  style:background-color={backgroundColor}
  style:color={textColor}
  bind:this={line}
>
  <!-- Navire / Armateur -->
  <div class="flex flex-row items-baseline gap-2 lg:flex-col">
    <div class="text-lg lg:text-2xl">{escale.navire}</div>

    {#if escale.voyage}
      <div class="voyage">
        {`(escale n°${escale.voyage})`}
      </div>
    {/if}

    <div>{$tiers?.get(escale.armateur)?.nom_court || ""}</div>
  </div>

  <!-- Dates et heures -->
  <div class="group-[.departed]:hidden lg:group-[.departed]:block">
    {#each etxList as etx}
      {#if etx.status !== "departed"}
        <div
          style:font-weight={callStatus === etx.status ? "bold" : "normal"}
          style:font-size={callStatus === etx.status ? "1.1rem" : "1rem"}
        >
          <span class="nom">{etx.acronym} :</span>
          <span class="date">{etx.date}</span>
          <span class="heure">{etx.time}</span>
        </div>
      {/if}
    {/each}
  </div>

  <!-- TE -->
  <div class="flex flex-row gap-4 lg:flex-col lg:gap-0">
    <div>
      A :
      {escale.te_arrivee
        ? escale.te_arrivee.toLocaleString(undefined, {
            minimumFractionDigits: 2,
          }) + " m"
        : ""}
    </div>

    <div>
      D :
      {escale.te_depart
        ? escale.te_depart.toLocaleString(undefined, {
            minimumFractionDigits: 2,
          }) + " m"
        : ""}
    </div>
  </div>

  <!-- Ports -->
  <div class="flex flex-row lg:flex-col gap-4 lg:gap-1 lg:text-center">
    <div>
      {$ports?.find((port) => port.locode === escale.last_port)
        ?.nom_affichage || ""}
    </div>
    <div class="font-bold">
      <div class="inline lg:block">{escale.call_port}</div>
      <div class="inline lg:block">{escale.quai}</div>
    </div>
    <div>
      {$ports?.find((port) => port.locode === escale.next_port)
        ?.nom_affichage || "À ordres"}
    </div>
  </div>

  <!-- Marchandises -->
  <div>
    {#each escale.marchandises as marchandise}
      <div>
        <div>
          <span class="font-bold">{marchandise.marchandise}</span>
          <span class="ml-2">{marchandise.client}</span>
        </div>

        <div>
          <span>{marchandise.environ ? "~" : ""}</span>

          {#if marchandise.tonnage_bl}
            <span class="ml-3">
              {marchandise.tonnage_bl.toLocaleString("fr-FR", {
                minimumFractionDigits: 3,
              }) + " MT"}
            </span>
          {/if}

          {#if marchandise.cubage_bl}
            <span class="ml-3 italic">
              {marchandise.cubage_bl.toLocaleString("fr-FR", {
                minimumFractionDigits: 3,
              }) + " m3"}
            </span>
          {/if}

          {#if marchandise.nombre_bl}
            <span class="ml-3">
              {marchandise.nombre_bl.toLocaleString("fr-FR") + " colis"}
            </span>
          {/if}
        </div>
      </div>
    {/each}

    <!-- Total -->
    {#if escale.marchandises.length > 1 && (tonnageTotal || cubageTotal || nombreTotal)}
      <div class="mt-1">
        Total
        {#if tonnageTotal}
          <span class="ml-3">
            {tonnageTotal.toLocaleString("fr-FR", {
              minimumFractionDigits: 3,
            }) + " MT"}
          </span>
        {/if}

        {#if cubageTotal}
          <span class="ml-3 italic">
            {cubageTotal.toLocaleString("fr-FR", {
              minimumFractionDigits: 3,
            }) + " m3"}
          </span>
        {/if}

        {#if nombreTotal}
          <span class="ml-3">
            {nombreTotal.toLocaleString("fr-FR") + " colis"}
          </span>
        {/if}
      </div>
    {/if}
  </div>

  <!-- Copie / Modification / Suppression -->
  {#if $currentUser.canEdit("consignation")}
    <div class="no-mobile invisible ms-auto self-center group-hover:visible">
      <LucideButton
        preset="edit"
        on:click={$goto(`./${escale.id}${archives ? "?archives" : ""}`)}
      />
    </div>
  {/if}

  <!-- Commentaire -->
  {#if escale.commentaire}
    <div class="mt-3 lg:col-span-5 lg:ml-6">
      {@html escale.commentaire.replace(/\r\n|\r|\n/g, "<br/>")}
    </div>
  {/if}
</div>
