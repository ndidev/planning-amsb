<!-- 
@component

```tsx
<ConfigLine bind:modificationEnCours bind:ligne>
  <slot />
  <slot name="actions" />
  <slot name="valider-annuler" />
</ConfigLine>
```

-->
<script lang="ts">
  import { onMount } from "svelte";

  export let ligne: HTMLDivElement = undefined;
  export let modificationEnCours = false;

  onMount(() => {
    // Si changement d'une ligne, activation de la classe "modificationEnCours"
    ligne
      ?.querySelectorAll<
        HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement
      >("input, textarea, select")
      .forEach((input) => {
        input.onchange = () => (modificationEnCours = true);
        input.oninput = () => (modificationEnCours = true);
      });
  });
</script>

<div
  class="flex flex-wrap items-center gap-4 w-full my-1 mx-auto p-3 rounded-md border-2 border-gray-200 bg-gray-100"
  bind:this={ligne}
  class:modificationEnCours
>
  <slot />

  <span class="ml-auto">
    <slot name="actions" />
  </span>
</div>

<style>
  .modificationEnCours {
    background-color: lightyellow;
  }
</style>
