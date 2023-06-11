<!-- 
  @component
  
  Bloc de configuration d'un compte utilisateur.

  Usage :
  ```tsx
  <Compte id: number|string />
  ```
 -->
<script lang="ts">
  import { onMount, onDestroy } from "svelte";

  import Notiflix from "notiflix";
  import autosize from "autosize";

  import { MaterialButton } from "@app/components";

  import { adminUsers, currentUser } from "@app/stores";

  import { sitemap, notiflixOptions, validerFormulaire } from "@app/utils";

  import { AccountStatus, UserRoles, TypesModules } from "@app/auth";

  import type { CompteUtilisateur } from "@app/types";

  export let id: CompteUtilisateur["uid"];

  let compte: CompteUtilisateur;
  let compteInitital: CompteUtilisateur;

  let inputLogin: HTMLInputElement;

  const unsubscribeAdminUsers = adminUsers.subscribe((users) => {
    compte = structuredClone(users.get(id));
    compteInitital = structuredClone(compte);
  });

  // Vérification que toutes les rubriques ont une valeur de rôle
  // Sinon, mettre à zéro
  for (const module of sitemap.keys()) {
    if (compte.roles[module] === undefined) {
      compte.roles[module] = UserRoles.NONE;
    }
  }

  /**
   * Ce composant.
   */
  let ligne: HTMLLIElement;

  /**
   * La ligne est un compte en cours de création.
   */
  let isNew: boolean = id?.startsWith("new");

  /**
   * État de modification du composant.
   */
  let modificationEnCours = isNew; // Modification en cours par défaut si nouveau compte uniquement;

  /**
   * État d'affichage des détails du compte.
   */
  let afficherDetails = isNew; // Afficher par défaut si nouveau compte uniquement

  /**
   * La ligne du compte est celle de l'utilisateur courant.
   */
  let self = compte.uid === $currentUser.uid;

  /**
   * @type {Explication}
   * @property {string} titre Titre de l'eplication
   */
  type Explication = {
    titre: string;
    texte: string;
    notiflix: {
      type: "success" | "info" | "warning" | "failure";
      options: {
        [type: string]: {
          backOverlayColor: string;
          svgColor: string;
          buttonBackground: string;
        };
      };
    };
  };

  /**
   * Explications statut
   */
  const explications: Record<AccountStatus, Explication> = {
    [AccountStatus.ACTIVE]: {
      titre: "Compte actif",
      texte: "Le compte est actif et peut être utilisé.",
      notiflix: {
        type: "success",
        options: {
          success: {
            backOverlayColor: "hsla(120, 60%, 50%, 0.2)",
            svgColor: "hsl(120, 60%, 50%)",
            buttonBackground: "hsl(120, 60%, 50%)",
          },
        },
      },
    },
    [AccountStatus.PENDING]: {
      titre: "Compte en attente d'activation",
      texte:
        "Le compte est en attente d'activation.<br/>" +
        "L'utilisateur doit initialiser son mot de passe lors d'une première connexion.",
      notiflix: {
        type: "info",
        options: {
          info: {
            backOverlayColor: "hsla(60, 60%, 50%, 0.2)",
            svgColor: "hsl(60, 60%, 50%)",
            buttonBackground: "hsl(60, 60%, 50%)",
          },
        },
      },
    },
    [AccountStatus.INACTIVE]: {
      titre: "Compte désactivé",
      texte:
        "Le compte a été désactivé par un administrateur.<br/>" +
        'Pour le réactiver, cliquer sur "Réinitialiser le compte".',
      notiflix: {
        type: "warning",
        options: {
          warning: {
            backOverlayColor: "hsla(0, 0%, 50%, 0.2)",
            svgColor: "hsl(0, 0%, 50%)",
            buttonBackground: "hsl(0, 0%, 50%)",
          },
        },
      },
    },
    [AccountStatus.LOCKED]: {
      titre: "Compte bloqué",
      texte:
        "Le compte a été automatiquement bloqué en raison d'un nombre \
           trop important de tentatives de connexions erronées \
           (maximum autorisé : " +
        import.meta.env.VITE_MAX_LOGIN_ATTEMPTS +
        ").<br/>" +
        'Pour le réactiver, cliquer sur "Réinitialiser le compte".',
      notiflix: {
        type: "failure",
        options: {
          failure: {
            backOverlayColor: "hsla(0, 60%, 50%, 0.2)",
            svgColor: "hsl(0, 60%, 50%)",
            buttonBackground: "hsl(0, 60%, 50%)",
          },
        },
      },
    },
    [AccountStatus.DELETED]: {
      titre: "Compte supprimé",
      texte:
        "Le compte a été supprimé.<br/>" +
        "Il est conservé pour l'historique mais ne peut pas être récupéré.",
      notiflix: {
        type: "failure",
        options: {
          failure: {
            backOverlayColor: "hsla(0, 60%, 50%, 0.2)",
            svgColor: "hsl(0, 60%, 50%)",
            buttonBackground: "hsl(0, 60%, 50%)",
          },
        },
      },
    },
  };

  /**
   * Afficher les explications sur le statut du compte.
   */
  function afficherExplications() {
    Notiflix.Report[explications[compte.statut].notiflix.type](
      explications[compte.statut].titre,
      explications[compte.statut].texte,
      "Fermer",
      explications[compte.statut].notiflix.options
    );
  }

  /**
   * Afficher l'historique du compte.
   */
  function afficherHistorique() {
    compte.historique = compte.historique.replaceAll("\n", "<br/>");

    Notiflix.Report.info(
      "Historique du compte",
      `Dernière connexion : ${compte.last_connection || "jamais"}` +
        `<p class="notiflix-historique-details">${compte.historique}</p>`,
      "Fermer",
      {
        className: "notiflix-historique",
        width: "380px",
        messageMaxLength: Infinity,
      }
    );
  }

  /**
   * Vérifier si le login n'est pas déjà utilisé.
   *
   * Si c'est le cas, mettre le champ invalide.
   */
  function verifierLogin() {
    inputLogin.value = inputLogin.value
      .toLowerCase()
      .replaceAll(/[^a-z0-9_-]/g, "");

    const newLogin = inputLogin.value;

    const loginList = [...$adminUsers.values()]
      .map((user) => user.login)
      .filter((login) => login !== compteInitital.login);

    if (loginList.includes(newLogin)) {
      inputLogin.setCustomValidity("L'identifiant existe déjà.");
    } else {
      inputLogin.setCustomValidity("");
    }
  }

  /**
   * Valider l'ajout.
   */
  async function validerAjout() {
    if (!validerFormulaire(ligne)) return;

    try {
      Notiflix.Block.dots([ligne], notiflixOptions.texts.ajout);
      ligne.style.minHeight = "initial";

      await adminUsers.create(compte);

      Notiflix.Notify.success("Le compte a été créé");
    } catch (erreur: any) {
      Notiflix.Notify.failure(erreur.message);
    } finally {
      Notiflix.Block.remove([ligne]);
    }
  }

  /**
   * Annuler l'ajout.
   */
  function annulerAjout() {
    adminUsers.cancel(compte.uid);
  }

  /**
   * Valider les modifications.
   */
  async function validerModification() {
    if (!validerFormulaire(ligne)) return;

    try {
      Notiflix.Block.dots([ligne], notiflixOptions.texts.modification);
      ligne.style.minHeight = "initial";

      await adminUsers.update(compte);

      Notiflix.Notify.success("Le compte a été modifié");
      compteInitital = structuredClone(compte);
      modificationEnCours = false;
    } catch (erreur: any) {
      Notiflix.Notify.failure(erreur.message);
    } finally {
      Notiflix.Block.remove([ligne]);
    }
  }

  /**
   * Annuler les modifications.
   */
  function annulerModification() {
    compte = structuredClone(compteInitital);
    modificationEnCours = false;
    ligne
      .querySelectorAll("input")
      .forEach((input) => input.setCustomValidity(""));
  }

  /**
   * Réinitialiser le compte.
   */
  function reinitialiserCompte() {
    // Demande de confirmation
    Notiflix.Confirm.show(
      "Réinitialisation du compte",
      "Voulez-vous vraiment réinitialiser le compte ?<br>" +
        "Ceci réactivera le compte (s'il est bloqué ou désactivé) et réinitialisera le mot de passe.",
      "Réinitialiser",
      "Annuler",
      async function () {
        try {
          Notiflix.Block.dots([ligne], "Réinitialisation en cours...");
          ligne.style.minHeight = "initial";

          await adminUsers.reset(compte.uid);

          Notiflix.Notify.success("Le compte a été réinitialisé");
        } catch (erreur: any) {
          Notiflix.Notify.failure(erreur.message);
        } finally {
          Notiflix.Block.remove([ligne]);
        }
      },
      null,
      notiflixOptions.themes.red
    );
  }

  /**
   * Désactiver le compte.
   */
  function desactiverCompte() {
    // Demande de confirmation
    Notiflix.Confirm.show(
      "Désactivation du compte",
      "Voulez-vous vraiment désactiver le compte ?<br/>" +
        "L'utilisateur ne pourra plus se connecter.<br/>" +
        "Ceci réinitialisera également le mot de passe.",
      "Désactiver",
      "Annuler",
      async function () {
        try {
          Notiflix.Block.dots([ligne], "Désactivation en cours...");
          ligne.style.minHeight = "initial";

          await adminUsers.deactivate(compte.uid);

          Notiflix.Notify.success("Le compte a été désactivé");
        } catch (erreur: any) {
          Notiflix.Notify.failure(erreur.message);
        } finally {
          Notiflix.Block.remove([ligne]);
        }
      },
      null,
      notiflixOptions.themes.red
    );
  }

  /**
   * Désactiver le compte.
   */
  function supprimerCompte() {
    // Demande de confirmation
    Notiflix.Confirm.show(
      "Suppression du compte",
      "Voulez-vous vraiment supprimer le compte ?<br/>" +
        "L'utilisateur ne pourra plus se connecter.<br/>" +
        "Le compte ne pourra pas être récupéré.<br/>" +
        "Pour une désactivation temporaire (ex: stagiaire), il est préférable de désactiver le compte pour pouvoir le réactiver plus tard.",
      "Supprimer",
      "Annuler",
      async function () {
        try {
          Notiflix.Block.dots([ligne], "Suppression en cours...");
          ligne.style.minHeight = "initial";

          await adminUsers.delete(compte.uid);

          Notiflix.Notify.success("Le compte a été supprimé");
        } catch (erreur: any) {
          Notiflix.Notify.failure(erreur.message);
          Notiflix.Block.remove([ligne]);
        }
      },
      null,
      notiflixOptions.themes.red
    );
  }

  onMount(() => {
    // Si changement d'une ligne, activation de la classe "modificationEnCours"
    ligne
      .querySelectorAll<HTMLInputElement | HTMLTextAreaElement>(
        "input, textarea"
      )
      .forEach((input) => {
        input.onchange = () => (modificationEnCours = true);
        input.oninput = () => (modificationEnCours = true);
      });

    // Autosize
    const inputCommentaire = ligne.querySelector("textarea");
    if (inputCommentaire) autosize(inputCommentaire);
  });

  onDestroy(() => {
    unsubscribeAdminUsers();
  });
