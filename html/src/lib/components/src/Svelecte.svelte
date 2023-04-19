<script lang="ts" context="module">
  import Svelecte, {
    addFormatter,
    config,
    TAB_SELECT_NAVIGATE,
  } from "svelecte/src/Svelecte.svelte";

  type ItemFormat = {
    id: number | string;
    search: string;
    label: string;
    tag: string;
  };

  addFormatter({
    classic: (item: ItemFormat, isSelected: boolean) => {
      if (isSelected) {
        return `<div title="${item.label}">${item.label}</div>`;
      }

      return `<span>${item.label}</span>`;
    },
    withTags: (item: ItemFormat, isSelected: boolean) => {
      if (isSelected) {
        return `<div title="${item.label}">${item.tag}</div>`;
      }

      return `<span>${item.label}</span>`;
    },
  });
</script>

<script lang="ts">
  import Item from "svelecte/src/components/Item.svelte";
  import { onDestroy, getContext } from "svelte";
  import type { Stores } from "@app/types";
  import type { Unsubscriber } from "svelte/store";

  // form and CE
  export let name = "svelecte";
  export let inputId = null;
  export let required = false;
  export let hasAnchor = false;
  export let disabled = config.disabled;
  // basic
  export let options = [];
  export let valueField = config.valueField;
  export let labelField = config.labelField;
  export let groupLabelField = config.groupLabelField;
  export let groupItemsField = config.groupItemsField;
  export let disabledField = config.disabledField;
  export let placeholder = name !== "svelecte" ? name : config.placeholder;
  // UI, UX
  export let searchable = config.searchable;
  export let clearable = config.clearable;
  export let renderer = null;
  export let disableHighlight = false;
  export let selectOnTab = config.selectOnTab;
  export let resetOnBlur = config.resetOnBlur;
  export let resetOnSelect = config.resetOnSelect;
  export let closeAfterSelect = config.closeAfterSelect;
  export let dndzone = () => ({ noop: true, destroy: () => {} });
  export let validatorAction = null;
  export let dropdownItem = Item;
  export let controlItem = Item;
  // multiple
  export let multiple = config.multiple;
  export let max = config.max;
  export let collapseSelection = config.collapseSelection;
  // creating
  export let creatable = config.creatable;
  export let creatablePrefix = config.creatablePrefix;
  export let allowEditing = config.allowEditing;
  export let keepCreated = config.keepCreated;
  export let delimiter = config.delimiter;
  export let createFilter = null;
  export let createTransform = null;
  // remote
  export let fetch = null;
  export let fetchMode = "auto";
  export let fetchCallback = config.fetchCallback;
  export let fetchResetOnBlur = true;
  export let minQuery = config.minQuery;
  // performance
  export let lazyDropdown = config.lazyDropdown;
  // virtual list
  export let virtualList = config.virtualList;
  export let vlHeight = config.vlHeight;
  export let vlItemSize = config.vlItemSize;
  // sifter related
  export let searchField = null;
  export let sortField = null;
  export let disableSifter = false;
  // styling
  let className = "svelecte-control";
  export { className as class };
  export let style = null;
  // i18n override
  export let i18n = null;
  export let readSelection = null;
  export let value = null;
  export let labelAsValue = false;
  export let valueAsObject = config.valueAsObject;

  export let highlightFirstItem = true;

  let isInvalid = false;
  $: svelecteClass = `${className} ${required && !value ? "invalid" : ""}`;

  // =================
  // Options générales
  // =================
  selectOnTab = multiple ? true : TAB_SELECT_NAVIGATE;
  collapseSelection = multiple;
  highlightFirstItem = required ? true : false;

  i18n = {
    empty: "Aucune donnée",
    nomatch: "Aucune correspondance trouvée",
    max: (num: number) => `Nombre maximum d'items ${num} selectionné`,
    collapsedSelection: (count: number) =>
      `${count} sélectionné${count > 1 ? "s" : ""}`,
    fetchBefore: "Entrez du texte pour rechercher",
    fetchQuery: (minQuery: number) =>
      `Entrez ${
        minQuery > 1 ? `au moins ${minQuery} charactères ` : ""
      }pour rechercher`,
    fetchEmpty: "Aucune donnée ne correspond à votre recherche",
    createRowLabel: (value) => `Create '${value}'`,
  };

  /**
   * Type prédéfini.
   */
  const typesPredefinis = ["tiers", "port", "pays"] as const;
  type ArrayType<T> = T extends readonly (infer U)[] ? U : never;

  export let type: ArrayType<typeof typesPredefinis> | undefined = undefined;

  /**
   * Rôle du tiers.
   */
  export let role:
    | "bois_fournisseur"
    | "bois_client"
    | "bois_transporteur"
    | "bois_affreteur"
    | "vrac_fournisseur"
    | "vrac_client"
    | "vrac_transporteur"
    | "maritime_armateur"
    | "maritime_affreteur"
    | "maritime_courtier" = undefined;

  if (typesPredefinis.includes(type)) {
    valueField = "id";
    labelField = "label";
    searchField = "search";
    sortField = "label";
    renderer = multiple ? "withTags" : "classic";
  }

  let unsubscribe: Unsubscriber;

  if (type === "tiers") {
    const { tiers } = getContext<Stores>("stores");
    unsubscribe = tiers.subscribe((listeTiers) => {
      if (listeTiers) {
        options = [...listeTiers.values()]
          .filter((tiers) => (role ? tiers[role] : true))
          .map((tiers): ItemFormat => {
            return {
              id: tiers.id,
              search: `${tiers.nom_court} ${tiers.nom_complet} ${tiers.ville}`,
              label: `${tiers.nom_court} - ${tiers.ville}`,
              tag: tiers.nom_court,
            };
          });
      }
    });
  }

  if (type === "pays") {
    const { pays } = getContext<Stores>("stores");
    unsubscribe = pays.subscribe((listePays) => {
      if (listePays) {
        options = listePays.map((pays): ItemFormat => {
          return {
            id: pays.iso,
            search: `${pays.nom} ${pays.iso}`,
            label: pays.nom,
            tag: pays.iso,
          };
        });
      }
    });
  }

  if (type === "port") {
    const { ports } = getContext<Stores>("stores");
    unsubscribe = ports.subscribe((listePorts) => {
      if (listePorts) {
        options = listePorts.map((port): ItemFormat => {
          return {
            id: port.locode,
            search: `${port.nom} ${port.nom_affichage} ${port.locode}`,
            label: port.nom_affichage,
            tag: port.locode,
          };
        });
      }
    });

    virtualList = true;
  }

  onDestroy(() => {
    if (unsubscribe) unsubscribe();
  });
