import Notiflix from "notiflix";

/**
 * Input : autocomplete "off" et trim
 */
for (const input of document.querySelectorAll("input")) {
  input.autocomplete = "off";

  input.addEventListener("change", () => {
    if (input.type !== "file") {
      input.value = input.value.replace(/\s+/g, " ").trim();
    }
  });
}
/* ---------------------------------------- */

/**
 * Notiflix
 */
Notiflix.Notify.init({
  position: "right-bottom",
  cssAnimationStyle: "from-bottom",
  messageMaxLength: 250,
});

Notiflix.Confirm.init({
  plainText: false,
  messageMaxLength: 500,
});

Notiflix.Report.init({
  plainText: false,
  backOverlay: true,
  backOverlayClickToClose: true,
});
/* ---------------------------------------- */
