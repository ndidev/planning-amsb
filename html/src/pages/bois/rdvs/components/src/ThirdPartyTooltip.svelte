<script lang="ts">
  import { getContext } from "svelte";

  import type { Tiers, Stores } from "@app/types";

  const { pays } = getContext<Stores>("stores");

  export let thirdParty: Tiers;
  export let role: "chargement" | "client" | "livraison";

  const tooltip = thirdParty
    ? [
        role.charAt(0).toUpperCase() + role.slice(1) + " :",
        thirdParty.nom_complet,
        thirdParty.adresse_ligne_1,
        thirdParty.adresse_ligne_2,
        [thirdParty.cp || "", thirdParty.ville || ""]
          .filter((champ) => champ)
          .join(" "),
        thirdParty.pays.toLowerCase() === "zz"
          ? ""
          : $pays?.find(({ iso }) => thirdParty.pays === iso)?.nom ||
            thirdParty.pays,
        thirdParty.telephone,
        thirdParty.commentaire ? " " : "",
        thirdParty.commentaire,
      ]
        .filter((champ) => champ)
        .join("\n")
    : "";
</script>

{tooltip}
