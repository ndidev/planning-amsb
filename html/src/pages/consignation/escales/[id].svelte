<!-- routify:options param-is-page -->
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
    fetcher,
    validerFormulaire,
    notiflixOptions,
    preventFormSubmitOnEnterKeydown,
  } from "@app/utils";

  import type { Stores, EscaleConsignation } from "@app/types";

  const { currentUser, consignationEscales } = getContext<Stores>("stores");

  let form: HTMLFormElement;
  let boutonAjouter: BoutonAction;
  let boutonModifier: BoutonAction;
  let boutonSupprimer: BoutonAction;

  const nouvelleEscale: EscaleConsignation = {
    id: null,
    navire: "TBN",
    voyage: null,
    armateur: null,
    eta_date: null,
    eta_heure: "",
    nor_date: null,
    nor_heure: "",
    pob_date: null,
    pob_heure: "",
    etb_date: null,
    etb_heure: "",
    ops_date: null,
    ops_heure: "",
    etc_date: null,
    etc_heure: "",
    etd_date: null,
    etd_heure: "",
    te_arrivee: null,
    te_depart: null,
    last_port: "",
    next_port: "",
    call_port: "Le Légué",
    quai: "",
    marchandises: [],
    commentaire: "",
  };

  const nouvelleMarchandise: EscaleConsignation["marchandises"][0] = {
    id: null,
    escale_id: null,
    operation: "Import",
    marchandise: "",
    client: "",
    environ: true,
    tonnage_bl: null,
    cubage_bl: null,
    nombre_bl: null,
    tonnage_outturn: null,
    cubage_outturn: null,
    nombre_outturn: null,
  };

  /**
   * Identifiant du RDV.
   */
  let id: EscaleConsignation["id"] = parseInt($params.id);

  /**
   * Clé "each" de ligne marchandise.
   */
  let i: number;

  const isNew = $params.id === "new";

  let escale: EscaleConsignation = isNew ? { ...nouvelleEscale } : null;
  const archives = "archives" in $params;

  // Récupérer les données de l'escale
  (async () => {
    try {
      if (id) {
        escale = structuredClone(await consignationEscales.get(id));
        if (!escale) throw new Error();
      }
    } catch (error) {
      $redirect("./new");
    }
  })();

  /**
   * Ajouter une ligne marchandise.
   */
  function ajouterMarchandise() {
    escale.marchandises = [
      ...escale.marchandises,
      structuredClone(nouvelleMarchandise),
    ];
  }

  /**
   * Supprimer une marchandise.
   */
  function supprimerMarchandise(
    marchandiseASupprimer: EscaleConsignation["marchandises"][0]
  ) {
    escale.marchandises = escale.marchandises.filter(
      (marchandise) => marchandise !== marchandiseASupprimer
    );
  }

  /**
   * Formattage heure ETX (majuscules + rajout auto ':' heure)
   */
  function formatterHeure(
    etx: "eta" | "nor" | "pob" | "etb" | "ops" | "etc" | "etd"
  ) {
    let contenu = escale[`${etx}_heure`].trim().toUpperCase();
    let heure_formattee = contenu;
    let regexp_HHMM = /^((([01][0-9]|2[0-3])([:H]?)[0-5][0-9])|24([:H]?)00)\b/;

    if (regexp_HHMM.test(contenu)) {
      let separateur =
        contenu.charAt(2) === ":" || contenu.charAt(2) === "H" ? 3 : 2;
      heure_formattee =
        contenu.substring(0, 2) +
        ":" +
        contenu.substring(separateur, contenu.length);
    }
    escale[`${etx}_heure`] = heure_formattee;
  }

  /**
   * N° voyage automatique
   * Laisser APRÈS le formattage de la date
   */
  async function calculerNumeroVoyage() {
    const navire = escale.navire;
    const eta_annee = escale.eta_date.substring(0, 4);

    if (navire === "" || navire === "TBN") {
      // Si navire non nommé, pas de numéro de voyage
      escale.voyage = "";
    } else {
      // Récupération du dernier numéro de voyage pour ce navire
      const { voyage } = await fetcher<{ voyage: string }>(
        "consignation/voyage",
        {
          params: {
            navire: escale.navire,
            id: id.toString(),
          },
        }
      );

      // Nouveau voyage par défaut (navire jamais venu)
      const nouveau_voyage = {
        annee: new Date().getFullYear(),
        numero: 1,
      };

      if (voyage) {
        // Le navire est déjà venu
        const dernier_voyage = {
          annee: parseInt(voyage.substring(0, 4)),
          numero: parseInt(voyage.substring(5)) || 0,
        };

        nouveau_voyage.annee = parseInt(eta_annee) || new Date().getFullYear();

        nouveau_voyage.numero =
          nouveau_voyage.annee === dernier_voyage.annee
            ? dernier_voyage.numero + 1 // Le navire est déjà venu cette année
            : 1; // Le navire n'est pas venu cette année
      }

      escale.voyage = nouveau_voyage.annee + "/" + nouveau_voyage.numero;
    }
  }

  /**
   * Ajouter l'escale.
   */
  async function ajouterEscale() {
    if (!validerFormulaire(form)) return;

    boutonAjouter.$set({ block: true });

    try {
      await consignationEscales.create(escale);

      Notiflix.Notify.success("L'escale a été créée");
      $goto(`./${archives ? "?archives" : ""}`);
    } catch (erreur) {
      Notiflix.Notify.failure(erreur.message);
      boutonAjouter.$set({ block: false });
    }
  }

  /**
   * Modifier l'escale.
   */
  async function modifierEscale() {
    if (!validerFormulaire(form)) return;

    boutonModifier.$set({ block: true });

    try {
      await consignationEscales.update(escale);

      Notiflix.Notify.success("L'escale a été modifiée");
      $goto(`./${archives ? "?archives" : ""}`);
    } catch (erreur) {
      Notiflix.Notify.failure(erreur.message);
      console.error(erreur);
      boutonModifier.$set({ block: false });
    }
  }

  /**
   * Supprimer l'escale.
   */
  function supprimerEscale() {
    if (!id) return;

    boutonSupprimer.$set({ block: true });

    // Demande de confirmation
    Notiflix.Confirm.show(
      "Suppression escale",
      `Voulez-vous vraiment supprimer l'escale ?`,
      "Supprimer",
      "Annuler",
      async function () {
        try {
          await consignationEscales.delete(id);

          Notiflix.Notify.success("L'escale a été supprimée");
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

<!-- routify:options title="Planning AMSB - Escale consignation" -->

{#if $currentUser.canEdit("consignation")}
  <main class="formulaire">
    <h1>Escale</h1>

    {#if !escale}
      <Chargement />
    {:else}
      <form
        class="pure-form pure-form-aligned"
        bind:this={form}
        use:preventFormSubmitOnEnterKeydown
      >
        <!-- Navire -->
        <div class="pure-control-group">
          <label for="navire">Navire</label>
          <input
            id="navire"
            bind:value={escale.navire}
            placeholder="Nom du navire"
            maxlength="255"
            class="navire-armateur"
            data-nom="Navire"
            on:blur={() => (escale.navire = escale.navire.trim().toUpperCase())}
            on:change={calculerNumeroVoyage}
          />
        </div>

        <!-- N° voyage -->
        <div class="pure-control-group">
          <label for="voyage">N° voyage</label>
          <input
            id="voyage"
            placeholder="N° voyage"
            maxlength="20"
            class="navire-armateur"
            data-nom="Voyage"
            pattern="20\d&#123;2}\/\d&#123;1,}"
            bind:value={escale.voyage}
          />
        </div>

        <!-- Armateur -->
        <div class="pure-control-group">
          <label for="armateur">Armateur</label>
          <Svelecte
            type="tiers"
            role="maritime_armateur"
            placeholder="Armateur"
            bind:value={escale.armateur}
          />
        </div>

        <!-- ETA -->
        <div class="pure-control-group">
          <label for="eta_date"
            ><abbr title="Estimated Time of Arrival">ETA</abbr></label
          >
          <input
            type="date"
            id="eta_date"
            placeholder="Date ETA"
            class="etx date"
            data-nom="Date ETA"
            on:change={calculerNumeroVoyage}
            bind:value={escale.eta_date}
          />
          <input
            id="eta_heure"
            maxlength="10"
            placeholder="Heure"
            class="etx heure"
            data-nom="Heure ETA"
            bind:value={escale.eta_heure}
            on:change={() => formatterHeure("eta")}
          />
        </div>

        <!-- NOR -->
        <div class="pure-control-group">
          <label for="nor_date"
            ><abbr title="Notice Of Readiness">NOR</abbr></label
          >
          <input
            type="date"
            id="nor_date"
            placeholder="Date NOR"
            class="etx date"
            data-nom="Date NOR"
            bind:value={escale.nor_date}
          />
          <input
            id="nor_heure"
            maxlength="10"
            placeholder="Heure"
            class="etx heure"
            data-nom="Heure NOR"
            bind:value={escale.nor_heure}
            on:change={() => formatterHeure("nor")}
          />
        </div>

        <!-- POB -->
        <div class="pure-control-group">
          <label for="pob_date"><abbr title="Pilot On Board">POB</abbr></label>
          <input
            type="date"
            id="pob_date"
            placeholder="Date POB"
            class="etx date"
            data-nom="Date POB"
            bind:value={escale.pob_date}
          />
          <input
            id="pob_heure"
            maxlength="10"
            placeholder="Heure"
            class="etx heure"
            data-nom="Heure POB"
            bind:value={escale.pob_heure}
            on:change={() => formatterHeure("pob")}
          />
        </div>

        <!-- ETB -->
        <div class="pure-control-group">
          <label for="etb_date"
            ><abbr title="Estimated Time of Berthing">ETB</abbr></label
          >
          <input
            type="date"
            id="etb_date"
            placeholder="Date ETB"
            class="etx date"
            data-nom="Date ETB"
            bind:value={escale.etb_date}
          />
          <input
            id="etb_heure"
            maxlength="10"
            placeholder="Heure"
            class="etx heure"
            data-nom="Heure ETB"
            bind:value={escale.etb_heure}
            on:change={() => formatterHeure("etb")}
          />
        </div>

        <!-- Opérations -->
        <div class="pure-control-group">
          <label for="ops_date">Début ops</label>
          <input
            type="date"
            id="ops_date"
            placeholder="Date ops"
            class="etx date"
            data-nom="Date début ops"
            bind:value={escale.ops_date}
          />
          <input
            id="ops_heure"
            maxlength="10"
            placeholder="Heure"
            class="etx heure"
            data-nom="Heure début ops"
            bind:value={escale.ops_heure}
            on:change={() => formatterHeure("ops")}
          />
        </div>

        <!-- ETC -->
        <div class="pure-control-group">
          <label for="etc_date"
            ><abbr title="Estimated Time of Completion">ETC</abbr></label
          >
          <input
            type="date"
            id="etc_date"
            placeholder="Date ETC"
            class="etx date"
            data-nom="Date ETC"
            bind:value={escale.etc_date}
            on:change={calculerNumeroVoyage}
          />
          <input
            id="etc_heure"
            maxlength="10"
            placeholder="Heure"
            class="etx heure"
            data-nom="Heure ETC"
            bind:value={escale.etc_heure}
            on:change={() => formatterHeure("etc")}
          />
        </div>

        <!-- ETD -->
        <div class="pure-control-group">
          <label for="etd_date"
            ><abbr title="Estimated Time of Departure">ETD</abbr></label
          >
          <input
            type="date"
            id="etd_date"
            placeholder="Date ETD"
            class="etx date"
            data-nom="Date ETD"
            bind:value={escale.etd_date}
          />
          <input
            id="etd_heure"
            maxlength="10"
            placeholder="Heure"
            class="etx heure"
            data-nom="Heure ETD"
            bind:value={escale.etd_heure}
            on:change={() => formatterHeure("etd")}
          />
        </div>

        <!-- TE arrivée -->
        <div class="pure-control-group">
          <label for="te_arrivee">TE arrivée</label>
          <InputDecimal
            id="te_arrivee"
            format="+2"
            bind:value={escale.te_arrivee}
            placeholder="TE arrivée"
            class="te"
          />
        </div>

        <!-- TE départ -->
        <div class="pure-control-group">
          <label for="te_depart">TE départ</label>
          <InputDecimal
            id="te_depart"
            format="+2"
            bind:value={escale.te_depart}
            placeholder="TE départ"
            class="te"
          />
        </div>

        <!-- Port et quai d'escale -->
        <div class="pure-control-group port-quai-escale">
          <label for="call_port">Port d'escale</label>
          <input
            list="call_port_list"
            id="call_port"
            placeholder="Port d'escale"
            data-nom="Port d'escale"
            bind:value={escale.call_port}
          />
          <input
            list="quai_list"
            id="quai"
            placeholder="Quai d'escale"
            data-nom="Quai d'escale"
            bind:value={escale.quai}
          />
        </div>

        <!-- Port de provenance -->
        <div class="pure-control-group last-port">
          <label for="last_port">Port de provenance</label>
          <Svelecte
            inputId="last_port"
            type="port"
            bind:value={escale.last_port}
            placeholder="Port de provenance"
          />
        </div>

        <!-- Port de destination -->
        <div class="pure-control-group next-port">
          <label for="next_port">Port de destination</label>
          <Svelecte
            inputId="next_port"
            type="port"
            bind:value={escale.next_port}
            placeholder="Port de destination"
          />
        </div>

        <!-- Commentaire -->
        <div class="pure-control-group">
          <label for="commentaire">Commentaire</label>
          <textarea
            class="escale_commentaire"
            id="commentaire"
            rows="3"
            cols="30"
            data-nom="Commentaire"
            bind:value={escale.commentaire}
          />
        </div>

        <!-- Marchandises -->
        <h2>Marchandises</h2>
        <div id="ajouter-marchandise">
          Ajouter une marchandise
          <MaterialButton
            icon="add"
            title="Ajouter une marchandise"
            on:click={ajouterMarchandise}
          />
        </div>
        <div>
          <ul id="marchandises">
            {#each escale.marchandises as marchandise ((i = marchandise.id ||= Math.random()))}
              <li class="ligne-marchandise pure-g">
                <input hidden class="id" />
                <div class="bloc pure-u-1 pure-u-lg-1-4">
                  <div class="pure-control-group">
                    <label
                      >Marchandise*
                      <input
                        list="marchandises_list"
                        class="nom marchandise"
                        maxlength="255"
                        data-nom="Marchandise"
                        bind:value={marchandise.marchandise}
                        required
                      />
                    </label>
                  </div>
                  <div class="pure-control-group">
                    <label
                      >Client*
                      <input
                        list="clients_list"
                        maxlength="255"
                        class="nom client"
                        data-nom="Client"
                        bind:value={marchandise.client}
                        required
                      />
                    </label>
                  </div>
                  <div class="pure-control-group">
                    <label>
                      Operation*
                      <select
                        class="operation"
                        bind:value={marchandise.operation}
                      >
                        <option value="Import">Import</option>
                        <option value="Export">Export</option>
                      </select>
                    </label>
                  </div>
                </div>

                <div class="quantite bloc pure-u-1 pure-u-lg-1-4">
                  <div class="quantite type pure-u-1">
                    <label class="pure-checkbox"
                      >BL (environ
                      <input
                        type="checkbox"
                        class="environ checkbox-environ"
                        bind:checked={marchandise.environ}
                      />)
                    </label>
                  </div>
                  <div class="pure-control-group">
                    <label for={`tonnage_bl_${i}`}>Tonnage</label>
                    <InputDecimal
                      id={`tonnage_bl_${i}`}
                      format="+3"
                      bind:value={marchandise.tonnage_bl}
                    />
                  </div>
                  <div class="pure-control-group">
                    <label for={`cubage_bl_${i}`}>Cubage</label>
                    <InputDecimal
                      id={`cubage_bl_${i}`}
                      format="+3"
                      bind:value={marchandise.cubage_bl}
                    />
                  </div>
                  <div class="pure-control-group">
                    <label for={`nombre_bl_${i}`}>Colis</label>
                    <InputDecimal
                      id={`nombre_bl_${i}`}
                      format="+0"
                      bind:value={marchandise.nombre_bl}
                    />
                  </div>
                </div>

                <div class="quantite bloc pure-u-1 pure-u-lg-1-4">
                  <div class="quantite type">Outturn</div>
                  <div class="pure-control-group">
                    <label for={`tonnage_outturn_${i}`}>Tonnage</label>
                    <InputDecimal
                      id={`tonnage_outturn_${i}`}
                      format="+3"
                      bind:value={marchandise.tonnage_outturn}
                    />
                  </div>
                  <div class="pure-control-group">
                    <label for={`cubage_outturn_${i}`}>Cubage</label>
                    <InputDecimal
                      id={`cubage_outturn_${i}`}
                      format="+3"
                      bind:value={marchandise.cubage_outturn}
                    />
                  </div>
                  <div class="pure-control-group">
                    <label for={`nombre_outturn_${i}`}>Colis</label>
                    <InputDecimal
                      id={`nombre_outturn_${i}`}
                      format="+0"
                      bind:value={marchandise.nombre_outturn}
                    />
                  </div>
                </div>
                <div class="poubelle">
                  <MaterialButton
                    preset="supprimer"
                    title="Supprimer la marchandise"
                    on:click={() => supprimerMarchandise(marchandise)}
                  />
                </div>
              </li>
            {/each}
          </ul>
        </div>
      </form>

      <!-- Validation/Annulation/Suppression -->
      <div class="boutons">
        {#if isNew}
          <!-- Bouton "Ajouter" -->
          <BoutonAction
            preset="ajouter"
            on:click={ajouterEscale}
            bind:this={boutonAjouter}
          />
        {:else}
          <!-- Bouton "Modifier" -->
          <BoutonAction
            preset="modifier"
            on:click={modifierEscale}
            bind:this={boutonModifier}
          />
          <!-- Bouton "Supprimer" -->
          <BoutonAction
            preset="supprimer"
            on:click={supprimerEscale}
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
    {/if}
  </main>

  <datalist id="call_port_list">
    <option value="Le Légué" />
    <option value="Tréguier" />
  </datalist>

  <datalist id="quai_list">
    <option value="Bassin" />
    <option value="Bassin 2" />
    <option value="Bassin 4/5" />
    <option value="Cesson" />
    <option value="Cesson 1" />
    <option value="Cesson 2" />
    <option value="Quai Garnier" />
    <option value="Quai Guindy" />
  </datalist>

  <datalist id="marchandises_list">
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

  <datalist id="clients_list">
    <option value="Altilis" />
    <option value="Ar Cour" />
    <option value="Ballay" />
    <option value="Caliance" />
    <option value="Catelys" />
    <option value="Cobrena" />
    <option value="Guyot" />
    <option value="Masson" />
    <option value="Protac" />
    <option value="Stora Enso" />
    <option value="Timab" />
    <option value="Traxys" />
    <option value="Wood2Wood" />
  </datalist>
{:else}
  {$goto("/")}
{/if}

<style>
  #marchandises {
    margin: 0;
    padding: 0;
    list-style-type: none;
  }

  #ajouter-marchandise:hover {
    cursor: pointer;
  }

  #marchandises label::after {
    content: none;
  }

  .ligne-marchandise {
    align-items: flex-end;
    margin: 5px 0;
    border: 1px solid #333;
  }

  .ligne-marchandise input,
  .ligne-marchandise select {
    width: initial;
  }

  .ligne-marchandise .poubelle {
    align-self: center;
  }

  .ligne-marchandise .bloc {
    margin: 0 10px;
  }

  .ligne-marchandise label {
    text-align: left !important;
  }

  .quantite.type {
    font-weight: bold;
    /* text-align: center; */
  }

  input[type="checkbox"] {
    vertical-align: middle;
  }

  input.etx.heure,
  :global(input.te) {
    width: 120px;
  }

  /* Mobile */
  @media screen and (max-width: 767px) {
    .ligne-marchandise .bloc {
      padding: 5px 0;
    }
  }

  /* Desktop */
  @media screen and (min-width: 768px) {
    .poubelle {
      margin-left: 10px;
    }
  }
</style>
