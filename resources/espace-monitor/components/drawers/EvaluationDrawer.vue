<script setup lang="ts">
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Button, Card, CheckField, DateField, Drawer, InputField, SignField, SliderField, TabSwitch } from '@shared/components';
import { routes } from '@espace-monitor/routes';
import { useStorage } from '@shared/hooks';
import type { StudentType } from '@common/types';
import { dateFormat } from '@shared/utils';
const emit = defineEmits(['close', 'review']);

const steps = [
    'Informations élève',
    'Formation Choisie',
    'Expérience de conduite',
    'Connaissance véhicule',
    'Attitude formation',
    'Habiletés constatées',
    'Compréhension',
    'Gestion émotions',
    'Résultats',
] as const;

type PropsType = {
    show: boolean;
    student: StudentType;
    mid: string;
    rid: string;
};
const props = defineProps<PropsType>();
const currentStep = ref<number>(0);
const [sign, setSign] = useStorage('MKSIGN-', props.mid);

const form = useForm({
    student: {
        wears_correction: false,
        visual_acuity: '',
    },
    training: {
        automatic: false,
        adapted_vehicle: false,
        accompanied_driving: false,
        manual: false,
        supervised_driving: false,
    },
    experience: {
        license: {
            am: false,
            b1: false,
            a1: false,
        },
        driving: {
            already_driven: false,
            with_parents: false,
            with_driving_school: false,
        },
        other_vehicles: {
            moped: false,
            bike: false,
            tractor: false,
            cart: false,
            quad: false,
            lawnmower: false,
            trolley: false,
        },
    },
    vehicle_knowledge: {
        steering: false,
        clutch: false,
        gearbox: false,
        braking: false,
    },
    attitude: {
        master_car_and_code: false,
        inevitable_step: false,
        anticipate_difficulties: false,
        desire_to_do_it: false,
    },
    skills: {
        vehicle_setup: '',
        steering_wheel: '',
        starting: '',
        stopping: '',
    },
    understanding: {
        comprehension: 0,
        restitution: 0,
    },
    environment: {
        trajectory: 0,
        orientation: 0,
        observation: 0,
        look: 0,
    },
    emotions: {
        relationship: 0,
        tension: 0,
    },
    results: {
        score: null,
        lessons_proposed: null,
        proposal_accepted: 'Non',
        // done_on: dateFormat('', 'iso'),
        parent_signature: '',
        student_signature: '',
        monitor_signature: sign.value || undefined,
    },
});

const close = () => {
    emit('close');
};
const progress = computed<number>(() => {
    return ((currentStep.value + 1) / steps.length) * 100;
});

const nextStep = (): void => {
    if (currentStep.value < steps.length - 1) {
        currentStep.value++;
    }
};

const prevStep = (): void => {
    if (currentStep.value > 0) {
        currentStep.value--;
    }
};

