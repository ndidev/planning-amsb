<!-- 
  @component
  
  Bouton coloré avec Material Icons.

  Usage :
  ```tsx
  <MaterialBouton
    icon: string="icon"      // Code de l'icône Material Icons
    title: string="title"    // Texte de l'attribut "title" du bouton
    color: string="..."      // Couleur de l'icône (doit être valide CSS)
    hoverColor: string="..." // Couleur de l'icône au survol (doit être valide CSS)
  />
  ```
 -->
<script lang="ts">
  type Preset = {
    name:
      | "ajouter"
      | "modifier"
      | "copier"
      | "supprimer"
      | "annuler"
      | "default";
    icon: string;
    text: string;
    color: string;
  };

  const presets: Map<Preset["name"], Omit<Preset, "name">> = new Map([
    [
      "ajouter",
      {
        icon: "add",
        text: "Ajouter",
        color: "green",
      },
    ],
    [
      "modifier",
      {
        icon: "edit",
        text: "Modifier",
        color: "yellow",
      },
    ],
    [
      "copier",
      {
        icon: "content_copy",
        text: "Copier",
        color: "blue",
      },
    ],
    [
      "supprimer",
      {
        icon: "delete",
        text: "Supprimer",
        color: "red",
      },
    ],
    [
      "annuler",
      {
        icon: "cancel",
        text: "Annuler",
        color: "default",
      },
    ],
    [
      "default",
      {
        icon: null,
        text: "",
        color: "default",
      },
    ],
  ]);

  /**
   * Type de bouton prédéfini.
   */
  export let preset: Preset["name"] = "default";

  let params: Omit<Preset, "name"> =
    presets.get(preset) || presets.get("default");

  /**
   * Code de l'icône.
   */
  export let icon: string = params.icon;

  /**
   * Texte de l'attribut "title".
   */
  export let title: string = params.text;

  /**
   * Couleur de l'icône.
   */
  export let color: string = `var(--${params.color}-color)`;

  /**
   * Couleur de l'icône lors du survol.
   */
  export let hoverColor:
    | string
    | undefined = `var(--${params.color}-hover-color)`;

  /**
   * Taille de l'icône.
   */
  export let fontSize = "24px";
</script>

<button
  type="button"
  class="material-symbols-outlined"
  style:--color={color}
  style:--hover-color={hoverColor}
  style:--font-size={fontSize}
  {title}
  on:click
>
  <slot>{icon}</slot>
</button>

<style>
  button {
    /* Green */
    --green-hover-color: hsl(0, 0%, 0%);

    /* Yellow */
    --yellow-hover-color: hsl(45, 85%, 56%);

    /* Blue */
    --blue-hover-color: hsl(193, 79%, 53%);

    /* Red */
    --red-hover-color: hsl(4, 100%, 64%);

    /* Default */
    --default-color: hsl(0, 0%, 63%);
    --default-hover-color: hsla(0, 0%, 0%, 0.8);

    --transition-time: 100ms;

    background-color: transparent;
    border: none;
    cursor: pointer;
    color: var(--color, var(--default-color));
    font-size: var(--font-size);
    position: relative;
    isolation: isolate;
    transition: color var(--transition-time) linear;
  }

  button:is(:hover, :focus) {
    color: var(--hover-color, var(--default-hover-color));
    font-variation-settings: "FILL" 1;
  }

  button::before {
    --scale: 1.5;
    content: "";
    position: absolute;
    top: calc(50% - 50%);
    left: 0;
    width: 100%;
    height: 100%;
    transform: scale(var(--scale, 1));
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
