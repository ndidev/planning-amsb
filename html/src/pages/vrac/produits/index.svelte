<!-- routify:options title="Planning AMSB - Produits vrac" -->
<script lang="ts">
  import { goto } from "@roxi/routify";

  import Notiflix from "notiflix";

  import { MaterialButton, BoutonAction } from "@app/components";
  import { LigneQualite } from "./components";

  import { vracProduits, currentUser } from "@app/stores";
  import { notiflixOptions } from "@app/utils";

  import type { ProduitVrac, QualiteVrac } from "@app/types";

  let boutonAjouter: BoutonAction;
  let boutonModifier: BoutonAction;
  let boutonSupprimer: BoutonAction;

  const modeleProduit: ProduitVrac = {
    id: null,
    nom: "",
    couleur: "#000000",
    unite: "",
    qualites: [],
  };

  const modeleQualite: QualiteVrac = {
    id: null,
    produit: null,
    nom: "",
    couleur: "#000000",
  };

  /**
   * Id du produit séctionné.
   */
  let selected: ProduitVrac["id"];

  let produit: ProduitVrac;

  /**
   * Produit sélectionné.
   */
  $: getProduit(selected);

  /**
   *
   */
  function getProduit(id: ProduitVrac["id"]) {
    produit = structuredClone($vracProduits?.get(id) || modeleProduit);
  }

  /**
   * Ajouter une qualité.
   */
  function ajouterQualite() {
    produit.qualites = [...produit.qualites, structuredClone(modeleQualite)];
  }

  /**
   * Supprimer une qualité.
   */
  function supprimerQualite(qualiteASupprimer: QualiteVrac) {
    console.log(qualiteASupprimer);

    if (qualiteASupprimer.id !== null) {
      Notiflix.Confirm.show(
        "Suppression produit",
        `Voulez-vous vraiment supprimer la qualité <strong>${qualiteASupprimer.nom}</strong> ?<br />` +
          `Ceci supprimera les RDV associés.`,
        "Supprimer",
        "Annuler",
        _supprimerQualite,
        null,
        notiflixOptions.themes.red
      );
    } else {
      _supprimerQualite();
    }

    function _supprimerQualite() {
      produit.qualites = produit.qualites.filter(
        (qualite) => qualite !== qualiteASupprimer
      );
    }
  }

  /**
   * Validation formulaire
   */
  function validerFormulaire() {
    const formulaire = document.querySelector("form");
    const inputs = formulaire.querySelectorAll("input");
    const champs_invalides = [];

    let valide = true;
    let i = 1;

    for (const input of inputs) {
      if (!input.checkValidity()) {
        valide = false;
        if (input.dataset.nom) {
          let nom_champ = input.dataset.nom;
          if (nom_champ === "Nom de la qualité") {
            nom_champ += " " + i;
            i++;
          }
          champs_invalides.push(nom_champ);
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
   * Nouveau produit
   */
  async function ajouterProduit() {
    if (!validerFormulaire()) return;

    boutonAjouter.$set({ block: true });

    try {
      produit = await vracProduits.create(produit);

      Notiflix.Notify.success("Le produit a été créé");

      selected = produit.id;
    } catch (erreur) {
      Notiflix.Notify.failure(erreur.message);
    } finally {
      boutonAjouter.$set({ block: false });
    }
  }

  /**
   * Modifier produit
   */
  async function modifierProduit() {
    if (!validerFormulaire()) return;

    boutonModifier.$set({ block: true });

    try {
      produit = structuredClone(await vracProduits.update(produit));

      Notiflix.Notify.success("Le produit a été modifié");
    } catch (erreur) {
      Notiflix.Notify.failure(erreur.message);
    } finally {
      boutonModifier.$set({ block: false });
    }
  }

  /**
   * Suppression produit
   */
  function supprimerProduit() {
    boutonSupprimer.$set({ block: true });

    Notiflix.Confirm.show(
      "Suppression produit",
      `Voulez-vous vraiment supprimer le produit <b>${produit.nom}</b> ?<br />` +
        `Ceci supprimera les RDV associés.`,
      "Supprimer",
      "Annuler",
      async function () {
        try {
          await vracProduits.delete(produit.id);

          Notiflix.Notify.success("Le produit a été supprimé");

          selected = null;
        } catch (erreur) {
          Notiflix.Notify.failure(erreur.message);
        }

        boutonSupprimer.$set({ block: false });
      },
      function () {
        boutonSupprimer.$set({ block: false });
      },
      notiflixOptions.themes.red
    );
  }
</script>

{#if $currentUser.canEdit("vrac")}
  <main class="formulaire">
    <h1>Produit</h1>

    <form class="pure-form pure-form-aligned">
      <!-- Liste déroulante -->
      <div class="pure-control-group">
        <label />
        <select id="id" name="id" bind:value={selected}>
          <option value="">Nouveau...</option>
          {#if $vracProduits}
            {#each [...$vracProduits.values()].sort( (a, b) => a.nom.localeCompare(b.nom) ) as produit}
              <option value={produit.id}>{produit.nom}</option>
            {/each}
          {/if}
        </select>
      </div>

      <!-- Nom produit -->
      <div class="pure-control-group">
        <label for="nom">Nom Produit</label>
        <input
          type="text"
          id="nom"
          name="nom"
          placeholder="Nom du produit"
          maxlength="255"
          data-nom="Nom du produit"
          bind:value={produit.nom}
          required
        />
      </div>

      <!-- Couleur produit -->
      <div class="pure-control-group">
        <label for="couleur">Couleur</label>
        <input
          type="color"
          id="couleur"
          name="couleur"
          bind:value={produit.couleur}
          class="couleur"
          required
        />
      </div>

      <!-- Unité -->
      <div class="pure-control-group">
        <label for="unite">Unité</label>
        <input
          type="text"
          id="unite"
          name="unite"
          maxlength="10"
          data-nom="Unité"
          bind:value={produit.unite}
        />
      </div>

      <!-- Qualités -->
      <div class="pure-controls">
        <fieldset>
          <legend>
            Qualités
            <MaterialButton
              icon="add"
              title="Ajouter une qualité"
              on:click={ajouterQualite}
            />
          </legend>
          <!-- <div class="pure-control-group"> -->
          <div class="input">
            <ul id="qualites">
              <!-- Liste des qualités dynamique en fonction du produit choisi -->
              {#each produit.qualites as qualite}
                <LigneQualite {qualite} {supprimerQualite} />
              {/each}
            </ul>
            <div />
          </div>
          <!-- </div> -->
        </fieldset>
      </div>
    </form>

    <!-- Validation/Annulation/Suppression -->
    <div class="boutons">
      {#if !produit.id}
        <!-- Bouton "Ajouter" -->
        <BoutonAction
          preset="ajouter"
          on:click={ajouterProduit}
          bind:this={boutonAjouter}
        />
      {/if}

      {#if produit.id}
        <!-- Bouton "Modifier" -->
        <BoutonAction
          preset="modifier"
          on:click={modifierProduit}
          bind:this={boutonModifier}
        />
        <!-- Bouton "Supprimer" -->
        <BoutonAction
          preset="supprimer"
          on:click={supprimerProduit}
          bind:this={boutonSupprimer}
        />
      {/if}

      <!-- Bouton "Annuler" -->
      <BoutonAction preset="annuler" on:click={() => $goto("../")} />
    </div>
  </main>
{:else}
  {$goto("/")}
{/if}

<style>
  main {
    width: 90%;
    margin: auto;
  }

  #qualites {
    margin: 0;
    padding: 0;
    list-style-type: none;
  }
</style>
