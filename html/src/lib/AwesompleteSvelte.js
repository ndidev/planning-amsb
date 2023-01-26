import * as specificity from "./cssSpecificity.js";
import { getCssRules } from "./getCssRules.js";

export { Awesomplete, AwesompleteWithTags };

/**
 * Awesomplete.
 *
 * Simple, lightweight, usable local autocomplete library for modern browsers.
 *
 * Because there weren’t enough autocomplete scripts in the world?
 * Because I’m completely insane and have NIH syndrome?
 * Probably both. :P
 *
 * @fires Awesomplete#awesomplete-open
 * @fires Awesomplete#awesomplete-highlight
 * @fires Awesomplete#awesomplete-select
 * @fires Awesomplete#awesomplete-selectcomplete
 * @fires Awesomplete#awesomplete-close
 *
 * @author Lea Verou <https://leaverou.github.io/awesomplete>
 * @author Nicolas DENIS <https://scoopandrun.github.io>
 *
 * @license MIT
 */
class Awesomplete {
  // Enhanced Awesomplete options

  /**
   * HTML context of the input field.
   *
   * @type {HTMLElement}
   */
  context;

  /**
   * Allow multiple values to be selected.
   *
   * @type {boolean}
   */
  multiple;

  /**
   * Allow duplicate values to be selected.
   *
   * @type {boolean}
   */
  duplicates;

  // Original Awesomplete options

  /**
   * Minimum characters the user has to type before suggestions are displayed.
   *
   * @type {number}
   */
  minChars;

  /**
   * Maximum number of suggestions to display.
   *
   * @type {number}
   */
  maxItems;

  /**
   * Trim the user input.
   *
   * @type {boolean}
   */
  trimInput;

  /**
   * Automatically highlight the first suggestion in the list.
   *
   * @type {boolean}
   */
  autoFirst;

  /**
   * Allow selection validation with the tab key.
   *
   * @type {boolean}
   */
  tabSelect;

  /**
   * Controls suggestions' label and value.
   *
   * @type {Function}
   */
  data;

  /**
   * Controls how entries get matched.
   *
   * @type {Function}
   */
  filter;

  /**
   * Controls how list items are ordered.
   *
   * @type {Function|boolean}
   */
  sort;

  /**
   * Controls how list container element is generated.
   *
   * @type {Function}
   */
  container;

  /**
   * Controls how list items are generated.
   *
   * @type {Function}
   */
  item;

  /**
   * Controls how the user’s selection replaces the user’s input.
   *
   * @type {Function}
   */
  replace;

  /**
   * Denotes a label to be used as aria-label on the generated autocomplete list.
   *
   * @type {string}
   */
  listLabel;

  /**
   * Display suggestions regardless of diacritics.
   *
   * @type {boolean}
   */
  ignoreDiacritics;

  /**
   * List of data to populate the Awesomplete instance.
   *
   * @type {any[]}
   */
  _list;

  /**
   * Ordinal number of the current Awesomplete instance.
   *
   * @type {number}
   */
  count;

  // === Setup ===

  /**
   * Open/closed state of the current Awesomplete widget.
   *
   * @type {boolean}
   */
  isOpened;

  /**
   * Value of the item(s)
   * @type {string}
   */
  value;

