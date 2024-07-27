import { defineConfig } from "vite";
import { svelte } from "@sveltejs/vite-plugin-svelte";
import { sveltePreprocess } from "svelte-preprocess";

export default defineConfig({
  envDir: "..",
  server: {
    port: 5000,
  },
  plugins: [
    svelte({
      preprocess: [sveltePreprocess()],
    }),
  ],
});
