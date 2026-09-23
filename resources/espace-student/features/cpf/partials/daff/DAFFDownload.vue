<script setup lang="ts">
import { Button } from '@shared/components';
import { ref } from 'vue';
import { downloadPdf } from '@common/utils';
import { daff } from '../../cpf';
import { PageDownIcon } from '@adersolutions/icons';
import { useApp } from '@shared/stores';
import { dateFormat } from '@shared/utils';
defineEmits(['close']);
defineProps({
    item: {
        type: Object,
        default: () => ({}),
    },
    cpf: {
        type: Object,
        default: () => ({}),
    },
    title: String,
});
const doc = ref(null);
const { user } = useApp();

const download = () => {
    downloadPdf(doc.value, {
        name: daff.title,
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
                <h2 class="text-center mt-0 mb-10">{{ daff.title }}</h2>
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
                    {{ daff.label4 }} <b> {{ dateFormat(item.data.date, 'fr') }}</b>
                </p>
                <div class="grid md:grid-cols-2 gap-3">
                    <div>
                        <p>{{ daff.signature.label }}</p>
                        <img :src="item.data?.signature" alt="signature" class="w-32 h-32 mt-0" />
                    </div>
                    <div class="">
                        <p class="text-center max-w-60 ml-auto">{{ daff.signature.represent }}</p>
                        <img src="/assets/images/common/contract.png" class="w-60 mt-1 block ml-auto" alt="contract" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