  /**
   * Creates an Awesomplete instance.
   *
   * @param {HTMLElement|string} input                    Input field (or CSS selector) to apply Awesomplete to
   * @param {Object}             options                  List of options to customize the widget
   * @param {HTMLElement}        options.context          HTML context of the input field
   * @param {boolean}            options.multiple         Allow multiple values to be selected
   * @param {boolean}            options.duplicates       Allow duplicate values to be selected
   * @param {number}             options.minChars         Minimum characters the user has to type before suggestions are displayed
   * @param {number}             options.maxItems         Maximum number of suggestions to display
   * @param {boolean}            options.trimInput        Trim user input value
   * @param {boolean}            options.autoFirst        Automatically highlight the first suggestion in the list
   * @param {boolean}            options.tabSelect        Allow selection validation with the tab key
   * @param {Function}           options.data             Controls suggestions' label and value
   * @param {Function}           options.filter           Controls how entries get matched
   * @param {Function|false}     options.sort             Controls how list items are ordered
   * @param {Function}           options.container        Controls how list container element is generated
   * @param {Function}           options.item             Controls how list items are generated
   * @param {Function}           options.replace          Controls how the user’s selection replaces the user’s input
   * @param {string}             options.listLabel        Denotes a label to be used as aria-label on the generated autocomplete list
   * @param {boolean}            options.ignoreDiacritics Display suggestions regardless of diacritics
   * @param {Function}           callback                 Function to execute after change (awesomplete-selectcomplete event)
   */
  constructor(input, options = {}, callback = () => {}) {
    /**
     * Current Awesomplete instance.
     *
     * @type {Awesomplete}
     */
    const me = this;

    this.callback = callback;

    // Increment the count of Awesomplete instances,
    // then assign the current count to this instance
    this.count = ++Awesomplete.count;

    // === Setup ===

    this.isOpened = false;

    // === Input setup ===

    /**
     * HTML context of the input.
     *
     * @type {HTMLElement|Document}
     */
    const context = options.context || Awesomplete.defaults.context;

    // The selector will be used to register the input fields
    let selector = input;

    // If "input" is an element, look for "_user" in the id and className to infer the selector from this
    if (typeof input !== "string") {
      // Look in the id
      if (/_user$/.test(input.id)) {
        selector = "#" + input.id.replace(/_user/i, "");
      }

      // Look in the className
      if (/_user\b/.test(input.className)) {
        selector =
          "." +
          input.className
            .split(" ")
            .find((class_name) => /_user$/i.test(class_name))
            ?.replace(/_user/i, "");
      }
    }

    /**
     * Input field to which the current Awesomplete instance is applied.
     *
     * @type {HTMLInputElement}
     */
    this.input = Awesomplete.$(input, context);

    // Change the attributes of the input field
    this.input.setAttribute("autocomplete", "off");
    this.input.setAttribute("aria-expanded", "false");
    this.input.setAttribute("aria-owns", "awesomplete_list_" + this.count);
    this.input.setAttribute("role", "combobox");

    // Set the instance properties
    this.configure(Awesomplete.defaults, options);

    /**
     * Index of the current selected suggestion.
     *
     * @type {number}
     */
    this.index = -1;

    // === Create necessary elements ===

    /**
     * Wrapper for the input field.
     *
     * @type {HTMLElement}
     */
    this.container = this.container(this.input);

    /**
     * Suggestion list container.
     *
     * @type {HTMLUListElement}
     */
    this.ul = Awesomplete.create("ul", {
      hidden: "hidden",
      role: "listbox",
      id: "awesomplete_list_" + this.count,
      inside: this.container,
      "aria-label": this.listLabel,
    });
    // console.debug(this.count, "ul", "should be second");

    /**
     * Span element to indicate the status of the widget.
     *
     * Used for accessibility pruposes.
     *
     * @type {HTMLSpanElement}
     */
    this.status = Awesomplete.create("span", {
      className: "visually-hidden",
      role: "status",
      "aria-live": "assertive",
      "aria-atomic": true,
      inside: this.container,
      textContent:
        this.minChars != 0
          ? "Type " + this.minChars + " or more characters for results."
          : "Begin typing for results.",
    });
    // console.debug(this.count, "span", "should be second");

    // === Bind events ===

    this._events = {
      input: {
        input: this.evaluate.bind(this),
        blur: this.close.bind(this, { reason: "blur" }),
        /**
         * Action to perform on keydown.
         *
         * @param {Event} event Keydown event
         */
        keydown: function (event) {
          /**
           * Key string of the pressed key.
           *
           * @type {string}
           */
          const c = event.key;

          // If the dropdown `ul` is in view, then act on keydown for the following keys:
          // Enter / Esc / Up / Down / Tab (if tabSelect true)
          if (me.opened) {
            if (c === "Enter" && me.selected) {
              // Enter
              event.preventDefault();
              me.select(undefined, undefined, event);
            } else if (c === "Tab" && me.selected && me.tabSelect) {
              // Tab
              me.select(undefined, undefined, event);
            } else if (c === "Escape") {
              // Esc
              me.close({ reason: "esc" });
            } else if (c === "ArrowUp" || c === "ArrowDown") {
              // Down/Up arrow
              event.preventDefault();
              me[c === "ArrowUp" ? "previous" : "next"]();
            }
          }
        },
      },
      form: {
        submit: this.close.bind(this, { reason: "submit" }),
      },
      ul: {
        /**
         * Prevent the default mousedown, which ensures the input is not blurred.
         *
         * The actual selection will happen on click. This also ensures dragging the
         * cursor away from the list item will cancel the selection
         *
         * @param {Event} event Mousedown event.
         */
        mousedown: function (event) {
          event.preventDefault();
        },
        /**
         * The click event is fired
         * even if the corresponding mousedown event has called preventDefault.
         *
         * @param {Event} event Click event.
         */
        click: function (event) {
          let li = event.target;

          if (li !== this) {
            while (li && !/li/i.test(li.nodeName)) {
              li = li.parentNode;
            }

            if (li && event.button === 0) {
              // Only select on left click
              event.preventDefault();
              me.select(li, event.target, event);
            }
          }
        },
      },
    };

    Awesomplete.bind(this.input, this._events.input);
    Awesomplete.bind(this.input.form, this._events.form);
    Awesomplete.bind(this.ul, this._events.ul);

    // Store the current Awesomplete instance
    Awesomplete.all.push(this);

    // For debug purposes only
    window.awesompletes = Awesomplete.all;
    // console.debug(window.awesompletes);
  }

  // Methods

