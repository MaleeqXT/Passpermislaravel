<script setup>
import {Head, useForm} from '@inertiajs/vue3';
import {Button, InputField, RadioField} from '@shared/components';
import {routes} from "@espace-client/routes.js";

const form = useForm({
    q1: null,
    q2: null,
    q3: null,
    q4: null,
    q5: null,
    q6: null,
    q7: null,
    q8: null,
    q9: null,
    q3_feedback: '',
    q5_feedback: '',
});
const props = defineProps(['info'])
const submit = () => {
    alert('en cliquant sur valider cela signifiera que vous attester et signer ce document')
    form.post(route(routes.cpfForm.attestationStore,props.info.id), {
        onSuccess: () => form.reset()
    });

};

const question = {
    1: {
        [1]: {
            id: 1,
            name: 'Il facilitera votre recherche d’emploi',
        },
        [2]: {
            id: 2,
            name: 'En application d’une clause de mobilité géographique, votre lieu de travail est maintenant significativement\n' +
                'éloigné de votre domicile',
        },
        [3]: {
            id: 3,
            name: 'Vous serez bientôt amené(e) à travailler en horaire décalé (notamment la nuit)',
        },
        [4]: {
            id: 4,
            name: 'Vous êtes amené(e) à exercer des contrats de travail successifs sur des lieux éloignés de votre domicile',
        },
        [5]: {
            id: 5,
            name: 'Autre (préciser) :',
        },
    },
    2: {
        [1]: {
            id: 1,
            name: 'J’atteste sur l’honneur ne pas être en situation de suspension/retrait de permis ou d’interdiction de passer le permis de conduire.',
        },
        [2]: {
            id: 2,
            name: "Je dispose déjà d’une ou plusieurs catégories de permis de conduire d’un véhicule terrestre à moteur en cours de validité.",
        },
    },
    3: {
        [1]: {
            id: 1,
            name: 'OUI',
        },
        [2]: {
            id: 2,
            name: "NON",
        },
    },
    4: {
        [1]: {
            id: 1,
            name: 'Atteste sur l’honneur que l’utilisation de mon CPF financera une action éligible comme indiquée ci-\n' +
                'dessus et que mes déclarations sont sincères.',
        }
    },
}


</script>

<template>
    <Head title="Test de positionnement en ligne CPF PPF"/>
    <main class="h-screen flex-center flex-col w-full bg-gradient-to-b from-primary to-[#01161d] px-2">
        <div class="fixed z-0 inset-0 opacity-70 pointer-events-none">
            <img src="/assets/bg.svg" class="object-cover w-full h-full"/>
        </div>

        <section
            class="sm:max-w-[700px] max-h-[99%] w-full mx-auto bg-white flex max-md:flex-col p-5 md:p-3 shadow-box rounded-xl gap-4 relative">

            <article class="flex-1 md:px-10 scrollbar overflow-y-auto">
                <div class=" w-full  my-5 ">
                    <div class="flex-center  flex-col gap-3 text-sm ">

                        <span class="font-bold">Attestation sur l’honneur</span>
                        <span class="font-bold">
                            Merci de répondre au questionnaire ci-dessous en fonction de votre situation :
                        </span>
                    </div>

                </div>
                <form class=" flex flex-col gap-2" @submit.prevent="submit">
                    <label class="text-primary text-sm font-bold">Cas n°1 L’obtention du permis de conduire
                        contribuerait à la réalisation de votre projet professionnel.</label>
                    <label class="text-sm font-semibold">Si oui, répondre aux deux questions ci-dessous :</label>
                    <label class="text-sm font-semibold">Quel est le projet ?</label>

                    <InputField
                        v-model="form.q1"
                        label="Quel est le projet ?"
                        :error="form.errors.q1"
                        type="text"
                        anme="q1"
                        autocomplete="Q1"
                        class="mt-1 block w-full"
                    />
                    <label class="text-sm font-semibold">En quoi ce permis contribuerait à la réalisation de votre
                        projet ?</label>

                    <InputField
                        v-model="form.q2"
                        label="En quoi ce permis contribuerait à la réalisation de votre projet ?"
                        :error="form.errors.q2"
                        type="text"
                        anme="q2"
                        autocomplete="Q2"
                        class="mt-1 block w-full"
                    />

                    <label class="text-primary text-sm font-bold">Cas n°2 L’obtention du permis de conduire
                        contribuerait à la sécurisation de votre parcours professionnel :</label>

                    <RadioField :error="form.errors.q3" class="text-xs" v-model:full="form.q3"
                                :items="question[1]"/>
                    <InputField
                        v-if="form.q3?.id === 5"
                        v-model="form.q3_feedback"
                        label="Autre ?"
                        :error="form.errors.q3_feedback"
                        type="text"
                        class="mt-1 block w-full"
                    />
                    <label class="text-primary text-sm font-bold">Cas n°3 - L’obtention du permis de conduire
                        contribuerait à une évolution professionnelle (et non pas
                        adaptation du poste de travail) au sein de votre entreprise.</label>

                    <InputField
                        v-model="form.q4"
                        label="Laquelle ? :"
                        :error="form.errors.q4"
                        type="text"
                        class="mt-1 block w-full"
                    />
                    <RadioField :error="form.errors.q5" class="text-xs" v-model:full="form.q5"
                                :items="question[2]"/>

                    <InputField
                        v-if="form.q5?.id === 2"
                        v-model="form.q5_feedback"
                        label="Préciser laquelle ou lesquelles ?"
                        :error="form.errors.q5_feedback"
                        type="text"
                        class="mt-1 block w-full"
                    />

                    <label class="text-primary text-sm font-bold">Avez-vous déjà mobilisé votre CPF pour financer un
                        permis de véhicule terrestre à moteur ?</label>
                    <RadioField :error="form.errors.q6" class="text-xs" v-model:full="form.q6"
                                :items="question[3]"/>


                    <InputField
                        v-model="form.q7"
                        label="Lequel/lesquels Et quand ?"
                        :error="form.errors.q7"
                        type="text"
                        class="mt-1 block w-full"
                    />

                    <label class="text-primary text-sm font-bold">domicilié(e) à</label>

                    <InputField
                        v-model="form.q8"
                        label="domicilié(e) à"
                        :error="form.errors.q8"
                        type="text"
                        class="mt-1 block w-full"
                    />
                    <label class="text-primary text-sm font-bold">Confirme </label>
                    <RadioField :error="form.errors.q9" class="text-xs" v-model:full="form.q9"
                                :items="question[4]"/>


                    <div class="mt-2">
                        <Button submit variant="primary" full :loading="form.mutating"> Suivant</Button>

                    </div>
                </form>
            </article>
        </section>
    </main>
</template>
