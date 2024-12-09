<!-- 
  @component
  
  Bloc de configuration d'un compte utilisateur.

  Usage :
  ```tsx
  <Compte id: number|string />
  ```
 -->
<script lang="ts">
  import { onMount, onDestroy, tick } from "svelte";

  import Notiflix from "notiflix";
  import autosize from "autosize";
  import {
    AccordionItem,
    Label,
    Input,
    Textarea,
    Radio,
    Button,
  } from "flowbite-svelte";
  import { CircleHelpIcon, HistoryIcon } from "lucide-svelte";

  import { LucideButton, Badge } from "@app/components";

  import { adminUsers, currentUser, authInfo } from "@app/stores";

  import { sitemap, notiflixOptions, validerFormulaire } from "@app/utils";

  import { AccountStatus, UserRoles, TypesModules } from "@app/auth";

  import type { CompteUtilisateur } from "@app/types";

  export let id: CompteUtilisateur["uid"];

  let compte: CompteUtilisateur;
  let compteInitital: CompteUtilisateur;

  let loginInput: HTMLInputElement;

  const unsubscribeAdminUsers = adminUsers.subscribe((users) => {
    compte = structuredClone(users.get(id));

    if (!compte) return;

    // Vérification que toutes les rubriques ont une valeur de rôle
    // Sinon, mettre à zéro
    for (const module of sitemap.keys()) {
      if (compte.roles[module] === undefined) {
        compte.roles[module] = UserRoles.NONE;
      }
    }

    compteInitital = structuredClone(compte);
  });

  /**
   * Ce composant.
   */
  let ligne: HTMLFormElement | undefined;

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
  let open = isNew; // Afficher par défaut si nouveau compte uniquement

  /**
   * La ligne du compte est celle de l'utilisateur courant.
   */
  let self = compte.uid === $currentUser.uid;

  $: if (open) {
    addEventListeners();
  }

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
  $: explications = {
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
        $authInfo.MAX_LOGIN_ATTEMPTS +
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
  } as Record<AccountStatus, Explication>;

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
    compte.historique = compte.historique.replace(/\r\n|\r|\n/g, "<br/>");

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
  function checkLoginIsAvailable() {
    loginInput.value = loginInput.value
      .toLowerCase()
      .replace(/[^a-z0-9_-]/g, "");

    const newLogin = loginInput.value;

    const loginList = [...$adminUsers.values()]
      .map((user) => user.login)
      .filter((login) => login !== compteInitital.login);

    if (loginList.includes(newLogin)) {
      loginInput.setCustomValidity("L'identifiant existe déjà.");
    } else {
      loginInput.setCustomValidity("");
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
  function reinitialiserCompte(e: Event) {
    e.preventDefault();

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
  function desactiverCompte(e: Event) {
    e.preventDefault();

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
  function supprimerCompte(e: Event) {
    e.preventDefault();

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

  async function addEventListeners() {
    await tick();

    if (!ligne) return;

    // Si changement d'une ligne, activation de la classe "modificationEnCours"
    ligne
      .querySelectorAll<
        HTMLInputElement | HTMLTextAreaElement
      >("input, textarea")
      .forEach((input) => {
        input.onchange = () => (modificationEnCours = true);
        input.oninput = () => (modificationEnCours = true);
      });

    // Autosize
    const inputCommentaire = ligne.querySelector("textarea");
    if (inputCommentaire) autosize(inputCommentaire);
  }

  onMount(() => {});

  onDestroy(() => {
    unsubscribeAdminUsers();
  });
</script>

{#if compte}
  <AccordionItem bind:open paddingDefault="p-3">
    <span slot="header">
      {#if !isNew}
        <span class="nom" style:color={`var(--statut-${compte.statut})`}>
          {compteInitital.nom}
        </span>
        {#if compteInitital.roles.admin}
          <Badge size="xs">Administrateur</Badge>
        {/if}
      {:else}
        Nouveau compte
      {/if}
    </span>

    {#if !isNew}
      <div class="flex flex-wrap gap-4 mb-4">
        <Button on:click={afficherExplications} color="light">
          <CircleHelpIcon size={20} />
          <span class="ms-2">Statut du compte</span>
        </Button>

        <Button on:click={afficherHistorique} color="light">
          <HistoryIcon size={20} /> <span class="ms-2">Historique</span>
        </Button>
      </div>
    {/if}

    <form bind:this={ligne} class="mb-3" class:modificationEnCours>
      <div
        class="grid [grid-template-areas:'a''b''d''e''c'] lg:[grid-template-areas:'a_b_c''d_d_d''e_e_e'] gap-3"
      >
        <div class="[grid-area:a]">
          <!-- Nom -->
          <div class="mb-4">
            <Label for={"nom_" + compte.uid}>Nom</Label>
            <Input
              type="text"
              id={"nom_" + compte.uid}
              name="nom"
              data-nom="Nom"
              bind:value={compte.nom}
              maxlength={255}
              required
            />
          </div>

          <!-- Login -->
          <div class="mb-4">
            <Label for={"login_" + compte.uid}>Login</Label>
            <Input let:props>
              <input
                bind:this={loginInput}
                type="text"
                class="login"
                id={"login_" + compte.uid}
                name="login"
                data-nom="Login"
                bind:value={compte.login}
                on:input={checkLoginIsAvailable}
                maxlength={255}
                required
                {...props}
              />
            </Input>
          </div>
        </div>

        <!-- Commentaire -->
        <div class="[grid-area:b]">
          <Label for={"commentaire_" + compte.uid}>Commentaire</Label>
          <Textarea
            id={"commentaire_" + compte.uid}
            name="commentaire"
            rows={3}
            bind:value={compte.commentaire}
          />
        </div>

        <!-- Boutons modification-->
        <div
          class="place-self-center [grid-area:c]"
          style:visibility={modificationEnCours ? "visible" : "hidden"}
        >
          <LucideButton
            preset="confirm"
            on:click={isNew ? validerAjout : validerModification}
          />
          <LucideButton
            preset="cancel"
            on:click={isNew ? annulerAjout : annulerModification}
          />
        </div>

        <!-- Rôles -->
        <div class="roles grid [grid-area:d]">
          {#each [...sitemap] as [module, { affichage, type }]}
            <fieldset
              name={module}
              class="mt-2 disabled:text-gray-500 disabled:opacity-50"
              disabled={/* Désactivé si admin : un administrateur ne peut pas se retirer lui-même le privilège */ module ===
                "admin" && self}
            >
              <legend class="text-sm">{affichage}</legend>

              <!-- Module de type "Accès" -->
              {#if type === TypesModules.ACCESS}
                <Radio bind:group={compte.roles[module]} value={UserRoles.NONE}>
                  Pas accès
                </Radio>
                <Radio
                  bind:group={compte.roles[module]}
                  value={UserRoles.ACCESS}
                >
                  Accès
                </Radio>
              {/if}

              <!-- Module de type "Modifier" -->
              {#if type === TypesModules.EDIT}
                <Radio bind:group={compte.roles[module]} value={UserRoles.NONE}>
                  Aucun
                </Radio>
                <Radio
                  bind:group={compte.roles[module]}
                  value={UserRoles.ACCESS}
                >
                  Voir
                </Radio>
                <Radio bind:group={compte.roles[module]} value={UserRoles.EDIT}>
                  Modifier
                </Radio>
              {/if}
            </fieldset>
          {/each}
        </div>
      </div>
    </form>

    <!-- Boutons -->
    {#if !self && !isNew}
      <div class="place-self-center [grid-area:e]">
        <Button
          on:click={reinitialiserCompte}
          disabled={compte.statut === AccountStatus.PENDING}
        >
          Réinitialiser le compte
        </Button>

        <Button
          on:click={desactiverCompte}
          disabled={compte.statut === AccountStatus.INACTIVE}
        >
          Désactiver le compte
        </Button>

        <Button
          color="red"
          on:click={supprimerCompte}
          disabled={compte.statut === AccountStatus.DELETED}
        >
          Supprimer le compte
        </Button>
      </div>
    {/if}
  </AccordionItem>
{/if}

<style>
  * {
    --statut-active: hsl(120, 60%, 50%);
    --statut-pending: hsl(60, 60%, 50%);
    --statut-inactive: hsl(0, 0%, 50%);
    --statut-locked: hsl(0, 60%, 50%);
  }

  /* -------------- */

  .modificationEnCours {
    background-color: lightyellow;
  }

  /* Notiflix */
  :global(.notiflix-historique-details) {
    overflow: auto;
    max-height: 200px;
  }

  /* Mobile */
  @media screen and (max-width: 767px) {
    .roles {
      grid-template-columns: repeat(2, 1fr);
    }
  }

  /* Desktop */
  @media screen and (min-width: 768px) {
    .roles {
      grid-column: 1 / span 3;
      grid-template-columns: repeat(4, 1fr);
    }
  }
</style>