</script>

<li class="compte" class:modificationEnCours bind:this={ligne}>
  <details bind:open={afficherDetails}>
    <!-- Nom + statut -->
    <summary class="nom-statut">
      {#if !isNew}
        <button
          class="nom"
          style:color={`var(--statut-${compte.statut})`}
          title="Cliquer pour afficher/masquer les détails"
          on:click={() => {
            afficherDetails = !afficherDetails;
          }}
        >
          {compteInitital.nom}
        </button>
        <MaterialButton
          icon="help"
          title="Statut du compte"
          on:click={afficherExplications}
        />
        <MaterialButton
          icon="history"
          title="Historique du compte"
          on:click={afficherHistorique}
        />
        {#if compteInitital.roles.admin}
          <span class="admin">Administrateur</span>
        {/if}
      {/if}
    </summary>

    <form class="pure-form">
      <div class="details">
        <div class="nom-login">
          <!-- Nom -->
          <div class="champ pure-control-group">
            <label for={"nom_" + compte.uid}>Nom</label>
            <input
              type="text"
              class="nom"
              id={"nom_" + compte.uid}
              name="nom"
              data-nom="Nom"
              bind:value={compte.nom}
              maxlength="255"
              required
            />
          </div>

          <!-- Login -->
          <div class="champ pure-control-group">
            <label for={"login_" + compte.uid}>Login</label>
            <input
              bind:this={inputLogin}
              type="text"
              class="login"
              id={"login_" + compte.uid}
              name="login"
              data-nom="Login"
              bind:value={compte.login}
              on:input={verifierLogin}
              maxlength="255"
              required
            />
          </div>
        </div>

        <!-- Commentaire -->
        <div class="champ commentaire">
          <label for={"commentaire_" + compte.uid}>Commentaire</label>
          <textarea
            id={"commentaire_" + compte.uid}
            name="commentaire"
            rows="3"
            bind:value={compte.commentaire}
          />
        </div>

        <!-- Boutons modification-->
        <div class="modif-suppr">
          <MaterialButton
            icon="done"
            title="Valider"
            on:click={isNew ? validerAjout : validerModification}
          />
          <MaterialButton
            icon="close"
            title="Annuler"
            on:click={isNew ? annulerAjout : annulerModification}
          />
        </div>

        <!-- Rôles -->
        <div class="roles">
          {#each [...sitemap] as [module, { affichage, type }]}
            <fieldset
              name={module}
              class="rubrique"
              disabled={/* Désactivé si admin : un administrateur ne peut pas se retirer lui-même le privilège */ module ===
                "admin" && self}
            >
              <legend>{affichage}</legend>

              <!-- Module de type "Accès" -->
              {#if type === TypesModules.ACCESS}
                <label class="pure-radio">
                  <input
                    type="radio"
                    bind:group={compte.roles[module]}
                    name={module}
                    value={UserRoles.NONE}
                  />
                  Pas accès
                </label>
                <label class="pure-radio">
                  <input
                    type="radio"
                    bind:group={compte.roles[module]}
                    name={module}
                    value={UserRoles.ACCESS}
                  />
                  Accès
                </label>
              {/if}

              <!-- Module de type "Modifier" -->
              {#if type === TypesModules.EDIT}
                <label class="pure-radio">
                  <input
                    type="radio"
                    bind:group={compte.roles[module]}
                    name={module}
                    value={UserRoles.NONE}
                  />
                  Aucun
                </label>
                <label class="pure-radio">
                  <input
                    type="radio"
                    bind:group={compte.roles[module]}
                    name={module}
                    value={UserRoles.ACCESS}
                  />
                  Voir
                </label>
                <label class="pure-radio">
                  <input
                    type="radio"
                    bind:group={compte.roles[module]}
                    name={module}
                    value={UserRoles.EDIT}
                  />
                  Modifier
                </label>
              {/if}
            </fieldset>
          {/each}
        </div>

        <!-- Boutons -->
        {#if !self && !isNew}
          <div class="boutons-action">
            <div class="action">
              <button
                on:click|preventDefault={reinitialiserCompte}
                disabled={compte.statut === AccountStatus.PENDING}
              >
                Réinitialiser le compte
              </button>
            </div>

            <div class="action">
              <button
                on:click|preventDefault={desactiverCompte}
                disabled={compte.statut === AccountStatus.INACTIVE}
              >
                Désactiver le compte
              </button>
            </div>

            <div class="action red">
              <button
                on:click|preventDefault={supprimerCompte}
                disabled={compte.statut === AccountStatus.DELETED}
              >
                Supprimer le compte
              </button>
            </div>
          </div>
        {/if}
      </div>
    </form>
  </details>
</li>

<style>
  * {
    --statut-active: hsl(120, 60%, 50%);
    --statut-pending: hsl(60, 60%, 50%);
    --statut-inactive: hsl(0, 0%, 50%);
    --statut-locked: hsl(0, 60%, 50%);
  }

  .compte {
    width: 80%;
    margin: 5px auto;
    padding: 10px;
    border-radius: 5px;
    border: 2px solid #ddd;
    background-color: #eee;
    list-style-type: none;
  }

  details > summary {
    list-style: none;
  }

  /* GRID STRUCTURE */

  .details {
    display: grid;
    gap: 10px;
  }

  .nom-login {
    grid-area: a;
  }

  .commentaire {
    grid-area: b;
  }

  .modif-suppr {
    grid-area: c;
  }

  .roles {
    grid-area: d;
  }

  .boutons-action {
    grid-area: e;
  }

  /* -------------- */

  .nom-statut > .nom {
    font-size: 1.2em;
    cursor: pointer;
    background-color: transparent;
    border: none;
  }

  .nom-statut > .admin {
    font-size: 0.8em;
    font-weight: bold;
    padding-left: 0.5em;
  }

  .champ {
    display: flex;
    flex-direction: column;
    margin-left: 1%;
  }

  fieldset:disabled {
    color: darkgray;
  }

  .compte textarea {
    padding: 5px;
  }

  .modificationEnCours {
    background-color: lightyellow;
  }

  .modificationEnCours .modif-suppr {
    visibility: visible;
  }

  .modif-suppr {
    justify-self: center;
    align-self: center;
    visibility: hidden;
  }

  .roles {
    display: grid;
  }

  .roles > .rubrique {
    margin-top: 10px;
  }

  .boutons-action {
    justify-self: center;
  }

  .action {
    display: inline-block;
    margin-block: 5px;
  }

  .action > button {
    padding: 5px;
    cursor: pointer;
  }

  .action > button:disabled {
    cursor: auto;
  }

  .action.red > button:not(:disabled) {
    color: var(--statut-locked);
  }

  /* Notiflix */
  :global(.notiflix-historique-details) {
    overflow: auto;
    max-height: 200px;
  }

  /* Mobile */
  @media screen and (max-width: 767px) {
    .details {
      grid-template-areas:
        "a"
        "b"
        "d"
        "e"
        "c";
    }

    .roles {
      grid-template-columns: repeat(2, 1fr);
    }

    .action,
    .action > button {
      width: 100%;
    }
  }

  /* Desktop */
  @media screen and (min-width: 768px) {
    .compte {
      min-width: 650px;
    }

    .details {
      grid-template-areas:
        "a b c"
        "d d d"
        "e e e";
    }

    .roles {
      grid-column: 1 / span 3;
      grid-template-columns: repeat(4, 1fr);
    }
  }
</style>
