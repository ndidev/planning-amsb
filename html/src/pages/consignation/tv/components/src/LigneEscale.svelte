<script lang="ts">
  // import { ArrowDownIcon, ArrowUpIcon } from "lucide-svelte";
  import ArrowDownIcon from "lucide-svelte/icons/arrow-down";
  import ArrowUpIcon from "lucide-svelte/icons/arrow-up";

  import { locale, luminance, NumberUtils } from "@app/utils";

  import { ports } from "@app/stores";

  import type { EscaleConsignation } from "@app/types";

  export let escale: EscaleConsignation;

  $: totalTonnage = escale.marchandises.reduce(
    (sum, { blTonnage }) => sum + blTonnage,
    0,
  );
  $: totalVolume = escale.marchandises.reduce(
    (sum, { blVolume }) => sum + blVolume,
    0,
  );
  $: totalUnits = escale.marchandises.reduce(
    (sum, { blUnits }) => sum + blUnits,
    0,
  );

  type Status = {
    name: "atsea" | "arrived" | "berthed" | "inops" | "completed" | "departed";
    background: string;
    text: string;
  };

  const statusMap = new Map<Status["name"], Status>([
    [
      "atsea",
      {
        name: "atsea",
        background: "hsla(0, 100%, 100%, 1)",
        text: "",
      },
    ],
    [
      "arrived",
      {
        name: "arrived",
        background: "hsla(60, 100%, 50%, 0.6)",
        text: "Sur rade",
      },
    ],
    [
      "berthed",
      {
        name: "berthed",
        background: "hsla(288, 100%, 39%, 0.8)",
        text: "À quai",
      },
    ],
    [
      "inops",
      {
        name: "inops",
        background: "hsla(182, 100%, 62%, 0.6)",
        text: "En opérations",
      },
    ],
    [
      "completed",
      {
        name: "completed",
        background: "hsla(120, 100%, 50%, 0.6)",
        text: "Terminé",
      },
    ],
    [
      "departed",
      {
        name: "departed",
        background: "hsla(221, 100%, 50%, 0.8)",
        text: "Parti",
      },
    ],
  ]);

  let callStatus: Status | null = null;
  let backgroundColor: Status["background"];
  let textColor: string;

  $: {
    callStatus = (escale, getCallStatus());

    backgroundColor = callStatus?.background || "white";

    textColor = luminance.getTextColor(backgroundColor);

    if (escale.call_port === "Tréguier" && textColor === "black") {
      textColor = "hsla(210, 100%, 40%, 0.9)";
    }
  }

  /**
   * Assignation classe à chaque escale en fonction de la date
   * afin de mettre en forme le planning
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
</script>

<div
  class="grid grid-cols-[20%_15%_7%_13%_40%_auto] gap-1 p-5 text-xl last:pb-12"
  style:background-color={backgroundColor}
  style:color={textColor}
>
  <!-- Navire / Armateur -->
  <div>
    <div class="text-3xl">{escale.navire}</div>

    {#if escale.voyage}
      <div class="mt-3">{`(escale n°${escale.voyage})`}</div>
    {/if}
  </div>

  <!-- Dates et heures -->
  <div class="text-xl uppercase">
    <!-- ETA -->
    {#if callStatus?.name === "atsea"}
      <span>
        {escale.eta_date
          ? new Date(escale.eta_date).toLocaleDateString(locale)
          : ""}
      </span>
      <span class="ml-2">{escale.eta_heure}</span>
    {:else}
      {callStatus?.text || ""}
    {/if}
  </div>

  <!-- TE -->
  <div>
    <div>
      A : <span class="te_arrivee">
        {escale.te_arrivee
          ? escale.te_arrivee.toLocaleString(undefined, {
              minimumFractionDigits: 2,
            }) + " m"
          : ""}
      </span>
    </div>

    <div>
      D : <span class="te_depart">
        {escale.te_depart
          ? escale.te_depart.toLocaleString(undefined, {
              minimumFractionDigits: 2,
            }) + " m"
          : ""}
      </span>
    </div>
  </div>

  <!-- Ports -->
  <div class="text-center">
    <div>
      {$ports?.find((port) => port.locode === escale.last_port)
        ?.nom_affichage || ""}
    </div>
    <div class="my-1 font-bold">
      {escale.quai}
    </div>
    <div>
      {$ports?.find((port) => port.locode === escale.next_port)
        ?.nom_affichage || "À ordres"}
    </div>
  </div>

  <!-- Marchandises -->
  <div>
    <table class="border-separate border-spacing-x-3 border-spacing-y-1">
      <tbody>
        {#each escale.marchandises as cargo}
          <tr>
            <td class="font-bold ml-3">
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
              </span>{cargo.cargoName}</td
            >
            <td>
              {#if cargo.blTonnage || cargo.blVolume || cargo.blUnits}
                {cargo.isApproximate ? "~" : ""}
              {/if}
            </td>

            <td class="text-right">
              {#if cargo.blTonnage}
                {NumberUtils.formatTonnage(cargo.blTonnage)}
              {/if}
            </td>

            <td class="text-right italic">
              {#if cargo.blVolume}
                {NumberUtils.formatVolume(cargo.blVolume)}
              {/if}
            </td>

            <td class="text-right">
              {#if cargo.blUnits}
                {NumberUtils.formatUnits(cargo.blUnits)}
              {/if}
            </td>
          </tr>
        {/each}
      </tbody>

      {#if escale.marchandises.length > 1 && (totalTonnage || totalVolume || totalUnits)}
        <tfoot>
          <tr>
            <td class="total mt-1">Total</td>
            <td></td>

            <td class="text-right">
              {#if totalTonnage}
                {NumberUtils.formatTonnage(totalTonnage)}
              {/if}
            </td>

            <td class="text-right italic">
              {#if totalVolume}
                {NumberUtils.formatVolume(totalVolume)}
              {/if}
            </td>

            <td class="text-right">
              {#if totalUnits}
                {NumberUtils.formatUnits(totalUnits)}
              {/if}
            </td>
          </tr>
        </tfoot>
      {/if}
    </table>
  </div>
</div>
