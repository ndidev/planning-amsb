<!-- routify:options title="Planning AMSB - Connexion" -->
<script lang="ts">
  import { onMount, onDestroy, setContext } from "svelte";
  import { writable } from "svelte/store";

  import { currentUser } from "@app/stores";

  import { Chargement } from "@app/components";
  import { LoginForm, LoginMenu, FirstLogin } from "./components";
  import { appURLs } from "@app/utils";
  import { User } from "@app/auth";

  let login: string;

  /**
   * Détermine le composant affiché.
   */
  let screen: string;

  const screens = {
    loginForm: LoginForm,
    loginMenu: LoginMenu,
    firstLogin: FirstLogin,
  };

  const screenStore = writable("");
  const unsubscribeScreen = screenStore.subscribe((value) => {
    screen = value;
  });
  setContext("screen", screenStore);

  const loginStore = writable("");
  const unsubscribeLogin = loginStore.subscribe((value) => {
    login = value;
  });
  setContext("login", loginStore);

  /**
   * Vérifier si une session est active.
   */
  async function checkSession() {
    const url = new URL(appURLs.auth);
    url.pathname += "check";

    try {
      const reponse = await fetch(url, { credentials: "include" });

      switch (reponse.status) {
        case 200:
          // Session active : affichage du menu
          const user = await reponse.json();
          localStorage.setItem("user", JSON.stringify(user));
          currentUser.set(new User(user));
          screenStore.set("loginMenu");
          break;

        case 401:
          // Session invalide/pas de session : login form
          screenStore.set("loginForm");
          break;

        default:
          throw new Error(`${reponse.status} - ${reponse.statusText}`);
      }
    } catch (error) {
      console.error(error);
      screenStore.set("loginForm");
    }
  }

  onMount(async () => {
    await checkSession();
  });

  onDestroy(() => {
    unsubscribeScreen();
    unsubscribeLogin();
  });
</script>

{#if screen}
  <svelte:component this={screens[screen]} />
{:else}
  <Chargement />
{/if}
