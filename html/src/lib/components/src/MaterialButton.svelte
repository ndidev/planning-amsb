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
  import "material-icons/iconfont/material-icons.css";

  /**
   * Code de l'icône.
   */
  export let icon: string = "";

  /**
   * Texte de l'attribut "title".
   */
  export let title: string = "";

  /**
   * Couleur de l'icône.
   */
  export let color: string = "rgb(160, 160, 160)";

  /**
   * Couleur de l'icône lors du survol.
   */
  export let hoverColor: string | undefined = undefined;

  // Modification de la couleur de survol
  // en fonction des icônes couramment utilisées
  if (!hoverColor) {
    switch (icon) {
      case "delete":
        hoverColor = "rgb(255, 85, 73)";
        break;
      case "edit":
        hoverColor = "rgb(255, 210, 100)";
        break;
      case "copy":
        hoverColor = "rgb(40, 190, 230)";
        break;
      default:
        hoverColor = "rgba(0, 0, 0, 0.8)";
        break;
    }
  }

  /**
   * Taille de l'icône (format "XXpx").
   */
  export let fontSize = "24px";
</script>

<button
  type="button"
  class="material-icons"
  style:--color={color}
  style:--hover-color={hoverColor}
  style:--font-size={fontSize}
  {title}
  on:click
>
  {icon}
</button>

<style>
  button {
    background-color: transparent;
    border: none;
    cursor: pointer;
    color: var(--color);
    font-size: var(--font-size);
  }

  button:is(:hover, :focus) {
    color: var(--hover-color);
  }
</style>
