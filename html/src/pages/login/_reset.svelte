<script lang="ts">
  const afficherFooterMode = import.meta.env.MODE !== "production";
</script>

<div class="container">
  <div class="page">
    <slot />
  </div>

  {#if afficherFooterMode}
    <footer>
      Mode <strong>{import.meta.env.MODE}</strong>
    </footer>
  {/if}
</div>

<style>
  * {
    --footer-height: 50px;
  }

  .page {
    min-height: calc(100svh - var(--footer-height));
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
