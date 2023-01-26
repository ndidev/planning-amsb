<!-- 
  @component
  
  Ligne de configuration d'un PDF.

  Usage :
  ```tsx
  <LigneConfigPDF configPdf: ConfigPDF={configPdf}>
  ```
 -->
<script lang="ts">
  import { onMount } from "svelte";

  import { MaterialButton } from "@app/components";

  import { configsPdf } from "@app/stores";

  import Notiflix from "notiflix";
  import autosize from "autosize";

  import { env, DateUtils } from "@app/utils";
  import { awesompleteTiers } from "../../lib/awesompleteTiers";

  export let configPdf: ConfigPDF;
  let configPdfInitial: ConfigPDF = structuredClone(configPdf);

  let isNew: boolean =
    typeof configPdf.id === "string" && configPdf.id.startsWith("new_");

  let modificationEnCours: boolean = isNew; // Modification en cours par défaut si nouveau compte uniquement;

  let ligne: HTMLLIElement;

  /**
   * Afficher les explications pour la liste d'e-mails.
   */
  function afficherExplicationsEmails() {
    Notiflix.Report.info(
      "Liste e-mails",
      "Une adresse par ligne.<br />" +
        "La liste d'adresses ne peut pas être vide.<br />" +
        "Pour ne pas envoyer à une adresse sans la supprimer, la faire précéder d'un point d'exclamation \"!\".",
      "Fermer"
    );
  }

  /**
   * Visualiser le PDF.
   */
  function visualiserPdf() {
    if (!validerFormulaire(ligne)) return;

    const date_debut = DateUtils.jourOuvrePrecedent(
      new Date(),
      configPdf.jours_avant
    )
      .toLocaleDateString()
      .split("/")
      .reverse()
      .join("-");

    const date_fin = DateUtils.jourOuvreSuivant(
      new Date(),
      configPdf.jours_apres
    )
      .toLocaleDateString()
      .split("/")
      .reverse()
      .join("-");

    Notiflix.Confirm.show(
      "Visualiser le PDF",
      `<div class="pure-form pdf-form-notiflix">
          <div class="pdf-champ-notiflix">
            <label>Date début : <input type="date" class="date_debut" value="${date_debut}"></label>
          </div>
          <div class="pdf-champ-notiflix">
            <label>Date fin : <input type="date" class="date_fin" value="${date_fin}"></label>
          </div>
        </div>`,
      "Visualiser",
      "Annuler",
      async () => {
        const url = new URL(env.api);
        url.pathname += "config/pdf/visu";

        const params = {
          module: configPdf.module,
          fournisseur: configPdf.fournisseur.toString(),
          date_debut:
            document.querySelector<HTMLInputElement>(".date_debut").value,
          date_fin: document.querySelector<HTMLInputElement>(".date_fin").value,
        };

        url.search = new URLSearchParams(params).toString();

        try {
          Notiflix.Block.dots(`#${ligne.id}`, "Téléchargement en cours...");

          const reponse = await fetch(url);

          if (!reponse.ok) {
            throw new Error(`${reponse.status} : ${reponse.statusText}`);
          }

          const blob = await reponse.blob();
          const file = URL.createObjectURL(blob);
          const filename = "planning.pdf";
          const link = document.createElement("a");
          link.href = file;
          link.download = filename;
          link.click();
        } catch (err) {
          Notiflix.Notify.failure(err.message);
        } finally {
          Notiflix.Block.remove(`#${ligne.id}`);
        }
      },
      null,
      {
        titleColor: "#32c682",
        okButtonColor: "#f8f8f8",
        okButtonBackground: "#32c682",
        cancelButtonColor: "#f8f8f8",
        cancelButtonBackground: "#a9a9a9",
      }
    );
  }

  /**
   * Envoyer le PDF par e-mail.
   */
  function envoyerPdf() {
    if (!validerFormulaire(ligne)) return;

    const date_debut = DateUtils.jourOuvrePrecedent(
      new Date(),
      configPdf.jours_avant
    )
      .toLocaleDateString()
      .split("/")
      .reverse()
      .join("-");

    const date_fin = DateUtils.jourOuvreSuivant(
      new Date(),
      configPdf.jours_apres
    )
      .toLocaleDateString()
      .split("/")
      .reverse()
      .join("-");

    Notiflix.Confirm.merge({
      titleColor: "#32c682",
      okButtonColor: "#f8f8f8",
      okButtonBackground: "#32c682",
      cancelButtonColor: "#f8f8f8",
      cancelButtonBackground: "#a9a9a9",
    });

    Notiflix.Confirm.show(
      "Envoyer le PDF",
      `<div class="pure-form pdf-form-notiflix">
          <div class="pdf-champ-notiflix">
            <label>Date début : <input type="date" class="date_debut" value="${date_debut}"></label>
          </div>
          <div class="pdf-champ-notiflix">
            <label>Date fin : <input type="date" class="date_fin" value="${date_fin}"></label>
          </div>
        </div>`,
      "Envoyer",
      "Annuler",
      async () => {
        const url = new URL(env.api);
        url.pathname += "config/pdf/envoi";

        const data = {
          module: configPdf.module,
          fournisseur: configPdf.fournisseur.toString(),
          date_debut:
            document.querySelector<HTMLInputElement>(".date_debut").value,
          date_fin: document.querySelector<HTMLInputElement>(".date_fin").value,
          liste_emails: configPdf.liste_emails,
        };

        const formData = new FormData();
        for (const [key, value] of Object.entries(data)) {
          formData.append(key, value);
        }

        try {
          Notiflix.Block.dots(`#${ligne.id}`, "Envoi en cours...");

          const reponse = await fetch(url, {
            method: "POST",
            body: formData,
          });

          if (!reponse.ok) {
            throw new Error(`${reponse.status} : ${reponse.statusText}`);
          }

          const resultat = await reponse.json();

          if (resultat.statut === "succes") {
            Notiflix.Notify.success("Le PDF a bien été envoyé");
          }

          if (resultat.statut === "echec") {
            throw new Error("Échec d'envoi du PDF");
          }
        } catch (err) {
          Notiflix.Notify.failure(err.message);
        } finally {
          Notiflix.Block.remove(`#${ligne.id}`);
        }
      }
    );
  }

  /**
   * Valider les modifications.
   */
  async function validerModification() {
    if (!validerFormulaire(ligne)) return;

    const url = new URL(env.api);
    url.pathname += `config/pdf/${configPdf.id}`;

    try {
      Notiflix.Block.dots(`#${ligne.id}`, "Modification en cours...");

      const reponse = await fetch(url, {
        method: "PUT",
        body: JSON.stringify(configPdf),
      });

      if (!reponse.ok) {
        throw new Error(`${reponse.status}, ${reponse.statusText}`);
      }

      Notiflix.Notify.success("La configuration a été modifiée");
      modificationEnCours = false;
      configPdfInitial = structuredClone(await reponse.json());
    } catch (erreur) {
      Notiflix.Notify.failure(erreur.message);
    } finally {
      Notiflix.Block.remove(`#${ligne.id}`);
    }
  }

  /**
   * Annuler les modifications.
   */
  function annulerModification() {
    configPdf = structuredClone(configPdfInitial);
    modificationEnCours = false;
    ligne.querySelectorAll("input").forEach((input) => {
      input.setCustomValidity("");
    });
  }

  /**
   * Valider l'ajout.
   */
  async function validerAjout() {
    if (!validerFormulaire(ligne)) return;

    const url = new URL(env.api);
    url.pathname += `config/pdf`;

    try {
      Notiflix.Block.dots(`#${ligne.id}`, "Ajout en cours...");

      const tempUid = configPdf.id;

      const reponse = await fetch(url, {
        method: "POST",
        body: JSON.stringify(configPdf),
      });

      if (!reponse.ok) {
        throw new Error(`${reponse.status}, ${reponse.statusText}`);
      }

      configPdf = await reponse.json();

      // Mise à jour du store
      configsPdf.update((configs) => {
        configs[configPdf.module].push(configPdf);
        configs[configPdf.module] = configs[configPdf.module].filter(
          (ligne) => ligne.id !== tempUid
        );
        return configs;
      });

      Notiflix.Notify.success("La ligne de configuration a été ajoutée");
    } catch (erreur) {
      Notiflix.Notify.failure(erreur.message);
    } finally {
      Notiflix.Block.remove(`#${ligne.id}`);
    }
  }

  /**
   * Annuler l'ajout.
   */
  function annulerAjout() {
    configsPdf.update((configs) => {
      configs[configPdf.module] = configs[configPdf.module].filter(
        (config) => config.id !== configPdf.id
      );
      return configs;
    });
  }

  /**
   * Supprimer une ligne.
   */
  function supprimerLigne() {
    // Demande de confirmation
    Notiflix.Confirm.show(
      "Suppression configuration",
      `Voulez-vous vraiment supprimer la configuration ?`,
      "Supprimer",
      "Annuler",
      async function () {
        const url = new URL(env.api);
        url.pathname += `config/pdf/${configPdf.id}`;

        try {
          Notiflix.Block.dots(`#${ligne.id}`, "Suppression en cours...");

          const reponse = await fetch(url, {
            method: "DELETE",
          });

          if (!reponse.ok) {
            throw new Error(`${reponse.status}, ${reponse.statusText}`);
          }

          Notiflix.Notify.success("La configuration a été supprimée");

          // Mise à jour du store
          configsPdf.update((configs) => {
            configs[configPdf.module] = configs[configPdf.module].filter(
              (ligne) => ligne.id !== configPdf.id
            );
            return configs;
          });
        } catch (erreur) {
          Notiflix.Notify.failure(erreur.message);
        } finally {
          Notiflix.Block.remove(`#${ligne.id}`);
        }
      },
      null,
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
   * Valider le formulaire.
   *
   * Renvoie `true` si formulaire valide, `false` sinon.
   * Affiche un message sur les champs invalides.
   *
   * @param {HTMLElement} context
   *
   * @returns {boolean}
   */
  function validerFormulaire(context) {
    const inputs = context.querySelectorAll("input, select");

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
        "Certains champs sont invalides : " + champs_invalides.join(", ")
      );
    }

    return valide;
  }

  onMount(() => {
    ligne.id = "config_pdf_" + configPdf.id;

    // Si changement d'une ligne, activation de la classe "modificationEnCours"
    ligne
      .querySelectorAll<HTMLInputElement | HTMLTextAreaElement>(
        "input, textarea"
      )
      .forEach((input) => {
        input.onchange = () => {
          modificationEnCours = true;
          console.log("change", input);
        };
        input.oninput = () => (modificationEnCours = true);
      });

    autosize(ligne.querySelector("textarea"));

    awesompleteTiers(
      ".fournisseur",
      {
        role: `${configPdf.module}_fournisseur`,
        role_affichage: `fournisseur ${configPdf.module}`,
        context: ligne,
      },
      () => {
        // Changement manuel car les valeurs ne sont pas changées automatiquement
        configPdf.fournisseur = parseInt(
          ligne.querySelector<HTMLInputElement>(".fournisseur_value").value
        );
        configPdf.fournisseur_nom =
          ligne.querySelector<HTMLInputElement>(".fournisseur_user").value;
      }
    );
  });
