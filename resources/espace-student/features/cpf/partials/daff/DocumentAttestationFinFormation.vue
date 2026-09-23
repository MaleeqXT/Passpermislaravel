<script setup lang="ts">
import { Button, Errors, Dialog } from '@shared/components';
import { useForm } from '@inertiajs/vue3';
// @ts-ignore
import { VueSignaturePad } from 'vue-signature-pad';
import { ref, watch } from 'vue';
import { ATTESTATION_FIN_FORMATION } from '@common/enums';
import { daff } from '../../cpf';
import { useApp } from '@shared/stores';
import { routes } from '@espace-student/routes';
import { dateFormat } from '@shared/utils';
const emit = defineEmits(['close']);

const props = defineProps({
    show: Boolean,
    cpf: Object,
});
const { user } = useApp();
const signaturePad = ref<any | null>(null);
const form = useForm({
    document: ATTESTATION_FIN_FORMATION,
    item: {
        date: new Date().toISOString(),
        signature: '',
    },
});
const clear = () => {
    signaturePad.value?.clearSignature?.();
};
const checkValidate = () => {
    form.errors = {};
    if (!form.item.signature) {
        form.errors.signature = 'Ce champ est obligatoire';
        return false;
    }
    return true;
};

const submit = () => {
    const { data } = signaturePad.value?.saveSignature?.() || {};
    form.item.signature = data;
    if (checkValidate()) {
        form.transform((data) => {
            data.data = data.item;
            delete data.item;
            return data;
        }).post(route(routes.cpf.store, props.cpf?.id), {
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
            <h2 class="text-center">{{ daff.title }}</h2>
            <p>{{ daff.content1 }}</p>
            <p>{{ daff.content2 }}</p>
            <p>
                {{ daff.t1 }} <b>{{ user.name }}</b> {{ daff.t2 }} <b>"{{ cpf.offer?.name }}"</b>.
            </p>
            <p>{{ daff.content3 }}</p>

            <p>
                {{ daff.label1 }} <b>{{ dateFormat(cpf.start_at, 'fr') }}</b>
            </p>
            <p>
                {{ daff.label2 }} <b>{{ dateFormat(cpf.end_at, 'fr') }}</b>
            </p>
            <p>
                {{ daff.label3 }} <b>{{ cpf.offer?.balance }} heures</b>
            </p>

            <p>
                {{ daff.content4 }}
            </p>

            <p>
                {{ daff.label4 }} <b> {{ dateFormat(form.item.date, 'fr') }}</b>
            </p>

            <div class="grid md:grid-cols-2 gap-3">
                <div>
                    <p>{{ daff.signature.label }}</p>
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
                <div class="">
                    <p class="text-center max-w-60 ml-auto">{{ daff.signature.represent }}</p>
                    <img src="/assets/images/common/contract.png" class="w-60 mt-1 block ml-auto" alt="contract" />
                </div>
            </div>
        </div>

        <div class="flex sticky bottom-0 bg-white shadow-up p-3 gap-3">
            <Button dark class="w-2/3 justify-center" @click="$emit('close')"> Fermer </Button>
            <Button variant="primary" full class=" " :loading="form.processing" @click="submit"> Valider le document </Button>
        </div>
    </Dialog>
</template>
