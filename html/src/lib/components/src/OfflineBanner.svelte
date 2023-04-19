<!-- 
  @component
  
  Bandeau pour indiquer si la connexion à Internet a été coupée.

  Usage:
  ```tsx
  <OfflineBanner />
  ```
 -->
<script lang="ts">
  import { online } from "@app/utils";
</script>

{#if !$online}
  <div class="offline-banner">Connexion à Internet perdue</div>
{/if}

<style>
  .offline-banner {
    --height: 50px;
    width: 100%;
    height: var(--height);
    line-height: var(--height);
    background-color: hsla(0, 70%, 80%, 0.6);
    color: hsl(0, 100%, 50%);
    text-align: center;
    font-weight: bold;
  }
</style>
