<template>
    <section class="container-app">
        <TabGroup :default-index="params.tab" @change="onChangeTab">
            <TabList
                :class="[
                    'flex gap-3 md:gap-5 max-w-3xl mx-auto ',

                    isStudentEspace
                        ? ' text-nowrap  max-w-[100vw] overflow-x-auto pb-2 md:pb-6 my-3 no-scrollbar'
                        : 'flex-wrap pb-5 md:pb-20 justify-center',
                ]"
            >
                <Tab v-for="(item, idx) in tabs" :key="idx" v-slot="{ selected }" as="template">
                    <button
                        :class="[
                            'leading-8 text-sm border border-orange-100 rounded-full py-[10px] px-5 font-medium focus:outline-none focus:ring-0',
                            selected ? 'bg-orange-100' : 'bg-white',
                        ]"
                    >
                        {{ item.name }}
                    </button>
                </Tab>
            </TabList>
            <section>
                <div
                    v-if="cart.state.successMsg"
                    class="text-sm text-green-700 bg-green-100 text-center py-2 rounded-full border border-green-400 max-w-xl mx-auto mb-3 md:mb-5"
                >
                    {{ cart.state.successMsg }}
                </div>
                <ul class="grid md:grid-cols-3 gap-3 md:gap-5">
                    <li
                        v-for="(item, idx) in offers?.data"
                        :key="idx"
                        class="flex-1 flex flex-col shadow-box rounded-xl p-5 gap-5 h-full bg-white"
                    >
                        <div class="flex justify-between items-center">
                            <div class="flex flex-col leading-8 max-w-[16rem]">
                                <span class="text-lg text-gray-500 font-semibold">
                                    {{ item.name }}
                                </span>
                                <div v-if="item.multi_payment > 1" class="py-1">
                                    <Badge variant="dark">
                                        Paiement possible en
                                        {{ item.multi_payment }} fois
                                    </Badge>
                                </div>
                            </div>
                            <div class="relative flex">
                                <template v-if="item.balance">
                                    <!-- <SvgHourCount
                                        v-for="(hour, key) in getIconValues(item.balance)"
                                        :key="key"
                                        :hours="hour"
                                    /> -->
                                </template>
                                <!-- <Icon
                                    v-else
                                    name="cpf/forfais-user"
                                    class="w-fit mx-auto"
                                /> -->
                            </div>
                        </div>
                        <div class="flex-1 prose prose-sm" v-html="item.caracteristiques"></div>
                        <div class="flex justify-between items-center">
                            <h3 class="text-[28px] font-bold flex items-baseline justify-center">
                                <span>
                                    {{ moneyFormat(item?.final_price) }}
                                </span>
                                <span v-if="item?.multi_payment > 1" class="text-xs font-bold text-primary mb-px ml-1 opacity-80">
                                    ({{ moneyFormat(item?.final_price / (item?.multi_payment || 1)) }}) {{ item?.multi_payment }} fois
                                </span>
                            </h3>
                            <Button
                                v-if="cart.isExist(item)"
                                danger
                                class="px-8 py-5"
                                :disabled="cart.mutation.mutating || cart.query.fetching"
                                :loading="cart.state.loading[item.id]"
                                @click="cart.remove(item)"
                            >
                                Retirer
                            </Button>
                            <Button
                                v-else
                                class="bg-orange-100 px-8 py-5 text-white"
                                :loading="cart.state.loading[item.id]"
                                :disabled="cart.mutation.mutating || cart.query.fetching"
                                @click="cart.add(item)"
                            >
                                Ajouter
                            </Button>
                        </div>
                    </li>
                </ul>
            </section>
        </TabGroup>
    </section>
</template>

<script setup lang="ts">
import { TabGroup, TabList, Tab } from '@headlessui/vue';
import { Button, Badge } from '@shared/components';
import { useCart } from '@shared/stores';
import { moneyFormat } from '@shared/utils';
// import { SvgHourCount } from '@espace-client/features/cpf/partials';
// import { getIconValues } from '@common/utils';
import { OffreTypeList, OffreTypeEnum } from '@common/enums';
import { useRoute } from '@shared/hooks';
defineProps({
    isStudentEspace: Boolean,
    offers: {
        type: Object,
        default: () => ({}),
    },
});
const cart = useCart();
const params = useRoute();
const pe = OffreTypeList;
delete pe[2];
const tabs = [
    {
        name: 'Forfaits Boîte Manuelle',
        params: {
            is_auto: 0,
            excluded_type_offre: [OffreTypeEnum.CODE_EN_LIGNE, OffreTypeEnum.NEPH],
            tab: 0,
        },
    },
    {
        name: 'Forfaits Boîte Automatique',
        params: { is_auto: 1, tab: 1 },
    },
    ...Object.values(pe).map((v, idx) => ({
        name: v.name,
        params: { type_offre: v.id, tab: idx + 2 },
    })),
];

const onChangeTab = (index) => {
    params.set(tabs[index].params, () => {}, true);
};
</script>
