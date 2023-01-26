<script lang="ts">
  import { onMount, onDestroy } from "svelte";

  import { Menu, MaterialButton } from "@app/components";

  import { currentUser, tiersModifiables, pays } from "@app/stores";

  import Notiflix from "notiflix";

  import {
    env,
    removeDiacritics as rd,
    fetcher,
    demarrerConnexionSSE,
  } from "@app/utils";

  import { Awesomplete } from "../lib/Awesomplete.js";
  import { AccountStatus } from "@app/auth";

  let source: EventSource;

  let inputRecherche: HTMLInputElement;
  let inputSelect: HTMLSelectElement;
  let inputLogo: HTMLInputElement;
  let inputCommentaire: HTMLTextAreaElement;
  let thumbnail: HTMLImageElement;
  let ajouterButton: HTMLButtonElement;
  let modifierButton: HTMLButtonElement;
  let supprimerButton: HTMLButtonElement;

  /**
   * Nombre de caractères minimum pour la recherche.
   */
  const MIN_CHAR_RECHERCHE = 3;

  /**
   * Liste des tiers.
   */
  $: listeTiers = $tiersModifiables;

  /**
   * Modèle tiers.
   */
  const modeleTiers: Tiers = {
    id: null,
    nom_court: "",
    nom_complet: "",
    adresse_ligne_1: "",
    adresse_ligne_2: "",
    cp: "",
    ville: "",
    pays: "",
    telephone: "",
    commentaire: "",
    bois_fournisseur: false,
    bois_client: false,
    bois_transporteur: false,
    bois_affreteur: false,
    vrac_fournisseur: false,
    vrac_client: false,
    vrac_transporteur: false,
    maritime_armateur: false,
    maritime_affreteur: false,
    maritime_courtier: false,
    non_modifiable: false,
    lie_agence: false,
    logo: null,
    actif: true,
    nombre_rdv: 0,
  };

  /**
   * Tiers sélectionné dans la liste déroulante.
   */
  let selectedTiers: Tiers = structuredClone(modeleTiers);

  let selectedTiersOriginal: Tiers = structuredClone(selectedTiers);

  let modificationEnCours: boolean = false;

  let rechercheEnCours: boolean = false;

  const AUCUN_LOGO =
    "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAJYAAACWBAMAAADOL2zRAAAAG1BMVEXc3NxmZma+vr6Dg4OSkpJ0dHTNzc2vr6+hoaHrm2uMAAAACXBIWXMAAA7EAAAOxAGVKw4bAAABqElEQVRoge2Uv0/CQBiGP1paOhqNxLEQIY6EUuNIkBjGhkroWFBwvUF2jfh/e79a23iNjXQy75P0eMN39xy9foUIAAAAAAAAAAAAAAAA/oJfe6Y1/mWCe1bb1Z4053I2zblebdaYa9wZFdbtC5V9waUK+5+TSrtG1CUSPn55q3CtsxUdwqvcxQv8ER3i+JrpbMKb0iJ33afkZq7klnYsc/HCwO8syQqZykaX49Mgc3Ui8Y12XagkXRYvWFGLGzZMZaNrR9RK9Xr7reDqZku4qy0Kyx4fbKay0XUkMVOtd/yCK8qeIB9lYTEQt8FUNrqSfv8m0uv5DzS7ZOEoLJtUZZPKioMgOK/nEmf/SNUueffzevfozVcxo+p7lLv05Cl7+uxVLrnkjmsnzXdfm1wHMThMrNrpnlC55FJ9YM9SvZW5J+7E4I1oQlYyokD0qsoll+7Pj9XljCp71e3q8SkOtuIdChJSuezy4lC2lPvu57marTwMd/idy5sO1Sd/5fJ8KsU/lVNpT5vxvPDrM23G9RzPw4dmVPzQ+35TKgAAAAAAAAAAAID/zxc+J0puZGHZZgAAAABJRU5ErkJggg==";
  const ERREUR_LOGO =
    "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAJYAAACWBAMAAADOL2zRAAAAG1BMVEXc3NxmZmavr6/Nzc2SkpK+vr50dHSDg4OhoaFfulIPAAAACXBIWXMAAA7EAAAOxAGVKw4bAAABiElEQVRoge2STU/CQBCGB7Y0vSt6rUKEY7GkeGwIokcOWq6Ej8qRAOl65CT8bGezCxKEWmONHt4n2c3b6fTpTlMiAAAAAAAAAAAAAAAA+F2uGS+toZ5ZJc47nU4vraOa3TX4quOfuMQxly56R+8fuorhXUJVsR5QU0qvuCJq6Zp2NV8Sl+hNzusmprruhx5VowqJCZVGDrsiXdOuIYk5WSFZUx1PuS6lOonkx2oLIos1G+OSnpnRCYkCL+A81fGUa+T7PhXLHK/417C5r29cZTIum29Ybp/zo47pM6r9hleBzYFxDbauAu9OGPHe1jHdNeOtxqvBE0vjmm1dDdUR2i6VznTM5lLvpUOXPowz3nR73zmX+l6fXKpoubZ49kzM5rJW5irZcyl34KnPbmI2lxjy4p/M6u65uCheSXTjZU/HU66LOI5XOxe1xg8LipbzYM9FrWWizku3k13MhFAT+AdF34xV+4g/pp+TR7HJydOviKdFTq7SWrZzUgEAAAAAAAAAAACAv+MdUMhMh73JJkQAAAAASUVORK5CYII=";

  /**
   * Données du nouveau logo.
   */
  let logoData: string | null = null;

  /**
   * Récupère le nombre de RDV par tiers.
   *
   * @returns Liste du nombre de RDV par tiers
   */
  async function recupererNombreRdv(): Promise<string> {
    try {
      return await fetcher("tiers/nombre_rdv");
    } catch (error) {
      Notiflix.Notify.failure(error.message);
    }
  }

  /**
   * Combine le nombre de RDV pour chaque tiers.
   */
  function combinerNombreRdvTiers(
    listeTiers: Tiers[],
    listeNombreRdv: { [id: string]: number }
  ): void {
    for (const tiers of listeTiers) {
      tiers.nombre_rdv = listeNombreRdv[tiers.id] || 0;
    }
  }

  /**
   * Recherche un tiers dans la liste.
   */
  async function filtrerListeDeroulante() {
    let recherche = rd(inputRecherche.value).trim();

    // X caractères minimum pour lancer la recherche
    // pour éviter un blocage navigateur en tapant les premiers caractères
    // (car recherche sur un grand nombre de tiers)
    if (recherche.length < MIN_CHAR_RECHERCHE) {
      rechercheEnCours = false;
      listeTiers = $tiersModifiables;
      return;
    }

    rechercheEnCours = true;

    const regexp = new RegExp(recherche, "i");

    listeTiers = $tiersModifiables.filter(
      (tiers) =>
        rd(`${tiers.nom_court} ${tiers.nom_complet} ${tiers.ville}`).search(
          regexp
        ) !== -1
    );

    if (listeTiers.length > 0) {
      selectedTiers = listeTiers[0];
      // await tick();
      inputSelect.value = selectedTiers.id.toString();
    } else {
      selectedTiers = structuredClone(modeleTiers);
    }
  }

  /**
   * Au changement de la liste déroulante,
   * lecture de la valeur sélectionnée dans la liste déroulante
   * puis changement des champs et boutons
   */
  function changerSelectedTiers() {
    selectedTiers =
      listeTiers.find((tiers) => tiers.id.toString() === inputSelect.value) ||
      structuredClone(modeleTiers);

    selectedTiersOriginal = structuredClone(selectedTiers);

    inputLogo.value = null;
    logoData = null;
  }

  /**
   * Afficher une preview du logo lorsqu'un fichier est choisi.
   */
  function afficherPreviewLogo() {
    // Suppression des données du fichier
    // précédemment choisi par l'utilisateur
    logoData = null;

    const fichier = inputLogo.files[0];

    if (!fichier.type.startsWith("image/")) return;

    const reader = new FileReader();
    reader.onload = () => {
      // La lecture du fichier avec readAsDataURL renvoie une string
      const result = reader.result as string;

      // Affichage de l'image
      thumbnail.src = result;

      // Enregistrement des données pour faciliter l'envoi du formulaire
      logoData = result.split(",")[1];
    };
    reader.readAsDataURL(fichier);
  }

  /**
   * Suppression du logo existant.
   */
  function supprimerLogoExistant() {
    selectedTiers.logo = null;
    logoData = null;
  }

  // FIXME: le bouton est cliqué deux fois : supprimerLogoExistant => retablirLogoExistant dans la foulée

  /**
   * Rétablir le logo existant
   * (= annuler la suppression ou le choix de fichier).
   */
  function retablirLogoExistant() {
    // Suppression des données du fichier
    // précédemment choisi par l'utilisateur
    inputLogo.value = null;
    logoData = null;

    selectedTiers.logo = selectedTiersOriginal.logo;
  }

  /**
   * Annuler les modifications.
   */
  function annulerModification() {
    selectedTiers = structuredClone(selectedTiersOriginal);
    modificationEnCours = false;
  }

  /**
   * Validation formulaire
   *
   * @param {HTMLFormElement} formulaire Formulaire à valider
   */
  function validerFormulaire(formulaire) {
    const inputs = formulaire.querySelectorAll("input");

    const champs_invalides = [];

    let valide = true;

    for (const input of inputs) {
      if (!input.checkValidity()) {
        valide = false;
        if (input.dataset.nom) {
          champs_invalides.push(input.dataset.nom);
        }
      }
    }

    if (!valide) {
      Notiflix.Notify.failure(
        "Certains champs du formulaire sont invalides : " +
          champs_invalides.join(", ")
      );
    }
    return valide;
  }

  /**
   * Nouveau tiers
   */
  async function ajouterTiers() {
    const form = document.querySelector("form");

    if (!validerFormulaire(form)) return;

    ajouterButton.setAttribute("disabled", "true");

    const formData = Object.fromEntries(new FormData(form));

    if (logoData) {
      const fichier = inputLogo.files[0];
      formData.logo = {
        type: fichier.type,
        data: logoData,
      };
    }

    const url = new URL(env.api);
    url.pathname += `tiers`;

    try {
      const reponse = await fetch(url, {
        method: "POST",
        body: JSON.stringify(formData),
      });

      if (!reponse.ok) {
        throw new Error(`${reponse.status} : ${reponse.statusText}`);
      }

      Notiflix.Notify.success("Le tiers a été créé");

      const nombreRdvPromise = recupererNombreRdv();

      const tiers = await reponse.json();

      listeTiersPromise.then(() => {
        inputSelect.value = tiers.id;
        inputSelect.removeAttribute("disabled");
      });

      Promise.all([listeTiersPromise, nombreRdvPromise]).then(
        ([tiers, nombre_rdv]) => {
          combinerNombreRdvTiers(listeTiers, nombre_rdv);
        }
      );
    } catch (erreur) {
      Notiflix.Notify.failure(erreur.message);
    } finally {
      ajouterButton.removeAttribute("disabled");
    }
  }

  /**
   * Modifier tiers
   */
  async function modifierTiers() {
    const form = document.querySelector("form");

    if (!validerFormulaire(form)) return;

    modifierButton.setAttribute("disabled", "true");

    const formData = Object.fromEntries(new FormData(form));

    if (logoData === null) {
      formData.logo = null;
    }

    if (logoData) {
      const fichier = inputLogo.files[0];
      formData.logo = {
        type: fichier.type,
        data: logoData,
      };
    }

    const id = inputSelect.value;

    const url = new URL(env.api);
    url.pathname += `tiers/${id}`;

    try {
      const reponse = await fetch(url, {
        method: "PUT",
        body: JSON.stringify(formData),
      });

      if (!reponse.ok) {
        throw new Error(`${reponse.status} : ${reponse.statusText}`);
      }

      Notiflix.Notify.success("Le tiers a été modifié");

      const nombreRdvPromise = recupererNombreRdv();

      listeTiersPromise.then(() => {
        inputSelect.value = id;
      });

      Promise.all([listeTiersPromise, nombreRdvPromise]).then(
        ([tiers, nombre_rdv]) => {
          combinerNombreRdvTiers(listeTiers, nombre_rdv);
        }
      );
    } catch (erreur) {
      Notiflix.Notify.failure(erreur.message);
    } finally {
      modifierButton.removeAttribute("disabled");
    }
  }

  /**
   * Suppression tiers
   */
  function supprimerTiers() {
    supprimerButton.setAttribute("disabled", "true");

    const tiers = inputSelect.options[inputSelect.selectedIndex].innerHTML;

    // Demande de confirmation
    Notiflix.Confirm.show(
      "Suppression tiers",
      `Voulez-vous vraiment supprimer le tiers <b>${tiers}</b> ?`,
      "Supprimer",
      "Annuler",
      async function () {
        const id = inputSelect.value;

        const url = new URL(env.api);
        url.pathname += `tiers/${id}`;

        try {
          const reponse = await fetch(url, {
            method: "DELETE",
          });

          if (!reponse.ok) {
            throw new Error(`${reponse.status} : ${reponse.statusText}`);
          }

          Notiflix.Notify.success("Le tiers a été supprimé");

          inputRecherche.value = null;

          // Remise à la sélection "Nouveau..."
          listeTiers = listeTiers.filter((tiers) => tiers.id.toString() != id);
          inputSelect.value = "";
        } catch (erreur) {
          Notiflix.Notify.failure(erreur.message);
        } finally {
          supprimerButton.removeAttribute("disabled");
        }
      },
      function () {
        supprimerButton.removeAttribute("disabled");
      },
      {
        titleColor: "#ff5549",
        okButtonColor: "#f8f8f8",
        okButtonBackground: "#ff5549",
        cancelButtonColor: "#f8f8f8",
        cancelButtonBackground: "#a9a9a9",
      }
    );
  }

  // /**
  //  * Saisie pays
  //  */
  // (async () => {
  //   const liste_pays = await recupererPays;

  //   // Lors du focus, remise à zéro de la validité
  //   pays_user.addEventListener("focus", (e) => {
  //     pays_user.setCustomValidity("");
  //   });

  //   // Lors de la saisie, remise à zéro du code pays
  //   pays_user.addEventListener("input", (e) => {
  //     pays_value.value = "";
  //   });

  //   // Sélection avec la table des pays filtrée
  //   new Awesomplete(pays_user, {
  //     list: liste_pays,
  //     sort: false,
  //     separate: true,
  //     data: (pays, input) => {
  //       return { label: pays.nom, value: pays.iso };
  //     },
  //   });

  //   // Vérification que la saisie est valide
  //   pays_user.addEventListener("blur", (e) => {
  //     const pays = liste_pays.find(
  //       (pays) => pays.nom.toLowerCase() === pays_user.value.toLowerCase()
  //     );
  //     if (pays) {
  //       pays_user.value = pays.nom;
  //       pays_value.value = pays.iso;
  //       pays_user.setCustomValidity("");
  //     }

  //     if (pays_value.value === "" && pays_user.value !== "") {
  //       const message = `"${pays_user.value}" n'est pas un pays connu`;
  //       Notiflix.Notify.failure(message);
  //       pays_user.setCustomValidity(message);
  //       pays_value.value = "";
  //     }
  //   });
  // })();

  onMount(() => {
    source = demarrerConnexionSSE(["tiers"]);

    thumbnail.onerror = () => {
      thumbnail.src = ERREUR_LOGO;
    };
  });

  onDestroy(() => {
    source.close();
  });
