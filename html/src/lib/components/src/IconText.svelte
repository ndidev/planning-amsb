<!-- 
  @component
  
  Bloc de texte accompagné d'une icône.

  Usage :
  ```tsx
  <IconText
    iconType: "icon" | "text" = "icon"
    hideIcon: ("mobile" | "desktop")[] = []
    hideText: ("mobile" | "desktop")[] = []
  />
  ```
 -->
<script lang="ts">
  import type { breakpoints } from "@app/utils";

  export let iconType: "icon" | "text" = "icon";

  export let hideIcon: Array<(typeof breakpoints)[number]["type"]> = [];
  export let hideText: Array<(typeof breakpoints)[number]["type"]> = [];
</script>

<div class="icon-text">
  <div
    class="icon"
    class:material-symbols-outlined={iconType === "icon"}
    class:no-desktop={hideIcon.includes("desktop")}
    class:no-mobile={hideIcon.includes("mobile")}
  >
    <slot name="icon" />
  </div>

  <div
    class="text"
    class:no-desktop={hideText.includes("desktop")}
    class:no-mobile={hideText.includes("mobile")}
  >
    <slot name="text" />

    {#if $$slots.tooltip}
      <div class="tooltip">
        <slot name="tooltip" />
      </div>
    {/if}
  </div>
</div>

<style>
  .icon-text {
    display: grid;
    grid-template-columns: min-content 1fr;
    align-items: center;
    justify-items: start;
    column-gap: 5px;
  }

  .tooltip {
    visibility: hidden;
    font-size: 0.8em;
    font-weight: normal;
    white-space: pre;
    background-color: black;
    color: white;
    padding: 5px;
    border-radius: 6px;
    position: absolute;
    z-index: 1;
  }

  .icon-text:hover .tooltip {
    visibility: visible;
  }

  /* Mobile */
  @media screen and (max-width: 767px) {
    .no-mobile {
      width: 0;
      visibility: hidden;
    }

    .icon {
      text-align: center;
      min-width: 24px;
    }
  }

  /* Desktop */
  @media screen and (min-width: 768px) {
    .no-desktop {
      width: 0;
      visibility: hidden;
    }
  }
</style>