const submit = (): void => {
    form.clearErrors()
        .transform((data) => {
            return {
                data,
                monitor_id: props.mid,
                reservation_id: props.rid,
            };
        })
        .post(route(routes.evaluations.store, props.student.id), {
            onSuccess: () => {
                !!form.results.monitor_signature && setSign(form.results.monitor_signature);
                emit('close');
                emit('review');
            },
            onError: (errors) => {
                // Handle errors
            },
        });
};
</script>
<template>
    <Drawer :show="!!show" title="Évaluation de conduite" @close="close">
        <form @submit.prevent="submit" class="flex flex-col h-full">
            <!-- Progress Steps -->
            <div class="px-3">
                <div class="mb-2 w-full flex items-center">
                    <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2 flex-1">
                        <span class="bg-indigo-500 text-white w-7 h-7 rounded-full flex-center text-xs">{{ currentStep + 1 }}</span>
                        {{ steps[currentStep] }}
                    </h2>
                    <span class="text-xs font-medium text-gray-700">Étape {{ currentStep + 1 }} / {{ steps.length }}</span>
                </div>
                <div class="relative pt-1">
                    <div class="overflow-hidden h-1 mb-4 text-xs flex rounded bg-gray-300">
                        <div
                            :style="`width: ${progress}%`"
                            class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-indigo-500 transition-all duration-300"
                        ></div>
                    </div>
                </div>
            </div>
            <div class="px-3">
                <div v-if="currentStep === 0" class="grid gap-4 enter-up">
                    <InputField label="Nom, Prénom" :model-value="student.user?.name" disabled />
                    <DateField label="Date de Naissance" :model-value="student.user?.date_naissance" disabled />
                    <InputField label="Téléphone" :model-value="student.user?.phone" type="tel" disabled />
                    <!-- <InputField label="Profession ou ambition professionnelle" :model-value="student.user?." /> -->
                    <CheckField label="Port de correction" v-model:checked="form.student.wears_correction" />
                    <InputField label="Acuité visuelle" v-model="form.student.visual_acuity" />
                </div>

                <Card v-else-if="currentStep === 1" title="Formation choisie" block padding class="gap-4 enter-up">
                    <CheckField label="Boite Automatique" v-model:checked="form.training.automatic" />
                    <CheckField label="Véhicule aménagé" v-model:checked="form.training.adapted_vehicle" />
                    <CheckField label="Conduite accompagnée" v-model:checked="form.training.accompanied_driving" />
                    <CheckField label="Boite Mécanique" v-model:checked="form.training.manual" />
                    <CheckField label="Conduite supervisée" v-model:checked="form.training.supervised_driving" />
                </Card>

                <div v-else-if="currentStep === 2" class="enter-up">
                    <Card title="Permis" block padding>
                        <div class="flex gap-3">
                            <CheckField label="AM" v-model:checked="form.experience.license.am" />
                            <CheckField label="B1" v-model:checked="form.experience.license.b1" />
                            <CheckField label="A1" v-model:checked="form.experience.license.a1" />
                        </div>
                    </Card>

                    <Card title="Conduite Auto" block padding>
                        <CheckField label="Déjà conduit" v-model:checked="form.experience.driving.already_driven" />
                        <CheckField label="Avec Parents" v-model:checked="form.experience.driving.with_parents" />
                        <CheckField label="En Auto-école" v-model:checked="form.experience.driving.with_driving_school" />
                    </Card>

                    <Card title="Autres véhicules" block padding>
                        <CheckField label="Cyclomoteur" v-model:checked="form.experience.other_vehicles.moped" />
                        <CheckField label="Vélo" v-model:checked="form.experience.other_vehicles.bike" />
                        <CheckField label="Tracteur" v-model:checked="form.experience.other_vehicles.tractor" />
                        <CheckField label="Voiturette" v-model:checked="form.experience.other_vehicles.cart" />
                        <CheckField label="Quad" v-model:checked="form.experience.other_vehicles.quad" />
                        <CheckField label="Tondeuse" v-model:checked="form.experience.other_vehicles.lawnmower" />
                        <CheckField label="Caddie" v-model:checked="form.experience.other_vehicles.trolley" />
                    </Card>
                </div>

                <Card title="Connaissance du véhicule" block padding v-else-if="currentStep === 3" class="enter-up">
                    <CheckField label="Direction" v-model:checked="form.vehicle_knowledge.steering" />
                    <CheckField label="Embrayage" v-model:checked="form.vehicle_knowledge.clutch" />
                    <CheckField label="Boite de vitesse" v-model:checked="form.vehicle_knowledge.gearbox" />
                    <CheckField label="Freinage" v-model:checked="form.vehicle_knowledge.braking" />
                </Card>

                <Card title="Attitude vis à vis de la formation de conduite" v-else-if="currentStep === 4" class="enter-up" block padding>
                    <CheckField label="Maîtriser la voiture et le code" v-model:checked="form.attitude.master_car_and_code" />
                    <CheckField label="C'est un passage inévitable" v-model:checked="form.attitude.inevitable_step" />
                    <CheckField
                        label="Prévoir les difficultés, savoir y faire face"
                        v-model:checked="form.attitude.anticipate_difficulties"
                    />
                    <CheckField label="Envie/Désir de le faire" v-model:checked="form.attitude.desire_to_do_it" />
                </Card>

                <Card v-else-if="currentStep === 5" title="Habiletés constatées lors de l'évaluation" class="enter-up" block padding>
                    <InputField label="Installation dans le véhicule" v-model="form.skills.vehicle_setup" :multiline="2" />
                    <InputField label="Manipulation du volant" v-model="form.skills.steering_wheel" :multiline="2" />
                    <InputField label="Démarrages" v-model="form.skills.starting" :multiline="2" />
                    <InputField label="Arrêts" v-model="form.skills.stopping" :multiline="2" />
                </Card>

                <div v-else-if="currentStep === 6" class="enter-up">
                    <Card title="Compréhension / Restitution" block padding class="gap-4">
                        <SliderField label="Compréhension" v-model="form.understanding.comprehension" :max="10" />
                        <SliderField label="Restitution" v-model="form.understanding.restitution" :max="10" />
                    </Card>
                    <Card title="Gestion de l'environnement" block padding class="gap-4">
                        <SliderField label="Trajectoire" v-model="form.environment.trajectory" :max="10" />
                        <SliderField label="Orientation" v-model="form.environment.orientation" :max="10" />
                        <SliderField label="Observation" v-model="form.environment.observation" :max="10" />
                        <SliderField label="Regard" v-model="form.environment.look" :max="10" />
                    </Card>
                </div>

                <Card v-else-if="currentStep === 7" title="Gestion des émotions" class="enter-up" block padding>
                    <SliderField label="Relationnel" v-model="form.emotions.relationship" :max="10" />
                    <SliderField label="Crispation" v-model="form.emotions.tension" :max="10" />
                </Card>

                <Card v-else-if="currentStep === 8" title="Résultat de l'évaluation" class="enter-up" block padding>
                    <InputField label="Score de l'évaluation" v-model="form.results.score" type="number" />
                    <InputField label="Nombre de leçons proposées" v-model="form.results.lessons_proposed" type="number" />
                    <div class="flex justify-between items-center my-2">
                        <label for="proposal_accepted" class="form-label">Proposition acceptée</label>
                        <TabSwitch
                            label="Proposition acceptée"
                            v-model="form.results.proposal_accepted"
                            light
                            class="bg-gray-200"
                            :items="[
                                { id: 'Non', name: 'Non', class: 'danger' },
                                { id: 'Oui', name: 'Oui', class: 'success' },
                            ]"
                        />
                    </div>
                    <InputField label="Fait le" :model-value="dateFormat(new Date(), 'letter')" disabled />
                    <div class="grid gap-1 grid-cols-2">
                        <!-- <SignField label="Signature élève" v-model="form.results.student_signature" /> -->
                        <SignField label="Signature moniteur" v-model="form.results.monitor_signature" />
                    </div>

                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700">Cachet de l'auto école</label>
                        <img src="/assets/signature.png" class="h-16 mx-auto mt-10" alt="Cachet de l'auto école" />
                    </div>
                </Card>
            </div>
            <!-- Navigation Buttons -->
            <!-- <div class="page-actions bg-white"> -->
            <div class="flex-1"></div>
            <div class="mt-8 flex justify-between page-actions bg-white">
                <Button type="button" @click="prevStep" :disabled="currentStep === 0" variant="secondary"> &larr; Précédent </Button>

                <Button type="button" @click="nextStep" v-if="currentStep < steps.length - 1" variant="indigo"> Suivant &rarr; </Button>

                <Button submit v-else variant="primary"> Soumettre l'évaluation </Button>
            </div>
        </form>
    </Drawer>
</template>
