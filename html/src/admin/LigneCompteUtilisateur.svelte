<!-- 
  @component
  
  Bloc de configuration d'un compte utilisateur.

  Usage :
  ```tsx
  <Compte compte: CompteUtilisateur={compte} />
  ```
 -->
<script lang="ts">
  import { onMount, onDestroy } from "svelte";

  import { MaterialButton } from "@app/components";

  import { comptesUtilisateurs } from "@app/stores";

  import Notiflix from "notiflix";
  import autosize from "autosize";

  import { env, sitemap, TypesModules } from "@app/utils";
  import { AccountStatus, UserRoles } from "@app/auth";

  export let compte: CompteUtilisateur;

  // Vérification que toutes les rubriques ont une valeur de rôle
  // Sinon, mettre à zéro
  // FIXME: n'est pas reflété sur la page.... ??
  for (const module of sitemap.keys()) {
    if (compte.roles[module] === undefined) {
      compte.roles[module] = UserRoles.NONE;
    }
  }

  let compteInitital: CompteUtilisateur = structuredClone(compte);

  let isNew: boolean = compte.uid!.startsWith("new");

  /**
   * Ce composant.
   */
  let ligneCompte: HTMLLIElement;

  /**
   * État de modification du composant.
   */
  let modificationEnCours: boolean = isNew; // Modification en cours par défaut si nouveau compte uniquement;

  /**
   * État d'affichage des détails du compte.
   */
  let afficherDetails: boolean = isNew; // Afficher par défaut si nouveau compte uniquement

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
  const explications: Record<string, Explication> = {
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
        process.env.AUTH_MAX_LOGIN_ATTEMPTS +
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
   * Validation formulaire.
   *
   * @param {HTMLElement} context Contexte
   *
   * @returns {boolean} `true` si formulaire valide, `false` sinon
   */
  function validerFormulaire(context: HTMLElement): boolean {
    const inputs = context.querySelectorAll<
      HTMLInputElement | HTMLSelectElement
    >("input, select");

    const champsInvalides = [];

    let valide = true;

    for (const input of inputs) {
      if (!input.checkValidity()) {
        valide = false;
        if (input.dataset.nom) {
          champsInvalides.push(input.dataset.nom);
        }
      }
    }

    if (!valide) {
      Notiflix.Notify.failure(
        "Certains champs sont invalides : " + champsInvalides.join(", ")
      );
    }

    return valide;
  }

  /**
   * Valider l'ajout.
   */
  async function validerAjout() {
    if (!validerFormulaire(ligneCompte)) return;

    const url = new URL(env.api);
    url.pathname += `admin/users`;

    try {
      Notiflix.Block.dots(`#${ligneCompte.id}`, "Ajout en cours...");

      const tempUid = compte.uid;

      const reponse = await fetch(url, {
        method: "POST",
        body: JSON.stringify(compte),
      });

      if (!reponse.ok) {
        throw new Error(`${reponse.status}, ${reponse.statusText}`);
      }

      compte = await reponse.json();

      // Mise à jour du store
      comptesUtilisateurs.update((comptes) => {
        comptes.push(compte);
        return comptes
          .filter((_compte: CompteUtilisateur) => _compte.uid !== tempUid)
          .sort((a: CompteUtilisateur, b: CompteUtilisateur) =>
            a.login < b.login ? -1 : 1
          );
      });

      Notiflix.Notify.success("Le compte a été créé");
    } catch (erreur: any) {
      Notiflix.Notify.failure(erreur.message);
    } finally {
      Notiflix.Block.remove(`#${ligneCompte.id}`);
    }
  }

  /**
   * Annuler l'ajout.
   */
  function annulerAjout() {
    comptesUtilisateurs.update((comptes) => {
      return comptes.filter(
        (_compte: CompteUtilisateur) => _compte.uid !== compte.uid
      );
    });
  }

  /**
   * Valider les modifications.
   */
  async function validerModification() {
    if (!validerFormulaire(ligneCompte)) return;

    const url = new URL(env.api);
    url.pathname += `admin/users/${compte.uid}`;

    try {
      Notiflix.Block.dots(`#${ligneCompte.id}`, "Modification en cours...");

      const reponse = await fetch(url, {
        method: "PUT",
        body: JSON.stringify(compte),
      });

      if (!reponse.ok) {
        throw new Error(`${reponse.status}, ${reponse.statusText}`);
      }

      compte = await reponse.json();
      compteInitital = structuredClone(compte);

      Notiflix.Notify.success("Le compte a été modifié");
      modificationEnCours = false;
    } catch (erreur: any) {
      Notiflix.Notify.failure(erreur.message);
    } finally {
      Notiflix.Block.remove(`#${ligneCompte.id}`);
    }
  }

  /**
   * Annuler les modifications.
   */
  function annulerModification() {
    compte = structuredClone(compteInitital);
    modificationEnCours = false;
    ligneCompte
      .querySelectorAll("input")
      .forEach((input) => input.setCustomValidity(""));
  }

  /**
   * Réinitialiser le compte.
   */
  function reinitialiserCompte(evt: Event) {
    evt.preventDefault();

    // Demande de confirmation
    Notiflix.Confirm.show(
      "Réinitialisation du compte",
      "Voulez-vous vraiment réinitialiser le compte ?<br>" +
        "Ceci réactivera le compte (s'il est bloqué ou désactivé) et réinitialisera le mot de passe.",
      "Réinitialiser",
      "Annuler",
      async function () {
        const url = new URL(env.api);
        url.pathname += `admin/users/${compte.uid}/reset`;

        try {
          Notiflix.Block.dots(
            `#${ligneCompte.id}`,
            "Réinitialisation en cours..."
          );

          const reponse = await fetch(url, {
            method: "PUT",
          });

          if (!reponse.ok) {
            throw new Error(`${reponse.status}, ${reponse.statusText}`);
          }

          compte.statut = AccountStatus.PENDING;

          Notiflix.Notify.success("Le compte a été réinitialisé");
        } catch (erreur: any) {
          Notiflix.Notify.failure(erreur.message);
        } finally {
          Notiflix.Block.remove(`#${ligneCompte.id}`);
        }
      },
      undefined,
      {
        titleColor: "#ff5549",
        okButtonColor: "#f8f8f8",
        okButtonBackground: "#ff5549",
        cancelButtonColor: "#f8f8f8",
        cancelButtonBackground: "#a9a9a9",
      }
    );
  }

  /**
   * Désactiver le compte.
   */
  function desactiverCompte(evt: Event): void {
    evt.preventDefault();

    // Demande de confirmation
    Notiflix.Confirm.show(
      "Désactivation du compte",
      "Voulez-vous vraiment désactiver le compte ?<br/>" +
        "L'utilisateur ne pourra plus se connecter.<br/>" +
        "Ceci réinitialisera également le mot de passe.",
      "Désactiver",
      "Annuler",
      async function () {
        const url = new URL(env.api);
        url.pathname += `admin/users/${compte.uid}`;

        try {
          Notiflix.Block.dots(
            `#${ligneCompte.id}`,
            "Désactivation en cours..."
          );

          const reponse = await fetch(url, {
            method: "DELETE",
          });

          if (!reponse.ok) {
            throw new Error(`${reponse.status}, ${reponse.statusText}`);
          }

          compte.statut = AccountStatus.INACTIVE;

          Notiflix.Notify.success("Le compte a été désactivé");
        } catch (erreur: any) {
          Notiflix.Notify.failure(erreur.message);
        } finally {
          Notiflix.Block.remove(`#${ligneCompte.id}`);
        }
      },
      undefined,
      {
        titleColor: "#ff5549",
        okButtonColor: "#f8f8f8",
        okButtonBackground: "#ff5549",
        cancelButtonColor: "#f8f8f8",
        cancelButtonBackground: "#a9a9a9",
      }
    );
  }

  onMount(() => {
    ligneCompte.id = "_" + compte.uid;

    // Si changement d'une ligne, activation de la classe "modificationEnCours"
    ligneCompte
      .querySelectorAll<HTMLInputElement | HTMLTextAreaElement>(
        "input, textarea"
      )
      .forEach((input) => {
        input.onchange = () => (modificationEnCours = true);
        input.oninput = () => (modificationEnCours = true);
      });

    // Autosize
    const inputCommentaire =
      ligneCompte.querySelector<HTMLTextAreaElement>(".commentaire");
    if (inputCommentaire) autosize(inputCommentaire);
  });
</script>

<li
  class="compte"
  class:modificationEnCours
  data-statut={compte.statut}
  bind:this={ligneCompte}
>
  <!-- Nom + statut -->
  {#if !isNew}
    <div class="nom-statut">
      <button
        class="nom"
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
    </div>
  {/if}

  <form class="pure-form">
    <div class="details" hidden={!afficherDetails}>
      <div>
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
            required
          />
        </div>
        <!-- Login -->
        <div class="champ pure-control-group">
          <label for={"login_" + compte.uid}>Login</label>
          <input
            type="text"
            class="login"
            id={"login_" + compte.uid}
            name="login"
            data-nom="Login"
            bind:value={compte.login}
            required
          />
        </div>
      </div>

      <!-- Commentaire -->
      <div class="champ">
        <label for={"commentaire_" + compte.uid}>Commentaire</label>
        <textarea
          class="commentaire"
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
          <fieldset name={module} class="rubrique">
            <legend>{affichage}</legend>

            <!-- Module de type "Accès" -->
            {#if type === TypesModules.ACCESS}
              <label class="pure-radio">
                <!-- Désactivé si admin : un administrateur ne peut pas se retirer lui-même le privilège -->
                <input
                  type="radio"
                  bind:group={compte.roles[module]}
                  name={module}
                  value={UserRoles.NONE}
                  disabled={module === "admin" && compte.self}
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
      {#if !compte.self && !isNew}
        <div class="action">
          <button
            on:click={reinitialiserCompte}
            disabled={compte.statut === AccountStatus.PENDING}
          >
            Réinitialiser le compte
          </button>
        </div>

        <div class="action">
          <button
            on:click={desactiverCompte}
            disabled={compte.statut === AccountStatus.INACTIVE}
          >
            Désactiver le compte
          </button>
        </div>
      {/if}
    </div>
  </form>
</li>

<style>
  :root {
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

  .compte[data-statut="actif"] .nom-statut > .nom {
    color: var(--statut-active);
  }

  .compte[data-statut="pending"] .nom-statut > .nom {
    color: var(--statut-pending);
  }

  .compte[data-statut="désactivé"] .nom-statut > .nom {
    color: var(--statut-inactive);
  }

  .compte[data-statut="bloqué"] .nom-statut > .nom {
    color: var(--statut-locked);
  }

  .details {
    display: grid;
    grid-template-columns: repeat(3, auto);
  }

  .champ {
    display: flex;
    flex-direction: column;
    margin-left: 1%;
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
    margin-left: auto;
    align-self: center;
    visibility: hidden;
  }

  .roles {
    grid-column: 1 / span 3;
    display: grid;
    grid-template-columns: repeat(4, 1fr);
  }

  .roles > .rubrique {
    margin-top: 10px;
  }

  .action {
    justify-self: center;
    margin-top: 10px;
  }

  .action > button {
    padding: 5px;
    cursor: pointer;
  }

  .action > button:disabled {
    cursor: auto;
  }

  /* Notiflix */
  :global(.notiflix-historique-details) {
    overflow: auto;
    max-height: 200px;
  }

  @media screen and (max-width: 480px) {
    .details {
      grid-template-columns: auto;
    }

    .commentaire {
      margin-top: 5px;
    }
  }
</style>
