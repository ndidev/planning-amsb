<!-- 
  @component
  
  Awesomplete tiers.

  Usage :
  ```tsx
  <AwesompleteTiers
    bind:value={value}
    required: boolean
    id: string | number    // Identifiant unique
    nom: string            // Nom du champ
    role: string           // Rôle du tiers dans la table tiers (bois_fournisseur, vrac_livraison, etc.)
    role_affichage: string // Rôle à afficher dans les notifications
    label: string          // Affichage du `label`
    context: HTMLElement   // Contexte de l'élément à sélectionner
    multiple: boolean      // Autoriser la saisie multiple ou non
    tags: boolean          // Afficher des tags pour chaque sélection
    actifs: boolean        // N'afficher que les tiers actifs
    callback: Function     // Fonction à exécuter après le changement de valeur
  />
  ```
 -->
<script>
  import { onMount, onDestroy } from "svelte";

  import Notiflix from "notiflix";

  import { tiers } from "@app/stores";

  import { Awesomplete, AwesompleteWithTags } from "../../AwesompleteSvelte.js";

  /** @type {HTMLInputElement} */
  let inputUser;

  /**
   * Valeur du champ `inputValue`.
   * @type {string|number}
   */
  export let value;

  /**
   * Identifiant unique.
   * @type {string|number}
   */
  export let id = "";

  /**
   * Nom du champ.
   * @type {string}
   */
  export let nom = "";

  /**
   * Rôle du tiers dans la table tiers
   * (bois_fournisseur, vrac_livraison, etc.).
   * @type {string}
   */
  export let role = "";

  /**
   * Rôle à afficher dans les notifications.
   * @type {string}
   */
  export let roleAffichage = "";

  /**
   * Affichage du `label`.
   * @type {string}
   */
  export let label = undefined;

  /**
   * Contexte de l'élément à sélectionner.
   * @type {HTMLElement}
   */
  export let context = document;

  /**
   * Autoriser la saisie multiple ou non.
   * @type {boolean}
   */
  export let multiple = false;

  /**
   * Afficher des tags pour chaque sélection.
   * @type {boolean}
   */
  export let tags = false;

  /**
   * N'afficher que les tiers actifs.
   * @type {boolean}
   */
  export let actifs = true;

  /**
   * Rendre la saisie obligatoire.
   * @type {boolean}
   */
  export let required = false;

  /**
   * Fonction à exécuter après le changement de valeur.
   * @type {Function}
   */
  export let callback = () => {};

  const _callback = () => {
    value = awesompleteInstance.value;
    callback();
  };

  const valueInitial = value;

  // Si annulation (retour à l'état initial)
  // alors forcer affichage inputUser
  $: if (value == valueInitial) {
    updateInputUser(value);
  }

  /** @type {Awesomplete} */
  let awesompleteInstance;

  const listeTiers = $tiers.filter(
    (tiers) => tiers[role] && (actifs ? tiers.actif : true)
  );

  /**
   * Lors de la saisie, remise à zéro de l'état.
   */
  function resetState() {
    inputUser.setCustomValidity("");
    value = "";
  }

  /**
   * Vérifier si la saisie est valide.
   */
  function verifierSaisieValide() {
    // Remplissage correct des champs si la saisie est valide
    const tiers = listeTiers.find(
      (tiers) => tiers.nom_court.toLowerCase() === inputUser.value.toLowerCase()
    );
    if (tiers) {
      inputUser.value = `${tiers.nom_court} - ${tiers.ville}`;
      value = tiers.id;
    }

    // Si saisie invalide, notification + champ invalide
    if (value === "" && inputUser.value !== "") {
      const message = `"${inputUser.value}" n'est pas connu comme ${roleAffichage}`;
      Notiflix.Notify.failure(message);
      inputUser.setCustomValidity(message);
    }
  }

  /**
   * Affiche les valeurs adéquates pour le champ "User"
   * en fonction du champ "Value".
   */
  function updateInputUser(value) {
    if (!inputUser || !value) return; // Before Mount

    inputUser.value = "";

    value
      .toString()
      .split(",")
      .forEach((tiers_id) => {
        if (!tiers_id) return;

        /** @type {Tiers} */
        const tiers = listeTiers.find((tiers) => tiers.id == tiers_id);

        if (!tags) {
          inputUser.value +=
            `${tiers.nom_court} - ${tiers.ville}` + (multiple ? ", " : "");
        }

        // Ajout tags initiaux
        if (tags) {
          awesompleteInstance.addTag({
            label: tiers.nom_court,
            value: tiers_id,
            original: tiers,
          });
        }
      });
  }

  const optionsAwesomplete = {
    list: listeTiers,
    sort: false,
    multiple,
    duplicates: false,
    context,
    data: function (tiers, input) {
      return {
        label: `${tiers.nom_court} - ${tiers.ville}`,
        value: `${tiers.id}`,
        original: tiers,
      };
    },
  };

  onMount(() => {
    // Sélection avec la table des tiers filtrée
    awesompleteInstance = tags
      ? new AwesompleteWithTags(inputUser, optionsAwesomplete, _callback)
      : new Awesomplete(inputUser, optionsAwesomplete, _callback);

    if (tags) {
      awesompleteInstance.addTag = function (item) {
        const tag = document.createElement("span");
        tag.className = "awesomplete-tag";
        tag.dataset.value = item.value;
        tag.isAwesompleteTag = true;
        tag.textContent = item.original.nom_court;
        tag.title = item.label;

        tag.onclick = () => {
          tag.remove();
          this.updateTagStore();
          this.input.hidden = this.hasTags();
        };

        this.keepInputBeforeUL();
        this.input.before(tag);

        this.updateTagStore();
      };

      document.addEventListener(
        "filtre-supprime",
        awesompleteInstance.clearTags
      );
    }

    // Remplissage avec les valeurs intiales
    try {
      awesompleteInstance.value = value;
      updateInputUser(value);
    } catch (err) {
      console.error(inputUser.id, value, err);
    }
  });

  onDestroy(() => {
    if (tags) {
      document.removeEventListener(
        "filtre-supprime",
        awesompleteInstance.clearTags
      );
    }
  });
</script>

<label style:--label-display={label} for={role + "_" + id}>{nom}</label>
<input
  class={role + "_user pure-input-1"}
  id={role + "_" + id}
  bind:this={inputUser}
  on:input={resetState}
  on:blur={verifierSaisieValide}
  data-nom={nom}
  {required}
/>

<style>
  :global(li) {
    text-align: left;
  }

  label {
    display: var(--label-display, "none");
  }
</style>
