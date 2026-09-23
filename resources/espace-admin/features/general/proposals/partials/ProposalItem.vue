<script setup lang="ts">
import { ItemImage } from '@common/components';
import { dateFormat, getFilePath } from '@shared/utils';
import { routes } from '@espace-admin/routes';
import { ProposalLessonStatus, ProposalStatusEnum } from '@common/enums';
import { ChatIcon, ClockIcon, LocationIcon, UnknownDeviceIcon } from '@adersolutions/icons';
import type { ProposalType } from '@common/types';
type PropsType = { item: ProposalType };

defineProps<PropsType>();
</script>

<template>
    <li :class="['relative flex gap-2 box  p-1', item.status === ProposalStatusEnum.PENDING ? 'bg-empty' : 'bg-white']">
        <component
            :is="ProposalLessonStatus[item.status].icon"
            :class="[
                'size-6  rounded-full ring-4 ring-gray-100 absolute -left-[31.5px] top-0 z-1 p-0.5',
                'status-' + ProposalLessonStatus[item.status].class,
            ]"
        />
        <div class="bg-dark text-white box flex-center flex-col gap-1 w-16 h-[70px] bg-rainbow rounded-xl min-w-20">
            <span class="text-base font-bold">
                {{ item.reservation.start_at }}
            </span>
            <span class="block text-[10px] font-medium">
                {{ item.reservation.end_at }}
            </span>
        </div>
        <div class="flex flex-col gap-0.5 text-sm col-span-2 w-1/4">
            <ItemImage
                v-if="item.reservation"
                :src="getFilePath(item.reservation.monitor?.user)"
                size="w-7 h-7"
                class="bg-white w-full rounded-lg p-0.5 border"
                :title="item.reservation.monitor?.user?.name"
                :href="route(routes.users.monitors.edit, item.reservation.monitor?.id, '-')"
            />
            <ItemImage
                :src="getFilePath(item.student?.user)"
                size="w-7 h-7"
                class="bg-white w-full rounded-lg p-0.5 border"
                :title="item.student?.user?.name"
                :href="route(routes.users.students.general, item?.student.id)"
            />
        </div>
        <dl class="flex flex-col gap-0.5 text-xs w-1/5 justify-evenly border-r">
            <dt class="flex items-center gap-2">
                <ClockIcon class="size-6 bg-gray-200 p-1 rounded-md" />
                <span class="first-letter:capitalize"> {{ dateFormat(item.reservation?.date, 'fromNow') }}</span>
            </dt>
            <dd class="flex items-center gap-2">
                <LocationIcon class="size-6 bg-gray-200 p-1 rounded-md" />
                <p class="flex-1 line-clamp-1">
                    {{ item.reservation.lieu?.name }}
                </p>
            </dd>
        </dl>

        <dl class="flex flex-col gap-0.5 text-xs justify-evenly flex-1">
            <dt class="flex items-center gap-2">
                <UnknownDeviceIcon class="size-6 bg-gray-200 p-1 rounded-md" />
                <p class="drop-shadow flex-1 line-clamp-1">
                    {{ ProposalLessonStatus[item.status].desc }}
                </p>
            </dt>
            <dd class="flex items-center gap-2">
                <ChatIcon class="size-6 bg-gray-200 p-1 rounded-md" />
                <p v-if="item.comment" class="flex-1 line-clamp-2 text-gray-600 rounded-lg drop-shadow">
                    {{ item.comment }}
                </p>
                <span v-else class="text-gray-400 font-light"> Sans commentaire </span>
            </dd>
        </dl>
    </li>
</template>
