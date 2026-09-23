<script setup>
import { ClientButton } from '@espace-client/components';
import { routes } from '@espace-student/routes';
import { CheckIcon, HandThumbUpIcon, UserIcon } from '@heroicons/vue/20/solid';
import { useApp } from '@shared/stores';
import { computed } from 'vue';
const { isLogged } = useApp();
const functionalities = computed(() => [
    {
        id: 1,
        img: '/assets/clients/assets15.webp',
        imgTitle: '/assets/clients/etape1-1.webp',
        btn: {
            title: 'Reserver Maintenant',
            variant: 'info',
            href: isLogged ? route(routes.reservations.index) : '/offers',
        },
        imgClass: 'max-w-xs',
        content:
            'Inscris-toi gratuitement sur notre site internet. <br /> Si tu n’as pas encore de numéro NEPH, nous le ferons pour toi. Si tu l’as déjà, choisis le forfait dont tu as besoin.',
    },
    {
        id: 2,
        img: '/assets/clients/assets14.webp',
        imgTitle: '/assets/clients/etape2-1.webp',
        btn: {
            title: 'Nos Forfaits',
            variant: 'success',
            href: isLogged ? route(routes.shop.index) : '/offers',
        },
        imgClass: 'max-w-sm',
        content: 'Trouve plus d’offres adaptées à ton besoin.',
    },
    {
        id: 3,
        img: '/assets/clients/etape3-ppf.webp',
        imgTitle: '/assets/clients/etape3-1.webp',
        imgClass: 'max-w-sm',
        btn: {
            title: 'Reserver Maintenant',
            variant: 'danger',
            href: isLogged ? route(routes.reservations.index) : '/offers',
        },
        content: 'Réalise ta première heure d’essai avec PPF.',
    },
]);
</script>
<template>
    <div class="flow-root mx-auto max-w-screen-lg pt-20 pb-24 sm:pt-32 px-5">
        <img
            loading="lazy"
            decoding="async"
            src="/assets/clients/notre-fonctionnement-1.webp"
            class="max-w-md mx-auto w-full object-contain"
            alt="Auto école en ligne à Toulouse"
        />
        <ul role="list" class="-mb-8 mt-10">
            <li v-for="(item, itemIdx) in functionalities" :key="item.id" class="wrapper-item">
                <div class="relative pb-20">
                    <div class="relative grid md:grid-cols-2 md:space-x-3 items-center item-inner">
                        <img :src="item.img" :class="[item.imgClass, 'w-full max-md:mb-5']" :alt="item.content" />
                        <div class="">
                            <img :src="item.imgTitle" class="h-14" alt="" />

                            <div v-html="item.content" class="text-lg text-gray-500 py-5"></div>
                            <ClientButton :href="item.btn.href" :variant="item.btn.variant" class="mt-4 flex items-center md:w-fit">
                                <HandThumbUpIcon class="h-5 w-5 mr-2" aria-hidden="true" />
                                {{ item.btn.title }}
                            </ClientButton>
                        </div>
                    </div>
                </div>
            </li>
        </ul>
    </div>
</template>

<style lang="scss">
.wrapper-item:nth-child(even) .item-inner > img {
    @screen md {
        order: 2;
    }
}
</style>
