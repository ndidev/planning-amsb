<!-- 
  @component
  
  Ligne de date du planning vrac

  Usage :
  ```tsx
  <LigneDate
    date: string={"yyyy-mm-dd"}
    maree: boolean/>
  ```
 -->
<script lang="ts">
  export let date: string;

  /**
   * `true` si une marée supérieure à 4m à cette date.
   */
  export let maree: boolean = false;

  /**
   * Navires à quai durant cette date.
   */
  export let navires: string[];

  const formattedDate = new Date(date).toLocaleDateString("fr-FR", {
    weekday: "long",
    year: "numeric",
    month: "long",
    day: "numeric",
  });
</script>

<div class="ligne-date pure-u-1">
  <span class="date">{formattedDate}</span>

  <!-- Point d'exclamation si vive-eau -->
  {#if maree}
    <span class="marees" title="Navires potentiellement à quai">!</span>
  {/if}

  <!-- Pictogramme + nom des navires à quai si applicable -->
  {#if navires.length > 0}
    <div class="navires">
      <i class="material-icons">directions_boat</i>
      <div class="texte-navires">{@html navires.join("<br />")}</div>
    </div>
  {/if}
</div>

<style>
  .ligne-date {
    font-size: 1.3em;
    margin-top: 30px;
    margin-bottom: 15px;
  }

  .date {
    color: #58c85f;
  }

  .marees,
  .navires {
    display: inline;
    position: relative;
    margin-left: 10px;
    font-weight: bold;
    color: red;
    cursor: help;
  }

  .texte-navires {
    display: none;
    position: absolute;
    width: auto;
    height: auto;
    top: 0px;
    left: 30px;
    padding: 5px;
    border: 2px solid black;
    background-color: beige;
    font-size: 0.5em;
    color: red;
    white-space: nowrap;
  }

  .navires:hover .texte-navires {
    display: block;
  }

  @media screen and (max-width: 480px) {
    .date {
      font-size: 1em;
    }
  }
</style>
