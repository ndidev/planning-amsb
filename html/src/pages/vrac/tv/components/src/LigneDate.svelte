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

<div class="text-2xl w-full text-green-400 mt-8 mb-4">
  <span class="date">{formattedDate}</span>
</div>
