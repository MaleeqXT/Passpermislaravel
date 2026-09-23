<script setup lang="ts">
import { Button } from '@shared/components';
import { ref } from 'vue';
import { downloadPdf } from '@common/utils';
import { dah } from '../../cpf';
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
        name: dah.title,
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
                <h2 class="text-center mt-0 mb-10">{{ dah.title }}</h2>
                <p>{{ dah.content1 }}</p>
                <p>{{ dah.content2 }}</p>
                <p><b>1°</b> {{ dah.c1 }}</p>
                <p><b>2°</b> {{ dah.c2 }}</p>
                <p>{{ dah.content3 }}</p>
                <div class="font-bold">
                    <div>
                        <p>{{ dah.label1 }}</p>
                        <b>
                            {{ item.data?.cas_1 || '----' }}
                        </b>
                    </div>
                    <div>
                        <p>{{ dah.label2 }}</p>
                        <b class="block">
                            {{ item.data?.cas_2 || '----' }}
                        </b>
                        <b v-if="item.data?.cas_2_autre" class="block">
                            {{ item.data?.cas_2_autre }}
                        </b>
                    </div>
                </div>
                <p>
                    <b>{{ dah.content4 }}</b>
                </p>
                <p>{{ dah.content5 }}</p>
                <p>{{ dah.text1 }}</p>
                <p>
                    <b>{{ user.name }}</b>
                </p>

                <p>{{ dah.text2 }}</p>
                <p>
                    <b>{{ user.adresse }}</b>
                </p>

                <p>{{ dah.text3 }}</p>
                <div>
                    A <b>{{ item.data?.certifie_a }}</b>
                </div>
                <div>
                    Le <b>{{ item.data?.certifie_le }}</b>
                </div>

                <p class="mb-0">{{ dah.signature.label }}</p>
                <img :src="item.data?.signature" alt="signature" class="w-32 h-32 mt-0" />
                <p>
                    <small>
                        {{ dah.signature.note }}
                    </small>
                </p>
            </div>
        </div>
    </div>
</template>
