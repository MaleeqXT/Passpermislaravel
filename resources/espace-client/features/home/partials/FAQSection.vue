<script setup>
import { routes } from '@espace-client/routes';
import { Link } from '@inertiajs/vue3';

const faqs = [
    {
        img: '/assets/clients/departement.webp',
        question: 'Tes leçons de conduite partout, tout le temps, dans ta ville ou ton département.',
        answer: "Tes enseignants diplômés d’état te proposent des leçons 7j/7 de 07h à 23h",
    },
    {
        img: '/assets/clients/toujours.webp',
        question: "Il y a toujours quelqu’un pour t’aider.",
        answer: 'Nos secrétaires en ligne Linda et Marie sont 100% disponibles et mobilisées pour ta réussite 6j/7 de 10h à 18h',
    },
    {
        img: '/assets/clients/eur.webp',
        question: 'Parrainer un proche',
        answer: 'On te fait économiser de l’argent si tu parraines un proche qui s’inscrit chez nous.',
        extra: '* Pour toute souscription d’un forfait minimum de 20h, on t’offre 1h de conduite.',
    },
];
</script>
<template>
    <section class="mx-auto max-w-7xl px-6 py-24 sm:pt-32 lg:px-8 lg:py-40">
        <div class="lg:grid lg:grid-cols-12 lg:gap-8">
            <div class="lg:col-span-5">
                <img
                    loading="lazy"
                    decoding="async"
                    src="/assets/clients/pourquoi-nous-choisir.webp"
                    class="max-w-md w-full object-contain"
                    alt="Auto école en ligne à Toulouse"
                />
                <p class="mt-4 text-pretty text-base/7 text-gray-600">
                    Tu ne trouves pas la réponse que tu cherches? Contacte notre
                    <Link :href="route(routes.contact.index)" class="font-medium underline text-sky-500 hover:text-sky-600"
                        >service client</Link
                    >
                </p>
            </div>
            <div class="mt-10 lg:col-span-7 lg:mt-0">
                <ul class="space-y-3 md:space-y-10">
                    <li v-for="faq in faqs" :key="faq.question" class="flex max-md:flex-col gap-3 max-md:bg-white max-md:p-3 max-md:b-box">
                        <img
                            :src="faq.img"
                            class="w-14 h-[58px] object-contain bg-gray-100 border border-b-4 border-dark2 rounded-full p-2"
                            :alt="faq.question"
                        />
                        <div>
                            <dt class="text-lg/7 font-semibold text-gray-900">{{ faq.question }}</dt>
                            <dd class="mt-2 text-base/7 text-gray-600">{{ faq.answer }}</dd>
                            <dd class="text-gray-600" v-if="faq.extra">{{ faq.extra }}</dd>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </section>
</template>
