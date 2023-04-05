<script lang="ts"></script>

{#each Array(5) as date}
  <div class="placeholder-date pure-g">
    <div class="pure-u-2-5 pure-u-lg-3-24" />
  </div>

  {#each Array(3) as rdv}
    <div class="placeholder-rdv pure-g">
      <div class="pure-u-lg-1-24 pure-u-4-24" />
      <div class="pure-u-lg-2-24 pure-u-6-24" />
      <div class="pure-u-lg-2-24 pure-u-6-24" />
      <div class="pure-u-lg-2-24 pure-u-6-24" />
      <div class="pure-u-lg-8-24 pure-u-1" />
      <div class="pure-u-lg-3-24 pure-u-1" />
      <div class="pure-u-lg-3-24 pure-u-12-24" />
      <hr />
    </div>
  {/each}
{/each}

<style>
  .placeholder-date {
    margin-top: 30px;
    margin-bottom: 15px;
  }

  .placeholder-date div {
    display: inline-block;
    height: 1.3em;
    background-color: #ddd;
    margin-right: 1%;
  }

  .placeholder-rdv {
    padding: 5px 0 0 5px;
  }

  .placeholder-rdv div {
    display: inline-block;
    height: 1.2em;
    background-color: #eee;
    margin-right: 1%;
  }
</style>
