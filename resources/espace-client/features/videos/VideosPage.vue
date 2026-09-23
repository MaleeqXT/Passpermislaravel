<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { InputField } from '@shared/components';
import { ClientButton, PageContainer } from '@espace-client/components';
import { routes } from '@espace-client/routes';
import { LocationIcon, LogoYoutubeIcon } from '@adersolutions/icons';
import { ref } from 'vue';
const videos = [
    {
        id: 'w3xbl4vZ8YM',
        title: '⭐️🚗 @KalashCriminelOfficiel PARLE DE NOTRE AUTO ÉCOLE TELLEMENT IL A APPRÉCIÉ NOTRE CONCEPT ☺️',
    },
    {
        id: 'rK8Mpe9XFs8',
        title: '⭐️🚗 MON ANCIEN ÉLÈVE @keblackofficiel FAIT UN P’TIT COUCOU À SON ANCIEN MONITEUR FLO.',
    },
    {
        id: 'o1iDXJvAshM',
        title: '🚦🏆 ELLE A EU SON PERMIS. ET ELLE RACONTE COMMENT ÇA S’EST PASSÉ! DE LA PURE MOTIVATION POUR VOUS',
    },
    {
        id: 'KYJt7PzHwoE',
        title: '🚦🚗 À 15MN DE PASSER SON EXAMEN 🧘',
    },
    {
        id: 'XLQoXpm5M5M',
        title: '🏆🚗 CENTRE D’EXAMEN À COLOMIERS (OCCITANIE) COMME SI TU PASSAIS LE PERMIS',
    },
    {
        id: 'mKB_RCSyDtE',
        title: '🚦🚗👌 DÉCOUVREZ NOS AUTO ÉCOLES PASSPERMISFACILE',
    },
    {
        id: '9WCCZQwbkvg',
        title: '🏆🚗 CENTRE D’EXAMEN À MONCHY (OISE) COMME SI TU PASSAIS LE PERMIS',
    },
    {
        id: 'sv7zPLvnYPs',
        title: '🏆🚗 4 TEXTOS SUR NOTRE FONCTIONNEMENT #autoécole',
    },
    {
        id: 'Sx98IlsRiBw',
        title: '🚦🅿️ 1/2 TOUR À L’EXAMEN : C’EST COMME ÇA 👌',
    },
    {
        id: 'clAdOlPF2ek',
        title: '🚦🅿️ CRÉNEAU : 13 ANS QUE ÇA MARCHE À L’EXAMEN 👌 NORMAL C’EST FLO QUI L’A INVENTÉ 😂',
    },
    {
        id: '3sOW50DcxHI',
        title: '✅ PASSPERMIS TURBO : SI TU HABITES À PARIS OU DS L’OISE OU À TOULOUSE, ALORS CETTE VIDÉO EST PR TOI',
    },
    {
        id: 'PimO3ThXJdc',
        title: '🚦🚗 -10 KM/H SUR LE PÉRIPHÉRIQUE ? MÊME POUR LES JEUNES CONDUCTEURS',
    },
    {
        id: 'dtO9XdbyTok',
        title: '🚦🚗 REMETTRE SES ROUES DROITES QUAND ON SAIT PLUS OÙ ON EN EST',
    },
    {
        id: 'jA2ohHWU72k',
        title: '🚦🚗 COMMENT PASSER AU FEU ORANGE? LE PIRE ENEMI À L’EXAMEN POUR BCP D’ÉLÈVES, CROYEZ-MOI SUR PAROLE',
    },
    {
        id: 'zuQFPlrGBXw',
        title: '✅ BON PLAN DANS L’OISE (60) : UNE AUTO ÉCOLE AU JUSTE PRIX POUR VOUS SERVIR',
    },
    {
        id: 'iNW5zIEf98k',
        title: '✅ PERMIS ACCÉLÉRÉE 🚀 C’EST BIEN POUR TOI SI ET SEULEMENT SI…….',
    },
    {
        id: 'AxAXP4ys5_c',
        title: '✅ BON PLAN À TOULOUSE : UNE AUTO ÉCOLE AU JUSTE PRIX POUR VOUS SERVIR',
    },
    {
        id: 'kAKJ4_YV3uw',
        title: '🏆🚗 CENTRE D’EXAMEN À COLOMIERS (OCCITANIE) COMME SI TU PASSAIS LE PERMIS',
    },
    {
        id: '0b5RYeJPtGE',
        title: '🏆🚗 CENTRE D’EXAMEN À TOULOUSE (LALANDE) COMME SI TU PASSAIS LE PERMIS',
    },
    {
        id: '4hERUYbk1K0',
        title: '🏆🚗 CENTRE D’EXAMEN À COMPIÈGNE (MARGNY-LES-COMPIEGNE) COMME SI TU PASSAIS LE PERMIS',
    },
    {
        id: '3PA8gyAcV5A',
        title: '✅ NOUVELLE AUTO ÉCOLE À TOULOUSE - MÉTRO ARÈNES . CONTACTEZ NOUS AU 09.70.70.16.16 PR TOUTES INFOS 😉',
    },
];

const selectedVideoId = ref<string | null>(null);

const openVideo = (id: string) => {
    selectedVideoId.value = id;
};

const closeVideo = () => {
    selectedVideoId.value = null;
};
</script>

<template>
    <PageContainer>
        <div class="py-24 sm:pb-32">
            <div class="mx-auto max-w-2xl px-6 lg:max-w-screen-xl lg:px-8">
                <img src="/assets/clients/tutos.webp" alt="" class="max-w-md mb-6 w-full mx-auto" />

                <section class="mt-10 grid grid-cols-1 gap-4 sm:mt-16 lg:grid-cols-3">
                    <article
                        v-for="(video, index) in videos"
                        :key="index"
                        class="relative flex flex-col justify-end overflow-hidden rounded-2xl bg-gray-900 aspect-video group cursor-pointer"
                        @click="openVideo(video.id)"
                    >
                        <img
                            :src="`https://i.ytimg.com/vi/${video.id}/sddefault.jpg`"
                            :alt="video.title"
                            class="absolute inset-0 -z-0 size-full object-cover"
                        />
                        <div class="absolute inset-0 bg-gradient-to-t from-gray-900 via-gray-900/40" />
                        <div class="absolute inset-0 rounded-2xl ring-1 ring-inset ring-gray-900/10" />
                        <LogoYoutubeIcon
                            class="absolute h-16 w-16 text-red-500 left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 opacity-0 group-hover:opacity-100 transition"
                        />
                        <h3 class="mt-3 text-md font-semibold text-white lowercase relative z-1 p-3">
                            <span class="line-clamp-2">{{ video.title }}</span>
                        </h3>
                    </article>
                </section>
            </div>
        </div>

        <!-- Modal -->
        <div
            v-if="selectedVideoId"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4"
            @click.self="closeVideo"
        >
            <div class="relative w-full max-w-4xl aspect-video bg-dark rounded-lg">
                <iframe
                    class="w-full h-full rounded-lg shadow-lg"
                    :src="`https://www.youtube.com/embed/${selectedVideoId}?autoplay=1`"
                    frameborder="0"
                    allow="autoplay; encrypted-media"
                    allowfullscreen
                ></iframe>
                <button @click="closeVideo" class="absolute top-2 right-2 text-white bg-black/60 rounded-full p-2 w-9 hover:bg-black/80">
                    ✕
                </button>
            </div>
        </div>
    </PageContainer>
</template>
