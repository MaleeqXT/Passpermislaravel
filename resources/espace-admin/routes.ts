const dashboard = {
    index: 'admin.dashboard.index',
};

const approvels = {
    index: 'admin.approvels.index',
    approve: 'admin.approvels.approve',
    reject: 'admin.approvels.reject',
};





const shop = {
    offers: {
        index: 'admin.offers.index',
        create: 'admin.offers.create',
        // show: 'admin.offers.store',
        store: 'admin.offers.store',
        edit: 'admin.offers.edit',
        update: 'admin.offers.update',
        updateStatus: 'admin.offers.updateStatus',
        delete: 'admin.offers.destroy',
    },
    cart: {
        index: 'admin.carts.index',
        show: 'admin.carts.show',
        edit: 'admin.carts.edit',
        delete: 'admin.carts.destroy',
    },
    commandes: {
        index: 'admin.commandes.index',
            unrestricted: {
                index: 'api.admin.reservations.unrestricted.index',
                store: 'api.admin.reservations.unrestricted.store',
                show: 'api.admin.reservations.unrestricted.show',
                update: 'api.admin.reservations.unrestricted.update',
                destroy: 'api.admin.reservations.unrestricted.destroy',
            },
        show: 'admin.commandes.show',
        delete: 'admin.commandes.destroy',
        refund: 'api.admin.payment.stripe.refund',
    },
};


const users = {
    user: {
        archive: 'admin.users.archive',
    },
    admins: {
        index: 'admin.users.admins.index',
        create: 'admin.users.admins.create',
        store: 'admin.users.admins.store',
        edit: 'admin.users.admins.edit',
        update: 'admin.users.admins.update',
        delete: 'admin.users.admins.destroy',
    },
    monitors: {
        index: 'admin.users.monitors.index',
        invoices: 'admin.users.monitors.invoices',
        create: 'admin.users.monitors.create',
        store: 'admin.users.monitors.store',
        edit: 'admin.users.monitors.edit',
        update: 'admin.users.monitors.update',
        delete: 'admin.users.monitors.destroy',
        fich: {
            documents: 'admin.users.monitors.documents',
            documentsUpdate: 'admin.users.monitors.documents.update',
            // coordonneesBancaire: 'admin.users.monitor.coordonneesBancaire',
            // coordonneesBancaireSave: 'admin.users.monitor.coordonneesBancaireSave',
        },
    },
    students: {
        index: 'admin.users.students.index',
        create: 'admin.users.students.create',
        store: 'admin.users.students.store',
        edit: 'admin.users.students.edit',
        update: 'admin.users.students.update',
        delete: 'admin.users.students.destroy',
        general: 'admin.users.students.edit',
        contract: 'admin.users.students.contract',
        withZones: 'admin.users.students.get.balance.locations',
        cpf: 'admin.users.students.get.cpf',
        livret: 'admin.users.students.get.competences',
        cart: 'admin.users.students.get.cart',
        docs: 'admin.users.students.docs',
    },
};
const reservations = {
    main: {
        index: 'admin.reservations.index',
        create: 'admin.reservations.create',
        unrestricted: 'admin.reservations.unrestricted.index',
        store: 'admin.reservations.store',
        edit: 'admin.reservations.edit',
        update: 'admin.reservations.update',
        delete: 'admin.reservations.destroy',
    },
    unrestricted: {
        index: 'admin.reservations.unrestricted.index',
    },
    sessions: {
        index: 'admin.sessions.index',
        create: 'admin.sessions.create',
        store: 'admin.sessions.store',
        edit: 'admin.sessions.edit',
        update: 'admin.sessions.update',
        delete: 'admin.sessions.destroy',
    },
};
const pages = {
    general: {
        update: 'admin.pages.general.update',
    },
    promo: {
        index: 'admin.pages.promo.index',
        create: 'admin.pages.promo.create',
        store: 'admin.pages.promo.store',
        edit: 'admin.pages.promo.edit',
        update: 'admin.pages.promo.update',
        delete: 'admin.pages.promo.delete',
    },
    home: {
        index: 'admin.pages.home.index',
        update: 'admin.pages.home.update',
    },
    code: {
        index: 'admin.pages.code.index',
    },
};
const cancellations = {
    index: 'admin.cancellations.index',
    update: 'admin.cancellations.update',
};

