<!-- 
  @component
  
  Ligne de date du planning vrac

  Usage :
  ```tsx
  <LigneDate date: string={"yyyy-mm-dd"} />
  ```
 -->
<script lang="ts">
  export let date: string;

  const formattedDate = new Date(date).toLocaleDateString("fr-FR", {
    weekday: "long",
    year: "numeric",
    month: "long",
    day: "numeric",
  });
</script>

<div class="ligne-date pure-u-1">
  <span class="date">{formattedDate}</span>
</div>

<style>
  .ligne-date {
    font-size: 1.4em;
    color: #58c85f;
    margin-top: 30px;
    margin-bottom: 15px;
  }
</style>