  /**
   * Sets the properties of the Awesomplete instance.
   *
   * @param {Object}             defaults Default properties of Awesomplete.
   * @param {Object}             options  Options submitted by the user.
   */
  configure(defaults, options) {
    // Getting the "data-" values if they are set
    const attrValues = {};
    for (const i in defaults) {
      const attrValue = this.input.dataset[i.toLowerCase()];
      if (attrValue) {
        attrValues[i] = attrValue;
      }
    }

    // Merging all the properties with priority order :
    // data-attr > options > defaults
    // and applying them to the current instance
    const properties = { ...defaults, ...options, ...attrValues };
    for (const i in properties) {
      // Apply all properties to the instance, except for "list"
      // The list is parsed later in the constructor
      if (i !== "list") {
        this[i] = properties[i];
      }
    }

    // Setting the data list
    if (this.input.hasAttribute("list")) {
      this.list = "#" + this.input.getAttribute("list");
      this.input.removeAttribute("list");
    } else {
      this.list = this.input.getAttribute("data-list") || options.list || [];
    }
  }

  /**
   * Set the list of items of the Awesomplete instance.
   *
   * @param {any[]|string} list
   */
  set list(list) {
    if (Array.isArray(list)) {
      // Provided list is an array
      this._list = list;
    } else if (typeof list === "string" && list.indexOf(",") > -1) {
      // Provided list is a coma-separated string
      this._list = list.split(/\s*,\s*/);
    } else {
      // Element or CSS selector
      list = Awesomplete.$(list);

      if (list && list.children) {
        const items = [];
        Awesomplete.slice.apply(list.children).forEach(function (element) {
          if (!element.disabled) {
            const text = element.textContent.trim();
            const value = element.value || text;
            const label = element.label || text;
            if (value !== "") {
              items.push({ label: label, value: value });
            }
          }
        });
        this._list = items;
      }
    }

    // Refresh the UL if the input is focused
    // and the instance is fully initialised
    if (document.activeElement === this.input && this.status) {
      this.evaluate();
    }
  }

  /**
   * List of items of the Awesomplete instance.
   *
   * @return {any[]} List
   */
  get list() {
    return this._list;
  }

  /**
   * Selection state of the widget.
   *
   * @returns {boolean} True if a suggestion is selected, false otherwise.
   */
  get selected() {
    return this.index > -1;
  }

  /**
   * Open/closed state of the suggestion list.
   *
   * @returns {boolean} True if the suggestion list is displayed, false otherwise.
   */
  get opened() {
    return this.isOpened;
  }

  /**
   * Closes the popup.
   *
   * @param {Object} options Options
   */
  close(options) {
    if (!this.opened) {
      return;
    }

    this.input.setAttribute("aria-expanded", "false");
    this.ul.setAttribute("hidden", "");
    this.isOpened = false;
    this.index = -1;

    this.status.setAttribute("hidden", "");

    Awesomplete.fire(this.input, "awesomplete-close", options || {});
  }

  /**
   * Opens the popup.
   */
  open() {
    this.input.setAttribute("aria-expanded", "true");
    this.ul.removeAttribute("hidden");
    this.isOpened = true;

    this.status.removeAttribute("hidden");

    if (this.autoFirst && this.index === -1) {
      this.goto(0);
    }

    Awesomplete.fire(this.input, "awesomplete-open");
  }

  /**
   * Highlights the next item in the popup.
   */
  next() {
    /**
     * Number of items in the list.
     */
    const count = this.ul.children.length;

    this.goto(this.index < count - 1 ? this.index + 1 : count ? 0 : -1);
  }

  /**
   * Highlights the previous item in the popup.
   */
  previous() {
    /**
     * Number of items in the list.
     */
    const count = this.ul.children.length;

    this.goto(this.index > 0 ? this.index - 1 : count ? count - 1 : -1);
  }

  /**
   * Highlights the item with index `i` in the popup (`-1` to deselect all).
   *
   * Avoid using this directly and try to use `next()` or `previous()` instead when possible.
   *
   * Should not be used, highlights specific item without any checks!
   *
   * @param {number} i Item index
   */
  goto(i) {
    /**
     * List of LI elements in the suggestion list.
     */
    const lis = this.ul.children;

    if (this.selected) {
      lis[this.index].setAttribute("aria-selected", "false");
    }

    this.index = i;

    if (i > -1 && lis.length > 0) {
      lis[i].setAttribute("aria-selected", "true");

      this.status.textContent =
        lis[i].textContent + ", list item " + (i + 1) + " of " + lis.length;

      this.input.setAttribute(
        "aria-activedescendant",
        this.ul.id + "_item_" + this.index
      );

      // scroll to highlighted element in case parent's height is fixed
      this.ul.scrollTop =
        lis[i].offsetTop - this.ul.clientHeight + lis[i].clientHeight;

      Awesomplete.fire(this.input, "awesomplete-highlight", {
        text: this.suggestions[this.index],
      });
    }
  }

