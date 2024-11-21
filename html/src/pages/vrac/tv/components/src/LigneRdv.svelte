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

<div class="rdv pure-g">
  <div class="produit-qualite pure-u-lg-4-24 pure-u-12-24">
    <span class="produit" style:color={produit.couleur}>{produit.nom}</span>
    {#if rdv.qualite}
      <span class="qualite" style:color={qualite.couleur}>{qualite.nom}</span>
    {/if}
  </div>

  <div class="heure pure-u-lg-1-24 pure-u-4-24">{rdv.heure ?? ""}</div>

  <div class="commande_prete pure-u-1 pure-u-lg-1-24" style:text-align="right">
    {#if rdv.commande_prete}
      <PackageIcon />
    {/if}
  </div>

  <div
    class="quantite-unite pure-u-lg-2-24 pure-u-6-24"
    style:color={rdv.max ? "red" : "initial"}
  >
    <span class="quantite">{rdv.quantite}</span>
    <span class="unite">{produit.unite}</span>
    <span class="max">{rdv.max ? "max" : ""}</span>
  </div>

  <div class="client pure-u-lg-7-24 pure-u-1">
    {client.nom_court}
    {client.ville}
  </div>
  <div class="transporteur pure-u-lg-3-24 pure-u-1">
    {transporteur.nom_court}
  </div>

  <div class="num_commande pure-u-lg-3-24 pure-u-12-24">{rdv.num_commande}</div>

  <div class="pure-u-lg-6-24">
    <!-- Espacement -->
  </div>
  <div class="commentaire pure-u-lg-17-24 pure-u-1">
    {@html rdv.commentaire.replace(/(?:\r\n|\r|\n)/g, "<br>")}
  </div>
</div>

<style>
  .rdv {
    font-size: 1.3em;
    padding: 8px 0 8px 5px;
    border-bottom: 1px solid #999;
  }

  .rdv:last-child {
    border-bottom: none;
  }

  .heure {
    font-weight: bold;
    color: #d91ffa;
  }

  .produit-qualite {
    font-weight: bold;
  }

  .quantite {
    font-weight: bold;
  }

  .transporteur {
    font-weight: bold;
  }

  .quantite-unite,
  .client,
  .transporteur,
  .num_commande,
  .commentaire {
    margin-left: 2%;
  }
</style>
