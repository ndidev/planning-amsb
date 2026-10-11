<!-- routify:options bundle=true -->

<script lang="ts">
  import { Guard } from "@app/auth";
</script>

<Guard>
  <slot />
</Guard>