  /**
   * Selects the currently highlighted item,
   * replaces the text field’s value with it and closes the popup.
   *
   * @param {HTMLLIElement} selected      Selected element in the suggestion list.
   * @param {HTMLElement}   origin        Clicked element in the suggestion list.
   * @param {Event}         originalEvent Event that caused the selection.
   */
  select(selected, origin, originalEvent) {
    if (selected) {
      this.index = Awesomplete.siblingIndex(selected);
    } else {
      selected = this.ul.children[this.index];
    }

    if (selected) {
      const suggestion = this.suggestions[this.index];

      const allowed = Awesomplete.fire(this.input, "awesomplete-select", {
        text: suggestion,
        origin: origin || selected,
        originalEvent,
      });

      if (allowed) {
        this.replace(suggestion);
        this.close({ reason: "select" });
        Awesomplete.fire(this.input, "awesomplete-selectcomplete", {
          text: suggestion,
          originalEvent,
        });

        this.callback();
      }
    }
  }

  /**
   * Evaluates the current state of the widget
   * and regenerates the list of suggestions or closes the popup if none are available.
   *
   * You need to call it if you dynamically set `list` while the popup is open.
   */
  evaluate() {
    /**
     * User input value.
     */
    let inputValue = this.multiple
      ? this.input.value.match(/[^,]*$/)[0]
      : this.input.value;

    inputValue = this.trimInput ? inputValue.trim() : inputValue;

    inputValue = this.ignoreDiacritics
      ? Awesomplete.removeDiacritics(inputValue)
      : inputValue;

    // Evaluate only if conditions are met
    if (
      inputValue.length >= this.minChars &&
      this.list &&
      this.list.length > 0
    ) {
      // Set current selected suggestion to none
      this.index = -1;

      // Clear the suggestion list
      this.ul.innerHTML = "";

      /**
       * List of suggestion objects.
       *
       * @type {Suggestion[]}
       */
      this.suggestions = this.list
        .map((item) => {
          // Build the full suggestion list
          return new Suggestion(this.data(item, inputValue));
        })
        .filter((item) => {
          // Filter based on the input value
          item = this.ignoreDiacritics
            ? Awesomplete.removeDiacritics(item)
            : item;
          return this.filter(item, inputValue);
        });

      // Sort the list if needed
      if (this.sort !== false) {
        this.suggestions = this.suggestions.sort(this.sort);
      }

      // Keep only the first few items (set by this.maxItems)
      this.suggestions = this.suggestions.slice(0, this.maxItems);

      // Append to the suggestion list UL
      this.suggestions.forEach((item, index) => {
        this.ul.appendChild(this.item(item, inputValue, index));
      });

      // If there is no match
      const matches = this.ul.children.length;
      if (matches === 0) {
        this.status.textContent = "No results found";

        this.close({ reason: "nomatches" });
      } else {
        this.open();

        this.status.textContent =
          matches + " " + (matches > 1 ? "results" : "result") + " found";
      }
    } else {
      this.close({ reason: "nomatches" });

      this.status.textContent = "No results found";
    }
  }

  /**
   * Clean up and remove the instance from the input.
   *
   * The container is only removed if it wasn't manually set but created by Awesomplete.
   */
  destroy() {
    //remove events from the input and its form
    Awesomplete.unbind(this.input, this._events.input);
    Awesomplete.unbind(this.input.form, this._events.form);

    // cleanup container if it was created by Awesomplete but leave it alone otherwise
    if (!this.options.container) {
      //move the input out of the awesomplete container and remove the container and its children
      const parentNode = this.container.parentNode;

      parentNode.insertBefore(this.input, this.container);
      parentNode.removeChild(this.container);
    }

    //remove autocomplete and aria-autocomplete attributes
    this.input.removeAttribute("autocomplete");
    this.input.removeAttribute("aria-autocomplete");

    //remove this awesomeplete instance from the global array of instances
    const indexOfAwesomplete = Awesomplete.all.indexOf(this);

    if (indexOfAwesomplete !== -1) {
      Awesomplete.all.splice(indexOfAwesomplete, 1);
    }
  }

  // Static methods/properties

  /**
   * Default values for the options.
   */
  static defaults = {
    // Enhanced Awesomplete options
    context: document,
    multiple: false,
    duplicates: true,
    trimInput: true,

    // Original Awesomplete options
    minChars: 2,
    maxItems: 10,
    autoFirst: true,
    tabSelect: true,
    data: Awesomplete.DATA,
    filter: Awesomplete.FILTER_CONTAINS,
    sort: Awesomplete.SORT_BYLENGTH,
    container: Awesomplete.CONTAINER,
    item: Awesomplete.ITEM,
    replace: Awesomplete.REPLACE,
    listLabel: "Results List",
    ignoreDiacritics: true,
  };

  /**
   * Total number of Awesomplete instances created.
   *
   * If an instance is removed/destroyed, the count is *not* decremented.
   */
  static count = 0;

  /**
   * Contains all the instances of the Awesomplete class.
   *
   * @type {Awesomplete[]}
   */
  static all = [];

