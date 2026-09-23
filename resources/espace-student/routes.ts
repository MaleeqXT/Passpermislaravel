const dashboard = {
    index: 'student.dashboard.index',
};

// const conduite = {
//     lesson: {
//         list: 'student.sessions.index',
//     },
// };
const reservations = {
    index: 'student.reservations.index',
    store: 'student.reservations.store',
    create: 'student.reservations.create',
    edit: 'student.reservations.edit',
    update: 'student.reservations.update',
    destroy: 'student.reservations.destroy',
};
const cancellations = {
    index: 'student.cancellations.index',
    store: 'student.cancellations.store',
    update: 'student.cancellations.update',
};

const approvels = {
    store: 'student.approvels.store',
};
const shop = {
    index: 'student.shop.index',
};

const competences = {
    index: 'student.competences.index',
};
const settings = {
    profile: {
        index: 'student.profile.index',
        update: 'student.profile.update',
        destroy: 'student.profile.destroy',
    },
    contract: {
        index: 'student.settings.contrat-de-formation.index',
    },

    neph: {
        index: 'student.settings.neph.index',
        update: 'student.settings.neph.update',
    },
};

const commandes = {
    index: 'student.commandes.index',
    show: 'student.commandes.show',
};
const notifications = {
    index: 'student.notifications.index',
};
const pdf = {
    // index: 'student.notifications.index',
    evaluations: 'student.evaluations.pdf',
    contract: 'student.contract.pdf',
};
const cpf = {
    index: 'student.cpf.index',
    view: 'student.cpf.view',
    store: 'student.cpf.store',
};
const api = {
    cancellations: {
        index: 'api.student.cancellations.index',
    },
    // general: {
    //     infos: 'api.student.general.info',
    // },
    reservations: {
        index: 'api.student.reservations.index',
        get: 'api.student.reservations.get',
    },
    balances: {
        getBalanceByStudent: 'api.balances.student.getBalanceByStudent',
        balanceManaging: 'api.balances.student.balanceManaging',
        updateBalance: 'api.balances.student.updateBalance',
    },
    notifications: {
        proposals: {
            index: 'api.student.notifications.proposals.index',
            count: 'api.student.notifications.proposals.count',
            update: 'api.student.notifications.proposals.update',
        },
    },
};

export const routes = {
    dashboard,
    reservations,
    cancellations,
    approvels,
    commandes,
    notifications,
    shop,
    // conduite,
    competences,
    settings,
    cpf,
    pdf,
    api,
};
