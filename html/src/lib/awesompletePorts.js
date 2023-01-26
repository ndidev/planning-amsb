import env from "./environment.js";
import { Awesomplete } from "./Awesomplete.js";

/**
 * Récupération de la liste des ports
 */
const ports = new Promise(async (resolve, reject) => {
  const url = new URL(env.api);
  url.pathname += "ports";

  try {
    const reponse = await fetch(url);

    if (!reponse.ok) {
      throw new Error(`${reponse.status} : ${reponse.statusText}`);
    }

    const ports = await reponse.json();
    resolve(ports);
  } catch (err) {
    reject(err.message);
  }
});

/**
 * Awesomplete ports
 *
 * @param {string}      selector Sélecteur CSS
 * @param {HTMLElement} context  Contexte de l'élément à sélectionner
 */
export async function awesompletePorts(selector, context = document) {
  // Inputs
  const input_user = context.querySelector(`${selector}_user`);
  const input_value = context.querySelector(`${selector}_value`);

  // Valeur transmise = valeur entrée par l'utilisateur si aucune correspondance
  // dans la table des ports
  input_user.addEventListener("input", (e) => {
    input_value.value = input_user.value;
  });

  // Sélection avec la table des ports
  new Awesomplete(input_user, {
    list: await ports,
    sort: false,
    data: function (port, input) {
      return { label: port.nom, value: port.locode };
    },
    filter: function (item, input) {
      return RegExp(Awesomplete.regExpEscape(input.trim()), "i").test(
        item.label + " " + item.value
      );
    },
    item: function (item, input, item_id) {
      return Awesomplete.ITEM(
        { label: `[${item.value}] ${item.label}`, value: item.value },
        input,
        item_id
      );
    },
    replace: function (item) {
      input_user.value =
        item.value === "ZZUKN"
          ? item.label
          : `${item.label}, ${item.value.substr(0, 2)}`;
      input_value.value = item.value || input_user.value;
    },
  });
}
