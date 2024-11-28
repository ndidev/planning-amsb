<script lang="ts">
  import { getContext } from "svelte";

  import type { Tiers, Stores } from "@app/types";

  const { pays } = getContext<Stores>("stores");

  export let thirdParty: Tiers;

  const address = thirdParty
    ? [
        thirdParty.nom_court,
        thirdParty.pays?.toLowerCase() === "fr"
          ? thirdParty.cp?.substring(0, 2)
          : "",
        thirdParty.ville,
        ["fr", "zz"].includes(thirdParty.pays?.toLowerCase())
          ? ""
          : `(${
              $pays?.find(({ iso }) => thirdParty.pays === iso)?.nom ||
              thirdParty.pays
            })`,
      ]
        .filter((champ) => champ)
        .join(" ")
    : "";
</script>

{address}
