<script setup lang="ts">
import {
    Card,
    EmptyState,
    Select,
    Page,
    Thumb,
    SingleImageField,
    CheckField,
    EditorField,
    Button,
    MultiCheckField,
    InputField,
    FileLibrary,
} from '@shared/components';
import { useForm } from '@inertiajs/vue3';
import { useDebounce, useQuery } from '@shared/hooks';
import { routes } from '@espace-admin/routes';
import { DeleteIcon } from '@adersolutions/icons';
import { getFilePath, moneyFormat } from '@shared/utils';
import { ButtonGroup } from '@shared/components';
import ContainerWrapper from './ContainerWrapper.vue';
import { ListOffersDialog } from '@common/components';
import { PageTypeEnum } from '@common/enums';
import { useApp } from '@shared/stores';
import type { OfferType, PageType } from '@common/types';
import type { DataListType } from '@shared/types';
type extraType = {
    subtitle: string;
    description: string;
    price: string;
    image: string | undefined;
    offers: OfferType[];
};
type PropsType = {
    offers: DataListType<OfferType[]>;
    page: Partial<PageType<extraType>> | null;
};
const props = withDefaults(defineProps<PropsType>(), {
    page: () => ({}),
});
const { user } = useApp();

const form = useForm({
    title: props.page?.title || '',
    location: 'code',
    type: props.page?.type || PageTypeEnum.CUSTOMIZE_CODE,
    is_active: props.page?.is_active || false,
    user_id: user.id,
    extra: props.page?.extra || {
        description: '',
        subtitle: '',
        price: '',
        image: undefined,
        offers: [],
    },
});

const onSubmit = () => {
    form.post(route(routes.pages.general.update));
};
</script>
<template>
    <ContainerWrapper>
        <FileLibrary @submit="form.extra.image = $event.path" />
        <ul class="mt-10 max-w-3xl mx-auto w-full px-4 grid grid-cols-3 gap-5">
            <Card class="col-span-2">
                <div class="pb-5 space-y-3">
                    <InputField v-model="form.title" :error="form.errors.title" label="Titre de code Offre" />
                    <InputField v-model="form.extra.subtitle" label="Sous-Titre " />

                    <EditorField v-model="form.extra.description" label="Description" class="mt-1 block w-full" />

                    <MultiCheckField
                        v-if="offers.total"
                        label="Choisir le Code désiré "
                        v-model="form.extra.offers"
                        :extra="(item:OfferType) => `Prix: ${moneyFormat(item.final_price)}`"
                        :items="offers.data"
                    />
                    <EmptyState v-else title="Aucun code disponible" class="pb-4 pt-2" />
                </div>

                <ButtonGroup
                    :actions="[
                        {
                            label: 'Enregistrer',
                            variant: 'primary',
                            submit: true,
                            disabled: !form.isDirty,
                            loading: form.processing,
                            onAction: onSubmit,
                        },
                        {
                            label: 'Reintialiser',
                            disabled: form.processing || !form.isDirty,
                            variant: 'secondary',
                            onAction: () => form.reset(),
                        },
                    ]"
                />
            </Card>
            <div class="sticky top-0">
                <CheckField v-model:checked="form.is_active" label="Activer" content="Activer offer sur la page de code" class="mb-5" />
                <SingleImageField :src="form.extra.image" @delete="form.extra.image = undefined" />
            </div>
        </ul>
    </ContainerWrapper>
</template>
