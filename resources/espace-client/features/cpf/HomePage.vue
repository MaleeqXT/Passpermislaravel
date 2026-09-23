<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { Button, Errors, InputField, RadioField } from '@shared/components';
import { VueSignaturePad } from 'vue-signature-pad';
import { ref } from 'vue';
import { routes } from '@espace-client/routes.js';
import { useRoute } from '@shared/hooks';
const params = useRoute();
const form = useForm({
    name: params.name || '',
    date_naissance: params.date_naissance || '',
    phone: params.phone || '',
    email: params.email || '',
    type: params.type || '',
    first: null,
    two: null,
    two_feedback: '',
    three: null,
    four: null,
    five: null,
    sex: null,
    seven: null,
    eight: null,
    nine: null,
    ten: null,
    eleven: null,
    twelve: null,
    commentaire_formateur: '',
    nb_heur: '',
    date_evaluation: '',
    signature: '',
});

const question = {
    1: {
        [1]: {
            id: 1,
            name: 'Oui, régulièrement',
        },
        [2]: {
            id: 2,
            name: 'Oui, occasionnellement',
        },
        [3]: {
            id: 3,
            name: 'Non, jamais',
        },
    },
    2: {
        [1]: {
            id: 1,
            name: 'Conduite accompagnée',
        },
        [2]: {
            id: 2,
            name: 'Conduite supervisée',
        },
        [3]: {
            id: 3,
            name: 'Auto-école (cours interrompus)',
        },
        [4]: {
            id: 4,
            name: 'Autre',
        },
    },
    3: {
        [1]: {
            id: 1,
            name: 'Oui, encore valide',
        },
        [2]: {
            id: 2,
            name: 'Oui, mais expiré',
        },
        [3]: {
            id: 3,
            name: 'Non, jamais',
        },
    },
    4: {
        [1]: {
            id: 1,
            name: 'Très bonne',
        },
        [2]: {
            id: 2,
            name: 'Moyenne',
        },
        [3]: {
            id: 3,
            name: 'Faible',
        },
    },
    5: {
        [1]: {
            id: 1,
            name: 'Oui',
        },
        [2]: {
            id: 2,
            name: 'Non',
        },
    },
    6: {
        [1]: {
            id: 1,
            name: 'Interdiction de stationner',
        },
        [2]: {
            id: 2,
            name: 'Zone d’arrêt temporaire',
        },
        [3]: {
            id: 3,
            name: 'Priorité Piétonne',
        },
    },
    7: {
        [1]: {
            id: 1,
            name: 'La coordination des pédales (embrayage, frein, accélérateur)',
        },
        [2]: {
            id: 2,
            name: 'L’anticipation des dangers',
        },
        [3]: {
            id: 3,
            name: "L'Adaptation à la circulation",
        },
    },
    8: {
        [1]: {
            id: 1,
            name: 'Débutant (30h et plus recommandées)',
        },
        [2]: {
            id: 2,
            name: 'Intermédiaire (20-25h recommandées)',
        },
        [3]: {
            id: 3,
            name: 'Expérimenté (10-15h recommandées)',
        },
    },
};
const signaturePad = ref(null);

const checkValidate = () => {
    form.errors = {};
    if (!form.signature) {
        form.errors.signature = 'Ce champ est obligatoire';
        return false;
    }
    return true;
};

const clear = () => {
    signaturePad.value?.clearSignature?.();
};

const submit = () => {
    const { data } = signaturePad.value?.saveSignature?.() || {};
    form.signature = data;
    if (checkValidate()) {
        calculateNiveau();
        form.post(route(routes.cpfForm.testPositionnement), {
            onSuccess: () => form.reset(),
        });
    }
};

