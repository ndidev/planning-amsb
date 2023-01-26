<!-- 
  @component
  
  Titre de section (`<h2>`).

  Usage :
  ```tsx
  <TitreSection titre: string="Mon titre" />
  ```
 -->
<script>
  export let titre = "Titre section";
</script>

<h2>{titre}</h2>

<style>
  h2 {
    margin: 20px 10% 5px 10%;
    color: #333;
  }
</style>
