<!-- 
  @component
  
  Ligne de RDV du planning vrac.

  Usage :
  ```tsx
  <LigneRdv rdv: RdvVrac={rdv} />
  ```
 -->
<script lang="ts">
  // import { PackageIcon } from "lucide-svelte";
  import PackageIcon from "lucide-svelte/icons/package";

  import { tiers, vracProduits } from "@app/stores";
  import type { RdvVrac } from "@app/types";

  export let rdv: RdvVrac;

  $: client = $tiers?.get(rdv.client);

  $: transporteur = $tiers?.get(rdv.transporteur);

  $: produit = $vracProduits?.get(rdv.produit);

  $: qualite = produit?.qualites.find((qualite) => qualite.id === rdv.qualite);
</script>

<div class="grid grid-cols-[16%_4%_4%_8%_9%_14%_auto] gap-2 px-1 py-2 text-xl">
  <!-- Produit + Qualité -->
  <div class="font-bold">
    <span style:color={produit?.couleur || "#000000"}>{produit?.nom || ""}</span
    >
    {#if rdv.qualite}
      <span style:color={qualite?.couleur || "#000000"}
        >{qualite?.nom || ""}</span
      >
    {/if}
  </div>

  <!-- Heure -->
  <div class="font-bold text-[#d91ffa]">
    {rdv.heure ?? ""}
  </div>

  <!-- Commande prête -->
  <div class="text-center">
    {#if rdv.commande_prete}
      <PackageIcon />
    {/if}
  </div>

  <!-- Quantité + unité + max -->
  <div style:color={rdv.max ? "red" : "initial"}>
    <span class="font-bold">{rdv.quantite}</span>
    <span class="unite">{produit.unite}</span>
    <span class="max">{rdv.max ? "max" : ""}</span>
  </div>

  <!-- Transporteur -->
  <div class="font-bold">
    {transporteur?.nom_court || ""}
  </div>

  <!-- Numéro de commande -->
  <div>{rdv.num_commande}</div>

  <!-- Commentaire -->
  <div>
    {@html rdv.commentaire_public.replace(/\r\n|\r|\n/g, "<br/>")}
  </div>
</div>
