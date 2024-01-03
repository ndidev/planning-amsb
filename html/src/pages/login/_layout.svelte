<!-- routify:options reset=true -->
<!-- routify:options bundle=true -->
<script lang="ts">
  import { EnvFooter } from "@app/components";
</script>

<div class="container">
  <div class="page">
    <slot />
  </div>

  <EnvFooter />
</div>

<style>
</style>
