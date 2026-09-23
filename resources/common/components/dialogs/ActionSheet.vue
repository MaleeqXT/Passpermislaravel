<script setup lang="ts">
import { reactive } from 'vue';
import { Button, Dialog } from '@shared/components';
import { GroupActionType } from '@shared/types';
type PropsType = {
    actions: GroupActionType[];
    show?: boolean;
};
const emit = defineEmits(['close']);
withDefaults(defineProps<PropsType>(), {
    actions: () => [] as GroupActionType[],
    show: false,
});

const state = reactive({
    show: false,
});
const onShowAction = () => {
    state.show = true;
};
const onClose = () => {
    state.show = false;
    emit('close');
};
const onAction = (action: GroupActionType) => {
    onClose();
    action.onAction?.();
};
</script>

<template>
    <div class="contents">
        <div @click="onShowAction">
            <slot />
        </div>
        <Dialog
            :show="state.show || show"
            custom-class="!h-fit z-10 w-full mt-auto mx-auto"
            z-index="z-[998]"
            class-name="!py-4"
            @close="onClose"
        >
            <div class="flex flex-col h-full divide-y bg-white rounded-xl overflow-clip">
                <slot name="content" />
                <Button
                    v-for="(item, idx) in actions"
                    :key="idx"
                    :href="item.href"
                    full
                    class="w-full rounded-none !p-3 !h-auto !text-base !font-medium"
                    @click="onAction(item)"
                >
                    {{ item.label }}
                </Button>
            </div>
            <span
                class="bg-white text-dark w-full flex-center p-3 rounded-xl mt-2 active:bg-gray-100 btn-m font-medium text-base"
                @click="onClose"
            >
                Fermer
            </span>
        </Dialog>
    </div>
</template>
