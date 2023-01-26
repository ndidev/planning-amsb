<script lang="ts"></script>

<style>
  #menu-add {
    display: inline-block;
    position: relative;
  }

  #menu-add:hover > ul {
    display: block;
  }

  #menu-add ul {
    display: none;
    position: absolute;
    z-index: 9;
    background-color: rgb(230, 230, 230);
    list-style-type: none;
    padding: 0;
    margin: 0;
  }

  #menu-add li {
    position: relative;
  }

  #menu-add li:hover {
    background-color: rgb(200, 200, 200);
  }

  #menu-add li:hover > a {
    color: white;
  }

  #menu-add li:hover > ul {
    display: block;
  }

  #menu-add ul a {
    color: black;
    padding: 12px;
    font: bold 12px Arial, Helvetica;
    text-transform: uppercase;
    text-decoration: none;
    white-space: nowrap;
    display: block;
  }

  #menu-add ul a:hover {
    background-color: rgb(150, 150, 150);
    color: white;
  }

  #menu-add ul ul {
    left: 100%;
    top: 0;
    background-color: rgb(200, 200, 200);
  }
</style>
