<!-- routify:options title="Planning AMSB - Affrètement maritime" -->
<script lang="ts">
  import { getContext } from "svelte";
  import { params, goto, redirect } from "@roxi/routify";

  import Notiflix from "notiflix";

  import {
    MaterialButton,
    Svelecte,
    InputDecimal,
    Chargement,
    BoutonAction,
  } from "@app/components";

  import {
    validerFormulaire,
    notiflixOptions,
    preventFormSubmitOnEnterKeydown,
  } from "@app/utils";

  import type { Stores, Charter } from "@app/types";

  const { currentUser, charteringCharters } = getContext<Stores>("stores");

  let form: HTMLFormElement;
  let boutonAjouter: BoutonAction;
  let boutonModifier: BoutonAction;
  let boutonSupprimer: BoutonAction;

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
  function ajouterEtape() {
    charter.legs = [...charter.legs, structuredClone(nouvelleEtape)];
  }

  /**
   * Supprimer une étape.
   */
  function supprimerEtape(etapeASupprimer: Charter["legs"][0]) {
    charter.legs = charter.legs.filter((etape) => etape !== etapeASupprimer);
  }

  /**
   * Ajouter l'affrètement.
   */
  async function ajouterAffretement() {
    if (!validerFormulaire(form)) return;

    boutonAjouter.$set({ block: true });

    try {
      await charteringCharters.create(charter);

      Notiflix.Notify.success("L'affrètement a été créé");
      $goto(`./${archives ? "?archives" : ""}`);
    } catch (erreur) {
      Notiflix.Notify.failure(erreur.message);
      boutonAjouter.$set({ block: false });
    }
  }

  /**
   * Modifier l'affrètement.
   */
  async function modifierAffretement() {
    if (!validerFormulaire(form)) return;

    boutonModifier.$set({ block: true });

    try {
      await charteringCharters.update(charter);

      Notiflix.Notify.success("L'affrètement a été modifié");
      $goto(`./${archives ? "?archives" : ""}`);
    } catch (erreur) {
      Notiflix.Notify.failure(erreur.message);
      boutonModifier.$set({ block: false });
    }
  }

  /**
   * Supprimer l'affrètement.
   */
  function supprimerAffretement() {
    if (!id) return;

    boutonSupprimer.$set({ block: true });

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

        boutonSupprimer.$set({ block: false });
      },
      () => {
        boutonSupprimer.$set({ block: false });
      },
      notiflixOptions.themes.red
    );
  }
</script>

<!-- routify:options param-is-page -->
<!-- routify:options guard="chartering/edit" -->

