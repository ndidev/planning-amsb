declare module "lucide-svelte/icons/*" {
  import type { SvelteComponentTyped } from "svelte";
  import type { IconProps } from "lucide-svelte/types";

  const component: typeof SvelteComponentTyped<IconProps>;
  export default component;
}
