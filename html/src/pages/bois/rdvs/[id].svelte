<!-- routify:options title="Planning AMSB - RDV bois" -->
<script lang="ts">
  import { getContext } from "svelte";
  import { params, goto, redirect } from "@roxi/routify";

  import { Label, Input, Toggle, Textarea } from "flowbite-svelte";
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

  let form: HTMLFormElement;
  let createButton: BoutonAction;
  let updateButton: BoutonAction;
  let deleteButton: BoutonAction;

  const newAppointment: RdvBois = {
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
    dispatch: [],
  };

  /**
   * Identifiant du RDV.
   */
  let id: RdvBois["id"] = parseInt($params.id);

  let isNew = $params.id === "new";
  const copie = parseInt($params.copie);

  let appointment: RdvBois = { ...newAppointment };
  let numero_bl = appointment?.numero_bl;
  let heure_arrivee = appointment?.heure_arrivee?.substring(0, 5) ?? "";
  let heure_depart = appointment?.heure_depart?.substring(0, 5) ?? "";

  (async () => {
    try {
      if (id || copie) {
        appointment = structuredClone(await boisRdvs.get(id || copie));
        if (!appointment) throw new Error();
        numero_bl = appointment.numero_bl;
        heure_arrivee = appointment.heure_arrivee?.substring(0, 5) ?? "";
        heure_depart = appointment.heure_depart?.substring(0, 5) ?? "";
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

    const appointmentTime = (appointment["heure_" + type] as string) || "";

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
    if (appointment.client !== null && appointment.livraison === null) {
      appointment.livraison = appointment.client;
    }
  }

  /**
   * Vérification du numéro BL directement pour éviter doublon
   */
  async function checkDeliveryNoteNumber() {
    try {
      if (numero_bl === appointment.numero_bl || numero_bl === "") {
        appointment.numero_bl = numero_bl;
        return;
      }

      const isDeliveryNoteNumberAvailable: boolean = await fetcher(
        `bois/check-delivery-note-available`,
        {
          searchParams: {
            supplierId: appointment.fournisseur?.toString(),
            deliveryNoteNumber: numero_bl,
            currentAppointmentId: appointment.id?.toString(),
          },
        }
      );

      if (isDeliveryNoteNumberAvailable) {
        appointment.numero_bl = numero_bl;
      } else {
        const supplierName = $tiers.get(appointment.fournisseur)?.nom_court;

        Notiflix.Report.failure(
          "Erreur",
          `Le numéro BL ${numero_bl} est déjà utilisé pour ${supplierName}.`,
          "OK"
        );

        numero_bl = appointment.numero_bl;
      }
    } catch (err: unknown) {
      const error = err as HTTP.Error | Error;
      Notiflix.Notify.failure(error.message);
      numero_bl = appointment.numero_bl;
    }
  }

  /**
   * Afficher des suggestions de transporteurs.
   */
  async function showCarrierSuggestions() {
    if (!appointment.chargement || !appointment.livraison) {
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
            chargement: appointment.chargement.toString(),
            livraison: appointment.livraison.toString(),
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
   * Afficher les explications pour le commentaire privé.
   */
  function showPrivateCommentsHelp() {
    Notiflix.Report.info(
      "Commentaire privé",
      "Ce commentaire ne sera pas visible dans le planning envoyé au client." +
        "<br/>" +
        "Utile pour ajouter des informations sur les tarifs d'affrètement, l'état de préparation de la commande, etc.",
      "Fermer"
    );
  }

  /**
   * Créer le RDV.
   */
  async function createAppointment() {
    if (!validerFormulaire(form)) return;

    createButton.$set({ disabled: true });

    try {
      appointment.heure_arrivee = addSeconds(heure_arrivee, "arrivee");
      appointment.heure_depart = addSeconds(heure_depart, "depart");

      await boisRdvs.create(appointment);

      Notiflix.Notify.success("Le RDV a été ajouté");
      $goto("./");
    } catch (erreur) {
      Notiflix.Notify.failure(erreur.message);
      console.error(erreur);
      createButton.$set({ disabled: false });
    }
  }

  /**
   * Modifier le RDV.
   */
  async function updateAppointment() {
    if (!validerFormulaire(form)) return;

    updateButton.$set({ disabled: true });

    try {
      appointment.heure_arrivee = addSeconds(heure_arrivee, "arrivee");
      appointment.heure_depart = addSeconds(heure_depart, "depart");

      await boisRdvs.update(appointment);

      Notiflix.Notify.success("Le RDV a été modifié");
      $goto("./");
    } catch (erreur) {
      Notiflix.Notify.failure(erreur.message);
      console.error(erreur);
      updateButton.$set({ disabled: false });
    }
  }

  /**
   * Supprimer le RDV.
   */
  function deleteAppointment() {
    if (!id) return;

    deleteButton.$set({ disabled: true });

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
          deleteButton.$set({ disabled: false });
        }
      },
      () => deleteButton.$set({ disabled: false }),
      notiflixOptions.themes.red
    );
  }

  function addDispatchLine() {
    appointment.dispatch = [
      ...appointment.dispatch,
      {
        staffId: null,
        date: "",
        remarks: "",
      },
    ];
  }

  function deleteDispatchLine(index: number) {
    appointment.dispatch.splice(index, 1);

    appointment.dispatch = appointment.dispatch;
  }
</script>

<!-- routify:options param-is-page -->
<!-- routify:options guard="bois/edit" -->

<main class="mx-auto w-10/12 lg:w-1/3">
  <PageHeading>Rendez-vous</PageHeading>

  {#if !appointment}
    <Chargement />
  {:else}
    <form
      class="flex flex-col gap-3 mb-4"
      bind:this={form}
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
            bind:value={appointment.date_rdv}
            required={!appointment.attente}
            class="w-full lg:w-max"
          />
        </div>

        <!-- En attente -->
        <div class="lg:self-center">
          <Toggle name="attente" bind:checked={appointment.attente}
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
          {#if appointment.fournisseur && appointment.affreteur && appointment.fournisseur !== appointment.affreteur && $tiers?.get(appointment.affreteur)?.roles.bois_fournisseur}
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
          bind:value={appointment.fournisseur}
          name="Fournisseur"
          required
        />
      </div>

      <!-- Chargement -->
      <div>
        <Label for="chargement"
          >Chargement
          {#if appointment.chargement && appointment.livraison === appointment.chargement}
            <span
              class="warning-fournisseur"
              title="Les lieux de chargement et livraison sont identiques"
              ><TriangleAlertIcon /></span
            >
          {/if}
        </Label>
        <Svelecte
          inputId="chargement"
          type="tiers"
          role="bois_client"
          bind:value={appointment.chargement}
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
          bind:value={appointment.client}
          name="Client"
          on:change={autoFillDeliveryPlace}
          required
        />
      </div>

      <!-- Livraison -->
      <div>
        <Label for="livraison"
          >Livraison
          {#if appointment.livraison && appointment.livraison === appointment.chargement}
            <span
              class="warning-fournisseur"
              title="Les lieux de chargement et livraison sont identiques"
              ><TriangleAlertIcon /></span
            >
          {/if}
        </Label>
        <Svelecte
          inputId="livraison"
          type="tiers"
          role="bois_client"
          bind:value={appointment.livraison}
          name="Livraison"
          required
        />
      </div>

      <!-- Transporteur -->
      <div>
        <Label for="transporteur"
          >Transporteur
          {#if appointment.affreteur === 1 || appointment.affreteur === null}
            <span>
              <LucideButton
                icon={SparklesIcon}
                title="Suggestions de transporteurs"
                on:click={showCarrierSuggestions}
              />
            </span>
          {/if}
        </Label>
        <Svelecte
          inputId="transporteur"
          type="tiers"
          role="bois_transporteur"
          bind:value={appointment.transporteur}
          name="Transporteur"
        />
      </div>

      <!-- Affréteur -->
      <div>
        <Label for="affreteur"
          >Affréteur
          {#if appointment.fournisseur && appointment.affreteur && appointment.fournisseur !== appointment.affreteur && $tiers?.get(appointment.affreteur)?.roles.bois_fournisseur}
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
          bind:value={appointment.affreteur}
          name="Affréteur"
        />
      </div>

      <!-- Commande prête -->
      <div>
        <Toggle name="commande_prete" bind:checked={appointment.commande_prete}
          >Commande prête</Toggle
        >
      </div>

      <!-- Confirmation d'affrètement -->
      <div>
        <Toggle
          name="confirmation_affretement"
          bind:checked={appointment.confirmation_affretement}
          disabled={$tiers?.get(appointment.affreteur)?.lie_agence === false ||
            !appointment.transporteur}>Confirmation d'affrètement</Toggle
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
          id="commentaire_public"
          rows={3}
          cols={30}
          bind:value={appointment.commentaire_public}
        />
      </div>

      <!-- Commentaire caché -->
      <div>
        <Label for="commentaire_prive"
          >Commentaire privé <LucideButton
            icon={CircleHelpIcon}
            on:click={showPrivateCommentsHelp}
          /></Label
        >
        <Textarea
          id="commentaire_prive"
          rows={3}
          cols={30}
          bind:value={appointment.commentaire_cache}
        />
      </div>

      <!-- Dispatch -->
      <div>
        <div class="text-xl font-bold">
          Dispatch
          <LucideButton
            preset="add"
            title="Ajouter une ligne"
            on:click={addDispatchLine}
          />
        </div>
        <div class="divide-y">
          {#each appointment.dispatch as dispatchItem, index}
            <div
              class="flex flex-col items-center gap-2 py-1 lg:flex-row lg:gap-4 lg:py-2"
            >
              <div class="w-full">
                <Label for="">Personnel</Label>
                <Svelecte
                  type="staff"
                  inputId="staff-{index}"
                  name="Personnel"
                  bind:value={dispatchItem.staffId}
                  placeholder="Sélectionner le personnel"
                  required
                />
              </div>

              <div class="w-min">
                <Label for="date-{index}">Date</Label>
                <Input
                  type="date"
                  id="date-{index}"
                  name="Date"
                  bind:value={dispatchItem.date}
                  required
                />
              </div>

              <div class="w-full">
                <Label for="remarks-{index}">Remarques</Label>
                <Input
                  type="text"
                  id="remarks-{index}"
                  bind:value={dispatchItem.remarks}
                  list="remarks"
                />
                <datalist id="remarks">
                  <option value="JCB"></option>
                  <option value="Trémie"></option>
                </datalist>
              </div>
              <div>
                <LucideButton
                  preset="delete"
                  title="Supprimer la ligne"
                  on:click={() => deleteDispatchLine(index)}
                />
              </div>
            </div>
          {/each}
        </div>
      </div>
    </form>

    <!-- Validation/Annulation/Suppression -->
    <div class="text-center">
      {#if isNew}
        <!-- Bouton "Ajouter" -->
        <BoutonAction
          preset="ajouter"
          on:click={createAppointment}
          bind:this={createButton}
        />
      {:else}
        <!-- Bouton "Modifier" -->
        <BoutonAction
          preset="modifier"
          on:click={updateAppointment}
          bind:this={updateButton}
        />
        <!-- Bouton "Supprimer" -->
        <BoutonAction
          preset="supprimer"
          on:click={deleteAppointment}
          bind:this={deleteButton}
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
