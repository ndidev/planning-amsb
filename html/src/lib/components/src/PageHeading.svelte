<!--
@component

  En-tête de page.

  Usage :
  ```tsx
  <PageHeading>Titre de la page</PageHeading>
  ```
-->
<script lang="ts">
  import { Heading } from "flowbite-svelte";
</script>

<Heading class="text-center my-5">
  <slot />
</Heading>
