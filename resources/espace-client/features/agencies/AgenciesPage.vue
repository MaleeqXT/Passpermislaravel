<script setup lang="ts">
import { ClientButton, PageContainer } from '@espace-client/components';
import AgencySection from './partials/AgencySection.vue';
const videos = [
    {
        videoID: 'w3xbl4vZ8YM',
        title: '⭐️🚗 @KalashCriminelOfficiel PARLE DE NOTRE AUTO ÉCOLE TELLEMENT IL A APPRÉCIÉ NOTRE CONCEPT ☺️',
    },
    {
        videoID: 'rK8Mpe9XFs8',
        title: '⭐️🚗 MON ANCIEN ÉLÈVE @keblackofficiel FAIT UN P’TIT COUCOU À SON ANCIEN MONITEUR FLO.',
    },
    {
        videoID: 'o1iDXJvAshM',
        title: '🚦🏆 ELLE A EU SON PERMIS. ET ELLE RACONTE COMMENT ÇA S’EST PASSÉ! DE LA PURE MOTIVATION POUR VOUS',
    },
    {
        videoID: 'KYJt7PzHwoE',
        title: '🚦🚗 À 15MN DE PASSER SON EXAMEN 🧘',
    },
    {
        videoID: 'XLQoXpm5M5M',
        title: '🏆🚗 CENTRE D’EXAMEN À COLOMIERS (OCCITANIE) COMME SI TU PASSAIS LE PERMIS',
    },
    {
        videoID: 'mKB_RCSyDtE',
        title: '🚦🚗👌 DÉCOUVREZ NOS AUTO ÉCOLES PASSPERMISFACILE',
    },
    {
        videoID: '9WCCZQwbkvg',
        title: '🏆🚗 CENTRE D’EXAMEN À MONCHY (OISE) COMME SI TU PASSAIS LE PERMIS',
    },
    {
        videoID: 'sv7zPLvnYPs',
        title: '🏆🚗 4 TEXTOS SUR NOTRE FONCTIONNEMENT #autoécole',
    },
    {
        videoID: 'Sx98IlsRiBw',
        title: '🚦🅿️ 1/2 TOUR À L’EXAMEN : C’EST COMME ÇA 👌',
    },
    {
        videoID: 'clAdOlPF2ek',
        title: '🚦🅿️ CRÉNEAU : 13 ANS QUE ÇA MARCHE À L’EXAMEN 👌 NORMAL C’EST FLO QUI L’A INVENTÉ 😂',
    },
    {
        videoID: '3sOW50DcxHI',
        title: '✅ PASSPERMIS TURBO : SI TU HABITES À PARIS OU DS L’OISE OU À TOULOUSE, ALORS CETTE VIDÉO EST PR TOI',
    },
    {
        videoID: 'PimO3ThXJdc',
        title: '🚦🚗 -10 KM/H SUR LE PÉRIPHÉRIQUE ? MÊME POUR LES JEUNES CONDUCTEURS',
    },
    {
        videoID: 'dtO9XdbyTok',
        title: '🚦🚗 REMETTRE SES ROUES DROITES QUAND ON SAIT PLUS OÙ ON EN EST',
    },
    {
        videoID: 'jA2ohHWU72k',
        title: '🚦🚗 COMMENT PASSER AU FEU ORANGE? LE PIRE ENEMI À L’EXAMEN POUR BCP D’ÉLÈVES, CROYEZ-MOI SUR PAROLE',
    },
    {
        videoID: 'zuQFPlrGBXw',
        title: '✅ BON PLAN DANS L’OISE (60) : UNE AUTO ÉCOLE AU JUSTE PRIX POUR VOUS SERVIR',
    },
    {
        videoID: 'iNW5zIEf98k',
        title: '✅ PERMIS ACCÉLÉRÉE 🚀 C’EST BIEN POUR TOI SI ET SEULEMENT SI…….',
    },
    {
        videoID: 'AxAXP4ys5_c',
        title: '✅ BON PLAN À TOULOUSE : UNE AUTO ÉCOLE AU JUSTE PRIX POUR VOUS SERVIR',
    },
    {
        videoID: 'kAKJ4_YV3uw',
        title: '🏆🚗 CENTRE D’EXAMEN À COLOMIERS (OCCITANIE) COMME SI TU PASSAIS LE PERMIS',
    },
    {
        videoID: '0b5RYeJPtGE',
        title: '🏆🚗 CENTRE D’EXAMEN À TOULOUSE (LALANDE) COMME SI TU PASSAIS LE PERMIS',
    },
    {
        videoID: '4hERUYbk1K0',
        title: '🏆🚗 CENTRE D’EXAMEN À COMPIÈGNE (MARGNY-LES-COMPIEGNE) COMME SI TU PASSAIS LE PERMIS',
    },
    {
        videoID: '3PA8gyAcV5A',
        title: '✅ NOUVELLE AUTO ÉCOLE À TOULOUSE - MÉTRO ARÈNES . CONTACTEZ NOUS AU 09.70.70.16.16 PR TOUTES INFOS 😉',
    },
];
</script>

