<!-- 
  @component
  
  Titre de section (`<h3>`) + bouton d'ajout de ligne (optionnel).

  Usage :
  ```tsx
  <TitreSousSection
    titre: string="Sous section"
    fonctionAjout: Function=() => {}
  />
  ```
 -->
<script lang="ts">
  /**
   * Titre de la sous-section.
   */
  export let titre: string = "Sous section";

  /**
   * Fonction à exécuter pour ajouter une ligne.
   */
  export let fonctionAjout: Function = undefined;
</script>

<h3>
  {titre}

  {#if fonctionAjout}
    <button title="Ajouter une ligne" on:click={() => fonctionAjout()}>
      Ajouter une ligne
      <i class="material-icons" title="Ajouter une ligne">add</i>
    </button>
  {/if}
</h3>

<style>
  h3 {
    margin: 20px 10% 5px 10%;
    color: #333;
    border-bottom: 1px solid #666;
  }

  button {
    font-size: 1rem;
    font-weight: normal;
    background-color: transparent;
    margin-left: 1%;
    border: none;
    cursor: pointer;
  }
</style>
