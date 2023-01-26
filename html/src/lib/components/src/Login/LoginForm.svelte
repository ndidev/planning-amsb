<!-- Identification -->
<script lang="ts">
  import { onMount, getContext } from "svelte";
  import type { Writable } from "svelte/store";

  import { env } from "@app/utils";

  import { AccountStatus } from "@app/auth";

  const screen: Writable<string> = getContext("screen");
  const login: Writable<string> = getContext("login");

  let password: string = "";

  let loginMessage = "";

  let loginInput: HTMLInputElement;
  let passwordInput: HTMLInputElement;
  let loginButton: HTMLButtonElement;

  /**
   * Procéder à l'identification de l'utilisateur.
   */
  async function logUserIn(event: Event) {
    event.preventDefault();

    loginButton.textContent = "Connexion...";
    loginButton.setAttribute("disabled", "true");

    const url = new URL(env.auth);
    url.pathname += "login";

    const loginData = new FormData();
    loginData.append("login", $login);
    loginData.append("password", password);

    try {
      const reponse = await fetch(url, {
        method: "POST",
        body: loginData,
      });

      const resultat = await reponse.json();

      if (reponse.status === 200) {
        const { login, nom, roles, statut } = resultat;

        // Affichage du menu
        if (statut === AccountStatus.ACTIVE) {
          localStorage.setItem(
            "stores/user",
            JSON.stringify({ login, nom, roles, statut })
          );

          $screen = "loginMenu";
        }

        // Initialisation du mot de passe
        if (statut === AccountStatus.PENDING) {
          $screen = "firstLogin";
        }
      } else {
        throw new Error(resultat.message);
      }
    } catch (erreur) {
      loginMessage = erreur.message;
    } finally {
      loginButton.textContent = "S'identifier";
      loginButton.removeAttribute("disabled");
    }
  }

  onMount(() => {
    $login === "" ? loginInput.focus() : passwordInput.focus();
  });
</script>

<div class="login-conteneur">
  <form
    class="pure-form pure-form-aligned"
    id="login-form"
    on:submit={logUserIn}
  >
    <fieldset>
      <div class="pure-control-group">
        <label for="login">Identifiant</label>
        <input
          type="text"
          name="login"
          id="login"
          bind:this={loginInput}
          bind:value={$login}
          autocomplete="username"
          placeholder="Identifiant"
        />
      </div>

      <div class="pure-control-group">
        <label for="password">Mot de passe</label>
        <input
          type="password"
          name="password"
          id="password"
          bind:this={passwordInput}
          bind:value={password}
          autocomplete="current-password"
          placeholder="Mot de passe"
        />
      </div>

      <div class="pure-controls">
        <button
          class="pure-button pure-button-primary"
          type="submit"
          bind:this={loginButton}
          on:click={logUserIn}
        >
          S'identifier
        </button>
      </div>
    </fieldset>
  </form>

  <div id="login-message" class="login-message">{loginMessage}</div>
</div>

<style>
  .login-message {
    text-align: center;
  }
</style>
