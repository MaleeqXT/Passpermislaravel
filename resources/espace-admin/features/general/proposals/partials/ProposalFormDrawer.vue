<script setup lang="ts">
import { watch } from 'vue';
import { Card, Errors, Spinner, Button, Drawer, TabSwitch } from '@shared/components';
import { CheckCircleIcon } from '@adersolutions/icons';
import { routes } from '@espace-admin/routes';
import { dateFormat, getFilePath } from '@shared/utils';
import { ItemImage } from '@common/components';
import { ProposalLessonStatus, ProposalStatusEnum } from '@common/enums';
import { useForm } from '@inertiajs/vue3';
import { useQuery } from '@shared/hooks';
import type { ProposalType } from '@common/types';

const emit = defineEmits(['close']);
type PropsType = { item: ProposalType | null };
const props = defineProps<PropsType>();

const form = useForm({
  status: props.item?.status ?? null,
  offer_id: undefined,
  comment: props.item?.comment ?? '',
});

const offersQuery = useQuery(
  { transformable: true },
  props.item?.status === ProposalStatusEnum.RESERVED
);

const onSubmitProposition = () => {
  // FIX: Use PUT for update instead of POST
  form.post(route(routes.propositions.update, props.item?.id || '-'), {
    onSuccess: () => {
      form.reset();
      emit('close');
    },
    onError: (errors) => {
      console.error('Validation errors', errors);
    },
  });
};

// Watch for prop changes
watch(
  () => props.item,
  (item) => {
    if (item) {
      form.status = item.status ?? null;
      form.offer_id = undefined;
      form.comment = item.comment ?? '';
      form.defaults();

      if (item.student?.id) {
        offersQuery.fetch(route(routes.api.balances.getBalanceByStudent, item.student.id));
      }
    }
  },
  { immediate: true }
);
</script>

<template>
  <Drawer :show="!!props.item" title="Proposition details" max-width="sm" @close="$emit('close')">
    <template #actions>
      <Button
        variant="primary"
        link
        :loading="form.processing"
        :disabled="!form.isDirty"
        @click="onSubmitProposition"
      >
        Enregister
      </Button>
    </template>

    <form class="h-full" @submit.prevent="onSubmitProposition">
      <ul v-if="props.item" class="flex flex-col gap-3 h-full p-3 pb-10">
        <li>
          <TabSwitch
            v-model="form.status"
            class="flex-1"
            wrapper-class="flex gap-1"
            item-class="bg-white"
            :items="Object.values(ProposalLessonStatus)"
            full
            :keys="['name', 'id']"
            :disabled="form.processing"
          />
        </li>

        <li class="flex flex-col divide-y box bg-white text-xs">
          <dl class="flex gap-2 p-2">
            <dt class="w-1/3">Date de proposition</dt>
            <dd class="font-bold flex-1">{{ dateFormat(props.item.created_at, 'letter') }}</dd>
          </dl>
          <dl class="flex gap-2 p-2">
            <dt class="w-1/3">Date Reservation</dt>
            <dd class="font-bold flex-1">{{ dateFormat(props.item.reservation.date, 'letter') }}</dd>
          </dl>
          <dl class="flex gap-2 p-2">
            <dt class="w-1/3">L'heure</dt>
            <dd class="font-bold flex-1">{{ props.item.reservation.start_at + ' à ' + props.item.reservation.end_at }}</dd>
          </dl>
          <dl class="flex gap-2 p-2">
            <dt class="w-1/3">Lieu</dt>
            <dd class="font-bold flex-1">{{ props.item.reservation?.lieu?.zone?.name }}, {{ props.item.reservation?.lieu?.name }}</dd>
          </dl>
        </li>

        <li class="grid grid-cols-2 gap-2">
          <Card title="Condidat">
            <ItemImage
              :src="getFilePath(props.item.student?.user)"
              size="w-9 h-9"
              class="box bg-white w-full p-1 text-sm"
              :title="props.item.student?.user?.name"
              :content="props.item.student?.user?.email"
              :phone="props.item.student?.user?.phone"
              :href="route(routes.users.students.general, props.item?.student?.id || '-')"
            />
          </Card>

          <Card title="Moniteur">
            <ItemImage
              v-if="props.item.reservation"
              size="w-9 h-9"
              class="box bg-white w-full p-1 text-sm"
              :src="getFilePath(props.item.reservation.monitor?.user)"
              :title="props.item.reservation.monitor?.user?.name"
              :content="props.item.reservation.monitor?.user?.email"
              :phone="props.item.reservation.monitor?.user?.phone"
              :href="route(routes.users.monitors.edit, props.item.reservation?.monitor?.id || '-')"
            />
          </Card>
        </li>

         <li v-if="form.status === ProposalStatusEnum.RESERVED">
                    <h4 class="text-xs font-medium text-gray-500">List des Produits</h4>
                    <Card v-if="offersQuery.fetching" block>
                        <Spinner class="w-6 h-6 mx-auto" />
                    </Card>
                    <Card v-else block class="!mb-0 divide-y" :separated="false" as="ul">
                        <li
                            v-for="pr in offersQuery.data"
                            :key="pr.id"
                            :class="[
                                'p-2 t-3 btn-m flex justify-between items-center',
                                pr.offer_id === form.offer_id ? 'text-primary bg-primary/5 rounded-xl' : '',
                                pr.balance === 0 ? 'opacity-70 pointer-events-none' : '',
                            ]"
                            @click="form.offer_id = pr.offer_id"
                        >
                            <ItemImage
                                :src="getFilePath(pr.offer, true)"
                                size="w-8 h-8"
                                is-mobile
                                :title="pr.offer?.name"
                                :content="`Balance: ${pr.balance}`"
                            />
                            <small v-if="pr.balance === 0">Indisponible</small>
                            <CheckCircleIcon v-else-if="pr.offer_id === form.offer_id" class="w-7 h-7 text-primary" />
                        </li>
                    </Card>
                    <Errors :errors="form.errors.offer_id" />
                </li>


        <li>
          <h4 class="text-xs font-medium text-gray-500">Commenyutaire</h4>

          <p v-if="props.item.comment" class="max-w-lg line-clamp-2 w-full text-xs text-gray-500 mb-2">
            Ancien : {{ props.item.comment }}
          </p>
          <textarea
            v-model="form.comment"
            class="w-full border rounded p-1 text-sm"
            placeholder="Ajouter un commentaire..."
            :disabled="form.processing"
          ></textarea>
          <Errors :errors="form.errors.comment" />
        </li>
      </ul>

      <div class="page-actions">
        <Button
          variant="primary"
          full
          :loading="form.processing"
          :disabled="!form.isDirty"
          type="submit"
        >
          Appliquer le changement
        </Button>
      </div>
    </form>
  </Drawer>
</template>
