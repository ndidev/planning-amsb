<!-- routify:options reset=true -->
<!-- routify:options bundle=true -->
<script lang="ts">
  import { EnvFooter } from "@app/components";

  import logo from "/src/images/logo_agence_combi.min.svg";
</script>

<div class="page">
  <header class="logo-header">
    <img src={logo} id="logo" alt="Agence Maritime Saint-Brieuc" />
  </header>

  <main>
    <slot />
  </main>
</div>

<EnvFooter />

<style>
  * {
    --footer-height: 50px;
  }

  .page {
    display: flex;
    flex-direction: column;
    height: calc(100svh - var(--footer-height));
  }

  main {
    flex: 1 1 auto;
    display: grid;
    justify-content: center;
    align-items: center;
  }

  .logo-header {
    flex: 0 1 auto;
  }

  #logo {
    display: block;
    margin: auto;
    margin-top: 5vh;
    max-width: 70%;
    max-height: 15vh;
  }
</style>