  /**
   * Filters the list items based on the input value.
   *
   * @param {Suggestion} item  Item of the instance's list.
   * @param {string}     input Value of the input field.
   *
   * @returns {boolean} True if the item's label contains the input value, false
   */
  static FILTER_CONTAINS(item, input) {
    if (this.trimInput) {
      // Divided search
      return RegExp("(" + input.split(" ").join(").*(") + ")", "i").test(
        item.label
      );
    } else {
      // Contiguous search
      return RegExp(Awesomplete.regExpEscape(input), "i").test(item.label);
    }
  }

  /**
   * Filters the list items based on the input value.
   *
   * @param {Suggestion} item  Item of the instance's list.
   * @param {string}     input Value of the input field.
   *
   * @returns {boolean} True if the item's label starts with the input value, false
   */
  static FILTER_STARTSWITH(item, input) {
    return RegExp("^" + Awesomplete.regExpEscape(input), "i").test(item.label);
  }

  /**
   * Sorts a list of suggestions by items length.
   *
   * @param {Suggestion} a Item a
   * @param {Suggestion} b Item b
   *
   * @returns {number}
   */
  static SORT_BYLENGTH(a, b) {
    if (a.label.length !== b.label.length) {
      return a.label.length - b.label.length;
    }

    return a.label < b.label ? -1 : 1;
  }

  /**
   * Controls how list container element is generated.
   *
   * @param {HTMLElement} input
   *
   * @returns {HTMLElement}
   */
  static CONTAINER(input) {
    return Awesomplete.create("div", {
      className: "awesomplete",
      around: input,
    });
  }

  /**
   * Creates a suggestion list item.
   *
   * @param {Suggestion} item    Suggestion item.
   * @param {string}     input   Input value.
   * @param {number}     item_id Index of the item in the suggestions array.
   *
   * @returns {HTMLLIElement}
   */
  static ITEM(item, input, item_id) {
    const text = item.label;
    input = this.ignoreDiacritics ? Awesomplete.removeDiacritics(input) : input;

    const text_comp = this.ignoreDiacritics
      ? Awesomplete.removeDiacritics(text)
      : text;

    // shadow = text without diacritics (for comparison)
    const shadow =
      input === ""
        ? text
        : text_comp.replace(
            RegExp("(" + input.split(" ").join(").*(") + ")", "gi"),
            "<mark>$&</mark>"
          );

    // Display diacritics in suggestions (html = with diacritics)
    let html = "";
    let index = 0;
    shadow.split(/(<\/?mark>)/).forEach((part) => {
      if (!/<\/?mark>/.test(part)) {
        html += text.substr(index, part.length);
        index += part.length;
      } else {
        html += part;
      }
    });

    return Awesomplete.create("li", {
      innerHTML: html,
      role: "option",
      "aria-selected": "false",
      id: "awesomplete_list_" + this.count + "_item_" + item_id,
    });
  }

  /**
   * Action to perform on item selection.
   *
   * @param {Suggestion} item
   */
  static REPLACE(item) {
    if (this.multiple) {
      const before_user = this.input.value.match(/^.+,\s*|/)[0];
      const before_value = this.value.match(/^.+,\s*|/)[0];
      if (!this.duplicates) {
        if (!RegExp("\\b" + item.value + "\\b").test(this.value)) {
          this.input.value = before_user + item.label + ", ";
          this.value = before_value + item.value + ",";
        } else {
          this.input.value = before_user;
        }
      } else {
        this.input.value = before_user + item.label + ", ";
        this.value = before_value + item.value + ",";
      }
    } else {
      this.input.value = item.label;
      this.value = item.value;
    }
  }

  static DATA(item /*, input*/) {
    return item;
  }

  // Helpers

  static slice = Array.prototype.slice;

  /**
   * Returns an `HTMLElement`.
   *
   * @param {HTMLElement|string} expression HTML element or CSS selector.
   * @param {HTMLElement}        context    HTML context of the CSS selector.
   *
   * @returns {HTMLElement} HTML element.
   */
  static $(expression, context = document) {
    return typeof expression === "string"
      ? context.querySelector(expression)
      : expression || null;
  }

  /**
   * Creates an HTMLElement with options.
   *
   * @param {string} tag     Tag of the HTML element to create.
   * @param {Object} options Options for the new element.
   *
   * @returns {HTMLElement}
   */
  static create(tag, options) {
    const element = document.createElement(tag);

    for (const i in options) {
      const val = options[i];

      if (i === "inside") {
        Awesomplete.$(val).appendChild(element);
      } else if (i === "around") {
        const ref = Awesomplete.$(val);
        ref.parentNode.insertBefore(element, ref);
        element.appendChild(ref);

        if (ref.getAttribute("autofocus") != null) {
          ref.focus();
        }
      } else if (i in element) {
        element[i] = val;
      } else {
        element.setAttribute(i, val);
      }
    }

    if (["ul", "span"].includes(tag)) {
      // console.debug(this.count, tag, "should be first");
    }

    return element;
  }

