<script setup lang="ts">
import { Card, DataTable } from '@shared/components';
import { useQuery } from '@shared/hooks';
import { routes } from '@espace-admin/routes';
import { ItemImage, Description, DataView } from '@common/components';
import { dateFormat, getFilePath, getName } from '@shared/utils';
import { ReservationType } from '@common/types';
import { CheckSmallIcon } from '@adersolutions/icons';

const props = defineProps({
    sid: String,
});

const resumeHours = useQuery<Record<string, ReservationType[]>>({
    url: route(routes.api.resumeHours.index, props.sid),
    // transformable: true,
    mounted: true,
});
</script>
<template>
    <DataView :query="resumeHours" :empty="Object.keys(resumeHours.data).length">
        <ul role="list" class="space-y-1 px-3 text-dark overflow-clip mb-10 pt-5">
            <template v-for="(group, date) in resumeHours.data" :key="date">
                <li class="relative flex gap-x-3">
                    <div :class="['-bottom-6', 'absolute left-0 top-0 flex w-6 justify-center']">
                        <div class="w-px bg-gray-300" />
                    </div>
                    <div class="relative flex size-6 flex-none items-center justify-center bg-gray-100">
                        <div class="size-1.5 rounded-full bg-gray-100 ring-1 ring-gray-300" />
                    </div>
                    <p class="flex-auto py-0.5 text-xs/5 text-gray-500 mt-0.5">
                        <b class="text-dark">{{ dateFormat(date, 'letter') }}</b>
                    </p>
                </li>
                <li v-for="item in group" :key="item.id" class="relative flex gap-x-2.5">
                    <div :class="['-bottom-8', 'absolute left-0 top-0 flex w-6 justify-center line']">
                        <div class="w-px bg-gray-300" />
                    </div>
                    <div class="relative flex size-6 bg-primary flex-center rounded-full">
                        <CheckSmallIcon class="size-6 text-white" aria-hidden="true" />
                    </div>

                    <dl class="flex-auto rounded-md overflow-clip bg-white ring-1 ring-inset ring-gray-200/60">
                        <dd class="flex justify-between gap-2 text-primary text-2xs py-1 border-b px-3">
                            <time :datetime="item.date"> {{ item.start_at }} - {{ item.end_at }} </time>
                        </dd>
                        <dd class="text-xs py-1 px-3">
                            <p class="flex-1">
                                <b>{{ item.training?.offer?.name }}</b> par Moniteur <b>{{ getName(item.monitor?.user) }}</b>
                            </p>
                            <p class="flex-1 truncate">{{ item.review_monitor?.comment }}</p>
                        </dd>
                    </dl>
                </li>
            </template>
        </ul>
    </DataView>
</template>
