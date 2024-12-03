<!-- routify:options title="Planning AMSB - RDV bois" -->
<script lang="ts">
  import { getContext } from "svelte";
  import { params, goto, redirect } from "@roxi/routify";

  import { Label, Input, Checkbox, Toggle, Textarea } from "flowbite-svelte";
  import {
    CircleHelpIcon,
    SparklesIcon,
    TriangleAlertIcon,
  } from "lucide-svelte";
  import Notiflix from "notiflix";

  import {
    PageHeading,
    Svelecte,
    LucideButton,
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
    commande_prete: false,
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
   * @param time
   * @param type
   */
  function addSeconds(time: string, type: "arrivee" | "depart"): string | null {
    if (!time) {
      return null;
    }

    const appointmentTime = (rdv["heure_" + type] as string) || "";

    if (time === appointmentTime.substring(0, 5)) {
      // Si l'heure n'a pas changé, conserver les secondes
      return appointmentTime;
    } else {
      // Si l'heure a changé, mettre les secondes à zéro
      return time + ":00";
    }
  }

  /**
   * Remplissage automatique de la livraison
   * lors de la saisie du client
   * si le champ livraison est vide
   */
  function autoFillDeliveryPlace() {
    if (rdv.client !== null && rdv.livraison === null) {
      rdv.livraison = rdv.client;
    }
  }

  /**
   * Vérification du numéro BL directement pour éviter doublon
   */
  async function checkDeliveryNoteNumber() {
    try {
      if (numero_bl === rdv.numero_bl || numero_bl === "") {
        rdv.numero_bl = numero_bl;
        return;
      }

      const isDeliveryNoteNumberAvailable: boolean = await fetcher(
        `bois/check-delivery-note-available`,
        {
          searchParams: {
            supplierId: rdv.fournisseur?.toString(),
            deliveryNoteNumber: numero_bl,
            currentAppointmentId: rdv.id?.toString(),
          },
        }
      );

      if (isDeliveryNoteNumberAvailable) {
        rdv.numero_bl = numero_bl;
      } else {
        const supplierName = $tiers.get(rdv.fournisseur)?.nom_court;

        Notiflix.Report.failure(
          "Erreur",
          `Le numéro BL ${numero_bl} est déjà utilisé pour ${supplierName}.`,
          "OK"
        );

        numero_bl = rdv.numero_bl;
      }
    } catch (err: unknown) {
      const error = err as HTTP.Error | Error;
      Notiflix.Notify.failure(error.message);
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
        "bois/suggestions-transporteurs",
        {
          searchParams: {
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

      let message = "";

      if (suggestions.transporteurs.length > 0) {
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

        message = ul.outerHTML;
      } else {
        message = "Aucun transport similaire n'a été effectué précédemment.";
      }

      Notiflix.Report.info("Suggestions de transporteurs", message, "Fermer", {
        messageMaxLength: Infinity,
        width: "min(400px, 95%)",
        info: {
          backOverlayColor: "hsla(200, 100%, 20%, 0.1)",
        },
      });
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
      rdv.heure_arrivee = addSeconds(heure_arrivee, "arrivee");
      rdv.heure_depart = addSeconds(heure_depart, "depart");

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
      rdv.heure_arrivee = addSeconds(heure_arrivee, "arrivee");
      rdv.heure_depart = addSeconds(heure_depart, "depart");

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

<main class="mx-auto w-10/12 lg:w-1/3">
  <PageHeading>Rendez-vous</PageHeading>

  {#if !rdv}
    <Chargement />
  {:else}
    <form
      class="flex flex-col gap-3 mb-4"
      bind:this={formulaire}
      use:preventFormSubmitOnEnterKeydown
    >
      <div class="flex flex-col lg:flex-row gap-3 lg:gap-8">
        <!-- Date -->
        <div>
          <Label for="date_rdv">Date (jj/mm/aaaa)</Label>
          <Input
            type="date"
            id="date_rdv"
            name="date_rdv"
            data-nom="Date"
            bind:value={rdv.date_rdv}
            required={!rdv.attente}
            class="w-full lg:w-max"
          />
        </div>

        <!-- En attente -->
        <div class="lg:self-center">
          <Toggle name="attente" bind:checked={rdv.attente}
            >En attente de confirmation</Toggle
          >
        </div>
      </div>

      <div class="flex flex-col lg:flex-row gap-3 lg:gap-8">
        <!-- Heure arrivée -->
        <div>
          <Label for="heure_arrivee">Heure arrivée (hh:mm)</Label>
          <Input
            type="time"
            name="heure_arrivee"
            id="heure_arrivee"
            bind:value={heure_arrivee}
            placeholder="hh:mm"
            class="w-full lg:w-max"
          />
        </div>

        <!-- Heure départ -->
        <div>
          <Label for="heure_depart">Heure départ (hh:mm)</Label>
          <Input
            type="time"
            name="heure_depart"
            id="heure_depart"
            bind:value={heure_depart}
            placeholder="hh:mm"
            class="w-full lg:w-max"
          />
        </div>
      </div>

      <!-- Fournisseur -->
      <div>
        <Label for="fournisseur"
          >Fournisseur
          {#if rdv.fournisseur && rdv.affreteur && rdv.fournisseur !== rdv.affreteur && $tiers?.get(rdv.affreteur)?.roles.bois_fournisseur}
            <span
              class="warning-fournisseur"
              title="Erreur possible : vérifier que le fournisseur et l'affréteur sont corrects"
              ><TriangleAlertIcon /></span
            >
          {/if}
        </Label>
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
      <div>
        <Label for="chargement">Chargement</Label>
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
      <div>
        <Label for="client">Client</Label>
        <Svelecte
          inputId="client"
          type="tiers"
          role="bois_client"
          bind:value={rdv.client}
          name="Client"
          on:change={autoFillDeliveryPlace}
          required
        />
      </div>

      <!-- Livraison -->
      <div>
        <Label for="livraison">Livraison</Label>
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
      <div>
        <Label for="transporteur"
          >Transporteur
          {#if rdv.affreteur === 1 || rdv.affreteur === null}
            <span>
              <LucideButton
                icon={SparklesIcon}
                title="Suggestions de transporteurs"
                on:click={afficherSuggestionsTransporteurs}
              />
            </span>
          {/if}
        </Label>
        <Svelecte
          inputId="transporteur"
          type="tiers"
          role="bois_transporteur"
          bind:value={rdv.transporteur}
          name="Transporteur"
        />
      </div>

      <!-- Affréteur -->
      <div>
        <Label for="affreteur"
          >Affréteur
          {#if rdv.fournisseur && rdv.affreteur && rdv.fournisseur !== rdv.affreteur && $tiers?.get(rdv.affreteur)?.roles.bois_fournisseur}
            <span
              class="warning-fournisseur"
              title="Erreur possible : vérifier que le fournisseur et l'affréteur sont corrects"
              ><TriangleAlertIcon /></span
            >
          {/if}</Label
        >
        <Svelecte
          inputId="affreteur"
          type="tiers"
          role="bois_affreteur"
          bind:value={rdv.affreteur}
          name="Affréteur"
        />
      </div>

      <!-- Commande prête -->
      <div>
        <Toggle name="commande_prete" bind:checked={rdv.commande_prete}
          >Commande prête</Toggle
        >
      </div>

      <!-- Confirmation d'affrètement -->
      <div>
        <Toggle
          name="confirmation_affretement"
          bind:checked={rdv.confirmation_affretement}
          disabled={$tiers?.get(rdv.affreteur)?.lie_agence === false ||
            !rdv.transporteur}>Confirmation d'affrètement</Toggle
        >
      </div>

      <!-- Numéro BL -->
      <div>
        <Label for="numero_bl">Numéro BL</Label>
        <Input
          type="text"
          name="numero_bl"
          id="numero_bl"
          bind:value={numero_bl}
          on:change={checkDeliveryNoteNumber}
        />
      </div>

      <!-- Commentaire public -->
      <div>
        <Label for="commentaire_public">Commentaire public</Label>
        <Textarea
          class="rdv_commentaire"
          name="commentaire_public"
          id="commentaire_public"
          rows={3}
          cols={30}
          bind:value={rdv.commentaire_public}
        />
      </div>

      <!-- Commentaire caché -->
      <div>
        <Label for="commentaire_cache"
          >Commentaire caché <LucideButton
            icon={CircleHelpIcon}
            on:click={afficherExplicationsCommentaireCache}
          /></Label
        >
        <Textarea
          class="rdv_commentaire"
          name="commentaire_cache"
          id="commentaire_cache"
          rows={3}
          cols={30}
          bind:value={rdv.commentaire_cache}
        />
      </div>
    </form>

    <!-- Validation/Annulation/Suppression -->
    <div class="text-center">
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
  .warning-fournisseur {
    color: red;
    cursor: help;
    animation: warning-animation 0.5s linear 0s 6 alternate;
    vertical-align: text-top;
  }

  @keyframes warning-animation {
    100% {
      color: white;
    }
  }

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
