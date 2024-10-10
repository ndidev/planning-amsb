<script lang="ts">
  import { url, goto } from "@roxi/routify";

  import Notiflix from "notiflix";

  import { currentUser } from "@app/stores";
  import { User } from "@app/auth";

  /**
   * Déconnecter l'utilisateur courant.
   */
  async function logout() {
    try {
      await $currentUser.logout();
      currentUser.set(new User());
      $goto("/");
    } catch (error: any) {
      Notiflix.Notify.failure(error.message);
      console.error(error);
    }
  }
</script>

<div class="user-nom">
  <a href={$url("/user")} title="Configuration utilisateur"
    >{$currentUser.nom}</a
  >
</div>
<div>
  <button class="logout-button" on:click={logout}>Se déconnecter</button>
</div>

<style>
  .user-nom {
    margin-bottom: 5px;
    font-weight: bold;
    text-align: center;
  }

  .user-nom a {
    color: var(--couleur-texte-menu);
    text-decoration: none;
  }

  .user-nom a:hover {
    text-decoration: underline;
  }

  .logout-button {
    padding: 0;
    background-color: transparent;
    border: none;
    color: var(--couleur-texte-menu);
    font-family: var(--menu-font-family);
    font-variant-caps: small-caps;
    text-align: center;
    outline: none;
    cursor: pointer;
  }

  .logout-button:hover {
    text-decoration: underline;
  }

  /* Desktop */
  @media screen and (min-width: 768px) {
    .user-nom {
      text-align: initial;
    }

    .logout-button {
      text-align: initial;
    }
  }
</style>
