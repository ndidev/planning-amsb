<script lang="ts">
  import { InputDecimal } from "@app/components";

  let obj = {
    value: 1,
  };
</script>

<h1>InputDecimal</h1>

<div>
  Format "+3" : <InputDecimal format="+3" bind:value={obj.value} />
</div>

<style></style>
