<script setup lang="ts">
import { Button, Errors, Dialog, InputField } from '@shared/components';
import NativeSelect from '../NativeSelect.vue';
import { useForm } from '@inertiajs/vue3';
import { VueSignaturePad } from 'vue-signature-pad';
import { ref, watch } from 'vue';
import { QUESTIONS_SATISFACTION } from '@common/enums';
import { dqs } from '../../cpf';
import { useApp } from '@shared/stores';
import moment from 'moment-timezone';
import { routes } from '@espace-student/routes';
const emit = defineEmits(['close']);

const props = defineProps({
    show: Boolean,
    cpf: Object,
});
const { user } = useApp();
const signaturePad = ref(null);
const form = useForm({
    document: QUESTIONS_SATISFACTION,
    item: {
        q1: '',
        q2: '',
        q3: '',
        q4: '',
        q5: '',
        q6: '',
        q7: '',
        q8: '',
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
            if (!element && key !== 'q7' && key !== 'q8') {
                form.errors[key] = 'Ce champ est obligatoire';
                isValid = false;
            }
        }
    }
    return isValid;
};
const submit = () => {
    const { data } = signaturePad.value?.saveSignature?.() || {};
    form.item.signature = data;
    if (checkValidate()) {
        form.transform((data) => {
            data.data = data.item;
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
            <h2 class="text-center">{{ dqs.title }}</h2>
            <p>{{ dqs.intro1 }}</p>
            <p>{{ dqs.intro2 }}</p>
            <div class="font-bold">
                <div>
                    <p>{{ dqs.q1.text }}</p>
                    <NativeSelect v-model="form.item.q1" :error="form.errors.q1" :options="dqs.q1.options" />
                </div>
                <div>
                    <p>{{ dqs.q2.text }}</p>
                    <NativeSelect v-model="form.item.q2" :error="form.errors.q2" :options="dqs.q2.options" />
                </div>
                <div>
                    <p>{{ dqs.q3.text }}</p>
                    <NativeSelect v-model="form.item.q3" :error="form.errors.q3" :options="dqs.q3.options" />
                </div>
                <div>
                    <p>{{ dqs.q4.text }}</p>
                    <NativeSelect v-model="form.item.q4" :error="form.errors.q4" :options="dqs.q4.options" />
                </div>
                <div>
                    <p>{{ dqs.q5.text }}</p>
                    <NativeSelect v-model="form.item.q5" :error="form.errors.q5" :options="dqs.q5.options" />
                </div>
                <div>
                    <p>{{ dqs.q6.text }}</p>
                    <NativeSelect v-model="form.item.q6" :error="form.errors.q6" :options="dqs.q6.options" />
                </div>
                <div>
                    <p>{{ dqs.q7.text }}</p>
                    <InputField v-model="form.item.q7" :error="form.errors.q7" placeholder="------" class="max-w-xs" />
                </div>
                <div>
                    <p>{{ dqs.q8.text }}</p>
                    <InputField v-model="form.item.q8" :error="form.errors.q8" placeholder="------" class="max-w-xs" />
                </div>
            </div>
            <p>{{ dqs.text1 }}</p>
            <InputField v-model="user.name" class="max-w-xs" disabled />
            <p>{{ dqs.text2 }}</p>
            <InputField v-model="user.adresse" class="max-w-xs" disabled />
            <br />
            <div>
                <b>{{ dqs.text3 }}</b>
            </div>
            <div>{{ dqs.text4 }}</div>
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
            <p>{{ dqs.signature.label }}</p>
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
            <Button variant="primary" full class=" " :loading="form.processing" @click="submit"> Valider le document </Button>
        </div>
    </Dialog>
</template>
