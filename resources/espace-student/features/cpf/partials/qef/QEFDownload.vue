<script setup lang="ts">
import { Button } from '@shared/components';
import { ref } from 'vue';
import { downloadPdf } from '@common/utils';
import { qef } from '../../cpf';
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
        name: qef.title,
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
                <h2 class="text-center mt-0 mb-10">{{ qef.title }}</h2>
                <p>{{ qef.content1 }}</p>
                <p>{{ qef.content2 }}</p>
                <div>
                    <span>1. {{ qef.qsts.qst_1 }}</span>
                    <b class="block">
                        {{ item.data?.permi_conduit }}
                    </b>
                </div>
                <div>
                    <span>2. {{ qef.qsts.qst_2 }}</span>
                    <b class="block">
                        {{ item.data?.handicap }}
                    </b>
                </div>
                <div>
                    <span>3. {{ qef.qsts.qst_3 }}</span>
                    <b class="block">
                        {{ item.data?.code_route }}
                    </b>
                </div>
                <div>
                    <span>4. {{ qef.qsts.qst_4 }}</span>
                    <b class="block">
                        {{ item.data?.neph }}
                    </b>
                </div>
                <div>
                    <span>5. {{ qef.qsts.qst_5 }}</span>
                    <b class="block">
                        {{ item.data?.examen_conduit }}
                    </b>
                </div>
                <p>{{ qef.text1 }}</p>
                <p>
                    <b>{{ user.name }}</b>
                </p>

                <p>{{ qef.text2 }}</p>
                <p>
                    <b>{{ user.adresse }}</b>
                </p>

                <p>{{ qef.text3 }}</p>
                <div>
                    A <b>{{ item.data?.certifie_a }}</b>
                </div>
                <div>
                    Le <b>{{ item.data?.certifie_le }}</b>
                </div>

                <p class="mb-0">{{ qef.signature.label }}</p>
                <img :src="item.data?.signature" alt="signature" class="w-32 h-32 mt-0" />
                <p>
                    <small>
                        {{ qef.signature.note }}
                    </small>
                </p>
            </div>
        </div>
    </div>
</template>
