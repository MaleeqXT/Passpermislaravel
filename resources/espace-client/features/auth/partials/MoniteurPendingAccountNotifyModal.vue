<script setup lang="ts">
import { ClientButton } from '@espace-client/components';
import { routes } from '@espace-client/routes';
import { UnknownDeviceIcon } from '@adersolutions/icons';
import { Dialog } from '@shared/components';

const show = defineModel('show', {
    type: Boolean,
    default: false,
});
</script>
<template>
    <Dialog :show="show" max-width="sm" @close="show = false">
        <div class="p-5 text-center">
            <div class="w-28 h-28 flex-center rounded-full bg-orange-100 text-white mx-auto my-5">
                <UnknownDeviceIcon class="w-12" />
            </div>
            <h2 class="font-medium text-2xl my-5">
                Votre compte est en attente de validation. Vous serez notifié par email dès que votre compte sera activé.
            </h2>
            <p class="text-gray-600">
                Si vous avez des questions, veuillez nous contacter à l'adresse suivante :
                <a class="btn btn-link btn-perimary" href="mailto:contactpasspermisfacile@gmail.com"> contactpasspermisfacile@gmail.com </a>
            </p>
        </div>
        <div class="flex p-5 gap-5">
            <ClientButton class="!px-6" @click="show = false" variant="light">Continue votre navigation</ClientButton>
            <ClientButton reload class="flex-1 !px-2" :href="route(routes.login)"> Connect sur votre espace moniteur </ClientButton>
        </div>
    </Dialog>
</template>
