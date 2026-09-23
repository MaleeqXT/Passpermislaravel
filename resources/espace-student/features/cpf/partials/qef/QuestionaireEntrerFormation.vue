<script setup lang="ts">
import { Button, Errors, Dialog, InputField } from '@shared/components';
import NativeSelect from '../NativeSelect.vue';
import { useForm } from '@inertiajs/vue3';
import { VueSignaturePad } from 'vue-signature-pad';
import { ref, watch } from 'vue';
import { QUESTIONS_FORMATION } from '@common/enums';
import { qef } from '../../cpf';
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
    document: QUESTIONS_FORMATION,
    item: {
        permi_conduit: '',
        handicap: '',
        code_route: '',
        neph: '',
        examen_conduit: '',
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
            if (!element) {
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
            <h2 class="text-center">{{ qef.title }}</h2>
            <p>{{ qef.content1 }}</p>
            <p>{{ qef.content2 }}</p>
            <ol class="font-bold">
                <li>
                    <p>{{ qef.qsts.qst_1 }}</p>
                    <NativeSelect v-model="form.item.permi_conduit" :error="form.errors.permi_conduit" :options="['Oui', 'Non']" />
                </li>
                <li>
                    <p>{{ qef.qsts.qst_2 }}</p>
                    <NativeSelect v-model="form.item.handicap" :error="form.errors.handicap" :options="['Non', 'Si Oui lequel ?']" />
                </li>
                <li>
                    <p>{{ qef.qsts.qst_3 }}</p>
                    <NativeSelect v-model="form.item.code_route" :error="form.errors.code_route" :options="['Oui', 'Non']" />
                </li>
                <li>
                    <p>{{ qef.qsts.qst_4 }}</p>
                    <NativeSelect
                        v-model="form.item.neph"
                        :error="form.errors.neph"
                        :options="['Non', 'Oui', 'Je ne sais pas ce qu’est un numéro « NEPH »']"
                    />
                </li>
                <li>
                    <p>{{ qef.qsts.qst_5 }}</p>
                    <NativeSelect
                        v-model="form.item.examen_conduit"
                        :error="form.errors.examen_conduit"
                        :options="['Non', 'Oui, une seule fois', 'Oui, deux fois', 'Oui, plus de deux fois']"
                    />
                </li>
            </ol>
            <p>{{ qef.text1 }}</p>
            <InputField v-model="user.name" class="max-w-xs" disabled />
            <p>{{ qef.text2 }}</p>
            <InputField v-model="user.adresse" class="max-w-xs" disabled />
            <p>{{ qef.text3 }}</p>
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
            <p>{{ qef.signature.label }}</p>
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
                    {{ qef.signature.note }}
                </small>
            </p>
        </div>

        <div class="flex sticky bottom-0 bg-white shadow-up p-3 gap-3">
            <Button dark class="w-2/3 justify-center" @click="$emit('close')"> Fermer </Button>
            <Button variant="primary" full class=" " :loading="form.processing" @click="submit"> Valider le document </Button>
        </div>
    </Dialog>
</template>
