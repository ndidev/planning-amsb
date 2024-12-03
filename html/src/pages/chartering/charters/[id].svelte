<!-- routify:options title="Planning AMSB - Affrètement maritime" -->
<script lang="ts">
  import { getContext } from "svelte";
  import { params, goto, redirect } from "@roxi/routify";

  import {
    Label,
    Input,
    Toggle,
    Textarea,
    Select,
    Heading,
  } from "flowbite-svelte";
  import Notiflix from "notiflix";

  import {
    PageHeading,
    LucideButton,
    Svelecte,
    NumericInput,
    Chargement,
    BoutonAction,
  } from "@app/components";

  import {
    validerFormulaire,
    notiflixOptions,
    preventFormSubmitOnEnterKeydown,
  } from "@app/utils";

  import type { Stores, Charter } from "@app/types";

  const { charteringCharters } = getContext<Stores>("stores");

  let form: HTMLFormElement;
  let createCharterButton: BoutonAction;
  let updateCharterButton: BoutonAction;
  let deleteCharterButton: BoutonAction;

  const nouvelAffretement: Charter = {
    id: null,
    statut: 0,
    lc_debut: null,
    lc_fin: null,
    cp_date: null,
    navire: "TBN",
    affreteur: null,
    armateur: null,
    courtier: null,
    fret_achat: null,
    fret_vente: null,
    surestaries_achat: null,
    surestaries_vente: null,
    legs: [],
    commentaire: "",
    archive: false,
  };

  const nouvelleEtape: Charter["legs"][0] = {
    id: null,
    charter: null,
    bl_date: null,
    pol: null,
    pod: null,
    marchandise: "",
    quantite: "",
    commentaire: "",
  };

  /**
   * Identifiant du RDV.
   */
  let id: Charter["id"] = parseInt($params.id);

  /**
   * Clé "each" de ligne marchandise.
   */
  let i: number;

  const isNew = $params.id === "new";

  let charter: Charter = isNew ? { ...nouvelAffretement } : null;
  const archives = "archives" in $params;

  (async () => {
    try {
      if (id) {
        charter = structuredClone(await charteringCharters.get(id));
        if (!charter) throw new Error();
      }
    } catch (error) {
      $redirect("./new");
    }
  })();

  /**
   * Ajouter une étape.
   */
  function addLeg() {
    charter.legs = [...charter.legs, structuredClone(nouvelleEtape)];
  }

  /**
   * Supprimer une étape.
   */
  function deleteLeg(etapeASupprimer: Charter["legs"][0]) {
    charter.legs = charter.legs.filter((etape) => etape !== etapeASupprimer);
  }

  /**
   * Ajouter l'affrètement.
   */
  async function createCharter() {
    if (!validerFormulaire(form)) return;

    createCharterButton.$set({ block: true });

    try {
      await charteringCharters.create(charter);

      Notiflix.Notify.success("L'affrètement a été créé");
      $goto(`./${archives ? "?archives" : ""}`);
    } catch (erreur) {
      Notiflix.Notify.failure(erreur.message);
      createCharterButton.$set({ block: false });
    }
  }

  /**
   * Modifier l'affrètement.
   */
  async function updateCharter() {
    if (!validerFormulaire(form)) return;

    updateCharterButton.$set({ block: true });

    try {
      await charteringCharters.update(charter);

      Notiflix.Notify.success("L'affrètement a été modifié");
      $goto(`./${archives ? "?archives" : ""}`);
    } catch (erreur) {
      Notiflix.Notify.failure(erreur.message);
      updateCharterButton.$set({ block: false });
    }
  }

  /**
   * Supprimer l'affrètement.
   */
  function deleteCharter() {
    if (!id) return;

    deleteCharterButton.$set({ block: true });

    // Demande de confirmation
    Notiflix.Confirm.show(
      "Suppression affrètement",
      `Voulez-vous vraiment supprimer l'affrètement ?`,
      "Supprimer",
      "Annuler",
      async function () {
        try {
          await charteringCharters.delete(id);

          Notiflix.Notify.success("L'affrètement a été supprimé");
          $goto(`./${archives ? "?archives" : ""}`);
        } catch (erreur) {
          Notiflix.Notify.failure(erreur.message);
        }

        deleteCharterButton.$set({ block: false });
      },
      () => {
        deleteCharterButton.$set({ block: false });
      },
      notiflixOptions.themes.red
    );
  }
</script>

<!-- routify:options param-is-page -->
<!-- routify:options guard="chartering/edit" -->