const invoices = {
    index: 'admin.invoices.index',
    view: 'admin.invoices.show',
    update: 'admin.invoices.update',
};
const contact = {
    index: 'admin.contact.index',
    update: 'admin.contact.update',
};
const cpf = {
    index: 'admin.cpf.index',
    store: 'admin.cpf.store',
    update: 'admin.cpf.update',
    destroy: 'admin.cpf.destroy',
};
const formCpf = {
    index: 'admin.forms-cpf.index',
    update: 'admin.forms-cpf.update',
    attestation: 'admin.forms-cpf.attestation.honneur.pdf',
    positionnement: 'admin.forms-cpf.test.positionnement.pdf',
    contact: 'admin.forms-cpf.contact.formation.pdf',
    reservations: 'admin.forms-cpf.reservations.pdf',
    attestationFinFormation: 'admin.forms-cpf.attestation-fin-formation.pdf',
    documents: 'admin.forms-cpf.documents',
    finCPf: 'admin.forms-cpf.fin',
};
const impersonate = {
    start: 'impersonate.start',
    stop: 'impersonate.stop',
};
const propositions = {
    index: 'admin.proposals.index',
    update: 'admin.proposals.update',
};
const settings = {
    competences: {
        group: {
            index: 'admin.competences.group.index',
            store: 'admin.competences.group.store',
            update: 'admin.competences.group.update',
            destroy: 'admin.competences.group.destroy',
        },
        sub: {
            index: 'admin.competences.sub.index',
            store: 'admin.competences.sub.store',
            update: 'admin.competences.sub.update',
            destroy: 'admin.competences.sub.destroy',
        },
    },
    locations: {
        area: {
            index: 'admin.locations.area.index',
            store: 'admin.locations.area.store',
            update: 'admin.locations.area.update',
            destroy: 'admin.locations.area.destroy',
            attach: 'admin.locations.area.attach',
            detach: 'admin.locations.area.detach',
        },
        zip: {
            index: 'admin.locations.zips.index',
            store: 'admin.locations.zips.store',
            update: 'admin.locations.zips.update',
            destroy: 'admin.locations.zips.destroy',
        },
        places: {
            index: 'admin.locations.places.index',
            store: 'admin.locations.places.store',
            update: 'admin.locations.places.update',
            destroy: 'admin.locations.places.destroy',
        },
    },
};

const reports = {
    activeStudents: {
        index: 'admin.reports.activeStudents.index',
    },
};

const exams = {
    index: 'admin.exams.index',
    update: 'admin.exams.update',
    edit: 'admin.exams.edit',
};

const pdf = {
    evaluations: 'admin.evaluations.pdf',
    contract: 'admin.contract.pdf',
};

const api = {
    reservations: {
        index: 'api.admin.reservations.index',
        events: 'api.admin.reservations.events',
        update: 'api.admin.reservations.update',
        store: 'api.admin.reservations.store',
        annulation: 'api.admin.reservations.annulation.session',
        destroy: 'api.admin.reservations.destroy',
    },
    reservation: {
        update: 'api.admin.reservations.reservation.update',
        store: 'api.admin.reservations.reservation.store',
    },

    reservation_unrestricted: {
    index: 'api.admin.reservations.unrestricted.index',
    store: 'api.admin.reservations.unrestricted.store',
    update: 'api.admin.reservations.unrestricted.update',
    destroy: 'api.admin.reservations.unrestricted.destroy',
},

    proposals: {
        index: 'api.admin.proposals.index',
    },
    availables: {
        index: 'api.admin.availables.all',
        // eleveReservation: 'api.admin.availables.getAllReservationsGroupedByWeek',
        store: 'api.admin.availables.store',
        destroy: 'api.admin.availables.destroy',
    },
    balances: {
        getBalanceByStudent: 'api.admin.balances.student.getBalanceByStudent',
        balanceManaging: 'api.admin.balances.student.balanceManaging',
    },
    offers: {
        index: 'api.admin.shop.offers.index',
    },
    cart: {
        getCart: 'api.admin.shop.cart.getCart',
        storeOrUpdateMany: 'api.admin.shop.cart.storeOrUpdateMany',
    },
    user: {
        // archive: 'api.admin.user.archiveUser',
        zones: 'api.admin.zones.getAllZones',
    },
    students: {
        all: 'api.admin.students.all',
    },
    // operators: {
    //     all: 'api.admin.operators.all',
    // },
    monitors: {
        all: 'api.admin.monitors.all',
        factures: {
            resumeHours: 'api.admin.monitors.invoice.rapport.hours',
        },
    },
    locations: {
        area: {
            index: 'api.admin.locations.area.index',
        },
        places: {
            index: 'api.admin.locations.places.index',
        },
        zips: {
            index: 'api.admin.locations.zips.index',
        },
    },
    comments: {
        index: 'api.admin.comments.index',
        store: 'api.admin.comments.store',
        update: 'api.admin.comments.update',
        delete: 'api.admin.comments.destroy',
    },
    competences: {
        index: 'api.admin.competences.index',
    },
    resumeHours: {
        index: 'api.admin.resume-hours.index',
    },
};
export const routes = {
    dashboard,
    shop,
    users,
    reservations,
    cancellations,
    settings,
    impersonate,
    propositions,
    invoices,
    cpf,
    contact,
    pages,
    exams,
    reports,
    pdf,
    api,
    formCpf,
    approvels,
};
