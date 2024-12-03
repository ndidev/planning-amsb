<!-- 
@component

Colored badge.

@prop color - The color of the badge.

Usage :
```tsx
<Badge color="blue">Hello</Badge>
```
 
-->
<script lang="ts">
  import { luminance } from "@app/utils";

  export let color: string = "white";

  export let size:
    | "xs"
    | "sm"
    | "base"
    | "md"
    | "lg"
    | "xl"
    | "2xl"
    | "3xl"
    | "4xl"
    | "5xl"
    | "6xl"
    | "7xl"
    | "8xl"
    | "9xl" = "base";

  $: textColor = luminance.getTextColor(color);
</script>

<span
  class={`inline-flex items-center rounded-full px-2.5 py-0.5 text-${size} font-medium`}
  style="background-color: {color}"
  style:color={textColor}
>
  <slot />
</span>
