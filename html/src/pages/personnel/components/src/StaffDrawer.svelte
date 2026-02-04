<script lang="ts">
  import { goto } from "@roxi/routify";

  import { Drawer, CloseButton } from "flowbite-svelte";
  import { sineIn } from "svelte/easing";
  import PencilIcon from "lucide-svelte/icons/pencil";
  import PhoneCallIcon from "lucide-svelte/icons/phone-call";

  import { LucideButton, Badge } from "@app/components";

  import { currentUser } from "@app/stores";

  import type { StevedoringStaff } from "@app/types";

  export let staff: StevedoringStaff;

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
      <span class="font-bold">{staff.fullname}</span>

      {#if $currentUser.canEdit("manutention")}
        <LucideButton
          preset="edit"
          icon={PencilIcon}
          on:click={$goto(`/personnel/${staff.id}`)}
          align="baseline"
        />
      {/if}
    </div>

    <!-- Statut -->
    {#if staff.isActive}
      <div>
        <Badge color="hsla(120, 90%, 40%, 0.6)">actif</Badge>
      </div>
    {:else}
      <div>
        <Badge color="hsla(0, 100%, 50%, 0.6)">inactif</Badge>
      </div>
    {/if}

    <!-- Téléphone -->
    <div>
      Téléphone :
      {#if staff.phone}
        <a href="tel:{staff.phone}" title="Appeler"
          ><span class="hover:underline">{staff.phone}</span>
          <PhoneCallIcon size="0.8em" /></a
        >
      {:else}
        non renseigné
      {/if}
    </div>

    <!-- Type de contrat -->
    <div>
      Type de contrat : {staff.type === "interim" ? "Intérimaire" : "Mensuel"}
    </div>

    <!-- Agence d'intérim -->
    {#if staff.tempWorkAgency}
      <div>Agence d'intérim : {staff.tempWorkAgency}</div>
    {/if}

    <!-- Commentaires -->
    <div>
      <div>Commentaires :</div>
      {#if staff.comments}
        <div class="ml-2">
          {@html staff.comments.replace(/\r\n|\r|\n/g, "<br/>")}
        </div>
      {:else}
        <div class="ml-2 italic">Aucun commentaire</div>
      {/if}
    </div>
  </div>
</Drawer>
