<script lang="ts">
  import Notiflix from "notiflix";

  import { currentUser } from "@app/stores";

  /**
   * Déconnecter l'utilisateur courant.
   */
  async function logout() {
    try {
      await $currentUser.logout();
    } catch (error: any) {
      Notiflix.Notify.failure(error.message);
    }
  }
</script>

<div class="UserFooter">
  <div class="user-nom">
    <a href="/user/" title="Configuration utilisateur">{$currentUser.nom}</a>
  </div>
  <div>
    <button class="LogoutButton" on:click={logout}>Se déconnecter</button>
  </div>
</div>

<style>
  .UserFooter {
    position: absolute;
    bottom: 20px;
    padding-left: 10px;
    color: var(--couleur-texte-menu);
    font-family: var(--menu-font-family);
    font-size: 12px;
    font-weight: bold;
  }

  .user-nom {
    margin-bottom: 5px;
  }

  .user-nom a {
    color: var(--couleur-texte-menu);
    text-decoration: none;
  }

  .user-nom a:hover {
    text-decoration: underline;
  }

  .LogoutButton {
    padding: 0;
    background-color: transparent;
    border: none;
    color: var(--couleur-texte-menu);
    font-family: var(--menu-font-family);
    text-transform: uppercase;
    outline: none;
    cursor: pointer;
  }

  .LogoutButton:hover {
    text-decoration: underline;
  }
</style>
