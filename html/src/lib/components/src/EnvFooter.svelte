<!-- 
  @component
  
  Bandeau affichant l'environnement (development, staging...) en bas de l'écran.

  Usage :
  ```tsx
  <EnvFooter />
  ```
 -->
<script lang="ts">
  const { MODE, PROD, DEV } = import.meta.env;
  console.log({
    MODE,
    PROD,
    DEV,
  });

  const afficherFooter = MODE !== "production";
</script>

{#if afficherFooter}
  <footer>
    Mode <strong>{import.meta.env.MODE}</strong>
  </footer>
{/if}

<style>
  * {
    --footer-height: 50px;
  }

  footer {
    position: sticky;
    bottom: 0;
    height: var(--footer-height);
    line-height: var(--footer-height);
    z-index: 10;
    background-color: lightpink;
    color: black;
    text-align: center;
  }
</style>
