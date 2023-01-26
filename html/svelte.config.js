const preprocess = require("svelte-preprocess");

module.exports = {
  compilerOptions: {},
  preprocess: [preprocess()],
  onwarn: (warning, handler) => {
    // e.g. don't warn on <marquee> elements, cos they're cool
    if (warning.code === "a11y-distracting-elements") return;
    if (warning.code === "css-unused-selector") return;

    // Handle other warnings normally
    handler(warning);
  },
};
