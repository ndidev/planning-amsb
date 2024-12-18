<script lang="ts" context="module">
  import Svelecte, { addRenderer, config } from "svelecte";

  type Item = {
    id: number | string;
    search: string;
    label: string;
    tag: string;
  };

  addRenderer("classic", (item: Item, isSelected, inputValue) => {
    return isSelected
      ? `<div title="${item.label}">${item.label}</div>`
      : `<span>${item.label}</span>`;
  });

  addRenderer("withTags", (item: Item, isSelected, inputValue) => {
    return isSelected
      ? `<div title="${item.label}">${item.tag}</div>`
      : `<span>${item.label}</span>`;
  });
</script>

<script lang="ts">
  import { onDestroy } from "svelte";
  import type { Unsubscriber } from "svelte/store";
  import type { SearchProps } from "svelecte/dist/utils/list";

  import { tiers, stevedoringStaff, ports, pays } from "@app/stores";

  // form and CE
  export let name = "svelecte";
  export let inputId = null;
  export let required = false;
  export let disabled = false;
  // basic
  export let options = [];
  let optionResolver = null;
  export let valueField = config.valueField;
  export let labelField = config.labelField;
  export let groupLabelField = config.groupLabelField;
  export let groupItemsField = config.groupItemsField;
  export let disabledField = config.disabledField;
  export let placeholder = name !== "svelecte" ? name : config.placeholder;
  // UI, UX
  export let searchable = config.searchable;
  export let searchProps: SearchProps = {};
  export let clearable = config.clearable;
  export let renderer = null;
  export let disableHighlight = false;
  export let selectOnTab = config.selectOnTab;
  export let resetOnBlur = config.resetOnBlur;
  export let resetOnSelect = config.resetOnSelect;
  export let closeAfterSelect = config.closeAfterSelect;
  export let dndzone = () => ({ noop: true, destroy: () => {} });
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
  // remote
  export let fetch = null;
  export let fetchMode: "auto" | "init" = "auto";
  export let fetchCallback = config.fetchCallback;
  export let fetchResetOnBlur = true;
  export let minQuery = config.minQuery;
  // performance
  export let lazyDropdown = config.lazyDropdown;
  // virtual list
  export let virtualList = config.virtualList;
  export let vlHeight = config.vlHeight;
  export let vlItemSize = config.vlItemSize;
  // styling
  let className = "svelecte-control";
  export { className as class };
  // i18n override
  export let i18n = null;
  export let readSelection = null;
  export let value = null;
  export let valueAsObject = config.valueAsObject;

  export let highlightFirstItem = true;

  $: svelecteClass = `${className} ${required && !value ? "invalid" : ""}`;

  // =================
  // Options générales
  // =================
  selectOnTab = multiple ? true : "select-navigate";
  collapseSelection = multiple ? "blur" : null;
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
    createRowLabel: (value) => `Créer '${value}'`,
  };

  /**
   * Type prédéfini.
   */
  const typesPredefinis = [
    "tiers",
    "port",
    "pays",
    "staff",
    "mensuels",
    "interimaires",
  ] as const;

  export let type: (typeof typesPredefinis)[number] | undefined = undefined;

  export let includeInactive = false;

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
    searchProps.fields = ["search"];
    searchProps.sort = "label";
    renderer = multiple ? "withTags" : "classic";
  }

  let unsubscribe: Unsubscriber;

  if (type === "tiers") {
    unsubscribe = tiers.subscribe((listeTiers) => {
      if (listeTiers) {
        options = [...listeTiers.values()]
          .filter((tiers) => {
            return (
              value === tiers.id || // Include the selected value even if it's inactive or doesn't match the role
              ((role ? tiers.roles[role] : true) &&
                (includeInactive ? true : tiers.actif))
            );
          })
          .map((tiers): Item => {
            return {
              id: tiers.id,
              search: `${tiers.nom_court} ${tiers.nom_complet} ${tiers.ville}`,
              label: `${tiers.nom_court} - ${tiers.ville}`,
              tag: tiers.nom_court,
            };
          });
      }
    });

    virtualList = true;
  }

  if (type === "pays") {
    unsubscribe = pays.subscribe((listePays) => {
      if (listePays) {
        options = listePays.map((pays): Item => {
          return {
            id: pays.iso,
            search: `${pays.nom} ${pays.iso}`,
            label: pays.nom,
            tag: pays.iso,
          };
        });
      }
    });

    virtualList = true;
  }

  if (type === "port") {
    unsubscribe = ports.subscribe((listePorts) => {
      if (listePorts) {
        options = listePorts.map((port): Item => {
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

  if (type === "staff") {
    unsubscribe = stevedoringStaff.subscribe((staffList) => {
      if (staffList) {
        options = [
          {
            label: "Mensuels",
            options: [...staffList.values()]
              .filter((staff) => {
                return (
                  value === staff.id ||
                  (staff.type === "mensuel" &&
                    (includeInactive ? true : staff.isActive) &&
                    !staff.deletedAt)
                );
              })
              .sort(
                (a, b) =>
                  a.lastname.localeCompare(b.lastname) ||
                  a.firstname.localeCompare(b.firstname)
              )
              .map((staff): Item => {
                return {
                  id: staff.id,
                  search: staff.fullname,
                  label: staff.fullname,
                  tag: staff.fullname,
                };
              }),
          },
          {
            label: "Intérimaires",
            options: [...staffList.values()]
              .filter((staff) => {
                return (
                  value === staff.id ||
                  (staff.type === "interim" &&
                    (includeInactive ? true : staff.isActive) &&
                    !staff.deletedAt)
                );
              })
              .sort(
                (a, b) =>
                  a.lastname.localeCompare(b.lastname) ||
                  a.firstname.localeCompare(b.firstname)
              )
              .map((staff): Item => {
                return {
                  id: staff.id,
                  search: staff.fullname,
                  label: `${staff.fullname} (${staff.tempWorkAgency})`,
                  tag: staff.fullname,
                };
              }),
          },
        ];
      }
    });
  }

  if (type === "mensuels") {
    unsubscribe = stevedoringStaff.subscribe((staffList) => {
      if (staffList) {
        options = [...staffList.values()]
          .filter((staff) => {
            return (
              value === staff.id ||
              (staff.type === "mensuel" &&
                (includeInactive ? true : staff.isActive) &&
                !staff.deletedAt)
            );
          })
          .sort(
            (a, b) =>
              a.lastname.localeCompare(b.lastname) ||
              a.firstname.localeCompare(b.firstname)
          )
          .map((staff): Item => {
            return {
              id: staff.id,
              search: staff.fullname,
              label: `${staff.fullname} (${staff.tempWorkAgency})`,
              tag: staff.fullname,
            };
          });
      }
    });
  }

  if (type === "interimaires") {
    unsubscribe = stevedoringStaff.subscribe((staffList) => {
      if (staffList) {
        options = [...staffList.values()]
          .filter((staff) => {
            return (
              value === staff.id ||
              (staff.type === "interim" &&
                (includeInactive ? true : staff.isActive) &&
                !staff.deletedAt)
            );
          })
          .sort(
            (a, b) =>
              a.lastname.localeCompare(b.lastname) ||
              a.firstname.localeCompare(b.firstname)
          )
          .map((staff): Item => {
            return {
              id: staff.id,
              search: staff.fullname,
              label: `${staff.fullname} (${staff.tempWorkAgency})`,
              tag: staff.fullname,
            };
          });
      }
    });
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
    {disabled}
    {options}
    {optionResolver}
    bind:value
    {valueAsObject}
    {valueField}
    {labelField}
    {groupLabelField}
    {groupItemsField}
    {disabledField}
    {placeholder}
    {searchable}
    {searchProps}
    {clearable}
    {renderer}
    {disableHighlight}
    {selectOnTab}
    {resetOnBlur}
    {resetOnSelect}
    {closeAfterSelect}
    {dndzone}
    {multiple}
    {max}
    {collapseSelection}
    {creatable}
    {creatablePrefix}
    {allowEditing}
    {keepCreated}
    {delimiter}
    {fetch}
    {fetchMode}
    {fetchCallback}
    {fetchResetOnBlur}
    {minQuery}
    {lazyDropdown}
    {virtualList}
    {vlHeight}
    {vlItemSize}
    class={svelecteClass}
    {i18n}
    {readSelection}
    on:input
    on:change
    {highlightFirstItem}
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
