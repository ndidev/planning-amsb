<!-- 
  @component
  
  Ligne de configuration d'un RDV rapide bois.

  Usage :
  ```tsx
  <LigneRdvRapideBois rdvRapide: RdvRapideBois={rdvRapide}>
  ```
 -->
<script lang="ts">
  import { onMount } from "svelte";

  import { AwesompleteTiers, MaterialButton } from "@app/components";

  import { rdvRapides } from "@app/stores";

  import Notiflix from "notiflix";

  import { env } from "@app/utils";

  export let rdvRapide: RdvRapideBois;
  let rdvRapideInitial: RdvRapideBois = structuredClone(rdvRapide);

  let isNew: boolean =
    typeof rdvRapide.id === "string" && rdvRapide.id.startsWith("new_");

  let modificationEnCours: boolean = isNew; // Modification en cours par défaut si nouveau compte uniquement;

  let ligne: HTMLLIElement;

  /**
   * Valider les modifications.
   */
  async function validerModification() {
    if (!validerFormulaire(ligne)) return;

    const url = new URL(env.api);
    url.pathname += `config/rdvrapides/${rdvRapide.id}`;

    try {
      Notiflix.Block.dots(`#${ligne.id}`, "Modification en cours...");

      const reponse = await fetch(url, {
        method: "PUT",
        body: JSON.stringify(rdvRapide),
      });

      if (!reponse.ok) {
        throw new Error(`${reponse.status}, ${reponse.statusText}`);
      }

      Notiflix.Notify.success("La configuration a été modifiée");
      modificationEnCours = false;
      rdvRapideInitial = await reponse.json();
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
    rdvRapide = structuredClone(rdvRapideInitial);
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
    url.pathname += `config/rdvrapides`;

    try {
      Notiflix.Block.dots(`#${ligne.id}`, "Ajout en cours...");

      const tempUid = rdvRapide.id;

      const reponse = await fetch(url, {
        method: "POST",
        body: JSON.stringify(rdvRapide),
      });

      if (!reponse.ok) {
        throw new Error(`${reponse.status}, ${reponse.statusText}`);
      }

      rdvRapide = await reponse.json();

      // Mise à jour du store
      rdvRapides.update((configs) => {
        configs[rdvRapide.module].push(rdvRapide);
        configs[rdvRapide.module] = configs[rdvRapide.module].filter(
          (ligne: RdvRapideBois) => ligne.id !== tempUid
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
    rdvRapides.update((configs) => {
      configs[rdvRapide.module] = configs[rdvRapide.module].filter(
        (config) => config.id !== rdvRapide.id
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
        url.pathname += `config/rdvrapides/${rdvRapide.id}`;

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
          rdvRapides.update((configs) => {
            configs[rdvRapide.module] = configs[rdvRapide.module].filter(
              (ligne) => ligne.id !== rdvRapide.id
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
    ligne.id = "config_rdvrapide_" + rdvRapide.id;

    // Si changement d'une ligne, activation de la classe "modificationEnCours"
    ligne
      .querySelectorAll<HTMLInputElement | HTMLTextAreaElement>(
        "input, textarea"
      )
      .forEach((input) => {
        input.onchange = () => {
          modificationEnCours = true;
        };
        input.oninput = () => (modificationEnCours = true);
      });
  });
</script>

<li class="ligne pure-form" bind:this={ligne} class:modificationEnCours>
  <div class="bloc pure-u-1 pure-u-lg-7-24">
    <!-- Fournisseur -->
    <div class="pure-control-group">
      <label for={"fournisseur_" + rdvRapide.id}>Fournisseur</label>
      <AwesompleteTiers
        id={rdvRapide.id}
        nom="Fournisseur"
        role="bois_fournisseur"
        roleAffichage="fournisseur bois"
        context={ligne}
        bind:value={rdvRapide.fournisseur}
        required
      />
    </div>

    <!-- Client -->
    <div class="pure-control-group">
      <label for={"client_" + rdvRapide.id}>Client</label>
      <AwesompleteTiers
        id={rdvRapide.id}
        nom="Client"
        role="bois_client"
        roleAffichage="client bois"
        context={ligne}
        bind:value={rdvRapide.client}
        required
      />
    </div>
  </div>

  <div class="bloc pure-u-1 pure-u-lg-7-24">
    <!-- Chargement -->
    <div class="pure-control-group">
      <label for={"chargement_" + rdvRapide.id}>Chargement</label>
      <AwesompleteTiers
        id={rdvRapide.id}
        nom="Chargement"
        role="bois_client"
        roleAffichage="lieu de chargement bois"
        context={ligne}
        bind:value={rdvRapide.chargement}
      />
    </div>

    <!-- Livraison -->
    <div class="pure-control-group">
      <label for={"livraison_" + rdvRapide.id}>Livraison</label>
      <AwesompleteTiers
        id={rdvRapide.id}
        nom="Livraison"
        role="bois_client"
        roleAffichage="lieu de livraison bois"
        context={ligne}
        bind:value={rdvRapide.livraison}
      />
    </div>
  </div>

  <div class="bloc pure-u-1 pure-u-lg-7-24">
    <!-- Transporteur -->
    <div class="pure-control-group">
      <label for={"transporteur_" + rdvRapide.id}>Transporteur</label>
      <AwesompleteTiers
        id={rdvRapide.id}
        nom="Transporteur"
        role="bois_transporteur"
        roleAffichage="transporteur bois"
        context={ligne}
        bind:value={rdvRapide.transporteur}
        required
      />
    </div>

    <!-- Affréteur -->
    <div class="pure-control-group">
      <label for={"affreteur_" + rdvRapide.id}>Affréteur</label>
      <AwesompleteTiers
        id={rdvRapide.id}
        nom="Affréteur"
        role="bois_affreteur"
        roleAffichage="affréteur bois"
        context={ligne}
        bind:value={rdvRapide.affreteur}
        required
      />
    </div>
  </div>

  <!-- Boutons -->
  <span class="actions">
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
  .ligne {
    text-align: right;
  }

  @media screen and (max-width: 480px) {
    .ligne {
      text-align: left;
    }
  }
</style>
