<!-- 
  @component
  
  Vérifie l'état de la session de l'utilisateur courant.

  Usage :
  ```tsx
  <SessionChecker />
  ```
 -->
<script lang="ts">
  import { onMount, onDestroy } from "svelte";

  import { appURLs } from "@app/utils";
  import { User } from "@app/auth";
  import { currentUser } from "@app/stores";

  /**
   * Vérifie l'état d'une session utilisateur
   * lorsque la fenêtre redevient visible.
   */
  function checkSessionOnVisible() {
    if (document.visibilityState === "visible") {
      checkSession();
    }
  }

  /**
   * Vérifier si une session est active.
   */
  async function checkSession() {
    const url = new URL(appURLs.auth);
    url.pathname += "check";

    try {
      const reponse = await fetch(url, { credentials: "include" });

      // Si erreur de session/compte, déconnexion
      if ([401, 403].includes(reponse.status)) {
        currentUser.set(new User());
      }
    } catch (error) {
      console.error(error);
    }
  }

  onMount(() => {
    checkSession();
    document.addEventListener("visibilitychange", checkSessionOnVisible);
  });

  onDestroy(() => {
    document.removeEventListener("visibilitychange", checkSessionOnVisible);
  });
</script>
