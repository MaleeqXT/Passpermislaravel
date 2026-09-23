<script setup lang="ts">
import { Button, TabSwitch } from '@shared/components';
import { routes } from '@espace-admin/routes';
import { reactive } from 'vue';
import { EditIcon, DeleteIcon } from '@adersolutions/icons';
import { ActivationStatus } from '@common/enums';
import { useForm } from '@inertiajs/vue3';
defineEmits(['item:delete']);
const props = defineProps({
    item: {
        type: Object,
        default: () => ({}),
    },
    isZip: Boolean,
});

const state = reactive({
    isEdit: false,
});
const form = useForm({
    name: props.item.name,
    url: props.item.url || '',
    code: props.item.code || '',
    status: props.item.status ? 1 : 0,
});

const onUpdate = () => {
    if (props.isZip) {
        return form.put(route(routes.settings.locations.zip.update, props.item.id), {
            onSuccess: () => {
                state.isEdit = false;
            },
        });
    }
    form.put(route(routes.settings.locations.places.update, props.item.id), {
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
    <tr>
        <td class="cell">
            <template v-if="state.isEdit">
                <input
                    v-if="isZip"
                    v-model="form.code"
                    type="text"
                    class="form-control -my-1 p-1 h-8"
                    placeholder="Le code postal"
                    autofocus
                />
                <div v-else class="flex flex-col border border-gray-300 divide-y rounded-lg overflow-hidden shadow-sm -my-1">
                    <input
                        v-model="form.name"
                        type="text"
                        class="text-xs focus:ring-0 p-1 h-7 !border-none"
                        placeholder="Localisation"
                        autofocus
                    />
                    <div></div>
                    <input
                        v-model="form.url"
                        type="url"
                        class="text-xs focus:ring-0 p-1 h-7 !border-none text-blue-500"
                        placeholder="URL: https://google.com/maps?q=1.00,-0.00"
                    />
                </div>
            </template>
            <div v-else>
                <p>
                    {{ item.name || item.code }}
                </p>
                <a
                    v-if="item.url && !isZip"
                    :href="item.url"
                    target="_blank"
                    class="!text-2xs btn btn-info btn-link !px-0 max-w-60 line-clamp-1 text-wrap"
                    rel="noopener noreferrer"
                >
                    {{ item.url }}
                </a>
            </div>
        </td>
        <td class="cell">
            <TabSwitch
                :model-value="form.status ? 1 : 0"
                size="sm"
                :disabled="!state.isEdit"
                class="bg-slate-200 !w-fit mx-auto"
                :items="Object.values(ActivationStatus)"
                @update:model-value="form.status = !!$event"
            />
        </td>
        <td class="sticky right-0 bg-white w-10 mx-auto border-l">
            <div :class="['flex justify-center items-center  h-full', state.isEdit ? 'gap-1' : 'gap-3']">
                <template v-if="!state.isEdit">
                    <Button :icon="EditIcon" info link tooltip="Modifier" @click="state.isEdit = true" />
                    <Button :icon="DeleteIcon" danger link tooltip="Supprimer" tr @click="$emit('item:delete')" />
                </template>
                <template v-else>
                    <Button dark link class="!text-xs" @click="onCancel">Annuler</Button>
                    <Button
                        info
                        link
                        class="!text-xs"
                        :disabled="isZip ? !form.code : !form.name"
                        :loading="form.processing"
                        @click="onUpdate"
                    >
                        Enreg
                    </Button>
                </template>
            </div>
        </td>
    </tr>
</template>
