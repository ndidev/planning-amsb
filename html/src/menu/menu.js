import Notiflix from "notiflix";

import { env } from "@app/utils";
import { UserRoles, supprimerElementsNonAutorises } from "@app/auth";

/* Menu (toutes rubriques) */

const MENU = document.getElementById("menu");
const NAV = MENU.querySelector("nav");

/* Si l'on est sur le module bois, charger les RDV rapides */
{
  const rubrique = window.location.pathname.split("/")[1];
  const roles = JSON.parse(localStorage.getItem("roles") || "{}");

  // Si l'on est sur le module bois, charger les RDV rapides
  if (rubrique === "bois" && roles[rubrique] >= UserRoles.EDIT) {
    import("./menu-rdv-rapides-bois");
  }
}

/**
 * Utilisation de sessionStorage pour enregistrer l'affichage du menu.
 * Si sessionStorage absent, affichage du menu.
 */
// Au chargement de la page
{
  const affichageMenu = JSON.parse(sessionStorage.getItem("menu") || "true");
  NAV.style.display = affichageMenu ? "block" : "none";
}

// Au clic (fermeture du menu si menu ouvert, et vice-versa)
document.getElementById("menu-toggle").addEventListener("click", function () {
  const affichageMenu = JSON.parse(sessionStorage.getItem("menu") || "true");
  NAV.style.display = !affichageMenu ? "block" : "none";
  sessionStorage.setItem("menu", JSON.stringify(!affichageMenu));
});

/* Liens vers pages "archives" */
{
  const liens = NAV.querySelectorAll("a");
  for (const lien of liens) {
    if (lien.hasAttribute("archives")) {
      lien.href += "?archives";
    }
  }
}

/* Suppression des liens non autorisés */
supprimerElementsNonAutorises(MENU);

/* UserFooter */
document.querySelector(".UserFooter a").textContent =
  localStorage.getItem("user-nom");

/* Déconnexion */
document.querySelector(".LogoutButton").onclick = async () => {
  const url = new URL(env.auth);
  url.pathname += "logout";

  try {
    const reponse = await fetch(url);

    if (!reponse.ok) {
      throw new Error(`${reponse.status}, ${reponse.statusText}`);
    }

    localStorage.removeItem("user-nom");
    localStorage.removeItem("roles");
    location.pathname = env.prefix + "/index.html";
  } catch (error) {
    Notiflix.Notify.failure(error.message);
  }
};
