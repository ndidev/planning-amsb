<!-- routify:options title="Planning AMSB - Utilisateur" -->
<script lang="ts">
  import { onDestroy } from "svelte";

  import Notiflix from "notiflix";

  import { BoutonAction } from "@app/components";

  import { currentUser } from "@app/stores";

  import { fetcher } from "@app/utils";
  import { User, type UserInfo } from "@app/auth";

  let validerButton: BoutonAction;

  /**
   * Longueur minimum du mot de passe.
   */
  const LONGUEUR_MINI_PASSWORD: number = parseInt(
    import.meta.env.VITE_LONGUEUR_MINI_PASSWORD
  );

  let password: string = "";
  let passwordConfirm: string = "";
  let passwordMessage: string = "";
  let passwordConfirmMessage: string = "";

  let nom: string;

  const unsubscribeCurrentUser = currentUser.subscribe((user) => {
    nom = user.nom;
  });

  $: verifierPasswords(password, passwordConfirm);

  /**
   * Vérifier que les deux champs password (mdp & confirmation) sont identiques et corrects.
   *
   * @param password        Mot de passe
   * @param passwordConfirm Confirmation du mot de passe
   */
  function verifierPasswords(password: string, passwordConfirm: string) {
    // Réinitialisation des messages à chaque saisie
    passwordMessage = "";
    passwordConfirmMessage = "";

    /**
     * Mot de passe supérieur à la longueur minimum
     * Password === login = INTERDIT
     * Champs mdp et confirmation identiques
     */
    const conditions = {
      longueur: password.length >= LONGUEUR_MINI_PASSWORD,
      differentIdentifiant: password !== $currentUser.login,
      identiques: password === passwordConfirm,
    };

    if (password.length > 0 || passwordConfirm.length > 0) {
      afficherConditions(conditions);
    }

    let conditionsValides = true;
    for (const condition in conditions) {
      if (conditions[condition] == false) {
        conditionsValides = false;
      }
    }

    if (password.length === 0 && passwordConfirm.length === 0)
      conditionsValides = true;

    if (validerButton) {
      validerButton.$set({ disabled: !conditionsValides });
    }
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
    // Longueur
    passwordMessage = longueur
      ? `✔ Le mot de passe fait ${LONGUEUR_MINI_PASSWORD} caractères ou plus`
      : `❌ Le mot de passe doit faire ${LONGUEUR_MINI_PASSWORD} caractères au minimum`;

    // Différent de l'identifiant
    passwordMessage = differentIdentifiant
      ? passwordMessage
      : `🚫 Le mot de passe ne peut pas être identique à l'identifiant (${$currentUser.login})`;

    // Identiques
    passwordConfirmMessage = identiques
      ? "✔ Les mots de passe sont identiques"
      : "❌ Les mots de passe ne sont pas identiques";
  }

  /**
   * Modifier les informations utilisateur.
   */
  async function submitForm() {
    validerButton.$set({ block: true });

    try {
      const user: UserInfo = await fetcher("user", {
        requestInit: {
          method: "PUT",
          body: JSON.stringify({
            nom,
            password,
          }),
        },
      });

      Notiflix.Notify.success("Les informations ont été modifiées");

      currentUser.set(new User(user));
      localStorage.setItem("stores/user", JSON.stringify(user));

      password = passwordConfirm = "";
    } catch (error) {
      Notiflix.Notify.failure(error.message);
    } finally {
      validerButton.$set({ block: false });
    }
  }

  /**
   * Réinitialiser le formulaire.
   */
  function annulerModification(e: Event) {
    nom = $currentUser.nom;
    password = passwordConfirm = "";
  }

  onDestroy(unsubscribeCurrentUser);
</script>

<main class="formulaire">
  <h1>Utilisateur</h1>

  <form
    class="pure-form pure-form-aligned"
    on:submit|preventDefault={submitForm}
  >
    <!-- Nom -->
    <div class="pure-control-group">
      <label for="nom">Nom</label>
      <input
        type="text"
        id="nom"
        name="nom"
        placeholder="Nom"
        bind:value={nom}
        maxlength="255"
        required
      />
    </div>

    <!-- Identifiant -->
    <div class="pure-control-group">
      <label for="login">Identifiant</label>
      <input
        type="text"
        id="login"
        name="login"
        autocomplete="off"
        value={$currentUser.login}
        readonly
      />
    </div>

    <!-- Mot de passe -->
    <div class="pure-control-group">
      <label for="password">Mot de passe</label>
      <input
        type="password"
        id="password"
        name="password"
        placeholder="Mot de passe"
        autocomplete="new-password"
        bind:value={password}
        minlength={LONGUEUR_MINI_PASSWORD}
        maxlength="255"
      />
      <span class="pure-form-message-inline">
        {passwordMessage}
      </span>
    </div>

    <!-- Confirmation mot de passe -->
    <div class="pure-control-group">
      <label for="passwordConfirm">Confirmation mot de passe</label>
      <input
        type="password"
        id="passwordConfirm"
        placeholder="Retaper le mot de passe"
        autocomplete="new-password"
        bind:value={passwordConfirm}
        minlength={LONGUEUR_MINI_PASSWORD}
        maxlength="255"
      />
      <span class="pure-form-message-inline">
        {passwordConfirmMessage}
      </span>
    </div>

    <!-- Validation/Annulation/Suppression -->
    <div class="boutons">
      <!-- Bouton "Valider" si modidification -->
      <BoutonAction preset="ajouter" bind:this={validerButton} type="submit">
        Valider
      </BoutonAction>

      <!-- Bouton "Annuler" -->
      <BoutonAction
        preset="annuler"
        type="reset"
        on:click={annulerModification}
      />
    </div>
  </form>
</main>

<style>
</style>
