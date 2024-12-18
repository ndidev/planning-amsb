<script lang="ts">
  import { pays } from "@app/stores";

  import type { Tiers } from "@app/types";

  export let thirdParty: Tiers;

  $: address = thirdParty
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
