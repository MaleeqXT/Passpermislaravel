<script setup lang="ts">
import { Button } from '@shared/components';
import { ref } from 'vue';
import { downloadPdf } from '@common/utils';
import { dsp } from '../../cpf';
import { PageDownIcon } from '@adersolutions/icons';
import { useApp } from '@shared/stores';
defineEmits(['close']);
defineProps({
    item: {
        type: Object,
        default: () => ({}),
    },
    title: String,
});
const doc = ref(null);
const { user } = useApp();

const download = () => {
    downloadPdf(doc.value, {
        name: dsp.title,
        unit: 'px',
        x: 0,
        y: 0,
        width: 450,
        windowWidth: 900,
    });
};
</script>

<template>
    <div class="contents">
        <Button class="btn-pdf" link :icon="PageDownIcon" info @click="download">
            {{ title || 'Télécharger mon document' }}
        </Button>
        <div class="hidden">
            <div ref="doc" class="prose prose-sm max-w-full py-5 px-14 font-pdf">
                <img src="/assets/logo.svg" alt="passpermisfacile" class="h-12 mx-auto my-3" />
                <h2 class="text-center mt-0 mb-10">{{ dsp.title }}</h2>
                <p>
                    {{ dsp.nom }} <b>{{ user.first_name }}</b>
                </p>
                <p>
                    {{ dsp.prenom }} <b>{{ user.last_name }}</b>
                </p>
                <div class="mb-4">
                    <div>{{ dsp.q1.text }}</div>
                    <b>{{ item.data.q1 }}</b>
                    <div>
                        {{ item.data.q1_precisez }}
                    </div>
                </div>
                <div class="mb-4">
                    <div>{{ dsp.q2.text }}</div>
                    <b>{{ item.data.q2 }}</b>
                    <div>{{ item.data.q2_precisez }}</div>
                </div>

                <div class="mb-4">
                    <div>{{ dsp.text1 }}</div>
                    <b>{{ user.name }}</b>
                </div>
                <div class="mb-4">
                    <div>{{ dsp.text2 }}</div>
                    <b>{{ user.adresse }}</b>
                </div>
                <div class="mb-4">
                    <div>{{ dsp.text3 }}</div>
                    <div>
                        A <b>{{ item.data?.certifie_a }}</b>
                    </div>
                    <div>
                        Le <b>{{ item.data?.certifie_le }}</b>
                    </div>
                </div>

                <p class="mb-0">{{ dsp.signature.label }}</p>
                <img :src="item.data?.signature" alt="signature" class="w-32 h-32 mt-0" />
            </div>
        </div>
    </div>
</template>
