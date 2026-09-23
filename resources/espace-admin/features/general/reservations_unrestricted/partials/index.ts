import { defineAsyncComponent } from 'vue';

export const ScheduleHeader = defineAsyncComponent(() => import('../../reservations/partials/ScheduleHeader.vue'));
export const ViewScheduleByMonth = defineAsyncComponent(() => import('../../reservations/partials/month/ViewScheduleByMonth.vue'));
export const ViewScheduleByWeek = defineAsyncComponent(() => import('../../reservations/partials/week/ViewScheduleByWeek.vue'));
export const LessonMonitorDialog = defineAsyncComponent(() => import('../../reservations/partials/dialogs/LessonMonitorDialog.vue'));
export const ReservationFormDrawer = defineAsyncComponent(() => import('../../reservations/partials/dialogs/ReservationFormDrawer.vue'));
export const ShowMoreLessonDialog = defineAsyncComponent(() => import('../../reservations/partials/dialogs/ShowMoreLessonDialog.vue'));
