import { writable } from "svelte/store";

/**
 * État de connexion à Internet de l'application.
 */
export const online = writable(navigator.onLine, (set) => {
  const DEBOUNCE_MILLISECONDS = 1000;

  function setOnlineToTrue() {
    const timeoutSet = setTimeout(() => {
      set(true);
      window.removeEventListener("offline", cancel);
    }, DEBOUNCE_MILLISECONDS);

    const cancel = () => clearTimeout(timeoutSet);
    window.addEventListener("offline", cancel);
  }

  function setOnlineToFalse() {
    const timeoutSet = setTimeout(() => {
      set(false);
      window.removeEventListener("online", cancel);
    }, DEBOUNCE_MILLISECONDS);

    const cancel = () => clearTimeout(timeoutSet);
    window.addEventListener("online", cancel);
  }

  window.addEventListener("online", setOnlineToTrue);
  window.addEventListener("offline", setOnlineToFalse);

  return () => {
    window.removeEventListener("online", setOnlineToTrue);
    window.removeEventListener("offline", setOnlineToFalse);
  };
});
