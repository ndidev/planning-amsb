<!-- routify:options title="Planning AMSB - RDV bois" -->
<script lang="ts">
  import { getContext } from "svelte";
  import { params, goto, redirect } from "@roxi/routify";

  import Notiflix from "notiflix";

  import {
    Svelecte,
    MaterialButton,
    Chargement,
    BoutonAction,
  } from "@app/components";

  import {
    fetcher,
    notiflixOptions,
    validerFormulaire,
    preventFormSubmitOnEnterKeydown,
    locale,
  } from "@app/utils";

  import { HTTP } from "@app/errors";

  import type { RdvBois, Stores } from "@app/types";

  const { boisRdvs, tiers } = getContext<Stores>("stores");

  let formulaire: HTMLFormElement;
  let boutonAjouter: BoutonAction;
  let boutonModifier: BoutonAction;
  let boutonSupprimer: BoutonAction;

  const nouveauRdv: RdvBois = {
    id: null,
    attente: false,
    date_rdv: new Date()
      .toLocaleDateString(locale)
      .split("/")
      .reverse()
      .join("-"),
    heure_arrivee: null,
    heure_depart: null,
    fournisseur: null,
    chargement: 1, // AMSB
    client: null,
    livraison: null,
    transporteur: null,
    affreteur: null,
    confirmation_affretement: false,
    numero_bl: "",
    commentaire_public: "",
    commentaire_cache: "",
  };

  /**
   * Identifiant du RDV.
   */
  let id: RdvBois["id"] = parseInt($params.id);

  let isNew = $params.id === "new";
  const copie = parseInt($params.copie);

  let rdv: RdvBois = { ...nouveauRdv };
  let numero_bl = rdv?.numero_bl;
  let heure_arrivee = rdv?.heure_arrivee?.substring(0, 5) ?? "";
  let heure_depart = rdv?.heure_depart?.substring(0, 5) ?? "";

  (async () => {
    try {
      if (id || copie) {
        rdv = structuredClone(await boisRdvs.get(id || copie));
        if (!rdv) throw new Error();
        numero_bl = rdv.numero_bl;
        heure_arrivee = rdv.heure_arrivee?.substring(0, 5) ?? "";
        heure_depart = rdv.heure_depart?.substring(0, 5) ?? "";
      }
    } catch (error) {
      $redirect("./new");
    }
  })();

  /**
   * Ajouter les secondes à l'heure d'arrivée/départ.
   *
   * Pour ne pas afficher les secondes dans l'input,
   * l'heure du formulaire est tronquée (hh:mm).
   *
   * Pour ne pas perdre les informations sur les secondes,
   * celles-ci sont rajoutée lors de la soumission du formulaire.
   *
   * @param heure
   * @param type
   */
  function ajouterSecondes(
    heure: string,
    type: "arrivee" | "depart"
  ): string | null {
    if (!heure) {
      return null;
    }

    const heure_rdv = (rdv["heure_" + type] as string) || "";

    if (heure === heure_rdv.substring(0, 5)) {
      // Si l'heure n'a pas changé, conserver les secondes
      return heure_rdv;
    } else {
      // Si l'heure a changé, mettre les secondes à zéro
      return heure + ":00";
    }
  }

  /**
   * Remplissage automatique de la livraison
   * lors de la saisie du client
   * si le champ livraison est vide
   */
  function remplirLivraisonAuto() {
    if (rdv.client !== null && rdv.livraison === null) {
      rdv.livraison = rdv.client;
    }
  }

  /**
   * Vérification du numéro BL directement pour éviter doublon
   */
  async function verifierNumeroBL() {
    try {
      await fetcher(`bois/rdvs/${rdv.id}`, {
        requestInit: {
          method: "PATCH",
          body: JSON.stringify({
            numero_bl,
            dry_run: "true",
          }),
        },
      });

      rdv.numero_bl = numero_bl;
    } catch (err: unknown) {
      const error = err as HTTP.Error | Error;
      if (error instanceof HTTP.BadRequest) {
        Notiflix.Report.failure("Erreur", error.message, "OK");
      } else {
        Notiflix.Notify.failure(error.message);
      }
      numero_bl = rdv.numero_bl;
    }
  }

  /**
   * Afficher des suggestions de transporteurs.
   */
  async function afficherSuggestionsTransporteurs() {
    if (!rdv.chargement || !rdv.livraison) {
      Notiflix.Notify.failure(
        "Le chargement et la livraison doivent être renseignés pour obtenir des suggestions"
      );
      return;
    }

    try {
      const suggestions: SuggestionsTransporteurs = await fetcher(
        "bois/suggestions_transporteurs",
        {
          params: {
            chargement: rdv.chargement.toString(),
            livraison: rdv.livraison.toString(),
          },
        }
      );

      type SuggestionsTransporteurs = {
        chargement: any;
        livraison: any;
        transporteurs: any[];
      };

      let ul = document.createElement("ul");
      ul.classList.add("suggestions");

      suggestions.transporteurs.forEach((transporteur) => {
        const li = document.createElement("li");
        li.classList.add("suggestion");

        const spanTransporteur = document.createElement("span");
        spanTransporteur.classList.add("suggestion-transporteur");
        spanTransporteur.textContent = transporteur.nom;

        const spanTelephone = document.createElement("span");
        spanTelephone.classList.add("suggestion-telephone");
        spanTelephone.textContent =
          transporteur.telephone || "Téléphone non renseigné";

        li.append(spanTransporteur, " - ", spanTelephone);
        ul.appendChild(li);
      });

      Notiflix.Report.info(
        "Suggestions de transporteurs",
        ul.outerHTML,
        "Fermer",
        {
          messageMaxLength: Infinity,
          width: "min(400px, 95%)",
          info: {
            backOverlayColor: "hsla(200, 100%, 20%, 0.1)",
          },
        }
      );
    } catch (erreur) {
      Notiflix.Notify.failure(erreur.message);
    }
  }

  /**
   * Afficher les explications pour le commentaire caché.
   */
  function afficherExplicationsCommentaireCache() {
    Notiflix.Report.info(
      "Commentaire caché",
      "Ce commentaire ne sera pas visible dans le planning envoyé au client." +
        "<br/>" +
        "Utile pour ajouter des informations sur les tarifs d'affrètement, l'état de préparation de la commande, etc.",
      "Fermer"
    );
  }

  /**
   * Créer le RDV.
   */
  async function ajouterRdv() {
    if (!validerFormulaire(formulaire)) return;

    boutonAjouter.$set({ disabled: true });

    try {
      rdv.heure_arrivee = ajouterSecondes(heure_arrivee, "arrivee");
      rdv.heure_depart = ajouterSecondes(heure_depart, "depart");

      await boisRdvs.create(rdv);

      Notiflix.Notify.success("Le RDV a été ajouté");
      $goto("./");
    } catch (erreur) {
      Notiflix.Notify.failure(erreur.message);
      console.error(erreur);
      boutonAjouter.$set({ disabled: false });
    }
  }

  /**
   * Modifier le RDV.
   */
  async function modifierRdv() {
    if (!validerFormulaire(formulaire)) return;

    boutonModifier.$set({ disabled: true });

    try {
      rdv.heure_arrivee = ajouterSecondes(heure_arrivee, "arrivee");
      rdv.heure_depart = ajouterSecondes(heure_depart, "depart");

      await boisRdvs.update(rdv);

      Notiflix.Notify.success("Le RDV a été modifié");
      $goto("./");
    } catch (erreur) {
      Notiflix.Notify.failure(erreur.message);
      console.error(erreur);
      boutonModifier.$set({ disabled: false });
    }
  }

  /**
   * Supprimer le RDV.
   */
  function supprimerRdv() {
    if (!id) return;

    boutonSupprimer.$set({ disabled: true });

    // Demande de confirmation
    Notiflix.Confirm.show(
      "Suppression RDV",
      `Voulez-vous vraiment supprimer le RDV ?`,
      "Supprimer",
      "Annuler",
      async function () {
        try {
          await boisRdvs.delete(id);

          Notiflix.Notify.success("Le RDV a été supprimé");
          $goto("./");
        } catch (erreur) {
          Notiflix.Notify.failure(erreur.message);
          console.error(erreur);
          boutonSupprimer.$set({ disabled: false });
        }
      },
      () => boutonSupprimer.$set({ disabled: false }),
      notiflixOptions.themes.red
    );
  }