<main class="w-18/24 md:w-17/24 lg:w-16/24 xl:w-15/24 mx-auto">
  <PageHeading>Affrètement</PageHeading>

  {#if !charter}
    <Chargement />
  {:else}
    <form
      class="flex flex-col lg:flex-row gap-3 mb-4"
      bind:this={form}
      use:preventFormSubmitOnEnterKeydown
    >
      <div class="flex flex-col gap-3 mb-4 w-full lg:w-4/12">
        <!-- Statut -->
        <div>
          <Label for="status">Statut</Label>
          <Select
            id="status"
            bind:value={charter.statut}
            placeholder=""
            required
          >
            <option value={0} selected>Planifié (pas confirmé)</option>
            <option value={1}>Confirmé par l'affréteur</option>
            <option value={2}>Affrété</option>
            <option value={3}>Chargement effectué</option>
            <option value={4}>Voyage terminé</option>
          </Select>
        </div>

        <!-- Archive -->
        <div>
          <Toggle name="archive" bind:checked={charter.archive}>Archive</Toggle>
        </div>

        <!-- Navire -->
        <div>
          <Label for="navire">Navire</Label>
          <Input
            id="navire"
            placeholder="Nom du navire"
            maxlength={255}
            data-nom="Navire"
            autocapitalize="characters"
            bind:value={charter.navire}
          />
        </div>

        <!-- Affréteur -->
        <div>
          <Label for="affreteur">Affréteur</Label>
          <Svelecte
            inputId="affreteur"
            type="tiers"
            role="maritime_affreteur"
            bind:value={charter.affreteur}
            name="Affréteur"
            placeholder="Nom de l'affréteur"
            required
          />
        </div>

        <!-- Armateur -->
        <div>
          <Label for="armateur">Armateur</Label>
          <Svelecte
            inputId="armateur"
            type="tiers"
            role="maritime_armateur"
            bind:value={charter.armateur}
            name="Armateur"
            placeholder="Nom de l'armateur"
          />
        </div>

        <!-- Courtier -->
        <div>
          <Label for="courtier">Courtier</Label>
          <Svelecte
            inputId="courtier"
            type="tiers"
            role="maritime_courtier"
            bind:value={charter.courtier}
            name="Courtier"
            placeholder="Nom du courtier"
          />
        </div>

        <!-- Laycan -->
        <div>
          <Label for="lc_debut">L/C début</Label>
          <Input
            type="date"
            id="lc_debut"
            placeholder="L/C début"
            data-nom="Début laycan"
            bind:value={charter.lc_debut}
          />
        </div>
        <div>
          <Label for="lc_fin">L/C fin</Label>
          <Input
            type="date"
            id="lc_fin"
            placeholder="L/C fin"
            data-nom="Fin laycan"
            bind:value={charter.lc_fin}
          />
        </div>

        <!-- C/P -->
        <div>
          <Label for="cp_date">C/P</Label>
          <Input
            type="date"
            id="cp_date"
            placeholder="Date C/P"
            data-nom="Date C/P"
            bind:value={charter.cp_date}
          />
        </div>

        <!-- Montants -->
        <div>
          <Label for="fret_achat">Fret (achat)</Label>
          <NumericInput
            id="fret_achat"
            format="2"
            placeholder="Fret (achat)"
            bind:value={charter.fret_achat}
          />
        </div>
        <div>
          <Label for="fret_vente">Fret (vente)</Label>
          <NumericInput
            id="fret_vente"
            format="2"
            placeholder="Fret (vente)"
            bind:value={charter.fret_vente}
          />
        </div>
        <div>
          <Label for="surestaries_achat">Surestaries (achat)</Label>
          <NumericInput
            id="surestaries_achat"
            format="2"
            placeholder="Surestaries (achat)"
            bind:value={charter.surestaries_achat}
          />
        </div>
        <div>
          <Label for="surestaries_vente">Surestaries (vente)</Label>
          <NumericInput
            id="surestaries_vente"
            format="2"
            placeholder="Surestaries (vente)"
            bind:value={charter.surestaries_vente}
          />
        </div>

        <!-- Commentaire -->
        <div>
          <Label for="commentaire">Commentaire</Label>
          <Textarea
            id="commentaire"
            rows={3}
            cols={30}
            data-nom="Commentaire (général)"
            bind:value={charter.commentaire}
          />
        </div>
      </div>

      <!-- Détails -->
      <div class="flex flex-col gap-3 mb-4 w-full lg:w-8/12">
        <div class="font-bold text-2xl">Étapes</div>
        <div>
          Ajouter une étape
          <LucideButton
            preset="add"
            title="Ajouter une étape"
            on:click={addLeg}
          />
        </div>
        <div>
          <ul>
            {#each charter.legs as leg ((i = leg.id ||= Math.random()))}
              <li
                class="my-1 flex flex-col items-start gap-2 rounded-lg border-[1px] border-gray-300 p-2 lg:flex-row"
              >
                <div class="flex w-full flex-col gap-1 lg:w-1/2">
                  <!-- Marchandise -->
                  <div>
                    <Label for="cargo-name-{i}">Marchandise*</Label>
                    <Input
                      id="cargo-name-{i}"
                      list="marchandises_list"
                      class="marchandise"
                      maxlength={255}
                      data-nom="Marchandise"
                      bind:value={leg.marchandise}
                      required
                    />
                  </div>
                  <div>
                    <!-- Quantité -->
                    <div>
                      <Label for="quantity-{i}">Quantité</Label>
                      <Input
                        id="quantity-{i}"
                        maxlength={255}
                        data-nom="Quantité"
                        bind:value={leg.quantite}
                      />
                    </div>
                  </div>
                  <div>
                    <!-- Date B/L -->
                    <div>
                      <Label for="date-bl-{i}">Date B/L</Label>
                      <Input
                        type="date"
                        id="date-bl-{i}"
                        maxlength={255}
                        data-nom="Date B/L"
                        bind:value={leg.bl_date}
                      />
                    </div>
                  </div>
                </div>

                <div class="flex w-full flex-col gap-1">
                  <!-- Port de chargement -->
                  <div>
                    <Label for="pol-{i}">Port de chargement*</Label>
                    <Svelecte
                      inputId="pol-{i}"
                      name="Port de chargement"
                      type="port"
                      bind:value={leg.pol}
                      required
                    />
                  </div>

                  <!-- Port de déchargement -->
                  <div>
                    <Label for="pod-{i}">Port de déchargement*</Label>
                    <Svelecte
                      inputId="pod-{i}"
                      name="Port de déchargement"
                      type="port"
                      bind:value={leg.pod}
                      required
                    />
                  </div>
                </div>

                <!-- Commentaire -->
                <div class="flex w-full flex-col gap-1">
                  <div>
                    <Label for="commentaire-{i}">Commentaire</Label>
                    <Textarea
                      id="commentaire-{i}"
                      class="commentaire"
                      rows={3}
                      cols={30}
                      bind:value={leg.commentaire}
                    />
                  </div>
                </div>

                <div class="w-min self-center">
                  <LucideButton
                    preset="delete"
                    title="Supprimer la ligne"
                    on:click={() => deleteLeg(leg)}
                  />
                </div>
              </li>
            {/each}
          </ul>
        </div>
      </div>
    </form>

    <!-- Validation/Annulation/Suppression -->
    <div class="text-center">
      {#if isNew}
        <!-- Bouton "Ajouter" -->
        <BoutonAction
          preset="ajouter"
          on:click={createCharter}
          bind:this={createCharterButton}
        />
      {:else}
        <!-- Bouton "Modifier" -->
        <BoutonAction
          preset="modifier"
          on:click={updateCharter}
          bind:this={updateCharterButton}
        />
        <!-- Bouton "Supprimer" -->
        <BoutonAction
          preset="supprimer"
          on:click={deleteCharter}
          bind:this={deleteCharterButton}
        />
      {/if}

      <!-- Bouton "Annuler" -->
      <BoutonAction
        preset="annuler"
        on:click={() => {
          $goto(`./${archives ? "?archives" : ""}`);
        }}
      />
    </div>
  {/if}
</main>

<datalist id="marchandises_list">
  <!-- TODO A remplacer par Svelecte avec appel API chartering_detail -->
  <option value="Ammonitrate" />
  <option value="Bois" />
  <option value="Bois (léger)" />
  <option value="Bois (mixte)" />
  <option value="Bois (lourd)" />
  <option value="Bois (LVL)" />
  <option value="Blé bio" />
  <option value="Chlorure de potasse" />
  <option value="Citrus" />
  <option value="CSR (balles)" />
  <option value="DAP" />
  <option value="Drêches de maïs" />
  <option value="Engrais vrac" />
  <option value="Entec" />
  <option value="Maerl" />
  <option value="Magnésie" />
  <option value="Magnésie K" />
  <option value="Magnésie KS" />
  <option value="Magnésie P" />
  <option value="MCP BB" />
  <option value="MCP vrac" />
  <option value="MCP vrac mini" />
  <option value="MCP vrac semoule" />
  <option value="Poids bio" />
  <option value="Sel" />
  <option value="Sel (fin)" />
  <option value="Sel (gros)" />
  <option value="Sulfate de potasse" />
  <option value="Tourteaux de colza" />
  <option value="Tourteaux de tournesol" />
  <option value="Triticale de blé bio" />
  <option value="Urée" />
  <option value="Woodchips" />
</datalist>