<template>
    <PageContainer>
        <div class="py-24 sm:pb-32">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 mb-10">
                <div class="relative isolate px-6 pb-14 text-center sm:px-16 z-0">
                    <img src="/assets/clients/nos-agences.webp" alt="" class="max-w-md mb-6 w-full mx-auto" />
                    <!--                    <p class="mx-auto mt-6 max-w-xl text-pretty text-lg/8 text-dark2">-->
                    <!--                        Incididunt sint fugiat pariatur cupidatat consectetur sit cillum anim id veniam aliqua proident excepteur commodo do-->
                    <!--                        ea.-->
                    <!--                    </p>-->
                </div>
            </div>
            <AgencySection
                img-title="/assets/clients/toulouse3-1.webp"
                gmap="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2889.7648889689813!2d1.4198501!3d43.59061330000001!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x12aebb0a06128b25%3A0xc77d5b56fe8ddc19!2s139%20Bd%20D%C3%A9odat%20de%20S%C3%A9v%C3%A9rac%2C%2031300%20Toulouse%2C%20France!5e0!3m2!1sen!2ssg!4v1744385144974!5m2!1sen!2ssg"
                img-agency="/assets/clients/toulouse.webp"
                leftClass="max-lg:rounded-t-[2rem] lg:rounded-tl-[2rem]"
                rightClass="lg:rounded-tr-[2rem]"
                dark
            >
                <div>
                    <b> Agence Toulouse (métro Arènes) : </b>
                    <p>139 boulevard Déodat de Séverac 31300 Toulouse.</p>
                    <p>Boulevard André Netwiller, 31200 Toulouse</p>
                </div>
                <div>
                    <b> Horaires d’ouverture : </b>
                    <p>du lundi au vendredi de 16h à 19h.</p>
                </div>
            </AgencySection>
            <AgencySection
                img-title="/assets/clients/creil1.webp"
                gmap="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2603.602299680006!2d2.4727457!3d49.2649835!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x47e64a19021182ad%3A0x8fa4af629862d057!2s15%20Rue%20des%20Pierres%2C%2060100%20Creil%2C%20France!5e0!3m2!1sen!2ssg!4v1744388155789!5m2!1sen!2ssg"
                img-agency="/assets/clients/gare-creil.webp"
                leftClass="md:order-2 max-md:mt-10"
                rightClass="md:order-1 max-md:mb-10"
                dark
            >
                <div>
                    <b> Agence creil (secteur Gare) : </b>
                    <p>15 rue des Pierres 60100 creil.</p>
                </div>
                <div>
                    <b> Horaires d’ouverture : </b>
                    <p>du mercredi au samedi de 9h à 18h.</p>
                </div>
            </AgencySection>
            <!-- <AgencySection
                img-title="/assets/clients/saint-denis.webp"
                gmap="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2620.462777038544!2d2.3706711!3d48.9446734!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x47e6695711ae7aa1%3A0x26e2f9682240ab84!2s53%20Rue%20de%20Chantilly%2C%2093200%20Saint-Denis%2C%20France!5e0!3m2!1sen!2ssg!4v1744388208567!5m2!1sen!2ssg"
                img-agency="/assets/clients/universite-saint-denis.webp"
                leftClass=" lg:rounded-bl-[2rem]"
                rightClass="max-lg:rounded-b-[2rem] lg:rounded-br-[2rem]"
                dark
            >
                <div>
                    <b> Agence de Saint Denis (métro Saint Denis Université – terminus ligne 13) : </b>
                    <p>53 rue de chantilly</p>
                </div>
                <div>
                    <b> Horaires d’ouverture : </b>
                    <p>mardi et jeudi de 16h à 19h.</p>
                </div>
            </AgencySection> -->

            <p class="max-w-2xl mx-auto text-pretty text-lg md:text-2xl/10 text-dark2 text-center mt-28 font-bold px-4">
                Si une de nos agences est fermée, contactez notre secrétariat en ligne disponible 6jrs/7 de 9h à 18h au numéro suivant:
                <span class="text-red-600">09.70.70.16.16</span>
            </p>
        </div>
    </PageContainer>
</template>