  /**
   * Bind events to HTML elements.
   *
   * @param {HTMLElement} element
   * @param {Object}      options
   */
  static bind(element, options) {
    if (element) {
      for (const event in options) {
        const callback = options[event];

        event.split(/\s+/).forEach(function (event) {
          element.addEventListener(event, callback);
        });
      }
    }
  }

  /**
   * Unbind events from HTML elements.
   *
   * @param {HTMLElement} element
   * @param {Object}      options
   */
  static unbind(element, options) {
    if (element) {
      for (const event in options) {
        const callback = options[event];

        event.split(/\s+/).forEach(function (event) {
          element.removeEventListener(event, callback);
        });
      }
    }
  }

  /**
   * Fires a custom event.
   *
   * @param {HTMLElement} target     Event target.
   * @param {string}      type       Event name.
   * @param {Object}      properties Event properties.
   *
   * @returns {boolean} True if the event has been dispatched, false otherwise.
   */
  static fire(target, type, properties) {
    const evt = new Event(type, {
      bubbles: true,
      cancelable: true,
    });

    for (const j in properties) {
      evt[j] = properties[j];
    }

    evt.awesompleteInstance = this;

    return target.dispatchEvent(evt);
  }

  /**
   * Creates a RegExp string from a normal string.
   *
   * @param {string} string Normal string.
   *
   * @returns {string} RegExp string with escaped characters.
   */
  static regExpEscape(string) {
    return string.replace(/[-\\^$*+?.()|[\]{}]/g, "\\$&");
  }

  /**
   * Returns the index of the selected element.
   *
   * @param {HTMLLIElement} element Selected element in the suggestion list.
   * @returns
   */
  static siblingIndex(element) {
    /* eslint-disable no-cond-assign */
    let i = 0;
    for (; (element = element.previousElementSibling); i++);
    return i;
  }

  /**
   * Returns a string or object without diacritics.
   *
   * @param {string|Object} input
   *
   * @returns {string|Object} String or object without diacritics
   */
  static removeDiacritics(input) {
    if (typeof input === "string") {
      return input
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "")
        .replace(/ø/, "o");
    }

    if (typeof input === "object") {
      return {
        label: input.label
          .normalize("NFD")
          .replace(/[\u0300-\u036f]/g, "")
          .replace(/ø/gi, "o"),
        value:
          typeof input.value === "string"
            ? input.value
                .normalize("NFD")
                .replace(/[\u0300-\u036f]/g, "")
                .replace(/ø/gi, "o")
            : input.value,
      };
    }
  }
}

/**
 * Awesomplete with tags.
 *
 * Extension for the Awesomplete class.
 *
 * @author Nicolas DENIS <https://scoopandrun.github.io>
 *
 * @license MIT
 */
class AwesompleteWithTags extends Awesomplete {
  /**
   * Contains the values of the selected items.
   *
   * @type {string[]}
   */
  tagStore = [];

