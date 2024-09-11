<script lang="ts">
  import { NumericInput } from "@app/components";

  let obj = {
    value: 1,
  };
</script>

<h1>InputDecimal</h1>

<div>
  Format "+3" : <NumericInput format="+3" bind:value={obj.value} />
</div>

<style></style>
