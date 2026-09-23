import { defineAsyncComponent } from 'vue';

export const ScheduleHeader = defineAsyncComponent(() => import('./ScheduleHeader.vue'));
export const ViewScheduleByMonth = defineAsyncComponent(() => import('./month/ViewScheduleByMonth.vue'));
export const ViewScheduleByWeek = defineAsyncComponent(() => import('./week/ViewScheduleByWeek.vue'));
export const LessonMonitorDialog = defineAsyncComponent(() => import('./dialogs/LessonMonitorDialog.vue'));
export const ReservationFormDrawer = defineAsyncComponent(() => import('./dialogs/ReservationFormDrawer.vue'));
export const ShowMoreLessonDialog = defineAsyncComponent(() => import('./dialogs/ShowMoreLessonDialog.vue'));
