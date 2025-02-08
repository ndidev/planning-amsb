<!-- 
  @component
  
  Bandeau affichant l'environnement (development, staging...) en bas de l'écran.

  Usage :
  ```tsx
  <EnvFooter />
  ```
 -->
<script context="module">
  const showFooter = import.meta.env.MODE !== "production";

  export const FOOTER_HEIGHT = showFooter ? 50 : 0;
</script>

<script lang="ts">
  import { onMount } from "svelte";

  onMount(() => {
    document.documentElement.style.setProperty(
      "--footer-height",
      FOOTER_HEIGHT + "px"
    );
  });
</script>

{#if showFooter}
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