</script>

<!-- <li class="ligne pure-form" class:modificationEnCours bind:this={ligne}> -->
<li class="ligne pure-form" class:modificationEnCours bind:this={ligne}>
  <!-- Fournisseur -->
  <div class="champ pure-u-1 pure-u-lg-5-24">
    <div class="pure-control-group">
      <label for={"founisseur_user_" + configPdf.id}>Fournisseur</label>
      <input
        type="text"
        class="fournisseur_user"
        id={"founisseur_user_" + configPdf.id}
        bind:value={configPdf.fournisseur_nom}
        data-nom="Fournisseur"
        required
      />
      <input
        hidden
        class="fournisseur_value"
        bind:value={configPdf.fournisseur}
        required
      />
    </div>
  </div>
  <!-- Jours avant -->
  <div class="champ pure-u-1 pure-u-lg-2-24">
    <label
      >Jours avant
      <input
        type="number"
        min="0"
        class="jours_avant"
        bind:value={configPdf.jours_avant}
        required
      />
    </label>
  </div>
  <!-- Jours après -->
  <div class="champ pure-u-1 pure-u-lg-2-24">
    <label
      >Jours après
      <input
        type="number"
        min="0"
        class="jours_apres"
        bind:value={configPdf.jours_apres}
        required
      />
    </label>
  </div>
  <!-- Envoi automatique -->
  <div class="champ pure-u-1 pure-u-lg-2-24">
    <label class="pure-checkbox"
      >Envoi automatique
      <input
        type="checkbox"
        class="envoi_auto"
        bind:checked={configPdf.envoi_auto}
      />
    </label>
  </div>
  <!-- Liste e-mails -->
  <div class="champ pure-u-1 pure-u-lg-9-24">
    <label for={"liste_emails_" + configPdf.id}
      >Liste e-mails <MaterialButton
        icon="help"
        title="Afficher les explications"
        on:click={afficherExplicationsEmails}
      /></label
    >
    <textarea
      class="liste_emails"
      id={"liste_emails_" + configPdf.id}
      bind:value={configPdf.liste_emails}
      rows="3"
    />
  </div>
  <!-- Boutons -->
  <span class="actions">
    <MaterialButton
      icon="visibility"
      title="Visualiser le PDF"
      on:click={visualiserPdf}
    />
    <MaterialButton icon="send" title="Envoyer le PDF" on:click={envoyerPdf} />
    <MaterialButton icon="delete" title="Supprimer" on:click={supprimerLigne} />
  </span>
  <span class="valider-annuler">
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
  </span>
</li>

<style>
  .ligne .champ {
    display: flex;
    flex-direction: column;
    margin-left: 1%;
  }

  .jours_avant,
  .jours_apres {
    width: 80%;
  }

  textarea {
    padding: 5px;
  }

  /* Dates dans Notiflix */
  :global(.pdf-form-notiflix) {
    text-align: right;
  }

  :global(.pdf-champ-notiflix) {
    margin-bottom: 5px;
    margin-right: 30px;
  }

  @media screen and (max-width: 480px) {
    .liste_emails {
      margin-top: 5px;
    }
  }
</style>
