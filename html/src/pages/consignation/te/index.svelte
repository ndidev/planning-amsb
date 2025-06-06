<!-- routify:options title="Planning AMSB - Tirants d'eau" -->
<script lang="ts">
  import { onMount, onDestroy } from "svelte";

  import {
    Chart,
    ScatterController,
    PointElement,
    LinearScale,
    Tooltip,
  } from "chart.js";

  Chart.register(ScatterController, PointElement, LinearScale, Tooltip);

  import { fetcher, locale } from "@app/utils";
  import { SseConnection } from "@app/components";
  import { consignationEscales } from "@app/stores";

  type TE = {
    navire: string;
    date: string;
    te: number;
    tonnage: number;
  };

  let canvas: HTMLCanvasElement;
  let chart: Chart<"scatter", TE[]>;

  /**
   * Récupérer les tirants d'eau.
   */
  async function fetchTE() {
    const data = await fetcher<TE[]>("consignation/te");

    if (chart) {
      chart.data.datasets[0].data = data;
      chart.update();
    }

    return data;
  }

  onMount(async () => {
    document.addEventListener(
      `planning:${consignationEscales.endpoint}`,
      fetchTE
    );

    // Chart
    chart = new Chart(canvas, {
      type: "scatter",
      data: {
        datasets: [
          {
            label: "Tirant d'eau",
            data: await fetchTE(),
          },
        ],
      },
      options: {
        parsing: {
          xAxisKey: "tonnage",
          yAxisKey: "te",
        },
        plugins: {
          tooltip: {
            callbacks: {
              label: function (context) {
                let label: string[] = [];
                label.push((context.raw as TE).navire);
                label.push(
                  new Date((context.raw as TE).date).toLocaleDateString(locale)
                );
                label.push((context.raw as TE).tonnage + " T");
                label.push((context.raw as TE).te + " m");
                return label;
              },
            },
          },
        },
      },
    });
  });

  onDestroy(() => {
    document.removeEventListener(
      `planning:${consignationEscales.endpoint}`,
      fetchTE
    );
  });
</script>

<!-- routify:options guard="consignation" -->

<SseConnection subscriptions={[consignationEscales.endpoint]} />

<main>
  <div id="canvas">
    <canvas bind:this={canvas} />
  </div>
</main>

<style>
  #canvas {
    position: relative;
    width: 80vw;
    height: 80vh;
    margin-left: 10vw;
    margin-top: 10vh;
  }
</style>
