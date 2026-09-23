<script setup lang="ts">
import { StarIcon, StarFilledIcon, ChatIcon } from '@adersolutions/icons';
import { CompetencyType } from '@common/types';
import { routes } from '@espace-monitor/routes';
import { useForm } from '@inertiajs/vue3';
import { Button } from '@shared/components';
import { ref } from 'vue';
defineEmits(['select']);

type PropsType = { item: CompetencyType; sid: string };
const props = defineProps<PropsType>();

const showComment = ref(false);
const feedbacks = [
    { id: 1, label: 'En cours' },
    { id: 2, label: 'À améliorer' },
    { id: 3, label: 'Maîtrisé' },
];
const form = useForm({
    comment: props.item.rating?.comment || '',
    rating: props.item.rating?.rating || '',
    student_id: props.sid,
});

const onSubmitChangeRating = (value: number) => {
    form.rating = value;
    form.post(route(routes.competences.storeOrUpdate, props.item.id), {
        preserveScroll: true,
        onSuccess: () => {
            form.defaults();
            showComment.value = false;
        },
    });
};
</script>

<template>
    <li class="">
        <div class="py-2 flex justify-between items-center gap-10">
            <div class="text-xs flex-1">
                <p class="font-semibold">{{ item.label }}</p>
                <!-- <p v-if="item.rating?.comment" class="opacity-70">
                    {{ item?.rating?.comment }}
                </p> -->
            </div>

            <ul class="grid grid-cols-3 gap-3 text-sm text-center">
                <li v-for="feed in feedbacks" :key="feed.id" @click="onSubmitChangeRating(feed.id)">
                    <component
                        :is="feed.id <= form.rating ? StarFilledIcon : StarIcon"
                        :class="['w-8 h-8  mx-auto', !form.rating ? 'text-gray-400' : 'text-yellow-500']"
                    />
                    <span class="text-2xs text-center text-gray-500">
                        {{ feed.label }}
                    </span>
                </li>
            </ul>
        </div>
        <div class="bg-slate-200 rounded-lg p-2 my-2 text-sm flex">
            <p class="flex-1">
                <b>Commentaire: </b>
                {{ form.comment }}
            </p>
            <ChatIcon :class="['btn-m w-5']" @click="showComment = !showComment" />
        </div>

        <div v-if="showComment" class="flex relative mb-2">
            <textarea
                v-model="form.comment"
                name="comment"
                rows="2"
                class="w-full rounded-lg bg-transparent text-sm border border-slate-300 px-2 pt-0"
            ></textarea>
            <Button
                class="!absolute text-xs right-0.5 bottom-0.5 !py-0 h-6"
                :disabled="!form.isDirty"
                variant="primary"
                :loading="form.processing"
                @click="onSubmitChangeRating(form.rating)"
            >
                Enreg
            </Button>
        </div>
    </li>
</template>
