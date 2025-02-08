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

  import { Modal } from "flowbite-svelte";
  import { ArrowDownIcon, ArrowUpIcon } from "lucide-svelte";
  import Hammer from "hammerjs";

  import { Badge, LucideButton, BoutonAction } from "@app/components";

  import { device, locale, NumberUtils } from "@app/utils";

  import { currentUser, tiers, ports } from "@app/stores";

  import type { EscaleConsignation } from "@app/types";

  export let escale: EscaleConsignation;
  let line: HTMLDivElement;

  let mc: HammerManager;
  let showModal = false;

  const archives: boolean = getContext("archives");

  $: totalTonnage = escale.marchandises.reduce(
    (sum, { blTonnage }) => sum + blTonnage,
    0
  );
  $: totalVolume = escale.marchandises.reduce(
    (sum, { blVolume }) => sum + blVolume,
    0
  );
  $: totalUnits = escale.marchandises.reduce(
    (sum, { blUnits }) => sum + blUnits,
    0
  );

  type Status = {
    name: "atsea" | "arrived" | "berthed" | "inops" | "completed" | "departed";
    text: string;
    background: string;
    nextEtxAcronym: string;
    nextEtxDate: string;
    nextEtxTime: string;
  };

  $: statusMap = new Map<Status["name"], Status>([
    [
      "atsea",
      {
        name: "atsea",
        text: "Prévu",
        background: "hsla(0, 0%, 95%, 1)",
        nextEtxAcronym: "ETA",
        nextEtxDate: escale.eta_date
          ? new Date(escale.eta_date).toLocaleDateString(locale)
          : "",
        nextEtxTime: escale.eta_heure,
      },
    ],
    [
      "arrived",
      {
        name: "arrived",
        text: "Sur rade",
        background: "hsla(60, 100%, 47%, 0.8)",
        nextEtxAcronym: "ETB",
        nextEtxDate: escale.etb_date
          ? new Date(escale.etb_date).toLocaleDateString(locale)
          : "",
        nextEtxTime: escale.etb_heure,
      },
    ],
    [
      "berthed",
      {
        name: "berthed",
        text: "À quai",
        background: "hsla(288, 100%, 39%, 0.8)",
        nextEtxAcronym: "Ops",
        nextEtxDate: escale.ops_date
          ? new Date(escale.ops_date).toLocaleDateString(locale)
          : "",
        nextEtxTime: escale.ops_heure,
      },
    ],
    [
      "inops",
      {
        name: "inops",
        text: "En opérations",
        background: "hsla(182, 100%, 62%, 0.6)",
        nextEtxAcronym: "ETC",
        nextEtxDate: escale.etc_date
          ? new Date(escale.etc_date).toLocaleDateString(locale)
          : "",
        nextEtxTime: escale.etc_heure,
      },
    ],
    [
      "completed",
      {
        name: "completed",
        text: "Terminé",
        background: "hsla(120, 100%, 50%, 0.6)",
        nextEtxAcronym: "ETD",
        nextEtxDate: escale.etd_date
          ? new Date(escale.etd_date).toLocaleDateString(locale)
          : "",
        nextEtxTime: escale.etd_heure,
      },
    ],
    [
      "departed",
      {
        name: "departed",
        text: "Parti",
        background: "hsla(221, 100%, 50%, 0.8)",
        nextEtxAcronym: "",
        nextEtxDate: "",
        nextEtxTime: "",
      },
    ],
  ]);

  let callStatus: Status | null = null;

  $: callStatus = (escale, archives ? null : getCallStatus());

  /**
   * Assignation classe à chaque escale en fonction de la date
   * afin de mettre en forme le planning.
   */
  function getCallStatus() {
    let eta = makeDateFromETX("eta");
    let etb = makeDateFromETX("etb");
    let ops = makeDateFromETX("ops");
    let etc = makeDateFromETX("etc");
    let etd = makeDateFromETX("etd");
    let now = new Date();

    let status: Status = statusMap.get("atsea");

    if (eta && eta < now) {
      status = statusMap.get("arrived");
    }

    if (etb && etb < now) {
      status = statusMap.get("berthed");
    }

    if (ops && ops < now) {
      status = statusMap.get("inops");
    }

    if (etc && etc < now) {
      status = statusMap.get("completed");
    }

    if (etd && etd < now) {
      status = statusMap.get("departed");
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
      time = "23:59:59"; // Si heure non renseignée
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

<Modal bind:open={showModal} outsideclose dismissable={false}>
  <BoutonAction
    preset="modifier"
    on:click={$goto(`./${escale.id}${archives ? "?archives" : ""}`)}
  />
  <BoutonAction preset="annuler" on:click={() => (showModal = false)} />
</Modal>

<div
  class="group grid gap-1 px-3 py-2 text-sm first:pt-5 lg:grid-cols-[20%_15%_10%_10%_40%_auto] lg:gap-2 lg:px-10 lg:text-base border-l-[10px] border-l-[color:var(--status-color)]"
  class:departed={callStatus?.name === "departed"}
  style:background-color={/* backgroundColor */ "white"}
  style:--status-color={callStatus?.background || "transparent"}
  style:color={escale.call_port === "Tréguier"
    ? "hsla(210, 100%, 40%, 0.9)"
    : "black"}
  bind:this={line}
>
  <!-- Navire / Armateur -->
  <div class="flex flex-row items-baseline gap-2 lg:flex-col">
    <div class="mb-2">
      <!-- Navire -->
      <div class="text-lg lg:text-2xl">{escale.navire}</div>

      <!-- Statut -->
      {#if callStatus}
        <Badge color={callStatus.background} size="sm">{callStatus.text}</Badge>
      {/if}
    </div>

    <!-- Numéro de voyage -->
    {#if escale.voyage}
      <div class="voyage">
        {`(escale n°${escale.voyage})`}
      </div>
    {/if}

    <div>{$tiers?.get(escale.armateur)?.nom_court || ""}</div>
  </div>

  <!-- Dates et heures -->
  <div class="group-[.departed]:hidden lg:group-[.departed]:block">
    {#each [...statusMap.values()] as status}
      {#if status.name !== "departed"}
        <div
          style:font-weight={callStatus?.name === status.name
            ? "bold"
            : "normal"}
          style:font-size={callStatus?.name === status.name ? "1.1em" : "1em"}
        >
          <span class="nom">{status.nextEtxAcronym} :</span>
          <span class="date">{status.nextEtxDate}</span>
          <span class="heure">{status.nextEtxTime}</span>
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
    {#each escale.marchandises as cargo}
      <div>
        <div>
          <span
            title={cargo.operation.charAt(0).toLocaleUpperCase() +
              cargo.operation.slice(1)}
            class="*:align-text-top"
          >
            {#if cargo.operation === "import"}
              <ArrowDownIcon size="1em" />
            {:else if cargo.operation === "export"}
              <ArrowUpIcon size="1em" />
            {/if}
          </span>
          <span class="font-bold">{cargo.cargoName}</span>
          <span class="ml-2">{cargo.customer}</span>
        </div>

        <div>
          {#if cargo.blTonnage || cargo.blVolume || cargo.blUnits}
            <span>{cargo.isApproximate ? "~" : ""}</span>
          {/if}

          {#if cargo.blTonnage}
            <span class="ml-3">
              {NumberUtils.formatTonnage(cargo.blTonnage)}
            </span>
          {/if}

          {#if cargo.blVolume}
            <span class="ml-3 italic">
              {NumberUtils.formatVolume(cargo.blVolume)}
            </span>
          {/if}

          {#if cargo.blUnits}
            <span class="ml-3">
              {NumberUtils.formatUnits(cargo.blUnits)}
            </span>
          {/if}
        </div>
      </div>
    {/each}

    <!-- Total -->
    {#if escale.marchandises.length > 1 && (totalTonnage || totalVolume || totalUnits)}
      <div class="mt-1">
        Total
        {#if totalTonnage}
          <span class="ml-3">
            {NumberUtils.formatTonnage(totalTonnage)}
          </span>
        {/if}

        {#if totalVolume}
          <span class="ml-3 italic">
            {NumberUtils.formatVolume(totalVolume)}
          </span>
        {/if}

        {#if totalUnits}
          <span class="ml-3">
            {NumberUtils.formatUnits(totalUnits)}
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
