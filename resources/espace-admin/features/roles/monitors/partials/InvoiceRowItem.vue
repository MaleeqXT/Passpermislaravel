<script setup lang="ts">
import { routes } from '@espace-admin/routes';
import { reactive } from 'vue';
import { Button, TabSwitch } from '@shared/components';
import { dateFormat, moneyFormat, strip } from '@shared/utils';
import { ActivationStatus } from '@common/enums';
import { Link, useForm } from '@inertiajs/vue3';
import { EditIcon, ChartLineIcon } from '@adersolutions/icons';
defineEmits(['click:resume-hours']);
const props = defineProps({
    item: {
        type: Object,
        default: () => ({}),
    },
});
const state = reactive({
    isEdit: false,
});
const form = useForm({
    date_paiement: props.item.date_paiement || null,
    status: props.item.status ? 1 : 0,
});

const handleSave = () => {
    form.put(route(routes.invoices.update, props.item.id), {
        onSuccess: () => {
            state.isEdit = false;
        },
    });
};
const onCancel = () => {
    form.reset();
    state.isEdit = false;
};
</script>

<template>
    <tr class="group/actions">
        <td class="cell">
            <Link :href="route(routes.invoices.view, item.id)" class="flex flex-col btn btn-link items-start btn-info">
                <b>{{ item.num_facture }}</b>
                <span class="text-xs text-gray-600">{{ dateFormat(item.created_at, 'full') }}</span>
            </Link>
        </td>

        <td class="cell">
            <b class="text-primary">{{ strip(item.details?.etpB) }}</b>
        </td>
        <td class="cell">
            <b class="text-primary">{{ strip(item.details?.num_heures) }} h</b>
        </td>
        <td class="cell text-center">
            {{ moneyFormat(item.details?.total) }}
        </td>
        <td class="cell text-center">
            <input v-if="state.isEdit" v-model="form.date_paiement" type="date" class="form-control -my-1" placeholder="Date de paiement" />
            <span v-else>
                {{ dateFormat(item.date_paiement, 'full') }}
            </span>
        </td>
        <td class="cell">
            <TabSwitch
                :model-value="form.status"
                :items="Object.values(ActivationStatus)"
                :disabled="!state.isEdit"
                size="sm"
                class="bg-slate-200 !w-fit mx-auto"
                @update:model-value="form.status = !!$event"
            />
        </td>

        <td class="sticky right-0 bg-white w-10 mx-auto border-l">
            <div :class="['flex justify-center items-center h-full', state.isEdit ? 'gap-1' : 'gap-3']">
                <template v-if="!state.isEdit">
                    <Button
                        :icon="ChartLineIcon"
                        class="group-hover/actions:btn-dark text-dark"
                        tooltip="Resume heures"
                        @click="
                            $emit('click:resume-hours', {
                                billing: item.id,
                                monitor: item.monitor?.id,
                            })
                        "
                    />
                    <Button
                        :icon="EditIcon"
                        tooltip="Modifier"
                        class="group-hover/actions:btn-info text-blue-500"
                        @click="state.isEdit = true"
                    />
                </template>
                <template v-else>
                    <Button dark link class="!text-xs" @click="onCancel">Annuler</Button>
                    <Button
                        info
                        link
                        class="!text-xs"
                        :disabled="!form.isDirty || form.processing"
                        :loading="form.processing"
                        @click="handleSave"
                    >
                        Enreg
                    </Button>
                </template>
            </div>
        </td>
    </tr>
</template>
