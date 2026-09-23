<script setup lang="ts">
import { moneyFormat } from '@shared/utils';
import { type CommandesStatsType, getStateVariant } from '../commandes';
import { ArrowDownIcon, ArrowUpIcon } from '@adersolutions/icons';
import { computed } from 'vue';

type PropsType = {
    stats: CommandesStatsType;
    days: number;
};
const props = defineProps<PropsType>();
const days = computed(() => {
    if (props.days > 365) {
        return `${(props.days / 365).toFixed(1)} derniers anneés`;
    } else if (props.days > 60) {
        return `${(props.days / 30).toFixed(1)} derniers mois`;
    } else {
        return `${props.days} derniers jours`;
    }
});

const parsedComarison = computed<Record<string, any>>(() => getStateVariant(props.stats.comparison, days.value));
</script>

<template>
    <section class="box overflow-clip text-sm mb-5">
        <article
            class="grid grid-cols-2 md:grid-cols-4 bg-dark bg-rainbow rainbow-opacity-50 text-white font-light divide-x divide-white/5"
        >
            <div class="p-3">
                <h3 class="flex justify-between">
                    <span>Revenu payé</span>
                    <span class="flex gap-1">
                        <span :class="['text-2xs flex-center px-1 h-5 rounded-md', parsedComarison.paid_revenue.color]">
                            <component :is="parsedComarison.paid_revenue.icon" class="w-4 h-4" />
                            {{ stats.comparison.paid_revenue.perecent }}%
                        </span>
                        <span
                            v-if="stats.comparison.paid_sales.value"
                            :tooltip="parsedComarison.paid_sales.text"
                            tb
                            :class="['text-2xs flex-center px-2 h-5 rounded-md', parsedComarison.paid_sales.color]"
                        >
                            {{ stats.comparison.paid_sales.value }}
                        </span>
                    </span>
                </h3>
                <p class="text-xl flex gap-1 -mb-0.5 mt-2 font-semibold">{{ moneyFormat(stats.current.paid_revenue) }}</p>
                <p class="text-2xs mt-0.5 opacity-70">{{ parsedComarison.paid_revenue.text }}</p>
            </div>
            <div class="p-3">
                <h3 class="flex justify-between">
                    <span>Revenu en attente</span>
                    <span :class="['text-2xs flex-center px-1 h-5 rounded-md ', parsedComarison.pending_revenue.color]">
                        <component :is="parsedComarison.pending_revenue.icon" class="w-4 h-4" />
                        {{ stats.comparison.pending_revenue.perecent }}%
                    </span>
                </h3>
                <p class="text-xl flex gap-1 -mb-0.5 mt-2 font-semibold">
                    {{ moneyFormat(stats.current.pending_revenue) }}
                </p>
                <p class="text-2xs mt-0.5 opacity-70">{{ parsedComarison.pending_revenue.text }}</p>
            </div>

            <div class="p-3">
                <h3 class="flex justify-between">
                    <span>Revenu annulé</span>
                    <span :class="['text-2xs flex-center px-1 h-5 rounded-md ', parsedComarison.canceled_revenue.color]">
                        <component :is="parsedComarison.canceled_revenue.icon" class="w-4 h-4" />
                        {{ stats.comparison.canceled_revenue.perecent }}%
                    </span>
                </h3>
                <p class="text-xl flex gap-1 -mb-0.5 mt-2 font-semibold">
                    {{ moneyFormat(stats.current.canceled_revenue) }}
                </p>
                <p class="text-2xs mt-0.5 opacity-70">{{ parsedComarison.canceled_revenue.text }}</p>
            </div>

            <div class="p-3">
                <h3 class="flex justify-between">
                    <span>Revenu remboursé</span>
                    <span :class="['text-2xs flex-center px-1 h-5 rounded-md text-white', parsedComarison.refunded_revenue.color]">
                        <component :is="parsedComarison.refunded_revenue.icon" class="w-4 h-4" />
                        {{ stats.comparison.refunded_revenue.perecent }}%
                    </span>
                </h3>
                <p class="text-xl flex gap-1 -mb-0.5 mt-2 font-semibold">
                    {{ moneyFormat(stats.current.refunded_revenue) }}
                </p>
                <p class="text-2xs mt-0.5 opacity-70">{{ parsedComarison.refunded_revenue.text }}</p>
            </div>
        </article>
        <div class="rainbow"></div>
        <article class="grid grid-cols-2 md:grid-cols-4 bg-white text-dark divide-x divide-dark/5">
            <dl class="px-3 py-2 flex items-center justify-between">
                <dt>Ventes payées</dt>
                <dd class="font-semibold">
                    {{ stats.current.paid_sales }}
                </dd>
            </dl>

            <dl class="px-3 py-2 flex items-center justify-between">
                <dt>Ventes en attente</dt>
                <dd class="font-semibold">{{ stats.current.pending_sales }}</dd>
            </dl>
            <dl class="px-3 py-2 flex items-center justify-between">
                <dt>Ventes annulées</dt>
                <dd class="font-semibold text-orange-600">{{ stats.current.canceled_sales }}</dd>
            </dl>
            <dl class="px-3 py-2 flex items-center justify-between">
                <dt>Ventes remboursées</dt>
                <dd class="font-semibold text-red-600">{{ stats.current.refunded_sales }}</dd>
            </dl>
        </article>
    </section>
</template>
