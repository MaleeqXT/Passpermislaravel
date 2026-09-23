<script setup lang="ts">
import { computed } from 'vue';
import { Button, ButtonsList, Card, Drawer } from '@shared/components';
import {
    ChatIcon,
    // ChevronRightIcon,
    BookIcon,
    // EditIcon,
    NoteAddIcon,
    WalletIcon,
    // PhoneOutIcon,
    PhoneIcon,
    MenuHorizontalIcon,
} from '@adersolutions/icons';
import { routes } from '@espace-monitor/routes';
// import { Link } from '@inertiajs/vue3';
// import { DialogPageAction } from '../common';
import { ActionSheet, ItemImage, PageMobile, StudentStatsCard } from '@common/components';
import { dateFormat, getFilePath } from '@shared/utils';
import { useReservationDetails } from '@espace-monitor/stores';
import { StudentType } from '@common/types';

defineEmits(['close']);

const details = useReservationDetails();
const student = computed(() => ({ ...(details.user?.student || {}), user: details.user } as StudentType));
const navigation = computed(() => {
    const rm = details.data?.review_monitor || null;
    return [
        {
            label: 'Toutes les séances',
            href: route(routes.reservations.student, student.value.id || '-'),
            icon: WalletIcon,
        },
        {
            label: 'Commentaires pédagogique',
            icon: ChatIcon,
            href: rm ? route(routes.reviews.show, rm.id) : route(routes.reviews.index, student.value.id || '-'),
        },
        {
            label: 'Competence',
            href: route(routes.competences.index, student.value.id || '-'),
            icon: BookIcon,
        },
        {
            label: 'Proposer une séance',
            href: route(routes.reservations.index, { student_id: student.value.id }),
            icon: NoteAddIcon,
        },
    ];
});
</script>

<template>
    <div class="contents">
        <Drawer :show="!!details.user" title="Condidat Details" @close="details.user = null">
            <template #actions>
                <ActionSheet :actions="navigation" @close="details.user = null">
                    <MenuHorizontalIcon class="w-6 h-6" />
                </ActionSheet>
            </template>
            <div class="px-3 flex-1 flex flex-col gap-3">
                <StudentStatsCard v-if="details.user" :student="student" />

                <Card title="Informations personnelles">
                    <ul class="divide-y box bg-white text-sm mt-1">
                        <!-- <li class="flex gap-3 px-3 py-2 justify-between">
                            <p>CPF</p>
                            <b class="block">{{ student.is_cpf === '1' ? 'Oui' : 'Non' }}</b>
                        </li> -->
                        <li class="flex gap-3 px-3 py-2 justify-between">
                            <span>Numéro NEPH</span>
                            <b class="block">{{ student.neph || 'N/A' }}</b>
                        </li>

                        <li class="flex gap-3 px-3 py-2 justify-between">
                            <span>Estimation</span>
                            <b class="block">{{ student.review_monitor?.estimation || 'N/A' }}</b>
                        </li>

                        <li v-if="student.user?.phone" class="flex gap-3 px-3 py-2 justify-between">
                            <span>Télèphone</span>
                            <b class="block">{{ student.user?.phone }}</b>
                        </li>
                        <li v-if="student.user?.ville" class="flex gap-3 px-3 py-2 justify-between">
                            <span>Ville</span>
                            <b class="block">{{ student.user?.ville }}</b>
                        </li>
                        <li v-if="student.user?.adresse" class="flex gap-3 px-3 py-2 justify-between">
                            <span>Lieu </span>
                            <b class="block">{{ student.user?.adresse }}</b>
                        </li>

                        <li v-if="student.user?.date_naissance" class="flex gap-3 px-3 py-2 justify-between">
                            <span>Date de naissance</span>

                            <b class="block">
                                {{ dateFormat(student.user?.date_naissance, 'letter') }}
                                ({{ dateFormat(student.user?.date_naissance, 'old') }}
                                ans)
                            </b>
                        </li>
                    </ul>
                </Card>
            </div>
            <div v-if="student.user" class="p-2 backdrop-blur-md bg-gray-100 sticky bottom-0 w-full">
                <Button variant="warning" :icon="PhoneIcon" full :href="`Tel:${student.user.phone}`" self> Appel immédiat </Button>
            </div>
        </Drawer>
    </div>
</template>
