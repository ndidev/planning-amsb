<script lang="ts">
  import { createEventDispatcher } from "svelte";

  const dispatch = createEventDispatcher();

  function clickOutside(node: HTMLElement) {
    const handleClick = (event: Event) => {
      if (!node.contains(event.target as Node)) {
        dispatch("outclick");
      }
    };

    setTimeout(
      () => document.addEventListener("click", handleClick, true),
      500
    );

    return {
      destroy() {
        document.removeEventListener("click", handleClick, true);
      },
    };
  }
</script>

<div class="container">
  <div class="content" use:clickOutside>
    <slot />
  </div>
</div>

<style>
  .container {
    z-index: 1000;
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(0, 0, 0, 0.1);
    overflow: auto;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .content {
    width: 350px;
    max-width: 95%;
    max-height: 95%;
  }
</style>
