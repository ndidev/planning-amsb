/**
 * Formats numeric inputs.
 *
 * The function allows to limit the input to figures
 * that may be preceded by a plus or minus sign
 * and a decimal separator.
 *
 * Applies to "input" elements with the attribute data-decimal=*
 * where * is (+/-)(\d).
 *
 * HTML: <input data-decimal=*>
 *
 * Examples :
 *
 * - data-decimal="2"   (+) or (-) number with 2 decimals
 * - data-decimal="-1"  Negative number with 1 decimal
 * - data-decimal="+0"  Positive integer (0 decimal)
 * - data-decimal="+"   Positive number (free decimals)
 * - data-decimal="-"   Negative number (free decimals)
 * - data-decimal=""    (+) or (-) number (free decimals)
 *
 * @author Nicolas DENIS
 * @link https://gist.github.com/ScoopAndrun/c9f16beed8506a44edcb328e0d42e3b4
 * @license Unlicense https://spdx.org/licenses/Unlicense.html
 *
 * @global
 *
 * @listens event:keypress
 * @listens event:blur
 *
 * @param {HTMLElement} context Parent element for which the function must be applied to the children
 */
export function formatDecimal(context: HTMLElement | Document = document) {
  // Name of the "data" attribute chosen for the numeric inputs
  const data_attribute = "data-decimal";

  // Get the decimal separator used by the browser
  const separator = parseFloat("1.1").toFixed(1).substring(1, 2);

  const numeric_inputs = context.querySelectorAll<HTMLInputElement>(
    "[" + data_attribute + "]"
  );

  for (let i = numeric_inputs.length; i--; ) {
    const input = numeric_inputs[i];
    const data_attr_value = input.getAttribute(data_attribute) || "";

    // Converting the input element to type "text"
    input.setAttribute("type", "text");

    let sign = "";
    let point = "";
    let decimals = "";

    // Checking sign and amount of decimals
    if (data_attr_value === "") {
      sign = "[\\+-]?";
    } else if (data_attr_value.substring(0, 1) === "-") {
      sign = "-";
      decimals = data_attr_value.substring(1);
    } else if (data_attr_value.substring(0, 1) === "+") {
      sign = "\\+?";
      decimals = data_attr_value.substring(1);
    } else {
      sign = "[\\+-]?";
      decimals = data_attr_value;
    }

    // Decimal separator for the regex
    if (parseInt(decimals) > 0) {
      point = "([,.]?)";
    }

    // RegExp applied to the input element
    const regex = new RegExp(
      "^" + sign + "[0-9]*" + point + "[0-9]{0," + decimals + "}$"
    );

    /* User input check */
    input.onkeydown = (event) => {
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
      let target_value =
        input.value.substring(0, caret_start) +
        key +
        input.value.substring(caret_end);

      // Keystroke validation
      if (regex.test(target_value)) {
        input.value = target_value;
        input.setSelectionRange(caret_start + 1, caret_start + 1); // Replaces the cursor at the right position
        input.dispatchEvent(new InputEvent("input", { data: key }));
      }
    };

    /* Decimals after the separator */
    input.onblur = () => {
      if (input.value !== "") {
        // Check for decimal separators (in case of pasted value)
        input.value = input.value.replace(/,|\./, separator);
        // Remove spaces
        input.value = input.value.replace(/\s/g, "");

        if (
          data_attr_value === "" ||
          data_attr_value === "+" ||
          data_attr_value === "-"
        ) {
          // For inputs with no amount of decimals chosen
          input.value = parseFloat(input.value).toString();
        } else {
          // For inputs with a specific amount of decimals chosen
          input.value = parseFloat(input.value).toFixed(parseInt(decimals));
        }
      }
    };
  }
}
