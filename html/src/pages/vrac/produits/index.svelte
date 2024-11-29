<!-- routify:options title="Planning AMSB - Produits vrac" -->
<script lang="ts">
  import { goto } from "@roxi/routify";

  import { Label, Input, Select, Helper } from "flowbite-svelte";
  import Notiflix from "notiflix";

  import { PageHeading, LucideButton, BoutonAction } from "@app/components";
  import { LigneQualite } from "./components";

  import { vracProduits } from "@app/stores";
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

<!-- routify:options guard="vrac/edit" -->

<main class="mx-auto w-10/12 lg:w-1/3">
  <PageHeading>Produit</PageHeading>

  <form class="flex flex-col gap-4 mb-4">
    <!-- Liste déroulante -->
    <div>
      <Label />
      <Select id="id" name="id" bind:value={selected} placeholder="">
        <option value="" selected>Nouveau...</option>
        {#if $vracProduits}
          {#each [...$vracProduits.values()].sort( (a, b) => a.nom.localeCompare(b.nom) ) as produit}
            <option value={produit.id}>{produit.nom}</option>
          {/each}
        {/if}
      </Select>
    </div>

    <!-- Nom produit -->
    <div>
      <Label for="nom">Nom Produit</Label>
      <Input
        type="text"
        id="nom"
        name="nom"
        placeholder="Nom du produit"
        maxlength={20}
        data-nom="Nom du produit"
        bind:value={produit.nom}
        required
      />
      <Helper>20 caractères maximum</Helper>
    </div>

    <!-- Couleur produit -->
    <div>
      <Label for="couleur">Couleur</Label>
      <Input
        type="color"
        id="couleur"
        name="couleur"
        bind:value={produit.couleur}
        class="min-h-10 w-full lg:w-40 p-1"
        required
      />
    </div>

    <!-- Unité -->
    <div>
      <Label for="unite">Unité</Label>
      <Input
        type="text"
        id="unite"
        name="unite"
        maxlength={10}
        data-nom="Unité"
        bind:value={produit.unite}
      />
      <Helper>10 caractères maximum</Helper>
    </div>

    <!-- Qualités -->
    <div>
      <fieldset>
        <legend>
          Qualités
          <LucideButton
            preset="add"
            title="Ajouter une qualité"
            on:click={ajouterQualite}
          />
        </legend>
        <div class="input">
          <ul id="qualites" class="m-0 p-0 list-none">
            <!-- Liste des qualités dynamique en fonction du produit choisi -->
            {#each produit.qualites as qualite}
              <LigneQualite {qualite} {supprimerQualite} />
            {/each}
          </ul>
          <div />
        </div>
      </fieldset>
    </div>
  </form>

  <!-- Validation/Annulation/Suppression -->
  <div class="text-center">
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
