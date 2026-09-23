const dashboard = {
    index: 'monitor.dashboard.index',
    // availability: 'monitor.dashboard.availability',
    competences: 'monitor.dashboard.competences',
};
const lecons = {
    index: 'monitor.lecons.index',
};
const students = {
    index: 'monitor.students.index',
};

const reservations = {
    index: 'monitor.reservations.index',
    student: 'monitor.reservations.student.index',
    settings: {
        storeOrUpdate: 'monitor.reservations.settings.store.or.update',
    },
};
const proposals = {
    index: 'monitor.proposals.index',
    storeMany: 'monitor.proposals.storeMany',
    delete: 'monitor.proposals.delete',
};
const competences = {
    index: 'monitor.competences.index',
    storeOrUpdate: 'monitor.competences.storeOrUpdate',
};

const reviews = {
    index: 'monitor.reviews.index',
    show: 'monitor.reviews.show',
};

const cancellations = {
    index: 'monitor.cancellations.index',
    store: 'monitor.cancellations.store',
    update: 'monitor.cancellations.update',
};

const invoices = {
    menu: 'monitor.invoices.menu',
    index: 'monitor.invoices.index',
    historique: 'monitor.invoices.historique',
    view: 'monitor.invoices.view',
};

const settings = {
    profile: {
        index: 'monitor.profile.index',
        edit: 'monitor.profile.edit',
        update: 'monitor.profile.update',
    },
    documentsPersonel: {
        index: 'monitor.documents.index',
        storeOrUpdate: 'monitor.documents.store.or.update',
        diplom: 'monitor.documents.diplom',
        permis: 'monitor.documents.permis',
        identities: 'monitor.documents.identities',
    },
    documentsProfessionnel: {
        index: 'monitor.documents.professionnel.index',
        storeOrUpdate: 'monitor.documents.professionnel.store.or.update',
        autorisation: 'monitor.documents.professionnel.store.or.autorisation',
    },
    places: {
        index: 'monitor.places.index',
        store: 'monitor.places.store',
    },
    cars: {
        index: 'monitor.cars.index',
        store: 'monitor.cars.store',
        create: 'monitor.cars.create',
        edit: 'monitor.cars.edit',
        update: 'monitor.cars.update',
        delete: 'monitor.cars.delete',
    },
};
const evaluations = {
    // index: 'monitor.evaluations.index',
    store: 'monitor.evaluations.store',
};
const api = {
    reservations: {
        index: 'api.monitor.reservations.index',
        events: 'api.admin.reservations.events',
        schedule: 'api.monitor.reservations.schedule',
        createMany: 'api.monitor.reservations.createMany',
        destroy: 'api.monitor.reservations.destroy',
    },
    students: {
        index: 'api.monitor.students.index',
        update: 'api.monitor.students.update',
    },
    reviews: {
        index: 'api.monitor.reviews.index',
        store: 'api.monitor.reviews.store',
        update: 'api.monitor.reviews.update',
    },
    invoices: {
        rapport: 'api.monitor.invoices.rapport.hours',
        // rapport: 'api.monitor.invoices.rapport',
    },
    proposals: {
        index: 'api.monitor.proposals.index',
        count: 'api.monitor.proposals.count',
        storeMany: 'api.monitor.proposals.storeMany',
        update: 'api.monitor.proposals.update',
        delete: 'api.monitor.proposals.delete',
    },
    cancellations: {
        index: 'api.monitor.cancellations.index',
    },
};

export const routes = {
    dashboard,
    competences,
    cancellations,
    lecons,
    students,
    reservations,
    proposals,
    reviews,
    invoices,
    settings,
    evaluations,
    api,
};