  /**
   * Creates an Awesomplete instance.
   *
   * @param {HTMLElement|string} input                    Input field (or CSS selector) to apply Awesomplete to.
   * @param {Object}             options                  List of options to customize the widget.
   * @param {HTMLElement}        options.context          HTML context of the input field.
   * @param {boolean}            options.multiple         Allow multiple values to be selected.
   * @param {boolean}            options.duplicates       Allow duplicate values to be selected.
   * @param {number}             options.minChars         Minimum characters the user has to type before suggestions are displayed.
   * @param {number}             options.maxItems         Maximum number of suggestions to display.
   * @param {boolean}            options.trimInput        Trim user input value.
   * @param {boolean}            options.autoFirst        Automatically highlight the first suggestion in the list.
   * @param {boolean}            options.tabSelect        Allow selection validation with the tab key.
   * @param {Function}           options.data             Controls suggestions' label and value.
   * @param {Function}           options.filter           Controls how entries get matched.
   * @param {Function|false}     options.sort             Controls how list items are ordered.
   * @param {Function}           options.container        Controls how list container element is generated.
   * @param {Function}           options.item             Controls how list items are generated.
   * @param {Function}           options.replace          Controls how the user’s selection replaces the user’s input.
   * @param {string}             options.listLabel        Denotes a label to be used as aria-label on the generated autocomplete list.
   * @param {boolean}            options.ignoreDiacritics Display suggestions regardless of diacritics.
   */
  constructor(input, options = {}) {
    super(input, options);

    this.replace = options.replace || AwesompleteWithTags.REPLACE;

    // Transfer styles from input to container
    this.container.style.cssText = AwesompleteWithTags.getCssText(this.input);

    // Render real input invisible
    const invisibleInputStyles = {
      border: "none",
      padding: 0,
      margin: "0px 2px",
      boxShadow: "none",
      display: "inline-block",
      width: "4px",
    };
    for (const property in invisibleInputStyles) {
      this.input.style[property] = invisibleInputStyles[property];
    }

    // On click on container, focus input
    this.container.addEventListener("click", (e) => {
      if (e.target === this.container) {
        this.input.hidden = false;
        this.keepInputBeforeUL();
        this.input.focus();
      }
    });

    // Set the correct width to the input field on focus
    this.input.addEventListener("focus", () => {
      this.input.style.width = this.getInputWidth();
    });

    // If the input is empty,
    // move the input after the tags on blur
    // and hide it if the container is not empty
    this.input.addEventListener("blur", () => {
      if (!this.input.value) {
        this.moveInput("end");
      }
    });

    // Use the keyboard to move the input, delete tags...
    this.input.addEventListener("keydown", (event) => {
      switch (event.key) {
        case "Backspace":
          // Backspace => if the input is empty, delete the preceeding tag
          if (!this.input.value) {
            this.removeTag("before");
          }
          break;

        case "Delete":
          // Delete => if the input is empty, delete the following tag
          if (!this.input.value) {
            this.removeTag("after");
          }
          break;

        case "ArrowLeft":
          // Left => if the input is empty, move the input left of the preceeding tag
          if (!this.input.value) {
            this.moveInput("before");
          }
          break;

        case "ArrowRight":
          // Right => if the input is empty, move the input right of the following tag
          if (!this.input.value) {
            this.moveInput("after");
          }
          break;

        case "Home":
          // Home => if the input is empty, move the input before the first tag
          if (!this.input.value) {
            event.preventDefault();
            this.moveInput("home");
          }
          break;

        case "End":
          // End => if the input is empty, move the input after the last tag
          if (!this.input.value) {
            event.preventDefault();
            this.moveInput("end");
          }
          break;

        default:
          break;
      }
    });

    this.input.addEventListener("input", () => {
      this.input.style.width = this.getInputWidth();
    });

    this.container.querySelectorAll(".awesomplete-tag").forEach((tag) => {
      tag.onclick = () => {
        tag.remove();
        this.updateTagStore();
      };
    });

    // Update tag store on page load
    this.updateTagStore();
  }

  /**
   * Returns true if tags are present, false otherwise.
   *
   * @returns {boolean}
   */
  hasTags() {
    return !!this.container.querySelectorAll(".awesomplete-tag").length;
  }

  /**
   * Update the tag store.
   *
   * Fills the tag store with all the values of the tags.
   */
  updateTagStore() {
    this.tagStore = [];
    this.container.querySelectorAll(".awesomplete-tag").forEach((tag) => {
      this.tagStore.push(tag.dataset.value);
    });

    this.value = this.tagStore.join(",");

    Awesomplete.fire(this.input, "awesomplete-tagsupdated");
  }

  /**
   * Creates a tag (span element) and adds it to the input container.
   *
   * @param {Suggestion} item Selected suggestion item.
   *
   * @returns {HTMLSpanElement} Tag
   */
  addTag(item) {
    const tag = document.createElement("span");
    tag.className = "awesomplete-tag";
    tag.dataset.value = item.value;
    tag.isAwesompleteTag = true;
    tag.textContent = item.label;
    tag.onclick = () => {
      tag.remove();
      this.updateTagStore();
      this.input.hidden = this.hasTags();
    };

    this.keepInputBeforeUL();
    this.input.before(tag);

    this.updateTagStore();
  }

  /**
   * Deletes the previous/next tag.
   *
   * @param {string} direction "Before" or "After"
   */
  removeTag(direction) {
    let neighbor;

    // Move the input while we have not encourtered a tag
    do {
      neighbor = (() => {
        if (direction === "before") return this.input.previousElementSibling;
        if (direction === "after") return this.input.nextElementSibling;
      })();

      neighbor?.[direction](this.input);
    } while (neighbor && !neighbor.isAwesompleteTag);

    // Delete the first encountered tag
    neighbor?.remove();
    this.updateTagStore();

    this.input.focus();
  }

  /**
   * Deletes all tags from the Awesomplete instance.
   */
  clearTags() {
    this.container
      .querySelectorAll(".awesomplete-tag")
      .forEach((tag) => tag.remove());
    this.updateTagStore();
    this.moveInput("home");
    this.input.blur();
    this.input.hidden = false;
  }

  /**
   * Moves the input before/after the previous/next tag.
   *
   * @param {string} direction "before", "after", "home" or "end"
   */
  moveInput(direction) {
    // If "home" or container empty, move input in first position (before UL)
    if (direction === "home" || !this.hasTags()) {
      this.container.insertBefore(this.input, this.container.firstElementChild);
      this.input.focus(); // Restore the focus to the input
      return;
    }

    // If "end", move the input to the end (but before UL)
    if (direction === "end") {
      this.container.appendChild(this.input);
      this.keepInputBeforeUL();
      this.input.focus(); // Restore the focus to the input
      return;
    }

    let neighbor;

    // Move the input while we have not encourtered a tag
    do {
      neighbor = (() => {
        if (direction === "before") return this.input.previousElementSibling;
        if (direction === "after") return this.input.nextElementSibling;
      })();

      neighbor?.[direction](this.input);
    } while (neighbor && !neighbor.isAwesompleteTag);

    // Move past the first encountered tag
    neighbor?.[direction](this.input);

    // Restore the focus to the input
    this.input.focus();
  }

