<template>
    <article class="text-dark py-10 md:py-32 flex-1">
        <h1 class="text-3xl font-bold leading-relaxed tracking-tight md:text-4xl mb-2">Devenir moniteur chez Nous</h1>
        <p class="text-xl opacity-70 font-medium md:max-w-sm mb-10">
            Vous souhaitez partager votre expertise et rejoindre notre réseau des moniteurs ? Suivez ces étapes simples pour intégrer notre
            équipe et développer votre activité avec nous.
        </p>
        <ul class="space-y-7">
            <li v-for="item in steps" :key="item.id" class="flex gap-5 relative">
                <span class="flex justify-center items-center w-10 h-10 rounded-full bg-primary text-white text-lg relative z-1">
                    {{ item.id }}
                </span>
                <span v-if="item.id !== 4" class="absolute left-5 top-5 -bottom-8 border-r border-primary border-dashed z-0"></span>
                <div>
                    <h3 class="text-dark text-lg font-medium mb-5">{{ item.title }}</h3>
                    <p class="text-gray-500 font-light max-w-md text-sm">
                        {{ item.content }}
                        <span v-if="item.actions" class="text-primary cursor-pointer hover:underline">{{ item.actions }}</span>
                    </p>
                </div>
            </li>
        </ul>
    </article>
</template>

<script setup lang="ts">
const steps = [
    {
        id: 1,
        title: 'Évaluation de Votre Zone d’Activité',
        content:
            'Pour garantir une collaboration fructueuse, nous analysons la demande en formation dans votre région. Cela nous permet d’optimiser votre visibilité et d’attirer un maximum d’élèves vers votre profil.',
    },
    {
        id: 2,
        title: ' Validation des Documents Requis',
        content:
            'Une fois votre candidature présélectionnée, nous vous guidons pour rassembler les documents nécessaires (diplômes, certifications, etc.). Notre équipe vérifie leur conformité pour une intégration rapide et sans tracas.',

    },
    {
        id: 3,
        title: 'Accompagnement Personnalisé',
        content:
            'Un conseiller dédié vous assiste à chaque étape, de l’inscription à votre première réservation. Nous vous fournissons des outils et conseils pour bien démarrer et maximiser votre réussite.',
    },
    {
        id: 4,
        title: 'Lancement & Promotion Active',
        content:
            'Dès votre profil validé, nous mettons en avant votre offre via nos canaux de communication (site web, réseaux sociaux, newsletters). Vous bénéficiez également de conseils marketing pour attirer plus d’élèves et développer votre activité.',
    },
];
</script>