</script>

{#if $currentUser.statut === AccountStatus.ACTIVE}
  <Menu />

  <main class="formulaire">
    <h1>Tiers</h1>

    <form
      class="pure-form pure-form-aligned"
      on:keydown={(e) => {
        // Empêcher la création d'un tiers en appuyant sur la touche "Entrée"
        if (e.key === "Enter" && document.activeElement !== inputCommentaire) {
          e.preventDefault();
        }
      }}
    >
      <!-- Liste déroulante -->
      <div class="pure-control-group">
        <label for="id">
          <select
            id="id"
            name="id"
            bind:this={inputSelect}
            on:change={changerSelectedTiers}
            disabled={!listeTiers || listeTiers.length === 0}
          >
            {#if !listeTiers}
              <option>Chargement...</option>
            {:else}
              {#if !rechercheEnCours}
                <option>Nouveau</option>
              {/if}
              {#each listeTiers as tiers (tiers.id)}
                <option value={tiers.id} class:inactif={!tiers.actif}>
                  {tiers.nom_court} - {tiers.ville}
                </option>
              {:else}
                <option value="">Aucun tiers trouvé</option>
              {/each}
            {/if}
          </select>
          <!-- Recherche -->
          <span class="pure-form-message-inline"
            ><input
              class="recherche"
              placeholder="Recherche"
              bind:this={inputRecherche}
              on:input={filtrerListeDeroulante}
              on:focus={() => {
                // Placeholder "X caractères minimum"
                const s = MIN_CHAR_RECHERCHE > 1 ? "s" : "";

                inputRecherche.setAttribute(
                  "placeholder",
                  `Recherche (${MIN_CHAR_RECHERCHE} caractère${s} minimum)`
                );
              }}
              on:blur={() =>
                inputRecherche.setAttribute("placeholder", "Recherche")}
            /></span
          >
        </label>
      </div>

      <!-- Nom complet -->
      <div class="pure-control-group">
        <label for="nom_complet">Nom complet</label>
        <input
          type="text"
          id="nom_complet"
          name="nom_complet"
          placeholder="Nom complet"
          maxlength="255"
          bind:value={selectedTiers.nom_complet}
          required
        />
      </div>

      <!-- Nom abbrégé -->
      <div class="pure-control-group">
        <label for="nom_court">Nom abbrégé</label>
        <input
          type="text"
          id="nom_court"
          name="nom_court"
          placeholder="Nom abbrégé"
          maxlength="255"
          bind:value={selectedTiers.nom_court}
        />
      </div>

      <!-- Adresse ligne 1 -->
      <div class="pure-control-group">
        <label for="adresse_ligne_1">Adresse (ligne 1)</label>
        <input
          type="text"
          id="adresse_ligne_1"
          name="adresse_ligne_1"
          placeholder="Adresse (ligne 1)"
          maxlength="255"
          bind:value={selectedTiers.adresse_ligne_1}
        />
      </div>

      <!-- Adresse ligne 2 -->
      <div class="pure-control-group">
        <label for="adresse_ligne_2">Adresse (ligne 2)</label>
        <input
          type="text"
          id="adresse_ligne_2"
          name="adresse_ligne_2"
          placeholder="Adresse (ligne 2)"
          maxlength="255"
          bind:value={selectedTiers.adresse_ligne_2}
        />
      </div>

      <!-- Code postal -->
      <div class="pure-control-group">
        <label for="cp">Code Postal</label>
        <input
          type="text"
          id="cp"
          name="cp"
          placeholder="Code postal"
          maxlength="20"
          data-nom="Code postal"
          bind:value={selectedTiers.cp}
        />
      </div>

      <!-- Ville -->
      <div class="pure-control-group">
        <label for="ville">Ville</label>
        <input
          type="text"
          id="ville"
          name="ville"
          placeholder="Ville"
          maxlength="255"
          data-nom="Ville"
          bind:value={selectedTiers.ville}
          required
        />
      </div>

      <!-- Pays -->
      <div class="pure-control-group">
        <label for="pays_user">Pays</label>
        <input
          type="text"
          id="pays_user"
          placeholder="Pays"
          maxlength="255"
          data-nom="Pays"
          bind:value={selectedTiers.pays}
          required
        />
        <input hidden id="pays_value" name="pays" required />
      </div>

      <!-- Téléphone -->
      <div class="pure-control-group">
        <label for="telephone">Téléphone</label>
        <input
          type="text"
          id="telephone"
          name="telephone"
          placeholder="Téléphone"
          maxlength="255"
          data-nom="Téléphone"
          bind:value={selectedTiers.telephone}
        />
      </div>

      <!-- Commentaire -->
      <div class="pure-control-group">
        <label for="commentaire">Commentaire</label>
        <textarea
          id="commentaire"
          name="commentaire"
          bind:this={inputCommentaire}
          bind:value={selectedTiers.commentaire}
          rows="5"
          cols="30"
          placeholder="Horaires, indications diverses..."
          maxlength="65535"
        />
      </div>

      <!-- Logo -->
      <div class="pure-control-group">
        <label for="logo">Logo</label>
        <input
          type="file"
          accept="image/jpeg, image/png, image/webp, image/gif, image/bmp"
          id="logo"
          bind:this={inputLogo}
          on:change={afficherPreviewLogo}
        />
        {#if logoData}
          <MaterialButton
            icon="cancel"
            title="Annuler le choix de fichier"
            on:click={retablirLogoExistant}
            color="hsla(0, 100%, 50%, 0.5)"
            hoverColor="hsla(0, 100%, 50%, 1)"
          />
        {/if}
      </div>
      <div class="pure-control-group">
        <label>
          {#if selectedTiers.logo && !logoData}
            <MaterialButton
              icon="delete"
              title="Supprimer le logo existant"
              on:click={supprimerLogoExistant}
            />
          {/if}
          {#if !selectedTiers.logo && selectedTiersOriginal.logo}
            <MaterialButton
              icon="undo"
              title="Rétablir le logo existant"
              on:click={retablirLogoExistant}
            />
          {/if}
        </label>
        <img
          id="thumbnail"
          bind:this={thumbnail}
          src={selectedTiers.logo || AUCUN_LOGO}
          alt="Logo"
          width="auto"
          height="100"
        />
      </div>

      <!-- Rôles -->
      <div class="pure-controls">
        <fieldset>
          <div class="roles">
            <div class="grid__container">
              <legend>Bois</legend>
              <!-- Bois -->
              <label class="pure-checkbox">
                <input
                  type="checkbox"
                  name="bois_fournisseur"
                  bind:checked={selectedTiers.bois_fournisseur}
                />
                Fournisseur
              </label>
              <label class="pure-checkbox">
                <input
                  type="checkbox"
                  name="bois_client"
                  bind:checked={selectedTiers.bois_client}
                />
                Client
              </label>
              <label class="pure-checkbox">
                <input
                  type="checkbox"
                  name="bois_transporteur"
                  bind:checked={selectedTiers.bois_transporteur}
                />
                Transporteur
              </label>
              <label class="pure-checkbox">
                <input
                  type="checkbox"
                  name="bois_affreteur"
                  bind:checked={selectedTiers.bois_affreteur}
                />
                Affréteur
              </label>
            </div>
            <div class="grid__container">
              <legend>Vrac</legend>
              <!-- Vrac -->
              <label class="pure-checkbox">
                <input
                  type="checkbox"
                  name="vrac_fournisseur"
                  bind:checked={selectedTiers.vrac_fournisseur}
                />
                Fournisseur
              </label>
              <label class="pure-checkbox">
                <input
                  type="checkbox"
                  name="vrac_client"
                  bind:checked={selectedTiers.vrac_client}
                />
                Client
              </label>
              <label class="pure-checkbox">
                <input
                  type="checkbox"
                  name="vrac_transporteur"
                  bind:checked={selectedTiers.vrac_transporteur}
                />
                Transporteur
              </label>
            </div>
            <div class="grid__container">
              <legend>Maritime</legend>
              <!-- Maritime -->
              <label class="pure-checkbox">
                <input
                  type="checkbox"
                  name="maritime_armateur"
                  bind:checked={selectedTiers.maritime_armateur}
                />
                Armateur
              </label>
              <label class="pure-checkbox">
                <input
                  type="checkbox"
                  name="maritime_courtier"
                  bind:checked={selectedTiers.maritime_courtier}
                />
                Courtier
              </label>
              <label class="pure-checkbox">
                <input
                  type="checkbox"
                  name="maritime_affreteur"
                  bind:checked={selectedTiers.maritime_affreteur}
                />
                Affréteur
              </label>
            </div>
          </div>
        </fieldset>
      </div>

      <!-- Actif -->
      <div class="pure-control-group">
        <label for="actif">Actif</label>
        <input
          type="checkbox"
          name="actif"
          id="actif"
          bind:checked={selectedTiers.actif}
        />
      </div>
    </form>

    <!-- Validation/Annulation/Suppression -->
    <div id="boutonsAMS">
      <div class="boutons">
        <!-- Bouton "Ajouter" -->
        {#if !selectedTiers.id}
          <button
            class="pure-button bouton bouton-ajouter"
            bind:this={ajouterButton}
            on:click={ajouterTiers}>Ajouter</button
          >
        {/if}

        <!-- Bouton "Modifier" -->
        {#if selectedTiers.id}
          <button
            class="pure-button bouton bouton-modifier"
            bind:this={modifierButton}
            on:click={modifierTiers}>Modifier</button
          >
        {/if}

        <!-- Bouton "Supprimer" -->
        {#if selectedTiers.id}
          <div class="tooltip">
            <button
              class="pure-button bouton bouton-supprimer"
              bind:this={supprimerButton}
              on:click={supprimerTiers}
              disabled={selectedTiers.nombre_rdv > 0}
            >
              Supprimer
            </button>
            <!-- Affichage info-bulle si impossibilité de supprimer -->
            {#if selectedTiers.nombre_rdv > 0}
              <div class="tooltip-supprimer">
                Le tiers est concerné par ${selectedTiers.nombre_rdv} rdv.<br />
                Impossible de le supprimer.
              </div>
            {/if}
          </div>
        {/if}

        <!-- Bouton "Annuler" -->
        <button
          class="pure-button bouton bouton-annuler"
          on:click={annulerModification}
          disabled={!modificationEnCours}
        >
          Annuler
        </button>
      </div>
    </div>
  </main>
{:else}
  {(location.href = "/")}
{/if}

<style>
  @import "/src/css/commun.css";
  @import "/src/css/formulaire.css";
  @import "/src/css/awesomplete.css";

  option.inactif {
    color: darkgray;
  }

  .roles {
    display: flex;
    flex-wrap: wrap;
  }

  .grid__container {
    width: 250px;
  }

  @media (max-width: 800px) {
    .roles {
      flex-direction: column;
    }

    .grid__container {
      width: 100%;
    }

    .grid__container:not(:first-child) {
      margin-top: 20px;
    }
  }
</style>
