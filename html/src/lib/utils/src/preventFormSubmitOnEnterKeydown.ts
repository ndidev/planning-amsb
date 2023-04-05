/**
 * Empêcher la soumission d'un formulaire en appuyant sur la touche "Entrée".
 */
export function preventFormSubmitOnEnterKeydown(form: HTMLFormElement) {
  form.addEventListener("keydown", action);

  return {
    destroy() {
      form.removeEventListener("keydown", action);
    },
  };

  function action(e: KeyboardEvent) {
    if (
      e.key === "Enter" &&
      !Array.from<Element>(form.querySelectorAll("textarea")).includes(
        document.activeElement
      )
    ) {
      e.preventDefault();
    }
  }
}
