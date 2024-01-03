<!-- 
  @component
  
  Bandeau affichant l'environnement (development, staging...) en bas de l'écran.

  Usage :
  ```tsx
  <EnvFooter />
  ```
 -->
<script lang="ts">
  import { onMount } from "svelte";

  const afficherFooter = import.meta.env.MODE !== "production";

  onMount(() => {
    document.documentElement.style.setProperty(
      "--footer-height",
      afficherFooter ? "50px" : "0px"
    );
  });
</script>

{#if afficherFooter}
  <footer>
    Mode <strong>{import.meta.env.MODE}</strong>
  </footer>
{/if}

<style>
  :global(:root) {
    --footer-height: 0px;
  }

  footer {
    position: fixed;
    bottom: 0;
    width: 100vw;
    height: var(--footer-height);
    line-height: var(--footer-height);
    z-index: 10;
    background-color: lightpink;
    color: black;
    text-align: center;
  }
</style>
