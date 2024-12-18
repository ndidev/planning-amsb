<script lang="ts">
  import { Router } from "@roxi/routify";
  import { routes } from "../.routify/routes";

  import Notiflix from "notiflix";
  import Hammer from "hammerjs";

  import { device } from "@app/utils";

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
