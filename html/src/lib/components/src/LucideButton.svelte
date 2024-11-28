<!-- 
  @component
  
  Bouton coloré avec icônes Lucide.

  Usage :
  ```tsx
  <LucideButton
    preset: "edit" | "copy" | "delete" | "confirm" | "cancel" | "add" | "default" = "default"
    icon: ComponentType<Icon> = null // Icône à afficher
    title: string="title"            // Texte de l'attribut "title" du bouton
    color: string="..."              // Couleur de l'icône (doit être valide CSS)
    hoverColor: string="..."         // Couleur de l'icône au survol (doit être valide CSS)
    staticallyColored: boolean=false // Colorer l'icône sans survol
  />
  ```
 -->
<script lang="ts">
  import type { ComponentType } from "svelte";
  import {
    PlusIcon,
    PencilIcon,
    CopyIcon,
    Trash2Icon,
    CheckIcon,
    XIcon,
    type Icon,
  } from "lucide-svelte";

  type Preset = {
    name: "edit" | "copy" | "delete" | "confirm" | "cancel" | "add" | "default";
    icon: ComponentType<Icon>;
    title: string;
    color: string;
    hoverColor?: string;
  };

  const presets: Map<Preset["name"], Omit<Preset, "name">> = new Map([
    [
      "edit",
      {
        icon: PencilIcon,
        title: "Modifier",
        color: "yellow",
      },
    ],
    [
      "copy",
      {
        icon: CopyIcon,
        title: "Copier",
        color: "blue",
      },
    ],
    [
      "delete",
      {
        icon: Trash2Icon,
        title: "Supprimer",
        color: "red",
      },
    ],
    [
      "confirm",
      {
        icon: CheckIcon,
        title: "Valider",
        color: "green",
      },
    ],
    [
      "cancel",
      {
        icon: XIcon,
        title: "Annuler",
        color: "default",
      },
    ],
    [
      "add",
      {
        icon: PlusIcon,
        title: "Ajouter",
        color: "default",
        hoverColor: "black",
      },
    ],
    [
      "default",
      {
        icon: null,
        title: "",
        color: "default",
        hoverColor: "black",
      },
    ],
  ]);

  /**
   * Type de bouton prédéfini.
   */
  export let preset: Preset["name"] = "default";

  let params = presets.get(preset) || presets.get("default");

  /**
   * Code de l'icône.
   */
  export let icon = params.icon;

  /**
   * Texte de l'attribut "title".
   */
  export let title = params.title;

  /**
   * Couleur de l'icône.
   */
  export let color = params.color;

  /**
   * Couleur de l'icône au survol.
   */
  export let hoverColor = params.hoverColor || color;

  /**
   * Colorer l'icône sans survol.
   */
  export let staticallyColored = false;

  if (!staticallyColored) {
    color = "";
  }

  /**
   * Taille de l'icône.
   *
   * Exemple : "24px"
   */
  export let size: string = "24px";

  function colorIsCode(color: string): boolean {
    return (
      color.startsWith("#") ||
      color.startsWith("rgb") ||
      color.startsWith("hsl")
    );
  }
</script>

<button
  type="button"
  style:--color={colorIsCode(color) ? color : `var(--${color}-color)`}
  style:--hover-color={colorIsCode(hoverColor)
    ? hoverColor
    : `var(--${hoverColor}-color)`}
  style:width={size}
  style:height={size}
  {title}
  on:click
>
  <slot>
    <svelte:component this={icon} {size} />
  </slot>
</button>

<style>
  button {
    --default-color: hsl(0, 0%, 63%);
    --green-color: hsl(120, 100%, 35%);
    --yellow-color: hsl(45, 85%, 56%);
    --blue-color: hsl(193, 79%, 53%);
    --red-color: hsl(4, 100%, 64%);
    --black-color: hsl(0, 0%, 0%);

    --default-hover-color: hsl(0, 0%, 0%);

    --transition-time: 100ms;

    appearance: none;
    cursor: pointer;
    color: var(--color, var(--default-color));
    font-size: var(--font-size);
    position: relative;
    isolation: isolate;
    vertical-align: middle;
    transition: color var(--transition-time) linear;
  }

  button:is(:hover, :focus) {
    color: var(--hover-color, var(--default-hover-color));
  }

  button :global(.lucide-icon) {
    vertical-align: baseline;
  }

  /* Cercle au survol */
  button::before {
    --scale: 1.5;
    content: "";
    position: absolute;
    top: 50%;
    left: 50%;
    width: 100%;
    height: 100%;
    transform: translate(-50%, -50%) scale(var(--scale, 1));
    border-radius: 50%;
    background-color: var(--hover-color, var(--default-hover-color));
    opacity: 0;
    z-index: -1;
    transition: opacity var(--transition-time) linear;
  }

  button:is(:hover)::before {
    opacity: 0.15;
  }
</style>
