<!-- 
  @component
  
  Bouton textuel coloré.

  Usage :
  ```tsx
  <BoutonAction
    preset: string    // Options prédéfinies pour le bouton
    title: string     // Texte de l'attribut "title" du bouton
    color: string     // Couleur de l'icône (doit être valide CSS)
    type: string      // Rôle du bouton dans le formulaire
    disabled: boolean // Attribut `disabled` du bouton
    block: boolean    // Contrôle l'état Notiflix "Block" du bouton
  />
  ```
 -->
<script lang="ts">
  import { afterUpdate } from "svelte";

  import Notiflix from "notiflix";

  import { online } from "@app/utils";

  type Preset = {
    name:
      | "ajouter"
      | "modifier"
      | "copier"
      | "supprimer"
      | "annuler"
      | "default";
    text: string;
    color: string;
    needsOnline: boolean;
  };

  const presets: Map<Preset["name"], Omit<Preset, "name">> = new Map([
    [
      "ajouter",
      {
        text: "Ajouter",
        color: "green",
        needsOnline: true,
      },
    ],
    [
      "modifier",
      {
        text: "Modifier",
        color: "yellow",
        needsOnline: true,
      },
    ],
    [
      "copier",
      {
        text: "Copier",
        color: "blue",
        needsOnline: true,
      },
    ],
    [
      "supprimer",
      {
        text: "Supprimer",
        color: "red",
        needsOnline: true,
      },
    ],
    [
      "annuler",
      {
        text: "Annuler",
        color: "default",
        needsOnline: false,
      },
    ],
    [
      "default",
      {
        text: "",
        color: "default",
        needsOnline: false,
      },
    ],
  ]);

  let button: HTMLButtonElement;

  /**
   * Type de bouton prédéfini.
   */
  export let preset: Preset["name"] = "default";

  let params: Omit<Preset, "name"> =
    presets.get(preset) || presets.get("default");

  export let color = params.color;
  export let title = params.text;
  export let disabled = false;
  export let type: "button" | "reset" | "submit" = "button";
  export let needsOnline = params.needsOnline;

  /**
   * Contrôle l'état Notiflix "Block" du bouton.
   */
  export let block = false;

  afterUpdate(() => {
    if (block) {
      Notiflix.Block.standard([button], { svgSize: "30px" });
      button.style.minHeight = "initial";
    } else {
      if (button.querySelector(".notiflix-block")) {
        Notiflix.Block.remove([button]);
      }
    }
  });
</script>

<button
  bind:this={button}
  {type}
  style:--bg-color="var(--{color}-bg-color)"
  style:--color="var(--{color}-color)"
  style:--hover-color="var(--{color}-hover-color)"
  {title}
  disabled={(needsOnline && !$online) || disabled}
  on:click|preventDefault
>
  <slot>{params.text}</slot>
</button>

<style>
  button {
    /* Green */
    --green-bg-color: hsl(152, 60%, 49%);
    --green-color: hsl(0, 0%, 0%);
    --green-hover-color: hsl(0, 0%, 0%);

    /* Yellow */
    --yellow-bg-color: hsl(45, 85%, 56%);
    --yellow-color: hsl(0, 0%, 0%);
    --yellow-hover-color: hsl(0, 0%, 0%);

    /* Blue */
    --blue-bg-color: hsl(193, 79%, 53%);
    --blue-color: hsl(0, 0%, 0%);
    --blue-hover-color: hsl(0, 0%, 0%);

    /* Red */
    --red-bg-color: hsl(4, 100%, 64%);
    --red-color: hsl(0, 0%, 0%);
    --red-hover-color: hsl(0, 0%, 100%);

    /* Default */
    --default-bg-color: hsl(0, 0%, 85%);
    --default-color: hsla(0, 0%, 0%, 0.8);
    --default-hover-color: hsla(0, 0%, 0%, 0.8);

    /* Border */
    --border-width: 2px;

    font-family: inherit;
    font-size: 100%;
    color: var(--color, var(--default-color));
    background-color: white;
    padding: 0.4em 0.9em;
    border: var(--border-width) solid var(--bg-color, var(--default-bg-color));
    border-radius: 4px;
    text-decoration: none;
    margin: 2px 0;
    width: 100%;

    display: inline-block;
    white-space: nowrap;
    vertical-align: middle;
    line-height: 1.4;
    text-align: center;
    cursor: pointer;
    -webkit-user-drag: none;
    -webkit-user-select: none;
    user-select: none;
    box-sizing: border-box;

    transition-duration: 250ms;
  }

  button:focus,
  button:hover {
    color: var(--hover-color, var(--default-hover-color));
    background-color: var(--bg-color, var(--default-bg-color));
  }

  button:disabled {
    box-shadow: none;
    cursor: not-allowed;
    opacity: 0.4;
    pointer-events: none;
  }

  /* Desktop */
  @media screen and (min-width: 768px) {
    button {
      width: initial;
    }
  }
</style>
