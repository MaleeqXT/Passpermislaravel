<script setup lang="ts">
import { Button, Errors, Dialog, InputField } from '@shared/components';
import NativeSelect from '../NativeSelect.vue';
import { useForm } from '@inertiajs/vue3';
import { VueSignaturePad } from 'vue-signature-pad';
import { ref, watch } from 'vue';
import { ATTESTATION_HONOR } from '@common/enums';
import { dah } from '../../cpf';
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
const doc = ref(null);
const form = useForm({
    document: ATTESTATION_HONOR,
    item: {
        cas_1: '',
        cas_2: '',
        cas_2_autre: '',
        certifie_a: '',
        certifie_le: moment().format('yyyy-MM-DD'),
        signature: '',
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
            if (!element) {
                if (!form.item.cas_1 && !form.item.cas_2) {
                    form.errors.cas_1 = 'Ce champ est obligatoire si le cas 2 est vide';
                    form.errors.cas_2 = 'Ce champ est obligatoire si le cas 1 est vide';
                    isValid = false;
                }
                if (form.item.cas_2 === dah.cas2[6] && !form.item.cas_2_autre) {
                    form.errors.cas_2_autre = 'Ce champ est obligatoire';
                    isValid = false;
                }
                if (key !== 'cas_2' && key !== 'cas_2_autre' && key !== 'cas_1') {
                    form.errors[key] = 'Ce champ est obligatoire';
                    isValid = false;
                }
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
        <div ref="doc" class="prose max-w-full p-5 font-pdf">
            <h2 class="text-center">{{ dah.title }}</h2>
            <p>{{ dah.content1 }}</p>
            <p>{{ dah.content2 }}</p>
            <p><b>1°</b> {{ dah.c1 }}</p>
            <p><b>2°</b> {{ dah.c2 }}</p>
            <p>{{ dah.content3 }}</p>

            <div class="font-bold">
                <div>
                    <p>{{ dah.label1 }}</p>
                    <NativeSelect v-model="form.item.cas_1" :disabled="!!form.item.cas_2" :error="form.errors.cas_1" :options="dah.cas1" />
                </div>
                <div>
                    <p>{{ dah.label2 }}</p>
                    <NativeSelect v-model="form.item.cas_2" :disabled="!!form.item.cas_1" :error="form.errors.cas_2" :options="dah.cas2" />
                    <InputField
                        v-if="dah.cas2[6] === form.item.cas_2"
                        v-model="form.item.cas_2_autre"
                        :error="form.errors.cas_2_autre"
                        class="max-w-xs mt-2"
                        placeholder="Autre (précisez)"
                        :multiline="4"
                    />
                </div>
            </div>
            <p>
                <b>{{ dah.content4 }}</b>
            </p>
            <p>{{ dah.content5 }}</p>
            <p>{{ dah.text1 }}</p>
            <InputField v-model="user.name" class="max-w-xs" disabled />
            <p>{{ dah.text2 }}</p>
            <InputField v-model="user.adresse" class="max-w-xs" disabled />
            <p>{{ dah.text3 }}</p>
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
            <p>{{ dah.signature.label }}</p>
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
            <p>
                <small>
                    {{ dah.signature.note }}
                </small>
            </p>
        </div>

        <div class="flex sticky bottom-0 bg-white shadow-up p-3 gap-3">
            <Button dark class="w-2/3 justify-center" @click="$emit('close')"> Fermer </Button>
            <Button variant="primary" full class=" " :loading="form.processing" @click="submit"> Valider le document </Button>
        </div>
    </Dialog>
</template>
