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
  import { onMount, onDestroy, createEventDispatcher } from "svelte";

  import { Input } from "flowbite-svelte";

  let input: HTMLInputElement;

  export let value: number;
  export let format = "";
  export let min: number = null;
  export let max: number = null;
  export let placeholder = "";
  export let id = "";
  export let name = "";
  export let required = false;
  let className = "w-full lg:min-w-min lg:max-w-max";
  export { className as class };

  const dispatch = createEventDispatcher();

  let isUserInput = false;
  let valueJustSaved = false;

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
    isUserInput = true;

    // Allow non-printable keystrokes and ctrl combinations
    if (event.key.length > 1 || event.ctrlKey) return;

    // Preventing default keystroke
    event.preventDefault();

    // Saving current cursor/selection position
    const caretStart = input.selectionStart!;
    const caretEnd = input.selectionEnd!;

    // If period/coma is input, replacement by browser decimal separator
    const key = [",", "."].includes(event.key) ? separator : event.key;

    // Target value to be checked
    const targetValue =
      input.value.substring(0, caretStart) +
      key +
      input.value.substring(caretEnd);

    // Keystroke validation
    if (!regex.test(targetValue)) return;

    input.value = targetValue;
    input.setSelectionRange(caretStart + 1, caretStart + 1); // Replaces the cursor at the right position
    input.dispatchEvent(new InputEvent("input", { data: key }));
  }

  function saveValue() {
    value = input.value ? parseFloat(input.value) : null;

    valueJustSaved = true;

    dispatch("new-value", value);
  }

  /**
   * Decimals after the separator.
   */
  function setDecimals() {
    if (input.value === "") return;

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

  function checkValidity() {
    if (min !== null && value < min) {
      input.setCustomValidity(`La valeur doit être supérieure à ${min}.`);
    } else if (max !== null && value > max) {
      input.setCustomValidity(`La valeur doit être inférieure à ${max}.`);
    } else {
      input.setCustomValidity("");
    }
  }

  // Update the input value whenever the value prop changes
  $: {
    if (input && !isUserInput && !valueJustSaved) {
      input.value = String(value || "");
      setDecimals();
    }
    isUserInput = false;
    valueJustSaved = false;
  }

  onMount(() => {
    input.value = String(value || "");
    setDecimals();
    input.addEventListener("keydown", checkInput);
    input.addEventListener("input", saveValue);
    input.addEventListener("blur", checkValidity);
    input.addEventListener("blur", setDecimals);
  });

  onDestroy(() => {
    input.removeEventListener("keydown", checkInput);
    input.removeEventListener("input", saveValue);
    input.removeEventListener("blur", checkValidity);
    input.removeEventListener("blur", setDecimals);
  });
</script>

<Input let:props class={className}>
  <input
    type="text"
    autocomplete="off"
    bind:this={input}
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
    {...props}
  />
</Input>
