import { defineAsyncComponent } from 'vue';
export const SelectAreaPlaces = defineAsyncComponent(() => import('./SelectAreaPlaces.vue'));
export const MobileBottomBar = defineAsyncComponent(() => import('./MobileBottomBar.vue'));
export const TarifsSection = defineAsyncComponent(() => import('./TarifsSection.vue'));
export const PageMobile = defineAsyncComponent(() => import('./PageMobile.vue'));
export const ItemImage = defineAsyncComponent(() => import('./ItemImage.vue'));
export const ItemsCopy = defineAsyncComponent(() => import('./ItemsCopy.vue'));
export const ImiterBar = defineAsyncComponent(() => import('./ImiterBar.vue'));

export const VerifiedCredentialDialog = defineAsyncComponent(() => import('./VerifiedCredentialDialog.vue'));
export const Description = defineAsyncComponent(() => import('./Description.vue'));

// dialogs
export const AreaSelectionDialog = defineAsyncComponent(() => import('./dialogs/AreaSelectionDialog.vue'));
export const SelectLocationDialog = defineAsyncComponent(() => import('./dialogs/SelectLocationDialog.vue'));
export const ListMonitorsDialog = defineAsyncComponent(() => import('./dialogs/ListMonitorsDialog.vue'));
export const ListStudentsDialog = defineAsyncComponent(() => import('./dialogs/ListStudentsDialog.vue'));
export const ListOffersDialog = defineAsyncComponent(() => import('./dialogs/ListOffersDialog.vue'));
export const ActionSheet = defineAsyncComponent(() => import('./dialogs/ActionSheet.vue'));

// filters
export const WeekChanger = defineAsyncComponent(() => import('./filters/WeekChanger.vue'));

// mobile
export const ActionsBottomBar = defineAsyncComponent(() => import('./ActionsBottomBar.vue'));

// payments
export const PaymentCardPaypal = defineAsyncComponent(() => import('./payments/PaymentCardPaypal.vue'));
export const PaymentCardStripe = defineAsyncComponent(() => import('./payments/PaymentCardStripe.vue'));
export const ConfirmedPaymentModal = defineAsyncComponent(() => import('./payments/ConfirmedPaymentModal.vue'));

// schedules
export const WeeklySchedule = defineAsyncComponent(() => import('./schedules/WeeklySchedule.vue'));
export const ScheduleSection = defineAsyncComponent(() => import('./schedules/ScheduleSection.vue'));
export const ScheduleItem = defineAsyncComponent(() => import('./schedules/ScheduleItem.vue'));
export const MonthlyCountSchedule = defineAsyncComponent(() => import('./schedules/MonthlyCountSchedule.vue'));
export const ReservationMessagesInfo = defineAsyncComponent(() => import('./schedules/ReservationMessagesInfo.vue'));

// common
export const DataView = defineAsyncComponent(() => import('./common/DataView.vue'));
export const LineProgress = defineAsyncComponent(() => import('./common/LineProgress.vue'));

// reservations
export const StudentStatsCard = defineAsyncComponent(() => import('./reservations/StudentStatsCard.vue'));
export const ReservationLocateDetail = defineAsyncComponent(() => import('./reservations/ReservationLocateDetail.vue'));
