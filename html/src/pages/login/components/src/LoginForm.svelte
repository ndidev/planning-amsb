<!-- Identification -->
<script lang="ts">
  import { onMount, getContext } from "svelte";
  import type { Writable } from "svelte/store";

  import { Label, Input, Button } from "flowbite-svelte";

  import { appURLs } from "@app/utils";

  import { currentUser } from "@app/stores";

  import { AccountStatus, User, type UserInfo } from "@app/auth";

  const screen: Writable<string> = getContext("screen");
  const login: Writable<string> = getContext("login");

  let password: string = "";

  let loginMessage = "";

  let loginInput: HTMLInputElement;
  let passwordInput: HTMLInputElement;

  const loginButtonProperties = {
    text: "S'identifier",
    disabled: false,
  };

  /**
   * Procéder à l'identification de l'utilisateur.
   */
  async function logUserIn() {
    loginButtonProperties.text = "Connexion...";
    loginButtonProperties.disabled = true;
    loginMessage = "";

    const url = new URL(appURLs.auth);
    url.pathname += "login";

    const loginData = new FormData();
    loginData.append("login", $login);
    loginData.append("password", password);

    try {
      const response = await fetch(url, {
        method: "POST",
        body: loginData,
      });

      if (!response.ok) {
        throw new Error(await response.text());
      }

      const user: UserInfo = await response.json();
      const statut = user.statut;

      // Affichage du menu
      if (statut === AccountStatus.ACTIVE) {
        localStorage.setItem("user", JSON.stringify(user));
        currentUser.set(new User(user));

        screen.set("loginMenu");
      }

      // Initialisation du mot de passe
      if (statut === AccountStatus.PENDING) {
        screen.set("firstLogin");
      }
    } catch (erreur) {
      loginMessage = erreur.message;
    } finally {
      loginButtonProperties.text = "S'identifier";
      loginButtonProperties.disabled = false;
    }
  }

  onMount(() => {
    $login === "" ? loginInput.focus() : passwordInput.focus();

    // Écran de connexion = utilisateur non authentifié = suppression localStorage
    localStorage.clear();
  });
</script>

<div class="w-[75vw] max-w-96">
  <form on:submit|preventDefault={logUserIn}>
    <fieldset>
      <div class="mb-4">
        <Label for="login">Identifiant</Label>
        <Input let:props>
          <input
            type="text"
            name="login"
            id="login"
            bind:this={loginInput}
            bind:value={$login}
            autocomplete="username"
            placeholder="Identifiant"
            required
            {...props}
          />
        </Input>
      </div>

      <div class="mb-4">
        <Label for="password">Mot de passe</Label>
        <Input let:props>
          <input
            type="password"
            name="password"
            id="password"
            bind:this={passwordInput}
            bind:value={password}
            autocomplete="current-password"
            placeholder="Mot de passe"
            {...props}
          />
        </Input>
      </div>

      <div>
        <Button
          type="submit"
          class="w-full"
          disabled={loginButtonProperties.disabled}
          >{loginButtonProperties.text}</Button
        >
      </div>
    </fieldset>
  </form>

  <div class="mt-12 text-center text-error-600">{loginMessage}</div>
</div>
