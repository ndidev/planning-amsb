import env from "./environment.js";
import { Awesomplete, AwesompleteWithTags } from "./Awesomplete.js";
import Notiflix from "notiflix";

/**
 * Récupération de la liste des tiers
 */
const listeTiersPromise = new Promise(async (resolve, reject) => {
  const url = new URL(env.api);
  url.pathname += "tiers";

  try {
    const reponse = await fetch(url);

    if (!reponse.ok) {
      throw new Error(`${reponse.status} : ${reponse.statusText}`);
    }

    const tiers = await reponse.json();
    resolve(tiers);
  } catch (err) {
    reject(err.message);
  }
});

/**
 * Awesomplete tiers
 *
 * @param {string}        selecteur               Sélecteur CSS
 * @param {Object}        options                 Options
 * @param {string}        options.role            Rôle du tiers dans la table tiers (bois_fournisseur, vrac_livraison, etc.)
 * @param {string}        options.role_affichage  Rôle à afficher dans les notifications
 * @param {string|number} options.valeur_initiale Valeur initiale du champ "_value"
 * @param {HTMLElement}   options.context         Contexte de l'élément à sélectionner
 * @param {boolean?}      options.multiple        Autoriser la saisie multiple ou non
 * @param {boolean?}      options.tags            Afficher des tags pour chaque sélection
 * @param {boolean?}      options.actifs          N'afficher que les tiers actifs
 * @param {Function}      callback                Fonction à exécuter après le changement de valeur
 */
export async function awesompleteTiers(
  selecteur,
  {
    role,
    role_affichage,
    valeur_initiale: valeurInitiale,
    context,
    multiple,
    tags,
    actifs,
  } = {
    role: "",
    role_affichage: "",
    valeur_initiale: "",
    context: document,
    multiple: false,
    tags: false,
    actifs: true,
  },
  callback = () => {}
) {
  // Inputs
  const inputUser = context.querySelector(`${selecteur}_user`);
  const inputValue = context.querySelector(`${selecteur}_value`);

  const module = role.substring(0, role.indexOf("_"));

  if (!role_affichage) {
    role_affichage =
      (/^(#|\.)/.test(selecteur) ? selecteur.substring(1) : selecteur) +
      " " +
      module;
  }

  const listeTiers = (await listeTiersPromise).filter(
    (tiers) => tiers[role] && (actifs ? tiers.actif : true)
  );

  // Lors de la saisie, remise à zéro de l'état
  inputUser.addEventListener("input", (e) => {
    inputUser.setCustomValidity("");
    inputValue.value = "";
  });

  const options = {
    list: listeTiers,
    sort: false,
    multiple,
    duplicates: false,
    context,
    separate: true,
    data: function (tiers, input) {
      return {
        label: `${tiers.nom_court} - ${tiers.ville}`,
        value: `${tiers.id}`,
        original: tiers,
      };
    },
  };

  // Sélection avec la table des tiers filtrée
  const awesompleteInstance = tags
    ? new AwesompleteWithTags(inputUser, options, callback)
    : new Awesomplete(inputUser, options, callback);

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

    document.addEventListener("filtre-supprime", () =>
      awesompleteInstance.clearTags()
    );
  }

  // Remplissage avec les valeurs intiales
  try {
    if (valeurInitiale) {
      inputValue.value = valeurInitiale;

      valeurInitiale.split(",").forEach((tiers_id) => {
        if (!tiers_id) return;

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
  } catch (err) {
    console.error(selecteur, valeurInitiale, err);
  }

  // Vérification que la saisie est valide
  inputUser.addEventListener("blur", (e) => {
    // Remplissage correct des champs si la saisie est valide
    const tiers = listeTiers.find(
      (tiers) => tiers.nom_court.toLowerCase() === inputUser.value.toLowerCase()
    );
    if (tiers) {
      inputUser.value = `${tiers.nom_court} - ${tiers.ville}`;
      inputValue.value = tiers.id;
    }

    // Si saisie invalide, notification + champ invalide
    if (inputValue.value === "" && inputUser.value !== "") {
      const message = `"${inputUser.value}" n'est pas connu comme ${role_affichage}`;
      Notiflix.Notify.failure(message);
      inputUser.setCustomValidity(message);
    }
  });
}
