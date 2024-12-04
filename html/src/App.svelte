<script lang="ts">
  import { setContext } from "svelte";
  import { Router } from "@roxi/routify";
  import { routes } from "../.routify/routes";

  import Notiflix from "notiflix";
  import Hammer from "hammerjs";

  import * as stores from "@app/stores";
  import { device } from "@app/utils";
  import type { Stores } from "@app/types";

  setContext<Stores>("stores", {
    currentUser: stores.currentUser,
    boisRdvs: stores.boisRdvs(),
    vracRdvs: stores.vracRdvs(),
    vracProduits: stores.vracProduits,
    consignationEscales: stores.consignationEscales(),
    charteringCharters: stores.charteringCharters(),
    stevedoringStaff: stores.stevedoringStaff,
    tiers: stores.tiers,
    configBandeauInfo: stores.configBandeauInfo,
    configPdf: stores.configPdf,
    configAjoutsRapides: stores.configAjoutsRapides,
    configAgence: stores.configAgence,
    configCotes: stores.configCotes,
    marees: stores.marees(),
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
    plainText: false,
    messageMaxLength: 250,
    timeout: 5000,
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
  /* ---------------------------------- */

  /**
   * Hammer
   *
   * Autoriser la sélection du texte (désactivée par défaut).
   */
  device.subscribe((type) => {
    if (type.is("desktop")) {
      delete Hammer.defaults.cssProps.userSelect;
    } else {
      Hammer.defaults.cssProps.userSelect = "none";
    }
  });
  /* ---------------------------------- */
</script>

<Router {routes} />
