<!-- 
  @component
  
 Formats numeric inputs.

 The function allows to limit the input to figures
 that may be preceded by a plus or minus sign and a decimal separator.

  ```tsx
  <NumericInput
    value: number
    format: string  // see examples below
    placeholder: string
    id: string
    name: string
    required: boolean
    class: string
  />
  ```
 
 Examples :
  - format="2"   (+) or (-) number with 2 decimals
  - format="-1"  Negative number with 1 decimal
  - format="+0"  Positive integer (0 decimal)
  - format="+"   Positive number (free decimals)
  - format="-"   Negative number (free decimals)
  - format=""    (+) or (-) number (free decimals)

 -->
<script lang="ts">
  import { onMount, onDestroy } from "svelte";

  let input: HTMLInputElement;

  export let value: number;
  export let format = "";
  export let placeholder = "";
  export let id = "";
  export let name = "";
  export let required = false;
  let className = "";
  export { className as class };

  /** Decimal separator used by the browser. */
  const separator = parseFloat("1.1").toFixed(1).substring(1, 2);

  let sign = "";
  let point = "";
  let decimals = "";

  // Checking sign and amount of decimals
  if (format === "") {
    sign = "[\\+-]?";
  } else if (format.substring(0, 1) === "-") {
    sign = "-";
    decimals = format.substring(1);
  } else if (format.substring(0, 1) === "+") {
    sign = "\\+?";
    decimals = format.substring(1);
  } else {
    sign = "[\\+-]?";
    decimals = format;
  }

  // Decimal separator for the regex
  if (parseInt(decimals) > 0) {
    point = "([,.]?)";
  }

  // RegExp applied to the input element
  const regex = new RegExp(
    "^" + sign + "[0-9]*" + point + "[0-9]{0," + decimals + "}$"
  );

  /**
   * Check  and validate user input.
   *
   * @param event Keyboard event
   */
  function checkInput(event: KeyboardEvent) {
    // Allow non-printable keystrokes and ctrl combinations
    if (event.key.length > 1 || event.ctrlKey) return;

    // Preventing default keystroke
    event.preventDefault();

    // Saving current cursor/selection position
    const caret_start = input.selectionStart!;
    const caret_end = input.selectionEnd!;

    // If period/coma is input, replacement by browser decimal separator
    const key = [",", "."].includes(event.key) ? separator : event.key;

    // Target value to be checked
    const target_value =
      input.value.substring(0, caret_start) +
      key +
      input.value.substring(caret_end);

    // Keystroke validation
    if (regex.test(target_value)) {
      input.value = target_value;
      input.setSelectionRange(caret_start + 1, caret_start + 1); // Replaces the cursor at the right position
      input.dispatchEvent(new InputEvent("input", { data: key }));
    }
  }

  /**
   * Decimals after the separator.
   */
  function setDecimals() {
    if (input.value === "") {
      value = null;
      return;
    }

    // Check for decimal separators (in case of pasted value)
    input.value = input.value.replace(/,|\./, separator);
    // Remove spaces
    input.value = input.value.replace(/\s/g, "");

    if (format === "" || format === "+" || format === "-") {
      // For inputs with no amount of decimals chosen
      input.value = parseFloat(input.value).toString();
    } else {
      // For inputs with a specific amount of decimals chosen
      input.value = parseFloat(input.value).toFixed(parseInt(decimals));
    }
  }

  onMount(() => {
    setDecimals();
    input.addEventListener("keydown", checkInput);
    input.addEventListener("blur", setDecimals);
  });

  onDestroy(() => {
    input.removeEventListener("keydown", checkInput);
    input.removeEventListener("blur", setDecimals);
  });
</script>

<input
  type="text"
  autocomplete="off"
  bind:this={input}
  bind:value
  class={className}
  {placeholder}
  {id}
  {name}
  {required}
  on:input
  on:change
  on:focus
  on:blur
  on:keydown
  on:keyup
  on:keypress
/>

<style></style>
