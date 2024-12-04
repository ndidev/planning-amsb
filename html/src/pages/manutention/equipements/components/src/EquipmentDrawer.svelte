<script lang="ts">
  import { goto } from "@roxi/routify";

  import { Drawer, CloseButton } from "flowbite-svelte";
  import { sineIn } from "svelte/easing";
  import { PencilIcon, PhoneCallIcon } from "lucide-svelte";

  import { LucideButton, Badge } from "@app/components";

  import { currentUser } from "@app/stores";

  import type { StevedoringEquipment } from "@app/types";

  export let equipment: StevedoringEquipment;

  export let hidden = true;

  let transitionParams = {
    x: 320,
    duration: 200,
    easing: sineIn,
  };
</script>

<Drawer
  placement="right"
  bgOpacity="bg-opacity-10"
  width="w-3/4 lg:w-1/3"
  transitionType="fly"
  {transitionParams}
  bind:hidden
>
  <CloseButton
    on:click={() => (hidden = true)}
    class="absolute top-0 right-0 m-4"
  />
  <div class="flex flex-col gap-2 lg:gap-6 mt-8">
    <!-- Nom -->
    <div class="text-3xl *:align-baseline">
      <span class="font-bold">
        {equipment.brand}
        {equipment.model}
        {equipment.internalNumber}
      </span>

      {#if $currentUser.canEdit("manutention")}
        <LucideButton
          preset="edit"
          icon={PencilIcon}
          on:click={$goto(`/manutention/equipements/${equipment.id}`)}
          align="baseline"
        />
      {/if}
    </div>

    <!-- Statut -->
    {#if equipment.isActive}
      <div>
        <Badge color="hsla(120, 90%, 40%, 0.6)">actif</Badge>
      </div>
    {:else}
      <div>
        <Badge color="hsla(0, 100%, 50%, 0.6)">inactif</Badge>
      </div>
    {/if}

    <!-- Marque -->
    <div>
      Marque : {equipment.brand}
    </div>

    <!-- Modèle -->
    <div>
      Modèle : {equipment.model}
    </div>

    <!-- Type -->
    <div>
      Type : {equipment.type}
    </div>

    <!-- Numéro interne -->
    <div>
      Numéro interne : {equipment.internalNumber || "non renseigné"}
    </div>

    <!-- Numéro de série -->
    <div>
      Numéro de série : {equipment.serialNumber || "non renseigné"}
    </div>

    <!-- Commentaires -->
    <div>
      <div>Commentaires :</div>
      {#if equipment.comments}
        <div class="ml-2">
          {@html equipment.comments.replace("\r\n|\r|\n", "<br/>")}
        </div>
      {:else}
        <div class="ml-2 italic">Aucun commentaire</div>
      {/if}
    </div>
  </div>
</Drawer>
