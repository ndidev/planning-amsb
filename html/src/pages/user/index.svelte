<!-- routify:options title="Planning AMSB - Utilisateur" -->
<script lang="ts">
  import { onDestroy } from "svelte";

  import {
    Label,
    Input,
    Helper,
    Accordion,
    AccordionItem,
  } from "flowbite-svelte";
  import Notiflix from "notiflix";

  import { PageHeading, BoutonAction } from "@app/components";

  import { currentUser, authInfo } from "@app/stores";

  import { fetcher } from "@app/utils";
  import { User, type UserInfo } from "@app/auth";

  let submitButton: BoutonAction;

  let password: string = "";
  let passwordConfirm: string = "";
  let passwordIsValid: boolean = true;
  let passwordConfirmIsValid: boolean = true;
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
    passwordIsValid = true;
    passwordConfirmIsValid = true;

    /**
     * Mot de passe supérieur à la longueur minimum
     * Password === login = INTERDIT
     * Champs mdp et confirmation identiques
     */
    const conditions = {
      longueur: password.length >= $authInfo.LONGUEUR_MINI_PASSWORD,
      differentIdentifiant: password !== $currentUser.login,
      identiques: password === passwordConfirm,
    };

    if (password.length > 0 || passwordConfirm.length > 0) {
      passwordIsValid = false;
      afficherConditions(conditions);
    }

    if (password !== passwordConfirm) {
      passwordConfirmIsValid = false;
    }

    let conditionsValides = true;
    for (const condition in conditions) {
      if (conditions[condition] == false) {
        conditionsValides = false;
      }
    }

    if (password.length === 0 && passwordConfirm.length === 0) {
      conditionsValides = true;
    }

    if (submitButton) {
      submitButton.$set({ disabled: !conditionsValides });
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
      ? `✅ Le mot de passe fait ${$authInfo.LONGUEUR_MINI_PASSWORD} caractères ou plus`
      : `❌ Le mot de passe doit faire ${$authInfo.LONGUEUR_MINI_PASSWORD} caractères au minimum`;

    // Différent de l'identifiant
    passwordMessage = differentIdentifiant
      ? passwordMessage
      : `❌ Le mot de passe ne peut pas être identique à l'identifiant (${$currentUser.login})`;

    // Identiques
    passwordConfirmMessage = identiques
      ? "✅ Les mots de passe sont identiques"
      : "❌ Les mots de passe ne sont pas identiques";
  }

  /**
   * Modifier les informations utilisateur.
   */
  async function submitForm() {
    submitButton.$set({ block: true });

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
      submitButton.$set({ block: false });
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

<main class="w-7/12 mx-auto">
  <PageHeading>Utilisateur</PageHeading>

  <form>
    <!-- Nom -->
    <div class="mb-4">
      <Label for="nom">Nom</Label>
      <Input
        type="text"
        id="nom"
        name="nom"
        placeholder="Nom"
        bind:value={nom}
        maxlength={255}
        required
      />
    </div>

    <!-- Identifiant -->
    <div class="mb-4">
      <Label for="login"
        >Identifiant (demander à un administrateur pour le modifier)</Label
      >
      <Input
        type="text"
        id="login"
        name="login"
        autocomplete="off"
        value={$currentUser.login}
        disabled
      />
    </div>

    <!-- Mot de passe -->
    <Accordion class="mb-4">
      <AccordionItem>
        <span slot="header">Modifier le mot de passe</span>

        <div class="mb-4">
          <Label for="password">Mot de passe</Label>
          <Input
            type="password"
            id="password"
            name="password"
            placeholder="Mot de passe"
            autocomplete="new-password"
            bind:value={password}
            minlength={$authInfo.LONGUEUR_MINI_PASSWORD}
          />
          <Helper class="mt-2" color={passwordIsValid ? "green" : "red"}>
            {passwordMessage}
          </Helper>
        </div>

        <!-- Confirmation mot de passe -->
        <div class="mb-4">
          <Label for="passwordConfirm">Confirmation mot de passe</Label>
          <Input
            type="password"
            id="passwordConfirm"
            placeholder="Retaper le mot de passe"
            autocomplete="new-password"
            bind:value={passwordConfirm}
          />
          <Helper class="mt-2" color={passwordConfirmIsValid ? "green" : "red"}>
            {passwordConfirmMessage}
          </Helper>
        </div>
      </AccordionItem>
    </Accordion>

    <!-- Validation/Annulation -->
    <div class="text-center">
      <!-- Bouton "Valider" -->
      <BoutonAction
        preset="ajouter"
        type="submit"
        on:click={submitForm}
        bind:this={submitButton}
      >
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
