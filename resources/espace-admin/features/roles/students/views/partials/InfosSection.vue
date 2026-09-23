<script setup lang="ts">
import { ref } from 'vue';
import { Button } from '@shared/components';
import AddBalanceDrawer from './modals/AddBalanceDrawer.vue';
import { Card } from '@shared/components';
import { AdjustIcon } from '@adersolutions/icons';
import { UserType } from '@common/types';

type PropsType = {
    user: UserType;
};
const props = defineProps<PropsType>();

const currentBalance = ref(props.user.student?.balance);
const selectedBalance = ref(false);

const setBalance = (balance) => {
    currentBalance.value = balance;
};
</script>

<template>
    <div class="contents">
        <h1 class="text-base font-semibold mb-2 text-gray-600">Info personnel</h1>
        <Card block padding>
            <dl class="flex flex-col divide-y">
                <div class="pb-4 md:grid md:grid-cols-2 md:gap-4">
                    <div>
                        <dt class="text-sm/6 font-medium text-gray-900">Ville</dt>
                        <dd class="mt-1 text-sm/6 text-gray-700 sm:mt-2">
                            {{ user.ville }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm/6 font-medium text-gray-900">Code postal</dt>
                        <dd class="mt-1 text-sm/6 text-gray-700 sm:mt-2">
                            {{ user.postal }}
                        </dd>
                    </div>
                </div>
                <div class="py-4 md:grid md:grid-cols-2 md:gap-4">
                    <div>
                        <dt class="text-sm/6 font-medium text-gray-900">NEPH</dt>
                        <dd class="mt-1 text-sm/6 text-gray-700 sm:mt-2">
                            {{ user.student?.neph }}
                        </dd>
                    </div>
                    <!-- <div>
                        <dt class="text-sm/6 font-medium text-gray-900">Frequence</dt>
                        <dd class="mt-1 text-sm/6 text-gray-700 sm:mt-2">
                            {{ user.student?.frequence }}
                        </dd>
                    </div> -->
                </div>
                <div class="pt-4 md:grid md:grid-cols-2 md:gap-4">
                    <div>
                        <dt class="text-sm/6 font-medium text-gray-900 flex items-center justify-between">
                            <span> Balance Disponible </span>
                            <Button link info :icon="AdjustIcon" @click="selectedBalance = true"> Modifier </Button>
                        </dt>
                        <dd class="mt-1 text-sm/6 text-gray-700 sm:mt-2">
                            {{ currentBalance }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm/6 font-medium text-gray-900">Estimation</dt>
                        <dd class="mt-1 text-sm/6 text-gray-700 sm:mt-2">
                            {{ user.student?.review_monitor?.estimation || 0 }}
                        </dd>
                    </div>
                </div>
            </dl>
        </Card>

        <AddBalanceDrawer :show="selectedBalance" :user="user" @close="selectedBalance = false" @new-balance="setBalance" />
    </div>
</template>