</script>

<!-- routify:options param-is-page -->
<!-- routify:options guard="bois/edit" -->

<main class="formulaire">
  <h1>Rendez-vous</h1>

  {#if !rdv}
    <Chargement />
  {:else}
    <form
      class="pure-form pure-form-aligned"
      bind:this={formulaire}
      use:preventFormSubmitOnEnterKeydown
    >
      <!-- Date -->
      <div class="pure-control-group">
        <label for="date_rdv">Date (jj/mm/aaaa)</label>
        <input
          type="date"
          id="date_rdv"
          name="date_rdv"
          data-nom="Date"
          bind:value={rdv.date_rdv}
          required={!rdv.attente}
        />
      </div>

      <!-- En attente -->
      <div class="pure-control-group">
        <label for="attente">En attente de confirmation</label>
        <input
          type="checkbox"
          name="attente"
          id="attente"
          bind:checked={rdv.attente}
        />
      </div>

      <!-- Heure arrivée -->
      <div class="pure-control-group">
        <label for="heure_arrivee">Heure arrivée (hh:mm)</label>
        <input
          type="time"
          name="heure_arrivee"
          id="heure_arrivee"
          bind:value={heure_arrivee}
          placeholder="hh:mm"
        />
      </div>

      <!-- Heure départ -->
      <div class="pure-control-group">
        <label for="heure_depart">Heure départ (hh:mm)</label>
        <input
          type="time"
          name="heure_depart"
          id="heure_depart"
          bind:value={heure_depart}
          placeholder="hh:mm"
        />
      </div>

      <!-- Fournisseur -->
      <div class="pure-control-group">
        <label for="fournisseur">Fournisseur</label>
        <Svelecte
          inputId="fournisseur"
          type="tiers"
          role="bois_fournisseur"
          bind:value={rdv.fournisseur}
          name="Fournisseur"
          required
        />
      </div>

      <!-- Chargement -->
      <div class="pure-control-group">
        <label for="chargement">Chargement</label>
        <Svelecte
          inputId="chargement"
          type="tiers"
          role="bois_client"
          bind:value={rdv.chargement}
          name="Chargement"
          required
        />
      </div>

      <!-- Client -->
      <div class="pure-control-group">
        <label for="client">Client</label>
        <Svelecte
          inputId="client"
          type="tiers"
          role="bois_client"
          bind:value={rdv.client}
          name="Client"
          on:change={remplirLivraisonAuto}
          required
        />
      </div>

      <!-- Livraison -->
      <div class="pure-control-group">
        <label for="livraison">Livraison</label>
        <Svelecte
          inputId="livraison"
          type="tiers"
          role="bois_client"
          bind:value={rdv.livraison}
          name="Livraison"
          required
        />
      </div>

      <!-- Transporteur -->
      <div class="pure-control-group">
        <label for="transporteur"
          >Transporteur {#if rdv.affreteur === 1 || rdv.affreteur === null}
            <MaterialButton
              icon="tips_and_updates"
              title="Suggestions de transporteurs"
              on:click={afficherSuggestionsTransporteurs}
            />
          {/if}</label
        >
        <Svelecte
          inputId="transporteur"
          type="tiers"
          role="bois_transporteur"
          bind:value={rdv.transporteur}
          name="Transporteur"
        />
      </div>

      <!-- Affréteur -->
      <div class="pure-control-group">
        <label for="affreteur">Affréteur</label>
        <Svelecte
          inputId="affreteur"
          type="tiers"
          role="bois_affreteur"
          bind:value={rdv.affreteur}
          name="Affréteur"
        />
      </div>

      <!-- Confirmation d'affrètement -->
      <div class="pure-control-group">
        <label for="confirmation_affretement">Confirmation d'affrètement</label>
        <input
          type="checkbox"
          name="confirmation_affretement"
          id="confirmation_affretement"
          bind:checked={rdv.confirmation_affretement}
          disabled={$tiers?.get(rdv.affreteur)?.lie_agence === false ||
            !rdv.transporteur}
        />
      </div>

      <!-- Numéro BL -->
      <div class="pure-control-group">
        <label for="numero_bl">Numéro BL</label>
        <input
          type="text"
          name="numero_bl"
          id="numero_bl"
          bind:value={numero_bl}
          on:change={verifierNumeroBL}
        />
      </div>

      <!-- Commentaire public -->
      <div class="pure-control-group">
        <label for="commentaire_public">Commentaire public</label>
        <textarea
          class="rdv_commentaire"
          name="commentaire_public"
          id="commentaire_public"
          rows="3"
          cols="30"
          bind:value={rdv.commentaire_public}
        />
      </div>

      <!-- Commentaire caché -->
      <div class="pure-control-group">
        <label for="commentaire_cache"
          >Commentaire caché<br /><MaterialButton
            icon="help"
            on:click={afficherExplicationsCommentaireCache}
          /></label
        >
        <textarea
          class="rdv_commentaire"
          name="commentaire_cache"
          id="commentaire_cache"
          rows="3"
          cols="30"
          bind:value={rdv.commentaire_cache}
        />
      </div>
    </form>

    <!-- Validation/Annulation/Suppression -->
    <div class="boutons">
      {#if isNew}
        <!-- Bouton "Ajouter" -->
        <BoutonAction
          preset="ajouter"
          on:click={ajouterRdv}
          bind:this={boutonAjouter}
        />
      {:else}
        <!-- Bouton "Modifier" -->
        <BoutonAction
          preset="modifier"
          on:click={modifierRdv}
          bind:this={boutonModifier}
        />
        <!-- Bouton "Supprimer" -->
        <BoutonAction
          preset="supprimer"
          on:click={supprimerRdv}
          bind:this={boutonSupprimer}
        />
      {/if}

      <!-- Bouton "Annuler" -->
      <BoutonAction
        preset="annuler"
        on:click={() => {
          $goto("./");
        }}
      />
    </div>
  {/if}
</main>

<style>
  :global(.notiflix-report .suggestions) {
    padding-left: 5%;
  }

  :global(.notiflix-report .suggestion) {
    list-style-type: none;
    padding-top: 2px;
  }

  :global(.notiflix-report .suggestion-transporteur) {
    font-weight: bold;
  }
</style>
