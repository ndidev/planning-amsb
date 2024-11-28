<!--
  @component
    
  Première connexion (initialisation mot de passe).

  Usage :
  ```tsx
  <FirstLogin />
  ```
 -->
<script lang="ts">
  import { onMount, getContext } from "svelte";
  import type { Writable } from "svelte/store";

  import { Label, Input, Button } from "flowbite-svelte";
  import Notiflix from "notiflix";

  import { appURLs } from "@app/utils";
  import { authInfo } from "@app/stores";

  const screen: Writable<string> = getContext("screen");
  const login: Writable<string> = getContext("login");

  let password: string = "";
  let passwordConfirm: string = "";
  let conditionsMessages: string[];

  $: verifierPasswords(password, passwordConfirm);

  /**
   * Longueur minimum du mot de passe.
   */
  const { LONGUEUR_MINI_PASSWORD } = $authInfo;

  let passwordInput: HTMLInputElement;

  const validerButtonProperties = { disabled: false };

  /**
   * Vérifier que les deux champs password (mdp & confirmation) sont identiques et corrects.
   *
   * @param password        Mot de passe
   * @param passwordConfirm Confirmation du mot de passe
   */
  function verifierPasswords(password: string, passwordConfirm: string) {
    /**
     * Mot de passe supérieur à la longueur minimum
     * Password === login = INTERDIT
     * Champs mdp et confirmation identiques
     */
    const conditions = {
      longueur: password.length >= LONGUEUR_MINI_PASSWORD,
      differentIdentifiant: password !== $login,
      identiques: password === passwordConfirm,
    };

    afficherConditions(conditions);

    let conditionsValides = true;
    for (const condition in conditions) {
      if (conditions[condition] == false) {
        conditionsValides = false;
      }
    }

    validerButtonProperties.disabled = !conditionsValides;
  }

  /**
   * Affichage des conditions de mot de passe.
   */
  function afficherConditions(
    { longueur, differentIdentifiant, identiques } = {
      longueur: false,
      differentIdentifiant: true,
      identiques: true,
    }
  ) {
    // Remise à zéro des conditions
    conditionsMessages = [];

    // Longueur
    conditionsMessages.push(
      longueur
        ? `✅ Le mot de passe fait ${LONGUEUR_MINI_PASSWORD} caractères ou plus`
        : `❌ Le mot de passe doit faire ${LONGUEUR_MINI_PASSWORD} caractères au minimum`
    );

    // Différent de l'identifiant
    conditionsMessages.push(
      differentIdentifiant
        ? `✅ Le mot de passe est différent de l'identifiant (${$login})`
        : `❌ Le mot de passe ne peut pas être identique à l'identifiant (${$login})`
    );

    // Identiques
    conditionsMessages.push(
      identiques
        ? "✅ Les mots de passe sont identiques"
        : "❌ Les mots de passe ne sont pas identiques"
    );
  }

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
  });

  /**
   * Initialiser le mot de passe.
   */
  async function initialiserPassword(e: Event) {
    e.preventDefault();

    validerButtonProperties.disabled = true;

    const url = new URL(appURLs.auth);
    url.pathname += "first-login";

    const passwordData = new FormData();
    passwordData.append("login", $login);
    passwordData.append("password", password);

    try {
      const reponse = await fetch(url, {
        method: "POST",
        body: passwordData,
      });

      if (!reponse.ok) {
        throw new Error(`${reponse.status} - ${reponse.statusText}`);
      }

      Notiflix.Notify.success("Le mot de passe a été initialisé");

      screen.set("loginForm");
    } catch (erreur) {
      Notiflix.Notify.failure(erreur.message);
    } finally {
      validerButtonProperties.disabled = false;
    }
  }

  onMount(() => {
    passwordInput.focus();
  });
</script>

<div>
  <div class="mx-auto mb-8 text-center text-lg">
    Initialisation du mot de passe

    <div class="mt-5 text-base">
      Un mot de passe <strong>long</strong> vaut mieux qu'un mot de passe
      compliqué.<br />
      <br />
      Astuce n°1 : utilisez une phrase.<br />
      Astuce n°2 : utilisez un gestionnaire de mots de passe.
    </div>
  </div>

  <form>
    <fieldset>
      <!-- Mot de passe -->
      <div class="mb-4">
        <Label for="new-password">Mot de passe</Label>
        <Input let:props>
          <input
            type="password"
            id="new-password"
            name="password"
            bind:value={password}
            bind:this={passwordInput}
            autocomplete="new-password"
            placeholder="Mot de passe"
            minlength={LONGUEUR_MINI_PASSWORD}
            {...props}
          />
        </Input>
      </div>

      <!-- Confirmation mot de passe -->
      <div class="mb-4">
        <Label for="new-password-confirm">Confirmation mot de passe</Label>
        <Input
          type="password"
          id="new-password-confirm"
          bind:value={passwordConfirm}
          autocomplete="new-password"
          placeholder="Retaper le mot de passe"
        />
      </div>

      <div class="mb-4 text-center">
        <Button
          type="submit"
          on:click={initialiserPassword}
          disabled={validerButtonProperties.disabled}
        >
          Définir le mot de passe
        </Button>
      </div>
    </fieldset>
  </form>

  <div class="mx-auto">
    {#each conditionsMessages as message}
      <div>{message}</div>
    {/each}
  </div>
</div>
