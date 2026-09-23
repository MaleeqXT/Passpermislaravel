<script setup lang="ts">
import { useDebounce, useMutation, useQuery } from '@shared/hooks';
import { routes } from '@espace-admin/routes';
import { onMounted, ref, watch } from 'vue';
import { Button, Scrollable, SearchField, InlineConfirm, EditorField, EmptyState, Thumb, Spinner, Drawer } from '@shared/components';
import { dateFormat, getFilePath } from '@shared/utils';
import { router, useForm } from '@inertiajs/vue3';
import { PlusIcon } from '@adersolutions/icons';
import type { StudentType, StudentCommentType } from '@common/types';
import { useApp } from '@shared/stores';

type PropsType = {
    student: NonNullable<StudentType>;
};

const props = defineProps<PropsType>();
const searchQuery = ref({
    comment: '',
});
const { user } = useApp();
const editedForm = useMutation({
    id: '',
    comment: '',
    student_id: props.student?.id,
});
const form = useMutation({
    comment: '',
    student_id: props.student.id,
});
const comments = useQuery({
    url: route(routes.api.comments.index, { student_id: props.student?.id }),
    key: 'comments',
});

const isEdit = ref(false);
watch(
    () => searchQuery.value.comment,
    (comment) => {
        debouncedFetchComments(comment, true);
    }
);
const onGetComments = (search = '', refetch = false, loadmore = false) => {
    if (comments.data?.length && !refetch) {
        return;
    }
    searchQuery.value.comment = search;
    comments.fetch('', { search }, loadmore);
};
onMounted(async () => {
    await onGetComments('');
});

const debouncedFetchComments = useDebounce()(onGetComments);

const onSubmit = () => {
    const url = isEdit.value ? route(routes.api.comments.update, editedForm.id) : route(routes.api.comments.store);
    editedForm.transform(({ id, ...data }) => data);
    editedForm.post(url).then(() => {
        onGetComments('', true);
        form.reset('comment');
        isEdit.value = false;
        editedForm.reset();
    });
};
const onEdit = (item: StudentCommentType) => {
    editedForm.id = item.id;
    editedForm.comment = item.comment || '';
};

const onConfirmedDelete = (item: StudentCommentType) => {
    editedForm.delete(route(routes.api.comments.delete, item?.id)).then(() => {
        onGetComments('', true);
    });
};
</script>

<template>
    <div class="">
        <h1 class="text-base font-semibold mb-2 text-gray-600">Commentaires</h1>
        <SearchField v-model="searchQuery.comment" class="bg-white box" />
        <scrollable
            class="max-h-96 min-h-60 scrollbar overflow-y-auto w-full pb-4 mt-4 relative rounded-lg"
            @scroll:end="onGetComments(searchQuery.comment, true, true)"
        >
            <ul class="w-full space-y-4 h-full">
                <li v-for="item in comments.data || []" :key="item.id" class="flex gap-3">
                    <Thumb :src="getFilePath(item?.user)" />
                    <div class="flex flex-col flex-1 bg-gray-50 border p-3 rounded-xl">
                        <dl class="flex justify-between">
                            <dd>
                                <div class="font-bold">
                                    {{ item?.user?.name }}
                                </div>
                                <div class="flex gap-2 text-xs">
                                    <span class="cursor-pointer text-blue-600 hover:underline" @click="onEdit(item)"> Editer </span>
                                    <InlineConfirm
                                        class="text-red-600 hover:underline cursor-pointer"
                                        :loading="editedForm.processing"
                                        @confirm="onConfirmedDelete(item)"
                                    >
                                        Supprimer
                                    </InlineConfirm>
                                </div>
                            </dd>
                            <dd class="text-xs text-gray-500">
                                {{ dateFormat(item?.created_at, 'fromNow') }}
                            </dd>
                        </dl>

                        <div class="prose" v-html="item?.comment"></div>
                    </div>
                </li>
            </ul>
            <div v-if="comments.fetching" class="absolute inset-0 flex-center min-h-60 backdrop-blur-sm z-1">
                <Spinner class="w-6" />
            </div>
            <EmptyState v-if="!comments.meta.total" heading="Aucun commentaire" class="py-10" block>
                Aucun commentaire n'a été ajouté pour le moment.
            </EmptyState>
        </scrollable>

        <form class="flex gap-3 mt-4 relative" @submit.prevent="onSubmit">
            <Thumb :src="getFilePath(user)" />
            <EditorField v-model="form.comment" :error="form.errors.comment" label="Nouveau commentaire" class="flex-1" />
            <Button
                type="submit"
                :loading="form.processing"
                :disabled="!form.isDirty"
                class-name="!absolute btn-primary btn bottom-1 right-1"
                :icon="PlusIcon"
            >
                Commenter
            </Button>
        </form>
        <Drawer :show="!!editedForm.id" title="Modifier le commentaire" @close="editedForm.id = ''">
            <div v-if="editedForm.id" class="flex-1 p-4">
                <EditorField v-model="editedForm.comment" :error="editedForm.errors.comment" />
            </div>
            <div class="p-4">
                <Button :loading="editedForm.processing" :disabled="!editedForm.isDirty" full variant="primary" @click="onSubmit">
                    Enregistrer
                </Button>
            </div>
        </Drawer>
    </div>
</template>
