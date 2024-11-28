<!-- 
  @component
  
  Ligne de RDV du planning vrac.

  Usage :
  ```tsx
  <LigneRdv rdv: RdvVrac={rdv} />
  ```
 -->
<script lang="ts">
  import { PackageIcon } from "lucide-svelte";

  import { tiers, vracProduits } from "@app/stores";
  import type { RdvVrac, ProduitVrac, QualiteVrac, Tiers } from "@app/types";

  export let rdv: RdvVrac;

  const tiersVierge: Partial<Tiers> = {
    nom_court: "",
  };

  const produitVierge: Partial<ProduitVrac> = {
    nom: "",
    couleur: "#000000",
    qualites: [],
  };

  const qualiteVierge: Partial<QualiteVrac> = {
    nom: "",
    couleur: "#000000",
  };

  $: client = $tiers?.get(rdv.client) || { ...tiersVierge };

  $: transporteur = $tiers?.get(rdv.transporteur) || { ...tiersVierge };

  $: produit = $vracProduits?.get(rdv.produit) || { ...produitVierge };

  $: qualite = produit.qualites.find(
    (qualite) => qualite.id === rdv.qualite
  ) || { ...qualiteVierge };
</script>

<div
  class="grid grid-cols-[16%_4%_4%_8%_29%_8%_16%] gap-2 px-1 py-2 text-xl border-b-[1px] border-b-gray-400 last:border-b-0"
>
  <!-- Produit + Qualité -->
  <div class="font-bold">
    <span style:color={produit.couleur}>{produit.nom}</span>
    {#if rdv.qualite}
      <span style:color={qualite.couleur}>{qualite.nom}</span>
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

  <!-- Client -->
  <div>
    {client.nom_court}
    {client.ville}
  </div>

  <!-- Transporteur -->
  <div class="font-bold">
    {transporteur.nom_court}
  </div>

  <!-- Numéro de commande -->
  <div>{rdv.num_commande}</div>

  <!-- Espacement avant commentaire -->
  <div class="col-span-3"></div>

  <!-- Commentaire -->
  <div class="col-span-4">
    {@html rdv.commentaire.replace(/(?:\r\n|\r|\n)/g, "<br>")}
  </div>
</div>