</script>

{#if options.length > 0}
  <Svelecte
    {name}
    {inputId}
    {required}
    {hasAnchor}
    {disabled}
    {options}
    {valueField}
    {labelField}
    {groupLabelField}
    {groupItemsField}
    {disabledField}
    {placeholder}
    {searchable}
    {clearable}
    {renderer}
    {disableHighlight}
    {selectOnTab}
    {resetOnBlur}
    {resetOnSelect}
    {closeAfterSelect}
    {dndzone}
    {validatorAction}
    {dropdownItem}
    {controlItem}
    {multiple}
    {max}
    {collapseSelection}
    {creatable}
    {creatablePrefix}
    {allowEditing}
    {keepCreated}
    {delimiter}
    {createFilter}
    {createTransform}
    {fetch}
    {fetchMode}
    {fetchCallback}
    {fetchResetOnBlur}
    {minQuery}
    {lazyDropdown}
    {virtualList}
    {vlHeight}
    {vlItemSize}
    {searchField}
    {sortField}
    {disableSifter}
    class={svelecteClass}
    {style}
    {i18n}
    {readSelection}
    bind:value
    {labelAsValue}
    {valueAsObject}
    on:input
    on:change
    {highlightFirstItem}
    {...$$restProps}
  />
{:else}
  <Svelecte />
{/if}

<style>
  :root {
    --sv-border-invalid: 2px solid var(--error-color, red);
  }

  :global(.svelecte.svelecte-control:has(select:invalid)) {
    --sv-border: var(--sv-border-invalid);
  }

  @supports not selector(:has(a, b)) {
    :global(.svelecte.svelecte-control.invalid) {
      --sv-border: var(--sv-border-invalid);
    }
  }
</style>
