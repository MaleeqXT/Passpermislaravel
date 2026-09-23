<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

const history = [
    { year: '2023', code: '69 %', conduite: '61 %' },
    { year: '2024', code: '72 %', conduite: '64 %' },
];
</script>

<template>
<Head title="Résultats & Performances – PASS PERMIS FACILE" />

<main class="bg-gray-50 text-gray-900">

    <!-- HEADER -->
    <section class="bg-gray-900 text-white py-12">
        <div class="max-w-5xl mx-auto px-4">
            <h1 class="text-3xl md:text-4xl font-bold">
                Résultats & Performances – PASS PERMIS FACILE
            </h1>

            <div class="mt-6">
                <h2 class="text-lg font-semibold">🎯 Nos résultats – Année 2024</h2>
                <p class="mt-2 text-sm text-gray-300">
                    Chez PASSPERMISFACILE, nous suivons avec attention les performances de nos élèves afin de garantir un accompagnement efficace et transparent.
                </p>
            </div>
        </div>
    </section>

    <section class="max-w-5xl mx-auto px-4 py-10 space-y-10">

        <!-- CODE -->
        <div class="bg-white p-6 rounded-xl shadow">
            <h2 class="font-bold text-lg">📊 Taux de réussite – Code de la route (2024)</h2>

            <ul class="mt-4 space-y-1">
                <li>● Taux de réussite : 72 %</li>
                <li>● Moyenne nationale : environ 60 %</li>
            </ul>

            <p class="mt-3">✔ Formation intensive + suivi pédagogique personnalisé</p>
        </div>

        <!-- DRIVING -->
        <div class="bg-white p-6 rounded-xl shadow">
            <h2 class="font-bold text-lg">🚗 Taux de réussite – Examen pratique (Permis B) – 2024</h2>

            <ul class="mt-4 space-y-1">
                <li>● Taux de réussite global : 64 %</li>
            </ul>

            <p class="mt-2 font-medium">Détail :</p>

            <ul class="mt-2 space-y-1">
                <li>● Boîte manuelle : 62 %</li>
                <li>● Boîte automatique : 68 %</li>
            </ul>

            <ul class="mt-3 space-y-1">
                <li>✔ Présentation à l’examen optimisée</li>
                <li>✔ Parcours adapté au niveau de chaque élève</li>
            </ul>
        </div>

        <!-- CPF -->
        <div class="bg-white p-6 rounded-xl shadow">
            <h2 class="font-bold text-lg">Nombre d’élèves formés CPF – 2024</h2>

            <ul class="mt-4 space-y-1">
                <li>● 248 élèves accompagnés</li>
                <li>● Dont :</li>
                <li class="ml-4">○ 180 en formation complète (avec code)</li>
                <li class="ml-4">○ 68 en formation conduite uniquement</li>
            </ul>
        </div>

        <!-- SATISFACTION -->
        <div class="bg-white p-6 rounded-xl shadow">
            <h2 class="font-bold text-lg">⭐ Satisfaction élèves</h2>

            <ul class="mt-4 space-y-1">
                <li>● Note moyenne Google : 4,7 / 5</li>
                <li>● Basée sur 132 avis</li>
            </ul>

            <div class="mt-4 space-y-2">
                <p>💬 “Très bonne pédagogie, permis obtenu rapidement”</p>
                <p>💬 “Moniteur à l’écoute, accompagnement au top”</p>
            </div>
        </div>

        <!-- HISTORY -->
        <div class="bg-white p-6 rounded-xl shadow">
            <h2 class="font-bold text-lg">📅 Historique des performances</h2>

            <table class="w-full mt-4 border">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="p-2 text-left">Année</th>
                        <th class="p-2 text-left">Code</th>
                        <th class="p-2 text-left">Conduite</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="item in history" :key="item.year" class="border-t">
                        <td class="p-2">{{ item.year }}</td>
                        <td class="p-2">{{ item.code }}</td>
                        <td class="p-2">{{ item.conduite }}</td>
                    </tr>
                </tbody>
            </table>

            <p class="mt-4">
                👉 Une progression constante grâce à l’amélioration continue de nos méthodes pédagogiques.
            </p>
        </div>

        <!-- IMPORTANT -->
        <div class="bg-white p-6 rounded-xl shadow">
            <h2 class="font-bold text-lg">⚠ Informations importantes</h2>

            <p class="mt-3">
                Les résultats présentés concernent l’année 2023 et 2024 et peuvent varier selon :
            </p>

            <ul class="mt-3 space-y-1">
                <li>● le niveau initial de l’élève</li>
                <li>● l’assiduité</li>
                <li>● le respect du parcours recommandé</li>
            </ul>
        </div>

        <!-- SHORT VERSION -->
        <div class="bg-black text-white p-6 rounded-xl">
            <h2 class="font-bold text-lg">🔥 VERSION COURTE (si tu veux plus simple)</h2>

            <p class="mt-3">Si tu veux une version plus minimaliste :</p>

            <p class="mt-4 font-semibold">Résultats 2023 :</p>

            <ul class="mt-2 space-y-1">
                <li>● Code : 72 %</li>
                <li>● Conduite : 64 %</li>
                <li>● 248 élèves formés</li>
                <li>● Satisfaction : 4,7 / 5 (Google)</li>
            </ul>
        </div>

    </section>

</main>
</template>