<main class="formulaire">
  <h1>Affrètement</h1>

  {#if !charter}
    <Chargement />
  {:else}
    <form
      class="pure-form pure-form-aligned"
      bind:this={form}
      use:preventFormSubmitOnEnterKeydown
    >
      <!-- Statut -->
      <div class="pure-control-group">
        <label for="statut">Statut</label>
        <Svelecte
          inputId="statut"
          options={[
            { value: 0, text: "Plannifié (pas confirmé)" },
            { value: 1, text: "Confirmé par l'affréteur" },
            { value: 2, text: "Affrété" },
            { value: 3, text: "Chargement effectué" },
            { value: 4, text: "Voyage terminé" },
          ]}
          bind:value={charter.statut}
        />
      </div>

      <!-- Archive -->
      <div class="pure-control-group">
        <label for="archive">Archive</label>
        <input
          type="checkbox"
          name="archive"
          id="archive"
          bind:checked={charter.archive}
        />
      </div>

      <!-- Navire -->
      <div class="pure-control-group">
        <label for="navire">Navire</label>
        <input
          name="navire"
          id="navire"
          placeholder="Nom du navire"
          maxlength="255"
          class="navire-armateur"
          data-nom="Navire"
          autocapitalize="characters"
          bind:value={charter.navire}
        />
      </div>

      <!-- Affréteur -->
      <div class="pure-control-group">
        <label for="affreteur">Affréteur</label>
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
      <div class="pure-control-group">
        <label for="armateur">Armateur</label>
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
      <div class="pure-control-group">
        <label for="courtier">Courtier</label>
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
      <div class="pure-control-group">
        <label for="lc_debut">L/C début</label>
        <input
          type="date"
          name="lc_debut"
          id="lc_debut"
          placeholder="L/C début"
          data-nom="Début laycan"
          bind:value={charter.lc_debut}
        />
      </div>
      <div class="pure-control-group">
        <label for="lc_fin">L/C fin</label>
        <input
          type="date"
          name="lc_fin"
          id="lc_fin"
          placeholder="L/C fin"
          data-nom="Fin laycan"
          bind:value={charter.lc_fin}
        />
      </div>

      <!-- C/P -->
      <div class="pure-control-group">
        <label for="cp_date">C/P</label>
        <input
          type="date"
          name="cp_date"
          id="cp_date"
          placeholder="Date C/P"
          data-nom="Date C/P"
          bind:value={charter.cp_date}
        />
      </div>

      <!-- Montants -->
      <div class="pure-control-group">
        <label for="fret_achat">Fret (achat)</label>
        <InputDecimal
          name="fret_achat"
          id="fret_achat"
          format="2"
          placeholder="Fret (achat)"
          bind:value={charter.fret_achat}
        />
      </div>
      <div class="pure-control-group">
        <label for="fret_vente">Fret (vente)</label>
        <InputDecimal
          name="fret_vente"
          id="fret_vente"
          format="2"
          placeholder="Fret (vente)"
          bind:value={charter.fret_vente}
        />
      </div>
      <div class="pure-control-group">
        <label for="surestaries_achat">Surestaries (achat)</label>
        <InputDecimal
          name="surestaries_achat"
          id="surestaries_achat"
          format="2"
          placeholder="Surestaries (achat)"
          bind:value={charter.surestaries_achat}
        />
      </div>
      <div class="pure-control-group">
        <label for="surestaries_vente">Surestaries (vente)</label>
        <InputDecimal
          name="surestaries_vente"
          id="surestaries_vente"
          format="2"
          placeholder="Surestaries (vente)"
          bind:value={charter.surestaries_vente}
        />
      </div>

      <!-- Commentaire -->
      <div class="pure-control-group">
        <label for="commentaire">Commentaire</label>
        <textarea
          class="charter_commentaire"
          name="commentaire"
          id="commentaire"
          rows="3"
          cols="30"
          data-nom="Commentaire (général)"
          bind:value={charter.commentaire}
        />
      </div>

      <!-- Détails -->
      <h2>Détails</h2>
      <div id="ajouter-ligne">
        Ajouter une ligne
        <MaterialButton
          icon="add"
          title="Ajouter une ligne"
          on:click={ajouterEtape}
        />
      </div>
      <div>
        <ul id="details">
          {#each charter.legs as leg ((i = leg.id ||= Math.random()))}
            <li class="ligne-detail pure-g">
              <div class="bloc pure-u-1 pure-u-lg-7-24">
                <!-- Marchandise -->
                <div class="pure-control-group">
                  <label
                    >Marchandise*
                    <input
                      list="marchandises_list"
                      class="marchandise"
                      maxlength="255"
                      data-nom="Marchandise"
                      bind:value={leg.marchandise}
                      required
                    />
                  </label>
                </div>
                <div class="pure-u-1 pure-u-lg-12-24">
                  <!-- Quantité -->
                  <div class="pure-control-group">
                    <label
                      >Quantité
                      <input
                        class="quantite"
                        maxlength="255"
                        data-nom="Quantité"
                        bind:value={leg.quantite}
                      />
                    </label>
                  </div>
                </div>
                <div class="pure-u-1 pure-u-lg-11-24">
                  <!-- Date B/L -->
                  <div class="pure-control-group">
                    <label
                      >Date B/L
                      <input
                        type="date"
                        class="bl_date"
                        maxlength="255"
                        data-nom="Date B/L"
                        bind:value={leg.bl_date}
                      />
                    </label>
                  </div>
                </div>
              </div>

              <div class="bloc pure-u-1 pure-u-lg-7-24">
                <!-- Port de chargement -->
                <div class="pure-control-group">
                  <label for={`pol_${i}`}>Port de chargement*</label>
                  <Svelecte
                    inputId={`pol_${i}`}
                    name="Port de chargement"
                    type="port"
                    bind:value={leg.pol}
                    required
                  />
                </div>

                <!-- Port de déchargement -->
                <div class="pure-control-group">
                  <label for={`pod_${i}`}>Port de déchargement*</label>
                  <Svelecte
                    inputId={`pod_${i}`}
                    name="Port de déchargement"
                    type="port"
                    bind:value={leg.pod}
                    required
                  />
                </div>
              </div>

              <!-- Commentaire -->
              <div class="bloc pure-u-1 pure-u-lg-7-24">
                <div class="pure-control-group">
                  <label
                    >Commentaire
                    <textarea
                      class="commentaire"
                      rows="3"
                      cols="30"
                      bind:value={leg.commentaire}
                    />
                  </label>
                </div>
              </div>

              <div class="poubelle">
                <MaterialButton
                  preset="supprimer"
                  on:click={() => {
                    supprimerEtape(leg);
                  }}
                />
              </div>
            </li>
          {/each}
        </ul>
      </div>

      <!-- Validation/Annulation/Suppression -->
      <div class="boutons">
        {#if isNew}
          <!-- Bouton "Ajouter" -->
          <BoutonAction
            preset="ajouter"
            on:click={ajouterAffretement}
            bind:this={boutonAjouter}
          />
        {:else}
          <!-- Bouton "Modifier" -->
          <BoutonAction
            preset="modifier"
            on:click={modifierAffretement}
            bind:this={boutonModifier}
          />
          <!-- Bouton "Supprimer" -->
          <BoutonAction
            preset="supprimer"
            on:click={supprimerAffretement}
            bind:this={boutonSupprimer}
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
    </form>
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

<style>
  #ajouter-ligne:hover {
    cursor: pointer;
  }

  #details {
    margin: 0;
    padding: 0;
    list-style-type: none;
  }

  #details label::after {
    content: none;
  }

  .ligne-detail {
    align-items: flex-start;
    margin: 10px 0;
    padding: 10px 0;
    background-color: rgb(250, 250, 250);
    border: 1px solid #ccc;
    border-radius: 5px;
  }

  .ligne-detail .poubelle {
    align-self: center;
  }

  .ligne-detail .bloc {
    margin: 0 10px;
  }

  .ligne-detail label {
    text-align: left !important;
  }

  .ligne-detail .quantite {
    width: 90%;
  }

  input[type="checkbox"] {
    vertical-align: middle;
  }

  @media screen and (max-width: 480px) {
    .ligne-detail .bloc {
      padding: 5px 0;
    }
  }
</style>