  /**
   * Keeps the input element before the UL
   * to avoid suggestion list misplacement.
   */
  keepInputBeforeUL() {
    for (const item of this.container.querySelectorAll("*")) {
      // If the input comes first, leave it as it is
      if (item === this.input) {
        break;
      }

      // If the UL comes first, move the input before
      if (item === this.ul) {
        this.ul.before(this.input);
        break;
      }
    }
  }

  /**
   * Calculate the input field's width.
   *
   * @returns {string} Input width in pixels.
   */
  getInputWidth() {
    if (!this.input.value) return "4px";

    const inputWidth =
      document.querySelector("#awesomplete-input-width") ||
      AwesompleteWithTags.createAwesompleteInputWidth();
    inputWidth.textContent = this.input.value;

    // Transfert styles
    [
      "letterSpacing",
      "fontSize",
      "fontFamily",
      "fontWeight",
      "textTransform",
    ].forEach((style) => {
      inputWidth.style[style] = getComputedStyle(this.input)[style];
    });

    return getComputedStyle(inputWidth).width;
  }

  /**
   * Create an invisible element to calculate the width of the input field.
   *
   * @returns {HTMLElement} Div element.
   */
  static createAwesompleteInputWidth() {
    const div = document.createElement("div");
    div.id = "awesomplete-input-width";

    const styles = {
      display: "inline-block",
      zIndex: -1000,
      position: "fixed",
      top: 0,
      left: 0,
      display: "inline-block",
      color: "transparent",
      whiteSpace: "pre",
    };

    for (const [property, value] of Object.entries(styles)) {
      div.style[property] = value;
    }

    document.body.appendChild(div);

    return div;
  }

  /**
   * Gets the cssText for an element or a selector.
   *
   * @param {string|HTMLElement} element
   *
   * @returns {string} CSS text for inline styles.
   */
  static getCssText(element) {
    const cssRules = getCssRules(element);
    const rules = {};

    cssRules.forEach((cssRule, index) => {
      const cssText = cssRule.cssText;
      const selectorText = cssRule.selectorText;
      const ruleSet = cssText.substring(
        cssText.indexOf("{") + 1,
        cssText.indexOf("}")
      );
      ruleSet.split(";").forEach((rule) => {
        selectorText.split(",").forEach((selector) => {
          selector = selector.trim();
          const [property, value] = rule.split(":").map((x) => x.trim());
          if (property && element.matches(selector)) {
            if (!rules[property]) rules[property] = [];
            rules[property].push({
              value,
              selector,
              specificity: specificity.calculate(selector)[0].specificityArray,
              cascadeOrder: index,
            });
          }
        });
      });
    });

    // Sort rules by specificity or cascade order
    for (const property in rules) {
      rules[property].sort(
        (a, b) =>
          specificity.compare(b.specificity, a.specificity) ||
          b.cascadeOrder - a.cascadeOrder
      );
    }

    // Build cssText string with the first set of each property
    let cssText = "";
    for (const [property, sets] of Object.entries(rules)) {
      cssText += `${property}: ${sets[0].value};`;
    }

    return cssText;
  }

  /**
   * Action to perform on item selection.
   *
   * @param {Suggestion} item
   */
  static REPLACE(item) {
    // Add tag
    if (!this.tagStore.includes(item.value) || this.duplicates) {
      this.addTag(item);
    }

    // Clear input and reset input field width
    this.input.value = null;
    this.input.style.width = this.getInputWidth();
  }
}

/**
 * Awesomplete suggestion item.
 */
class Suggestion {
  /**
   * Text label of the suggestion item.
   *
   * @type {string}
   */
  label;

  /**
   * Value of the suggestion item.
   *
   * @type {string}
   */
  value;

  /**
   * Original data.
   *
   * @type {any}
   */
  original;

  /**
   * Creates a suggestion object with a `label` and a `value`.
   *
   * @param {any} data Data to create the suggestion item
   */
  constructor(data) {
    /**
     * Suggestion item.
     *
     * @type {object}
     */
    const item = Array.isArray(data)
      ? { label: data[0], value: data[1] }
      : typeof data === "object" && "label" in data && "value" in data
      ? data
      : { label: data, value: data };

    this.label = item.label || item.value;
    this.value = item.value;
    this.original = item.original;
  }

  /**
   * Returns the suggestion's label.
   *
   * @returns {string} Suggestion label.
   */
  valueOf() {
    return "" + this.label;
  }

  /**
   * Returns the suggestion's label.
   *
   * @alias valueOf
   *
   * @returns {string} Suggestion label.
   */
  toString() {
    return this.valueOf();
  }

  get length() {
    return this.label.length;
  }
}
