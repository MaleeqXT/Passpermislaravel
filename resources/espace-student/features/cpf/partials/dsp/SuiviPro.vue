<script setup lang="ts">
import { Button, Errors, Dialog, InputField } from '@shared/components';
import NativeSelect from '../NativeSelect.vue';
import { useForm } from '@inertiajs/vue3';
import { VueSignaturePad } from 'vue-signature-pad';
import { ref, watch } from 'vue';
import { dsp } from '../../cpf';
import { useApp } from '@shared/stores';
import moment from 'moment-timezone';
import { routes } from '@espace-student/routes';
const emit = defineEmits(['close']);

const props = defineProps({
    show: Boolean,
    cpf: Object,
    typeDoc: Number,
});
const { user } = useApp();
const signaturePad = ref(null);
const form = useForm({
    document: null,
    item: {
        q1: '',
        q1_precisez: '',
        q2: '',
        q2_precisez: '',
        certifie_a: '',
        signature: '',
        certifie_le: moment().format('yyyy-MM-DD'),
    },
});
const clear = () => {
    signaturePad.value?.clearSignature?.();
};
const checkValidate = () => {
    let isValid = true;
    form.errors = {};
    for (const key in form.item) {
        if (Object.prototype.hasOwnProperty.call(form.item, key)) {
            const element = form.item[key];
            if (!element && key !== 'q1_precisez' && key !== 'q2_precisez') {
                form.errors[key] = 'Ce champ est obligatoire';
                isValid = false;
            }
        }
    }
    if (!form.item.q1_precisez && (form.item.q1 === dsp.q1.options[0] || form.item.q1 === dsp.q1.options[1])) {
        form.errors.q1_precisez = 'Ce champ est obligatoire 1';
        isValid = false;
    }
    if (!form.item.q2_precisez && (form.item.q2 === dsp.q2.options[0] || form.item.q2 === dsp.q2.options[1])) {
        form.errors.q2_precisez = 'Ce champ est obligatoire 2';
        isValid = false;
    }
    return isValid;
};
const submit = () => {
    const { data } = signaturePad.value?.saveSignature?.() || {};
    form.item.signature = data;
    if (checkValidate()) {
        form.transform((data) => {
            data.data = data.item;
            data.document = props.typeDoc;
            delete data.item;
            return data;
        }).post(route(routes.cpf.store, props.cpf.id), {
            onSuccess: () => {
                emit('close');
            },
        });
    }
};
watch(
    () => props.show,
    (value) => {
        setTimeout(() => {
            value && signaturePad.value?.resizeCanvas();
        }, 100);
    }
);
</script>

<template>
    <Dialog
        :show="!!show"
        class-name="!p-0"
        custom-class="bg-white rounded-t-3xl md:rounded-xl shadow-xl transform t-200 w-full mx-auto  max-w-screen-md h-full max-h-[calc(100%-3rem)] md:h-fit mt-auto md:mt-0 flex flex-col "
        @close="$emit('close')"
    >
        <div class="prose max-w-full p-5 font-pdf">
            <h2 class="text-center">{{ dsp.title }}</h2>
            <p>
                {{ dsp.nom }} <b>{{ user.first_name }}</b>
            </p>
            <p>
                {{ dsp.prenom }} <b>{{ user.last_name }}</b>
            </p>
            <div>
                <p>{{ dsp.q1.text }}</p>
                <NativeSelect v-model="form.item.q1" :error="form.errors.q1" :options="dsp.q1.options" />
                <InputField
                    v-if="dsp.q1.options[0] === form.item.q1 || dsp.q1.options[1] === form.item.q1"
                    v-model="form.item.q1_precisez"
                    :error="form.errors.q1_precisez"
                    class="max-w-xs mt-2"
                    :placeholder="dsp.q1.options[1] === form.item.q1 ? 'Quelles sont les raisons ?' : 'Comment ?'"
                    :multiline="2"
                />
            </div>
            <div>
                <p>{{ dsp.q2.text }}</p>
                <NativeSelect v-model="form.item.q2" :error="form.errors.q2" :options="dsp.q2.options" />
                <InputField
                    v-if="dsp.q2.options[0] === form.item.q2 || dsp.q2.options[1] === form.item.q2"
                    v-model="form.item.q2_precisez"
                    :error="form.errors.q2_precisez"
                    class="max-w-xs mt-2"
                    :placeholder="dsp.q2.options[1] === form.item.q2 ? 'Quelles sont les raisons ?' : 'Comment ?'"
                    :multiline="2"
                />
            </div>
            <p>{{ dsp.text1 }}</p>
            <InputField v-model="user.name" class="max-w-xs" disabled />
            <p>{{ dsp.text2 }}</p>
            <InputField v-model="user.adresse" class="max-w-xs" disabled />
            <br />
            <div>
                <b>{{ dsp.text3 }}</b>
            </div>
            <div>{{ dsp.text4 }}</div>
            <br />
            <div class="grid md:grid-cols-2 gap-3">
                <div>
                    <b>A</b>
                    <InputField v-model="form.item.certifie_a" :error="form.errors.certifie_a" placeholder="------" class="max-w-xs" />
                </div>
                <div>
                    <b>Le</b>
                    <InputField :model-value="form.item.certifie_le" class="max-w-xs" disabled />
                </div>
            </div>
            <p>{{ dsp.signature.label }}</p>
            <div class="w-[85vw] md:w-[200px] relative">
                <VueSignaturePad
                    ref="signaturePad"
                    class="bg-gray-200 rounded-lg shadow !h-[85vw] md:!h-[200px]"
                    width="100%"
                    height="100%"
                />
                <Errors v-if="form.errors.signature" :errors="form.errors.signature" />
                <Button variant="danger" full class="mt-2" @click="clear"> Effacer </Button>
            </div>
        </div>

        <div class="flex sticky bottom-0 bg-white shadow-up p-3 gap-3">
            <Button dark class="w-2/3 justify-center" @click="$emit('close')"> Fermer </Button>
            <Button variant="primary" full :loading="form.processing" @click="submit"> Valider le document </Button>
        </div>
    </Dialog>
</template>