const calculateNiveau = () => {
    // Just an example of logic: feel free to customize it
    let score = 0;

    if (form.first?.id === 1) score += 2;
    else if (form.first?.id === 2) score += 1;

    if (form.three?.id === 1) score += 2;
    else if (form.three?.id === 2) score += 1;

    if (form.four?.id === 1) score += 2;
    else if (form.four?.id === 2) score += 1;

    if (form.seven?.id === 1) score += 2;
    if (form.eight?.id === 1) score += 2;
    if (form.nine?.id === 1) score += 2;
    if (form.ten?.id === 1) score += 2;

    // Estimate based on total score
    if (score >= 10) {
        form.nb_heur = 15; // Expérimenté
    } else if (score >= 6) {
        form.nb_heur = 25; // Intermédiaire
    } else {
        form.nb_heur = 30; // Débutant
    }
};
</script>

<template>
    <Head title="Test de positionnement en ligne CPF PPF" />
    <main class="h-full w-full bg-gradient-to-b from-primary to-[#01161d] px-2 py-5 flex flex-col">
        <div class="fixed z-0 inset-0 opacity-70 pointer-events-none">
            <img src="/assets/bg.svg" class="object-cover w-full h-full" />
        </div>

        <section class="sm:max-w-[700px] w-full mx-auto bg-white p-5 md:p-3 shadow-box rounded-xl gap-4 relative">
            <article class="flex-1 md:px-10">
                <div class="sm:w-full sm:max-w-md my-5">
                    <div class="flex flex-col gap-2 text-xs">
                        <span class=""
                            ><b>Le test de positionnement en ligne</b>, appelé aussi évaluation de niveau, est une étape clé pour estimer le
                            volume d’heures de conduite nécessaire avant d’accepter un dossier CPF.</span
                        >
                        <span class="font-bold"> Modèle d’Évaluation de Niveau – Permis de Conduire CPF </span>
                    </div>
                </div>
                <form class="flex flex-col gap-1" @submit.prevent="submit">
                    <label class="text-primary text-sm font-bold">Informations de l’élève</label>

                    <InputField
                        v-model="form.name"
                        label="Nom et Prénom"
                        :error="form.errors.name"
                        type="text"
                        anme="name"
                        autocomplete="name"
                        class="mt-1 block w-full"
                    />
                    <InputField
                        v-model="form.date_naissance"
                        label="Date de naissance"
                        :error="form.errors.date_naissance"
                        type="date"
                        anme="date_naissance"
                        autocomplete="date_naissance"
                        class="mt-1 block w-full"
                    />
                    <InputField
                        v-model="form.phone"
                        label="Numéro de téléphone"
                        :error="form.errors.phone"
                        type="text"
                        anme="phone"
                        autocomplete="phone"
                        class="mt-1 block w-full"
                    />
                    <InputField
                        v-model="form.email"
                        label="Adresse e-mail"
                        :error="form.errors.email"
                        type="email"
                        anme="email"
                        autocomplete="email"
                        class="mt-1 block w-full"
                    />
                    <InputField
                        v-model="form.type"
                        label="Catégorie de permis visée "
                        :error="form.errors.type"
                        type="text"
                        anme="type"
                        autocomplete="type"
                        class="mt-1 block w-full"
                    />

                    <label class="text-primary text-sm font-bold mt-2">1. Expérience de conduite</label>

                    <label class="text-sm font-semibold mt-2">1️⃣ Avez-vous déjà conduit un véhicule (auto/moto) ?</label>
                    <RadioField :error="form.errors.first" class="text-xs" v-model:full="form.first" :items="Object.values(question[1])" />

                    <label class="text-sm font-semibold mt-2">2️⃣ Si oui, dans quel cadre ?</label>
                    <RadioField :error="form.errors.two" class="text-xs" v-model:full="form.two" :items="Object.values(question[2])" />
                    <InputField
                        v-if="form.two?.id === 4"
                        v-model="form.two_feedback"
                        label="Autre "
                        :error="form.errors.two_feedback"
                        type="text"
                        anme="Autre"
                        autocomplete="Autre"
                        class="mt-1 block w-full"
                    />

                    <label class="text-primary text-sm font-bold mt-2">2. Connaissances du Code de la Route</label>

                    <label class="text-sm font-semibold mt-2">3️⃣ Avez-vous déjà obtenu l’examen du Code ?</label>
                    <RadioField :error="form.errors.three" class="text-xs" v-model:full="form.three" :items="Object.values(question[3])" />

                    <label class="text-sm font-semibold mt-2">4️⃣ Votre connaissance des panneaux de signalisation est-elle :</label>
                    <RadioField :error="form.errors.four" class="text-xs" v-model:full="form.four" :items="Object.values(question[4])" />

                    <label class="text-sm font-semibold mt-2">5️⃣ Savez-vous ce que signifie un panneau de priorité ?</label>
                    <RadioField :error="form.errors.five" class="text-xs" v-model:full="form.five" :items="Object.values(question[5])" />

                    <label class="text-sm font-semibold mt-2">6️⃣ Que signifie un marquage au sol en zigzag devant un arrêt de bus ? </label>
                    <RadioField :error="form.errors.sex" class="text-xs" v-model:full="form.sex" :items="Object.values(question[6])" />

                    <label class="text-primary text-sm font-bold mt-2">⚙️ 3. Maîtrise technique d’un véhicule (auto-évaluation)</label>

                    <label class="text-sm font-semibold mt-2">7️⃣ Êtes-vous à l’aise avec : </label>
                    <label class="text-sm font-semibold mt-2">🚦 Le démarrage et l’arrêt du véhicule ? : </label>
                    <RadioField :error="form.errors.seven" class="text-xs" v-model:full="form.seven" :items="Object.values(question[5])" />

                    <label class="text-sm font-semibold mt-2">🏁 Les changements de vitesse ? </label>
                    <RadioField :error="form.errors.eight" class="text-xs" v-model:full="form.eight" :items="Object.values(question[5])" />

                    <label class="text-sm font-semibold mt-2">🔄 Les manœuvres (créneau, marche arrière) ? : </label>
                    <RadioField :error="form.errors.nine" class="text-xs" v-model:full="form.nine" :items="Object.values(question[5])" />

                    <label class="text-sm font-semibold mt-2"> 🛑 La gestion des distances de sécurité ? : </label>
                    <RadioField :error="form.errors.ten" class="text-xs" v-model:full="form.ten" :items="Object.values(question[5])" />

                    <label class="text-sm font-semibold mt-2"> 8️⃣Avez-vous des difficultés avec : </label>
                    <RadioField
                        :error="form.errors.eleven"
                        class="text-xs"
                        v-model:full="form.eleven"
                        :items="Object.values(question[7])"
                    />

                    <label class="text-sm font-semibold mt-2"> 📊 4. Résultat de l’évaluation</label>
                    <label class="text-sm font-semibold mt-2"> ✔️ Niveau estimé :</label>
                    <RadioField
                        :error="form.errors.twelve"
                        class="text-xs"
                        v-model:full="form.twelve"
                        :items="Object.values(question[8])"
                    />

                    <label class="text-sm font-semibold mt-2">📝 Commentaire du formateur : </label>
                    <InputField
                        v-model="form.commentaire_formateur"
                        label="Commentaire "
                        :error="form.errors.commentaire_formateur"
                        type="text"
                        anme="commentaire_formateur"
                        autocomplete="commentaire_formateur"
                        class="mt-1 block w-full"
                    />

                    <label class="text-sm font-semibold mt-2">📌 Évaluation réalisée le</label>
                    <InputField
                        v-model="form.date_evaluation"
                        label="Date"
                        :error="form.errors.date_evaluation"
                        type="date"
                        anme="date"
                        autocomplete="date"
                        class="mt-1 block w-full"
                    />

                    <label class="text-sm font-semibold mt-2">Signature : </label>
                    <div class="w-[85vw] md:w-[200px] relative">
                        <VueSignaturePad
                            ref="signaturePad"
                            class="bg-gray-200 rounded-lg shadow !h-[85vw] md:!h-[200px]"
                            width="100%"
                            height="100%"
                        />
                        <Errors v-if="form.errors.signature" :errors="form.errors.signature" />
                        <Button variant="danger" full class="mt-2" @click="clear"> Effacer</Button>
                    </div>

                    <div class="mt-2">
                        <Button submit variant="primary" full :loading="form.mutating"> Suivant</Button>
                    </div>
                </form>
            </article>
        </section>
    </main>
</template>
