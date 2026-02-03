<!--
@component

Liste des contacts associés à un tiers.

Usage:
```tsx
<Contacts contacts: Contact[] />
```
-->
<script lang="ts">
  // import { PlusIcon } from "lucide-svelte";
  import PlusIcon from "lucide-svelte/icons/plus";

  import { LucideButton } from "@app/components";

  import ContactModal from "./ContactModal.svelte";

  import type { Contact } from "@app/types";

  export let contacts: Contact[] = [];

  let modalOpen = false;
  let contactInModal: Contact | null = null;

  function addContact() {
    contacts = [
      ...contacts,
      {
        id: Math.random(),
        nom: "",
        email: "",
        telephone: "",
        fonction: "",
        commentaire: "",
        new: true,
      },
    ];

    contactInModal = contacts[contacts.length - 1];

    modalOpen = true;
  }

  function deleteContact(contact: Contact) {
    contacts = contacts.filter((c) => c !== contact);
  }
</script>

<div>
  <div class="mb-2 text-sm text-gray-500">
    {contacts.length} contact{contacts.length > 1 ? "s" : ""}
  </div>

  <ContactModal
    bind:open={modalOpen}
    bind:contacts
    bind:contact={contactInModal}
  />

  <button on:click|preventDefault={addContact} class="text-sm">
    Ajouter un contact
    <i class="icon align-middle" title="Ajouter un contact"
      ><PlusIcon class="inline" /></i
    >
  </button>

  <ul class="pl-5">
    {#each contacts as contact}
      <li>
        {contact.nom}

        {#if contact.fonction}
          ({contact.fonction})
        {/if}

        {#if contact.email}
          - {contact.email}
        {/if}

        {#if contact.telephone}
          - {contact.telephone}
        {/if}

        <LucideButton
          preset="edit"
          size="1em"
          on:click={() => {
            contactInModal = contact;
            modalOpen = true;
          }}
        />

        <LucideButton
          preset="delete"
          size="1em"
          on:click={() => deleteContact(contact)}
        />
      </li>
    {/each}
  </ul>
</div>
