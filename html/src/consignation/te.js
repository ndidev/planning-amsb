import Chart from "chart.js/auto";

import env from "../lib/environment";

const url = new URL(env.api);
url.pathname += "consignation/te";

fetch(url)
  .then((response) => response.json())
  .then((te) => {
    // Mini/maxi sur l'axe des tonnages
    const tonnage_min = te.reduce((previous, current) => {
      return parseFloat(previous.tonnage) < parseFloat(current.tonnage)
        ? previous
        : current;
    }).tonnage;

    const tonnage_max = te.reduce((previous, current) => {
      return parseFloat(previous.tonnage) > parseFloat(current.tonnage)
        ? previous
        : current;
    }).tonnage;

    // Mini/maxi sur l'axe des TE
    const te_min = te.reduce((previous, current) => {
      return parseFloat(previous.te) < parseFloat(current.te)
        ? previous
        : current;
    }).te;

    const te_max = te.reduce((previous, current) => {
      return parseFloat(previous.te) > parseFloat(current.te)
        ? previous
        : current;
    }).te;

    // Chart
    const chart = new Chart(document.getElementById("chart"), {
      type: "scatter",
      data: {
        datasets: [
          {
            label: "Tirant d'eau",
            data: te,
          },
        ],
      },
      options: {
        parsing: {
          xAxisKey: "tonnage",
          yAxisKey: "te",
        },
        scales: {
          x: {
            min: Math.floor(parseFloat(tonnage_min) / 100) * 100,
            max: Math.ceil(parseFloat(tonnage_max) / 1000) * 1000,
          },
          y: {
            min: Math.floor(parseFloat(te_min)),
            max: Math.ceil(parseFloat(te_max)),
          },
        },
        plugins: {
          tooltip: {
            callbacks: {
              label: function (context) {
                let label = [];
                label.push(context.raw.navire);
                label.push(new Date(context.raw.date).toLocaleDateString());
                label.push(context.raw.tonnage + " T");
                label.push(context.raw.te + " m");

                return label;
              },
            },
          },
        },
      },
    });
  });
