<script lang="ts">
  import { setContext } from "svelte";
  import { Router } from "@roxi/routify";
  import { routes } from "../.routify/routes";

  import Notiflix from "notiflix";

  import * as stores from "@app/stores";
  import type { Stores } from "@app/types";

  setContext<Stores>("stores", {
    currentUser: stores.currentUser,
    boisRdvs: stores.boisRdvs(),
    vracRdvs: stores.vracRdvs,
    vracProduits: stores.vracProduits,
    consignationEscales: stores.consignationEscales(),
    charteringCharters: stores.charteringCharters(),
    tiers: stores.tiers,
    configBandeauInfo: stores.configBandeauInfo,
    configPdf: stores.configPdf,
    configAjoutsRapides: stores.configAjoutsRapides,
    configAgence: stores.configAgence,
    configCotes: stores.configCotes,
    marees: stores.marees,
    mareesAnnees: stores.mareesAnnees,
    ports: stores.ports,
    pays: stores.pays,
    adminUsers: stores.adminUsers,
  });

  /**
   * Notiflix
   */
  Notiflix.Notify.init({
    position: "right-bottom",
    cssAnimationStyle: "from-bottom",
    messageMaxLength: 250,
  });

  Notiflix.Confirm.init({
    plainText: false,
    messageMaxLength: 500,
  });

  Notiflix.Report.init({
    plainText: false,
    backOverlay: true,
    backOverlayClickToClose: true,
  });
  /* ---------------------------------------- */
</script>

<Router {routes} />
