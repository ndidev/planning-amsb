<script lang="ts">
  import { Modal, Label, Input } from "flowbite-svelte";

  import { BoutonAction } from "@app/components";

  import { validerFormulaire } from "@app/utils";

  import type { Contact } from "@app/types";

  let form: HTMLFormElement;

  export let open: boolean;
  export let contacts: Contact[] = [];
  let originalContact: Contact;
  export { originalContact as contact };

  let contact: Contact;

  function updateContact() {
    if (!validerFormulaire(form)) return;

    contact.new = false;

    contacts = contacts.map((c) => (c.id === contact.id ? { ...contact } : c));

    open = false;
  }

  function cancelUpdate() {
    if (contact.new) {
      contacts = contacts.filter((c) => c.id !== contact.id);
    }

    open = false;
  }
</script>

<Modal
  title="Contact"
  bind:open
  dismissable={false}
  size="lg"
  on:open={() => (contact = structuredClone(originalContact))}
>
  <form class="divide-y" bind:this={form}>
    <div class="flex flex-col items-center gap-2 py-1 lg:gap-4 lg:py-2">
      <!-- Nom -->
      <div class="w-full">
        <Label for="name">Nom</Label>
        <Input
          id="name"
          name="Nom"
          bind:value={contact.nom}
          placeholder="Nom du contact"
          required
        />
      </div>

      <!-- E-mail -->
      <div class="w-full">
        <Label for="email">E-mail</Label>
        <Input
          type="email"
          id="email"
          name="E-mail"
          placeholder="Adresse e-mail"
          bind:value={contact.email}
        />
      </div>

      <!-- Téléphone -->
      <div class="w-full">
        <Label for="phone">Téléphone</Label>
        <Input
          type="tel"
          id="phone"
          name="Téléphone"
          bind:value={contact.telephone}
        />
      </div>

      <!-- Fonction -->
      <div class="w-full">
        <Label for="function">Fonction</Label>
        <Input
          id="function"
          name="Fonction"
          bind:value={contact.fonction}
          placeholder="Fonction du contact"
        />
      </div>

      <!-- Commentaire -->
      <div class="w-full">
        <Label for="comments">Commentaire</Label>
        <Input
          id="comments"
          name="Commentaire"
          bind:value={contact.commentaire}
          placeholder="Commentaires"
        />
      </div>
    </div>
  </form>

  <div class="text-center">
    <!-- Bouton "Modifier" -->
    <BoutonAction
      preset={contact.new ? "ajouter" : "modifier"}
      on:click={updateContact}
    />

    <!-- Bouton "Annuler" -->
    <BoutonAction preset="annuler" on:click={cancelUpdate} />
  </div>
</Modal>
